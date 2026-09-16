<?php

namespace App\Models;

use App\Core\Database\DB;
use PDO;

/**
 * Список порівняння товарів.
 *
 * Архітектурно повторює App\Models\Cart:
 *  - гість ідентифікується по session_id (user_id IS NULL)
 *  - авторизований — по user_id
 *  - при логіні гостьовий список переноситься на user_id через migrate()
 *
 * Свідомі архітектурні рішення:
 *  - Порівнювати можна тільки товари ОДНІЄЇ категорії (інакше таблиця
 *    характеристик втрачає сенс — не можна порівняти телевізор із взуттям).
 *    Додавання товару іншої категорії автоматично очищує список і починає
 *    новий — так само роблять Rozetka/Prom. Виклик add() повідомляє про це
 *    через 'category_switched' => true, щоб контролер показав повідомлення.
 *  - Ліміт MAX_ITEMS товарів у списку — щоб таблиця порівняння лишалась
 *    читабельною на екрані.
 *
 * Методи повертають КЛЮЧІ повідомлень (не перекладений текст) — переклад і
 * підстановку параметрів робить контролер через __()/sprintf(), як прийнято
 * в проєкті (див. Cart::add()).
 */
class CompareList
{
    private const TABLE = 'compare_list';
    public const MAX_ITEMS = 4;

    private static function userId(): ?int
    {
        return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    }

    private static function sessionId(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $sid = session_id();
        if (!$sid) {
            session_regenerate_id(true);
            $sid = session_id();
        }

        return $sid;
    }

    /**
     * Умова вибірки поточного списку порівняння:
     * - авторизований: user_id = ?
     * - гість: user_id IS NULL AND session_id = ?
     */
    private static function scope(): array
    {
        $userId = self::userId();

        if ($userId) {
            return [
                'where'  => 'cl.user_id = ?',
                'params' => [$userId],
            ];
        }

        return [
            'where'  => 'cl.user_id IS NULL AND cl.session_id = ?',
            'params' => [self::sessionId()],
        ];
    }

    /**
     * category_id товарів, що вже є в списку порівняння (null, якщо список порожній).
     */
    private static function currentCategoryId(): ?int
    {
        $scope = self::scope();

        $row = DB::query(
            "SELECT p.category_id
             FROM " . self::TABLE . " cl
             INNER JOIN products p ON p.id = cl.product_id
             WHERE {$scope['where']}
             LIMIT 1",
            $scope['params']
        )->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['category_id'] : null;
    }

    /**
     * Додати товар до списку порівняння.
     *
     * @return array{success:bool, message:?string, category_switched:bool}
     */
    public static function add(int $productId): array
    {
        $product = Product::findVisibleById($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'compare_product_not_found', 'category_switched' => false];
        }

        $productCategoryId = (int) ($product['category_id'] ?? 0);
        $currentCategoryId = self::currentCategoryId();
        $categorySwitched = false;

        if ($currentCategoryId !== null && $currentCategoryId !== $productCategoryId) {
            // Порівняння дозволене тільки в межах однієї категорії — починаємо новий список.
            self::clear();
            $categorySwitched = true;
        }

        if (self::isInList($productId)) {
            return ['success' => true, 'message' => null, 'category_switched' => $categorySwitched];
        }

        if (!$categorySwitched && self::getCount() >= self::MAX_ITEMS) {
            return ['success' => false, 'message' => 'compare_limit_reached', 'category_switched' => false];
        }

        $userId = self::userId();
        $sid = self::sessionId();

        DB::query(
            "INSERT INTO " . self::TABLE . " (user_id, session_id, product_id) VALUES (?, ?, ?)",
            [$userId, $userId ? null : $sid, $productId]
        );

