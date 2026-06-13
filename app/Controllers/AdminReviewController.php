<?php

namespace App\Controllers;

use App\Core\View\View;
use App\Core\Http\Csrf;
use App\Core\Database\DB;

class AdminReviewController
{
    private const PER_PAGE = 20;

    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    private function json(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index(): void
    {
        $this->checkAdmin();

        $filterStatus = $_GET['status'] ?? 'all';
        $filterSearch = trim($_GET['search'] ?? '');

        $where  = [];
        $params = [];

        if ($filterStatus === 'visible') { $where[] = 'r.is_visible = 1'; }
        if ($filterStatus === 'hidden')  { $where[] = 'r.is_visible = 0'; }

        if ($filterSearch !== '') {
            $where[] = '(r.author_name LIKE ? OR r.body LIKE ? OR p.name LIKE ?)';
            $like = '%' . $filterSearch . '%';
            array_push($params, $like, $like, $like);
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        [$reviews, $pager] = \App\Core\Pagination\Paginator::paginate(
            "SELECT r.id, r.author_name, r.body, r.rating, r.is_visible,
                    r.parent_id, r.created_at,
                    p.id AS product_id, p.name AS product_name, p.slug AS product_slug,
                    u.email AS user_email
             FROM product_reviews r
             LEFT JOIN products p ON p.id = r.product_id
             LEFT JOIN users    u ON u.id = r.user_id
             $whereSql
             ORDER BY r.created_at DESC",
            $params,
            "SELECT COUNT(r.id)
             FROM product_reviews r
             LEFT JOIN products p ON p.id = r.product_id
             $whereSql",
            $params,
            self::PER_PAGE
        );

        View::render('admin/reviews/index', [
            'reviews'      => $reviews,
            'pager'        => $pager,
            'filterStatus' => $filterStatus,
            'filterSearch' => $filterSearch,
            'csrf'         => Csrf::token(),
        ], 'admin');
    }

    public function delete(int $id): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний'], 419);
        }

        $review = DB::query('SELECT id FROM product_reviews WHERE id = ?', [$id])
                    ->fetch(\PDO::FETCH_ASSOC);

        if (!$review) {
            $this->json(['success' => false, 'message' => 'Коментар не знайдено'], 404);
        }

        // Видаляємо разом з відповідями
        DB::query('DELETE FROM product_reviews WHERE id = ? OR parent_id = ?', [$id, $id]);

        $this->json(['success' => true, 'message' => 'Коментар видалено']);
    }

    public function toggle(int $id): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний'], 419);
        }

        $review = DB::query('SELECT id, is_visible FROM product_reviews WHERE id = ?', [$id])
                    ->fetch(\PDO::FETCH_ASSOC);

        if (!$review) {
            $this->json(['success' => false, 'message' => 'Коментар не знайдено'], 404);
        }

        $newState = (int)$review['is_visible'] === 1 ? 0 : 1;
        DB::query(
            'UPDATE product_reviews SET is_visible = ?, updated_at = NOW() WHERE id = ?',
            [$newState, $id]
        );

        $this->json([
            'success'    => true,
            'is_visible' => $newState,
            'message'    => $newState ? 'Коментар опубліковано' : 'Коментар заблоковано',
        ]);
    }

    public function bulkAction(): never
    {
        $this->checkAdmin();

        $payload = json_decode(file_get_contents('php://input') ?: '', true) ?? [];

        if (!hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($payload['csrf'] ?? ''))) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний'], 419);
        }

        $action = $payload['action'] ?? '';
        $ids    = array_map('intval', (array)($payload['ids'] ?? []));

        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'Не обрано жодного коментаря'], 422);
        }
        if (!in_array($action, ['delete', 'hide', 'show'], true)) {
            $this->json(['success' => false, 'message' => 'Невідома дія'], 422);
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($action === 'delete') {
            DB::query("DELETE FROM product_reviews WHERE id IN ($placeholders) OR parent_id IN ($placeholders)",
                array_merge($ids, $ids));
            $message = 'Коментарі видалено';
        } elseif ($action === 'hide') {
            DB::query("UPDATE product_reviews SET is_visible = 0, updated_at = NOW() WHERE id IN ($placeholders)", $ids);
            $message = 'Коментарі заблоковано';
        } else {
            DB::query("UPDATE product_reviews SET is_visible = 1, updated_at = NOW() WHERE id IN ($placeholders)", $ids);
            $message = 'Коментарі опубліковано';
        }

        $this->json(['success' => true, 'message' => $message, 'count' => count($ids)]);
    }
}
