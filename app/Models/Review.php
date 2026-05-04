<?php

namespace App\Models;

use App\Core\Database\DB;

class Review
{
    public const MAX_BODY_LENGTH = 2000;

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

    public static function getAdminList(array $filters, int $limit, int $offset): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['product'])) {
            $where[] = 'p.name LIKE ?';
            $params[] = '%' . $filters['product'] . '%';
        }
        if (!empty($filters['author'])) {
            $where[] = 'r.author_name LIKE ?';
            $params[] = '%' . $filters['author'] . '%';
        }
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where[] = 'r.is_visible = ?';
            $params[] = (int) $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT r.*, p.name AS product_name, u.email AS user_email, parent.author_name AS parent_author
                FROM product_reviews r
                LEFT JOIN products p ON p.id = r.product_id
                LEFT JOIN users u ON u.id = r.user_id
                LEFT JOIN product_reviews parent ON parent.id = r.parent_id
                $whereSql
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";

        $rows = DB::query($sql, array_merge($params, [$limit, $offset]))->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $countRow = DB::query("SELECT COUNT(*) AS total FROM product_reviews r LEFT JOIN products p ON p.id = r.product_id $whereSql", $params)->fetch(\PDO::FETCH_ASSOC);
        return ['rows' => $rows, 'total' => (int) ($countRow['total'] ?? 0)];
    }

    public static function updateBody(int $id, string $body): void
    {
        DB::execute('UPDATE product_reviews SET body = ?, updated_at = NOW() WHERE id = ?', [$body, $id]);
    }

    public static function setVisibility(int $id, int $visible): void
    {
        DB::execute('UPDATE product_reviews SET is_visible = ?, updated_at = NOW() WHERE id = ?', [$visible, $id]);
    }

    public static function deleteById(int $id): void
    {
        DB::execute('DELETE FROM product_reviews WHERE id = ?', [$id]);
    }
}
