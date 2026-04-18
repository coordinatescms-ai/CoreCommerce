<?php

namespace App\Models;

use App\Core\Database\DB;
use PDO;

class Cart
{
    private const TABLE = 'cart';

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
     * Умова вибірки поточного кошика:
     * - авторизований: user_id = ?
     * - гість: user_id IS NULL AND session_id = ?
     */
    private static function scope(): array
    {
        $userId = self::userId();

        if ($userId) {
            return [
                'where' => 'c.user_id = ?',
                'params' => [$userId],
            ];
        }

        return [
            'where' => 'c.user_id IS NULL AND c.session_id = ?',
            'params' => [self::sessionId()],
        ];
    }

    private static function normalizeSelectedOptions(array $selectedOptions): array
    {
        $normalized = [];

        foreach ($selectedOptions as $option) {
            if (!is_array($option)) {
                continue;
            }

            $optionId = (int) ($option['option_id'] ?? 0);
            if ($optionId <= 0) {
                continue;
            }

            $price = (float) ($option['price'] ?? 0);
            if ($price < 0) {
                $price = 0;
            }

            $op = (($option['op'] ?? '+') === '-') ? '-' : '+';

            $normalized[] = [
                'option_id' => $optionId,
                'name' => (string) ($option['name'] ?? ''),
                'value' => (string) ($option['value'] ?? ''),
                'price' => (float) number_format($price, 2, '.', ''),
                'op' => $op,
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            return $a['option_id'] <=> $b['option_id'];
        });

        return array_values($normalized);
    }

    private static function selectedOptionsToJson(array $selectedOptions): ?string
    {
        if (empty($selectedOptions)) {
            return null;
        }

        return json_encode(
            self::normalizeSelectedOptions($selectedOptions),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: null;
    }

    private static function parseSelectedOptions(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return self::normalizeSelectedOptions($decoded);
    }

    private static function findCartItem(int $productId, ?string $selectedOptionsJson): ?array
    {
        $scope = self::scope();

        $sql = "SELECT c.*
                FROM " . self::TABLE . " c
                WHERE {$scope['where']} AND c.product_id = ?
                  AND (
                    (? IS NULL AND c.selected_options IS NULL)
                    OR c.selected_options = ?
                  )
                LIMIT 1";

        $row = DB::query($sql, array_merge($scope['params'], [$productId, $selectedOptionsJson, $selectedOptionsJson]))->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private static function resolveSelectedOptions(int $productId, array $selectedOptionIds): array
    {
        $optionIds = array_values(array_unique(array_filter(array_map('intval', $selectedOptionIds), static fn ($id) => $id > 0)));
        if (empty($optionIds)) {
            return ['success' => true, 'options' => [], 'stock_limit' => null];
        }

        $placeholders = implode(',', array_fill(0, count($optionIds), '?'));
        $rows = DB::query(
            "SELECT pa.product_id, pa.attribute_id, pa.attribute_option_id,
                    a.name AS attribute_name, ao.name AS option_name, ao.price_modifier, ao.price_operation, ao.stock_quantity
             FROM product_attributes pa
             INNER JOIN attributes a ON a.id = pa.attribute_id
             INNER JOIN attribute_options ao ON ao.id = pa.attribute_option_id
             WHERE pa.product_id = ? AND pa.attribute_option_id IN ($placeholders)",
            array_merge([$productId], $optionIds)
        )->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) !== count($optionIds)) {
            return ['success' => false, 'message' => 'selected_option_not_available'];
        }

        $uniqueAttributes = [];
        $options = [];
        $stockLimit = null;

        foreach ($rows as $row) {
            $attributeId = (int) ($row['attribute_id'] ?? 0);
            if ($attributeId <= 0 || isset($uniqueAttributes[$attributeId])) {
                return ['success' => false, 'message' => 'selected_option_not_available'];
            }
            $uniqueAttributes[$attributeId] = true;

            $modifier = max(0.0, (float) ($row['price_modifier'] ?? 0));
            $operation = (($row['price_operation'] ?? '+') === '-') ? '-' : '+';
            $optionStock = $row['stock_quantity'] !== null ? (int) $row['stock_quantity'] : null;
            if ($optionStock !== null) {
                $stockLimit = $stockLimit === null ? $optionStock : min($stockLimit, $optionStock);
            }

            $options[] = [
                'option_id' => (int) ($row['attribute_option_id'] ?? 0),
                'name' => (string) ($row['attribute_name'] ?? ''),
                'value' => (string) ($row['option_name'] ?? ''),
                'price' => (float) number_format($modifier, 2, '.', ''),
                'op' => $operation,
            ];
        }

        return ['success' => true, 'options' => self::normalizeSelectedOptions($options), 'stock_limit' => $stockLimit];
    }