        return ['success' => true, 'message' => null, 'category_switched' => $categorySwitched];
    }

    public static function isInList(int $productId): bool
    {
        $scope = self::scope();

        $row = DB::query(
            "SELECT 1 FROM " . self::TABLE . " cl WHERE {$scope['where']} AND cl.product_id = ? LIMIT 1",
            array_merge($scope['params'], [$productId])
        )->fetch(PDO::FETCH_ASSOC);

        return (bool) $row;
    }

    /**
     * ID товарів у поточному списку порівняння — для позначення "вже додано" на кнопках.
     */
    public static function getProductIds(): array
    {
        $scope = self::scope();

        $rows = DB::query(
            "SELECT cl.product_id FROM " . self::TABLE . " cl WHERE {$scope['where']} ORDER BY cl.id ASC",
            $scope['params']
        )->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows ?: []);
    }

    public static function remove(int $productId): void
    {
        $scope = self::scope();

        DB::query(
            "DELETE cl FROM " . self::TABLE . " cl WHERE {$scope['where']} AND cl.product_id = ?",
            array_merge($scope['params'], [$productId])
        );
    }

    public static function clear(): void
    {
        $scope = self::scope();

        DB::query(
            "DELETE cl FROM " . self::TABLE . " cl WHERE {$scope['where']}",
            $scope['params']
        );
    }

    public static function getCount(): int
    {
        $scope = self::scope();

        $row = DB::query(
            "SELECT COUNT(*) AS cnt FROM " . self::TABLE . " cl WHERE {$scope['where']}",
            $scope['params']
        )->fetch(PDO::FETCH_ASSOC);

        return max(0, (int) ($row['cnt'] ?? 0));
    }

    /**
     * Повні дані товарів у списку порівняння (для сторінки /compare).
     * Порядок — за часом додавання.
     */
    public static function getItems(): array
    {
        $scope = self::scope();

        $sql = "SELECT p.id, p.name, p.slug, p.image, p.price, p.sku, p.category_id,
                       c.name AS category_name,
                       COALESCE(ps.quantity, 0) AS stock_quantity
                FROM " . self::TABLE . " cl
                INNER JOIN products p ON p.id = cl.product_id
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_stocks ps
                    ON ps.sku COLLATE utf8mb4_general_ci = p.sku COLLATE utf8mb4_general_ci
                   AND ps.option_id IS NULL
                WHERE {$scope['where']}
                ORDER BY cl.id ASC";

        return DB::query($sql, $scope['params'])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Перенести гостьовий список порівняння на user_id після логіну.
     * Викликається: CompareList::migrate($oldSessionId, $userId)
     */
    public static function migrate($oldSessionId, $userId): bool
    {
        $oldSessionId = (string) $oldSessionId;
        $userId = (int) $userId;

        if ($oldSessionId === '' || $userId <= 0) {
            return false;
        }

        $guestItems = DB::query(
            "SELECT product_id FROM " . self::TABLE . " WHERE user_id IS NULL AND session_id = ?",
            [$oldSessionId]
        )->fetchAll(PDO::FETCH_ASSOC);

        if (empty($guestItems)) {
            return true;
        }

        $existingUserProducts = DB::query(
            "SELECT product_id FROM " . self::TABLE . " WHERE user_id = ?",
            [$userId]
        )->fetchAll(PDO::FETCH_COLUMN);
        $existingUserProducts = array_map('intval', $existingUserProducts ?: []);

        foreach ($guestItems as $item) {
            $productId = (int) $item['product_id'];
            if (in_array($productId, $existingUserProducts, true)) {
                continue;
            }
            if (count($existingUserProducts) >= self::MAX_ITEMS) {
                break;
            }

            DB::query(
                "INSERT INTO " . self::TABLE . " (user_id, session_id, product_id) VALUES (?, NULL, ?)",
                [$userId, $productId]
            );
            $existingUserProducts[] = $productId;
        }

        DB::query(
            "DELETE FROM " . self::TABLE . " WHERE user_id IS NULL AND session_id = ?",
            [$oldSessionId]
        );

        return true;
    }

    /**
     * Характеристики (атрибути) товарів для таблиці порівняння.
     *
     * Повертає рядки у форматі:
     *   [['attribute_id' => .., 'attribute_name' => .., 'values' => [product_id => 'значення', ...]], ...]
     *
     * Якщо товар має кілька значень одного атрибута (варіанти, напр. кілька
     * кольорів), значення об'єднуються через кому — це коректно відображає
     * "доступні варіанти" в порівнянні.
     *
     * @param int[] $productIds
     */
    public static function getAttributesMatrix(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $rows = DB::query(
            "SELECT pa.product_id, a.id AS attribute_id, a.name AS attribute_name, a.sort_order,
                    GROUP_CONCAT(DISTINCT COALESCE(ao.name, pa.value) ORDER BY COALESCE(ao.sort_order, 0), COALESCE(ao.name, pa.value) SEPARATOR ', ') AS value
             FROM product_attributes pa
             INNER JOIN attributes a ON a.id = pa.attribute_id
             LEFT JOIN attribute_options ao ON ao.id = pa.attribute_option_id
             WHERE pa.product_id IN ($placeholders)
               AND a.is_visible = 1
               AND (pa.value IS NOT NULL AND pa.value != '' OR pa.attribute_option_id IS NOT NULL)
             GROUP BY pa.product_id, a.id, a.name, a.sort_order
             ORDER BY a.sort_order ASC, a.name ASC",
            $productIds
        )->fetchAll(PDO::FETCH_ASSOC);

        $matrix = [];
        foreach ($rows as $row) {
            $attributeId = (int) $row['attribute_id'];
            if (!isset($matrix[$attributeId])) {
                $matrix[$attributeId] = [
                    'attribute_id'   => $attributeId,
                    'attribute_name' => $row['attribute_name'],
                    'values'         => array_fill_keys($productIds, null),
                ];
            }
            $matrix[$attributeId]['values'][(int) $row['product_id']] = $row['value'];
        }

        return array_values($matrix);
    }
}
