<?php

namespace App\Models;

use App\Core\Database\DB;
use PDO;

/**
 * Масове (пакетне) управління цінами товарів.
 *
 * Режими розрахунку нової ціни ($mode):
 *  - 'percent'  — зміна на X% від поточної ціни (напрям: increase/decrease)
 *  - 'fixed'    — зміна на фіксовану суму в головній валюті сайту (напрям: increase/decrease)
 *  - 'currency' — перерахунок за курсом: менеджер вказує, що поточна ціна обраних
 *                 товарів вказана у валюті $params['from_currency'] (наприклад товари
 *                 прайсовані постачальником у доларах), і система перераховує її в
 *                 ГОЛОВНУ валюту сайту (currencies, is_active = 1) за співвідношенням
 *                 курсів: new = old * (rate(from) / rate(головна)). Не потребує жодних
 *                 додаткових полів у товарі — рахунок повністю на основі поточної ціни
 *                 і актуальних курсів з таблиці currencies.
 *
 * Вибірка ($filters):
 *  - category_id + includeSubcategories (bool) — Category::getAllChildren()
 *  - vendor — точна відповідність products.vendor (бренд/постачальник)
 *  - Порожні фільтри = уся товарна база.
 *
 * Кожен виклик apply() створює один запис у price_change_batches і по одному рядку
 * в price_change_batch_items на кожен фактично змінений товар — це дає можливість
 * одним кліком "Скасувати" повернути попередні ціни (undo()).
 */
class PriceBatch
{
    public const MODE_PERCENT = 'percent';
    public const MODE_FIXED = 'fixed';
    public const MODE_CURRENCY = 'currency';

    private const MODES = [self::MODE_PERCENT, self::MODE_FIXED, self::MODE_CURRENCY];

    /**
     * SQL WHERE + params для вибірки товарів за фільтрами (спільне для preview і apply).
     */
    private static function buildScopeWhere(array $filters): array
    {
        $where = [];
        $params = [];

        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        if ($categoryId) {
            $categoryIds = [$categoryId];
            if (!empty($filters['include_subcategories'])) {
                foreach (Category::getAllChildren($categoryId) as $child) {
                    $categoryIds[] = (int) $child['id'];
                }
            }
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $where[] = "category_id IN ($placeholders)";
            $params = array_merge($params, $categoryIds);
        }

        $vendor = trim((string) ($filters['vendor'] ?? ''));
        if ($vendor !== '') {
            $where[] = 'vendor = ?';
            $params[] = $vendor;
        }

        return [
            'sql' => $where ? ('WHERE ' . implode(' AND ', $where)) : '',
            'params' => $params,
        ];
    }