    public static function add($productId, $quantity = 1, array $selectedOptionIds = []): array
    {
        $productId = (int) $productId;
        $quantity = max(1, (int) $quantity);

        $product = Product::findVisibleById($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'product_not_found'];
        }

        $resolvedOptions = self::resolveSelectedOptions($productId, $selectedOptionIds);
        if (!$resolvedOptions['success']) {
            return ['success' => false, 'message' => $resolvedOptions['message']];
        }

        $stock = (int) ($product['stock'] ?? 0);
        if ($resolvedOptions['stock_limit'] !== null) {
            $stock = min($stock, max(0, (int) $resolvedOptions['stock_limit']));
        }

        if ($stock < 1) {
            return ['success' => false, 'message' => 'not_enough_stock'];
        }

        $selectedOptionsJson = self::selectedOptionsToJson($resolvedOptions['options']);
        $existing = self::findCartItem($productId, $selectedOptionsJson);
        $currentQty = (int) ($existing['quantity'] ?? 0);
        $newQty = $currentQty + $quantity;

        if ($newQty > $stock) {
            return ['success' => false, 'message' => 'not_enough_stock'];
        }

        $userId = self::userId();
        $sid = self::sessionId();

        if ($existing) {
            DB::query(
                "UPDATE " . self::TABLE . " SET quantity = ? WHERE id = ?",
                [$newQty, (int) $existing['id']]
            );
        } else {
            DB::query(
                "INSERT INTO " . self::TABLE . " (user_id, session_id, product_id, selected_options, quantity)
                 VALUES (?, ?, ?, ?, ?)",
                [$userId, $userId ? null : $sid, $productId, $selectedOptionsJson, $newQty]
            );
        }

