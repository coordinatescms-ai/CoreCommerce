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

    private static function findCartItem(int $productId): ?array
    {
        $scope = self::scope();

        $sql = "SELECT c.*
                FROM " . self::TABLE . " c
                WHERE {$scope['where']} AND c.product_id = ?
                LIMIT 1";

        $row = DB::query($sql, array_merge($scope['params'], [$productId]))->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function add($productId, $quantity = 1): array
    {
        $productId = (int) $productId;
        $quantity = max(1, (int) $quantity);

        $product = Product::findById($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'product_not_found'];
        }

        $stock = (int) ($product['stock'] ?? 0);
        if ($stock < 1) {
            return ['success' => false, 'message' => 'not_enough_stock'];
        }

        $existing = self::findCartItem($productId);
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
                "INSERT INTO " . self::TABLE . " (user_id, session_id, product_id, quantity)
                 VALUES (?, ?, ?, ?)",
                [$userId, $userId ? null : $sid, $productId, $newQty]
            );
        }

        return ['success' => true];
    }

    public static function updateQuantity($productId, $quantity): array
    {
        $productId = (int) $productId;
        $quantity = (int) $quantity;

        $existing = self::findCartItem($productId);
        if (!$existing) {
            return ['success' => false, 'message' => 'product_not_found'];
        }

        if ($quantity <= 0) {
            DB::query("DELETE FROM " . self::TABLE . " WHERE id = ?", [(int) $existing['id']]);
            return ['success' => true];
        }

        $product = Product::findById($productId);
        if (!$product) {
            DB::query("DELETE FROM " . self::TABLE . " WHERE id = ?", [(int) $existing['id']]);
            return ['success' => false, 'message' => 'product_not_found'];
        }

        $stock = (int) ($product['stock'] ?? 0);
        if ($quantity > $stock) {
            return ['success' => false, 'message' => 'not_enough_stock'];
        }

        DB::query(
            "UPDATE " . self::TABLE . " SET quantity = ? WHERE id = ?",
            [$quantity, (int) $existing['id']]
        );

        return ['success' => true];
    }

    public static function remove($productId): void
    {
        $productId = (int) $productId;
        $scope = self::scope();

        DB::query(
            "DELETE c FROM " . self::TABLE . " c
             WHERE {$scope['where']} AND c.product_id = ?",
            array_merge($scope['params'], [$productId])
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

            if ($stock > 0 && $qty > $stock) {
                $qty = $stock;
            }

            $items[] = [
                'product_id'  => (int) $row['product_id'],
                'name'        => $row['name'] ?? '',
                'image'       => $row['image'] ?? null,
                'price'       => $price,
                'quantity'    => $qty,
                'stock'       => $stock,
                'total_price' => $price * $qty,
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
            "SELECT product_id, quantity
             FROM " . self::TABLE . "
             WHERE user_id IS NULL AND session_id = ?",
            [$oldSessionId]
        )->fetchAll(PDO::FETCH_ASSOC);

        if (empty($guestItems)) {
            return true;
        }

        foreach ($guestItems as $item) {
            $productId = (int) $item['product_id'];
            $qty = max(1, (int) $item['quantity']);

            $existing = DB::query(
                "SELECT id, quantity
                 FROM " . self::TABLE . "
                 WHERE user_id = ? AND product_id = ?
                 LIMIT 1",
                [$userId, $productId]
            )->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $newQty = (int) $existing['quantity'] + $qty;
                DB::query(
                    "UPDATE " . self::TABLE . " SET quantity = ? WHERE id = ?",
                    [$newQty, (int) $existing['id']]
                );
            } else {
                DB::query(
                    "INSERT INTO " . self::TABLE . " (user_id, session_id, product_id, quantity)
                     VALUES (?, NULL, ?, ?)",
                    [$userId, $productId, $qty]
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