    /**
     * Кількість товарів, що підпадають під фільтр — для живого попереднього перегляду
     * в модальному вікні ("Буде змінено N товарів"), ще до застосування.
     */
    public static function countMatching(array $filters): int
    {
        $scope = self::buildScopeWhere($filters);
        $row = DB::query("SELECT COUNT(*) AS cnt FROM products {$scope['sql']}", $scope['params'])
            ->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['cnt'] ?? 0);
    }

    private static function currencyRate(string $code): ?float
    {
        $row = DB::query('SELECT rate FROM currencies WHERE code = ? LIMIT 1', [$code])
            ->fetch(PDO::FETCH_ASSOC);

        return $row ? (float) $row['rate'] : null;
    }

    /**
     * Курс і код головної (активної на сайті) валюти — та, що позначена is_active = 1
     * в таблиці currencies (за конвенцією проєкту завжди має rate = 1.0000).
     */
    private static function mainCurrency(): ?array
    {
        $row = DB::query('SELECT code, rate FROM currencies WHERE is_active = 1 LIMIT 1')
            ->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Розрахувати нову ціну товару за обраним режимом.
     * Повертає null, якщо для цього товару розрахунок неможливий (пропустити) —
     * актуально лише для режиму 'currency', коли курс обраної валюти не знайдено.
     *
     * $params['round_price'] — якщо true, ціна округлюється до цілого числа
     * (без копійок/центів), незалежно від обраного режиму розрахунку.
     */
    private static function calculateNewPrice(array $product, string $mode, array $params): ?float
    {
        $oldPrice = (float) $product['price'];

        switch ($mode) {
            case self::MODE_PERCENT:
                $percent = abs((float) ($params['value'] ?? 0));
                $factor = ($params['direction'] ?? 'increase') === 'decrease' ? (1 - $percent / 100) : (1 + $percent / 100);
                $new = $oldPrice * $factor;
                break;

            case self::MODE_FIXED:
                $amount = abs((float) ($params['value'] ?? 0));
                $new = ($params['direction'] ?? 'increase') === 'decrease' ? $oldPrice - $amount : $oldPrice + $amount;
                break;

            case self::MODE_CURRENCY:
                $fromCode = strtoupper(trim((string) ($params['from_currency'] ?? '')));
                if ($fromCode === '') {
                    return null;
                }
                $fromRate = self::currencyRate($fromCode);
                $main = self::mainCurrency();
                if ($fromRate === null || $main === null || (float) $main['rate'] <= 0) {
                    return null;
                }
                // Поточна ціна трактується як зазначена у валюті $fromCode —
                // переводимо її в головну валюту сайту за співвідношенням курсів.
                $new = $oldPrice * ($fromRate / (float) $main['rate']);
                break;

            default:
                return null;
        }

        $decimals = !empty($params['round_price']) ? 0 : 2;

        return max(0, round($new, $decimals));
    }

    /**
     * Застосувати пакетну зміну цін.
     *
     * @return array{success:bool, message:?string, affected:int, skipped:int, batch_id:?int}
     */
    public static function apply(array $filters, string $mode, array $params, ?int $adminId): array
    {
        if (!in_array($mode, self::MODES, true)) {
            return ['success' => false, 'message' => 'price_batch_invalid_mode', 'affected' => 0, 'skipped' => 0, 'batch_id' => null];
        }

        $scope = self::buildScopeWhere($filters);
        $products = DB::query(
            "SELECT id, price FROM products {$scope['sql']}",
            $scope['params']
        )->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            return ['success' => false, 'message' => 'price_batch_no_products', 'affected' => 0, 'skipped' => 0, 'batch_id' => null];
        }

        $changes = [];
        $skipped = 0;

        foreach ($products as $product) {
            $newPrice = self::calculateNewPrice($product, $mode, $params);
            if ($newPrice === null) {
                $skipped++;
                continue;
            }
            $oldPrice = (float) $product['price'];
            if (abs($newPrice - $oldPrice) < 0.005) {
                continue; // ціна фактично не змінилась — нема сенсу писати в історію
            }
            $changes[(int) $product['id']] = ['old' => $oldPrice, 'new' => $newPrice];
        }

        if (empty($changes)) {
            return ['success' => false, 'message' => 'price_batch_nothing_to_change', 'affected' => 0, 'skipped' => $skipped, 'batch_id' => null];
        }

        DB::beginTransaction();
        try {
            DB::query(
                "INSERT INTO price_change_batches
                    (admin_id, mode, mode_params, scope_category_id, scope_include_subcategories, scope_vendor, affected_count, skipped_count)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $adminId,
                    $mode,
                    json_encode($params, JSON_UNESCAPED_UNICODE),
                    isset($filters['category_id']) && $filters['category_id'] ? (int) $filters['category_id'] : null,
                    !empty($filters['include_subcategories']) ? 1 : 0,
                    trim((string) ($filters['vendor'] ?? '')) !== '' ? trim((string) $filters['vendor']) : null,
                    count($changes),
                    $skipped,
                ]
            );
            $batchId = DB::lastInsertId();

            foreach ($changes as $productId => $change) {
                DB::query('UPDATE products SET price = ? WHERE id = ?', [$change['new'], $productId]);
                DB::query(
                    'INSERT INTO price_change_batch_items (batch_id, product_id, old_price, new_price) VALUES (?, ?, ?, ?)',
                    [$batchId, $productId, $change['old'], $change['new']]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'price_batch_apply_error', 'affected' => 0, 'skipped' => 0, 'batch_id' => null];
        }

        foreach (array_keys($changes) as $productId) {
            do_action('product.updated', $productId, ['price']);
        }

        return ['success' => true, 'message' => null, 'affected' => count($changes), 'skipped' => $skipped, 'batch_id' => $batchId];
    }

    /**
     * Скасувати пакетну зміну цін — повертає старі ціни всім товарам з батчу.
     * Одноразово: повторне скасування вже скасованого батчу неможливе.
     */
    public static function undo(int $batchId): array
    {
        $batch = DB::query('SELECT * FROM price_change_batches WHERE id = ? LIMIT 1', [$batchId])
            ->fetch(PDO::FETCH_ASSOC);

        if (!$batch) {
            return ['success' => false, 'message' => 'price_batch_not_found'];
        }
        if (!empty($batch['reverted_at'])) {
            return ['success' => false, 'message' => 'price_batch_already_reverted'];
        }

        $items = DB::query('SELECT product_id, old_price FROM price_change_batch_items WHERE batch_id = ?', [$batchId])
            ->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return ['success' => false, 'message' => 'price_batch_not_found'];
        }

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                DB::query('UPDATE products SET price = ? WHERE id = ?', [$item['old_price'], $item['product_id']]);
            }
            DB::query('UPDATE price_change_batches SET reverted_at = NOW() WHERE id = ?', [$batchId]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'price_batch_apply_error'];
        }

        foreach ($items as $item) {
            do_action('product.updated', (int) $item['product_id'], ['price']);
        }

        return ['success' => true, 'message' => null, 'affected' => count($items)];
    }

    /**
     * Останні пакетні зміни цін — для сторінки історії.
     */
    public static function recent(int $limit = 20): array
    {
        return DB::query(
            "SELECT b.*, c.name AS category_name, u.first_name, u.last_name
             FROM price_change_batches b
             LEFT JOIN categories c ON c.id = b.scope_category_id
             LEFT JOIN users u ON u.id = b.admin_id
             ORDER BY b.id DESC
             LIMIT ?",
            [$limit]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Список валют сайту (код, символ, курс, чи головна) — для селекта "У якій валюті
     * зараз вказані ціни" в режимі перерахунку за курсом.
     */
    public static function getCurrencies(): array
    {
        return DB::query('SELECT code, symbol, rate, is_active FROM currencies ORDER BY is_active DESC, code ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Список унікальних брендів/постачальників (products.vendor) — для селекта у формі.
     */
    public static function getVendors(): array
    {
        $rows = DB::query(
            "SELECT DISTINCT vendor FROM products WHERE vendor IS NOT NULL AND vendor != '' ORDER BY vendor ASC"
        )->fetchAll(PDO::FETCH_COLUMN);

        return $rows ?: [];
    }
}
