<?php

namespace App\Models;

use App\Core\Model;

class ProductImage extends Model
{
    protected static $table = 'product_images';

    public static function getByProduct(int $productId): array
    {
        return self::query(
            "SELECT * FROM " . static::$table . " WHERE product_id = ? ORDER BY sort_order ASC, id ASC",
            [$productId]
        ) ?? [];
    }

    public static function findById(int $id): ?array
    {
        $rows = self::query("SELECT * FROM " . static::$table . " WHERE id = ? LIMIT 1", [$id]);
        return $rows[0] ?? null;
    }

    public static function countByProduct(int $productId): int
    {
        $rows = self::query("SELECT COUNT(*) AS cnt FROM " . static::$table . " WHERE product_id = ?", [$productId]);
        return (int) ($rows[0]['cnt'] ?? 0);
    }

    public static function getNextSortOrder(int $productId): int
    {
        $rows = self::query("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort FROM " . static::$table . " WHERE product_id = ?", [$productId]);
        return (int) ($rows[0]['next_sort'] ?? 1);
    }

    public static function createForProduct(int $productId, string $imagePath, int $sortOrder): bool
    {
        return (bool) self::execute(
            "INSERT INTO " . static::$table . " (product_id, image_path, sort_order, created_at) VALUES (?, ?, ?, NOW())",
            [$productId, $imagePath, $sortOrder]
        );
    }

    public static function deleteById(int $id): bool
    {
        return (bool) self::execute("DELETE FROM " . static::$table . " WHERE id = ?", [$id]);
    }

    public static function deleteByProduct(int $productId): bool
    {
        return (bool) self::execute("DELETE FROM " . static::$table . " WHERE product_id = ?", [$productId]);
    }
}