        return ['success' => true];
    }

    public static function updateQuantity($cartItemId, $quantity): array
    {
        $cartItemId = (int) $cartItemId;
        $quantity = (int) $quantity;
        $scope = self::scope();

        $existing = DB::query(
            "SELECT c.* FROM " . self::TABLE . " c WHERE {$scope['where']} AND c.id = ? LIMIT 1",
            array_merge($scope['params'], [$cartItemId])
        )->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            return ['success' => false, 'message' => 'product_not_found'];
        }

        if ($quantity <= 0) {
            DB::query("DELETE FROM " . self::TABLE . " WHERE id = ?", [(int) $existing['id']]);
            return ['success' => true];
        }

        $productId = (int) ($existing['product_id'] ?? 0);
        $product = Product::findVisibleById($productId);
        if (!$product) {
            DB::query("DELETE FROM " . self::TABLE . " WHERE id = ?", [(int) $existing['id']]);
            return ['success' => false, 'message' => 'product_not_found'];
        }

        $stock = (int) ($product['stock'] ?? 0);
        $selectedOptions = self::parseSelectedOptions($existing['selected_options'] ?? null);
        if (!empty($selectedOptions)) {
            $optionIds = array_map(static fn (array $option): int => (int) $option['option_id'], $selectedOptions);
            $resolved = self::resolveSelectedOptions($productId, $optionIds);
            if (!$resolved['success']) {
                return ['success' => false, 'message' => 'selected_option_not_available'];
            }
            if ($resolved['stock_limit'] !== null) {
                $stock = min($stock, max(0, (int) $resolved['stock_limit']));
            }
        }

        if ($quantity > $stock) {
            return ['success' => false, 'message' => 'not_enough_stock'];
        }

        DB::query(
            "UPDATE " . self::TABLE . " SET quantity = ? WHERE id = ?",
            [$quantity, (int) $existing['id']]
        );

        return ['success' => true];
    }

    public static function remove($cartItemId): void
    {
        $cartItemId = (int) $cartItemId;
        $scope = self::scope();

        DB::query(
            "DELETE c FROM " . self::TABLE . " c
             WHERE {$scope['where']} AND c.id = ?",
            array_merge($scope['params'], [$cartItemId])
        );
    }

    public static function clear(): void
    {
        $scope = self::scope();

        DB::query(
            "DELETE c FROM " . self::TABLE . " c WHERE {$scope['where']}",
            $scope['params']
        );
    }

    public static function getItems(): array
    {
        $scope = self::scope();

        $sql = "SELECT
                    c.product_id,
                    c.id AS cart_item_id,
                    c.selected_options,
                    c.quantity,
                    p.name,
                    p.image,
                    p.price,
                    p.stock
                FROM " . self::TABLE . " c
                INNER JOIN products p ON p.id = c.product_id
                WHERE {$scope['where']}
                ORDER BY c.id DESC";

        $rows = DB::query($sql, $scope['params'])->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $price = (float) $row['price'];
            $qty = max(1, (int) $row['quantity']);
            $stock = max(0, (int) $row['stock']);
            $selectedOptions = self::parseSelectedOptions($row['selected_options'] ?? null);
            $priceDelta = 0.0;
            foreach ($selectedOptions as $option) {
                $optionPrice = max(0.0, (float) ($option['price'] ?? 0));
                $priceDelta += (($option['op'] ?? '+') === '-') ? -$optionPrice : $optionPrice;
            }
            $finalPrice = max(0.0, $price + $priceDelta);

            if ($stock > 0 && $qty > $stock) {
                $qty = $stock;
            }

            $items[] = [
                'cart_item_id' => (int) $row['cart_item_id'],
                'product_id'  => (int) $row['product_id'],
                'name'        => $row['name'] ?? '',
                'image'       => $row['image'] ?? null,
                'price'       => $finalPrice,
                'quantity'    => $qty,
                'stock'       => $stock,
                'selected_options' => $selectedOptions,
                'total_price' => $finalPrice * $qty,
            ];
        }

        return $items;
    }

    public static function getTotal(): float
    {
        $total = 0.0;

        foreach (self::getItems() as $item) {
            $total += (float) $item['total_price'];
        }

        return $total;
    }

    /**
     * Перенести гостьовий кошик на user_id після логіну.
     * Викликається: Cart::migrate($oldSessionId, $userId)
     */
    public static function migrate($oldSessionId, $userId): bool
    {
        $oldSessionId = (string) $oldSessionId;
        $userId = (int) $userId;

        if ($oldSessionId === '' || $userId <= 0) {
            return false;
        }

        $guestItems = DB::query(
            "SELECT product_id, selected_options, quantity
             FROM " . self::TABLE . "
             WHERE user_id IS NULL AND session_id = ?",
            [$oldSessionId]
        )->fetchAll(PDO::FETCH_ASSOC);

        if (empty($guestItems)) {
            return true;
        }

        foreach ($guestItems as $item) {
            $productId = (int) $item['product_id'];
            $selectedOptionsJson = $item['selected_options'] ?? null;
            $qty = max(1, (int) $item['quantity']);

            $existing = DB::query(
                "SELECT id, quantity
                 FROM " . self::TABLE . "
                 WHERE user_id = ? AND product_id = ?
                   AND ((? IS NULL AND selected_options IS NULL) OR selected_options = ?)
                 LIMIT 1",
                [$userId, $productId, $selectedOptionsJson, $selectedOptionsJson]
            )->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $newQty = (int) $existing['quantity'] + $qty;
                DB::query(
                    "UPDATE " . self::TABLE . " SET quantity = ? WHERE id = ?",
                    [$newQty, (int) $existing['id']]
                );
            } else {
                DB::query(
                    "INSERT INTO " . self::TABLE . " (user_id, session_id, product_id, selected_options, quantity)
                     VALUES (?, NULL, ?, ?, ?)",
                    [$userId, $productId, $selectedOptionsJson, $qty]
                );
            }
        }

        DB::query(
            "DELETE FROM " . self::TABLE . " WHERE user_id IS NULL AND session_id = ?",
            [$oldSessionId]
        );

        return true;
    }
}
