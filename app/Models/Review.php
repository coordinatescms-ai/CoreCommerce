<?php

namespace App\Models;

use App\Core\Database\DB;

class Review
{
    public static function create(array $data): int
    {
        DB::execute(
            'INSERT INTO product_reviews (product_id, user_id, parent_id, rating, author_name, body, is_visible, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
            [
                (int) $data['product_id'],
                (int) $data['user_id'],
                $data['parent_id'] !== null ? (int) $data['parent_id'] : null,
                $data['rating'] !== null ? (int) $data['rating'] : null,
                (string) $data['author_name'],
                (string) $data['body'],
            ]
        );

        return (int) DB::$pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $row = DB::query('SELECT * FROM product_reviews WHERE id = ? LIMIT 1', [$id])->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getThreadedByProduct(int $productId, int $limit, int $offset): array
    {
        $roots = DB::query(
            'SELECT r.*, u.email AS user_email FROM product_reviews r LEFT JOIN users u ON u.id = r.user_id WHERE r.product_id = ? AND r.parent_id IS NULL AND r.is_visible = 1 ORDER BY r.created_at DESC LIMIT ? OFFSET ?',
            [$productId, $limit, $offset]
        )->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if (empty($roots)) {
            return [];
        }

        $rootIds = array_map(static fn ($r) => (int) $r['id'], $roots);
        $placeholders = implode(',', array_fill(0, count($rootIds), '?'));

        $replies = DB::query(
            "SELECT r.*, u.email AS user_email FROM product_reviews r LEFT JOIN users u ON u.id = r.user_id WHERE r.parent_id IN ($placeholders) AND r.is_visible = 1 ORDER BY r.created_at ASC",
            $rootIds
        )->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $groupedReplies = [];
        foreach ($replies as $reply) {
            $groupedReplies[(int) $reply['parent_id']][] = $reply;
        }

        foreach ($roots as &$root) {
            $root['replies'] = $groupedReplies[(int) $root['id']] ?? [];
        }

        return $roots;
    }

    public static function countRootsByProduct(int $productId): int
    {
        return (int) (DB::query('SELECT COUNT(*) AS total FROM product_reviews WHERE product_id = ? AND parent_id IS NULL AND is_visible = 1', [$productId])->fetch()['total'] ?? 0);
    }
}
