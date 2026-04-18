<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\View\View;

class AdminOrderController
{
    private array $allowedStatuses = [
        'new',
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'completed',
        'cancelled',
        'returned',
    ];

    private ?bool $hasTtnCodeColumn = null;

    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void
    {
        $this->checkAdmin();

        $kanbanColumns = [
            'new' => 'Новий',
            'confirmed' => 'Підтверджено',
            'processing' => 'Комплектується',
            'shipped' => 'Відправлено',
        ];

        $orders = DB::query(
            'SELECT id, customer_name, total, status, created_at FROM orders ORDER BY created_at DESC'
        )->fetchAll(\PDO::FETCH_ASSOC);

        View::render('admin/orders/index', [
            'kanbanColumns' => $kanbanColumns,
            'orders' => $orders,
        ], 'admin');
    }

    public function updateStatus(): void
    {
        $this->checkAdmin();

        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Метод не підтримується']);
            return;
        }

        $rawInput = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawInput, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $orderId = (int) ($payload['order_id'] ?? 0);
        $newStatus = trim((string) ($payload['status'] ?? ''));
        $ttnCode = trim((string) ($payload['ttn_code'] ?? ''));

        if ($orderId <= 0 || $newStatus === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Некоректні дані']);
            return;
        }

        if (!in_array($newStatus, $this->allowedStatuses, true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Невідомий статус']);
            return;
        }

        if ($newStatus === 'shipped' && $ttnCode === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Для статусу "Відправлено" вкажіть ТТН']);
            return;
        }

        try {
            DB::$pdo->beginTransaction();
            $this->ensureStatusHistoryTable();

            $order = DB::query('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId])->fetch(\PDO::FETCH_ASSOC);
            if (!$order) {
                throw new \RuntimeException('Замовлення не знайдено');
            }

            $currentStatus = (string) ($order['status'] ?? 'new');
            if ($currentStatus === $newStatus) {
                DB::$pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Статус не змінено', 'status' => $currentStatus]);
                return;
            }

            if ($currentStatus !== 'processing' && $newStatus === 'processing') {
                $this->reserveOrderItems($orderId);
            }

            if (!in_array($currentStatus, ['cancelled', 'returned'], true)
                && in_array($newStatus, ['cancelled', 'returned'], true)
            ) {
                $this->returnOrderItemsToStock($orderId);
            }

            if ($this->hasTtnCodeColumn()) {
                DB::query('UPDATE orders SET status = ?, ttn_code = ? WHERE id = ?', [
                    $newStatus,
                    $ttnCode !== '' ? $ttnCode : null,
                    $orderId,
                ]);
            } else {
                DB::query('UPDATE orders SET status = ? WHERE id = ?', [$newStatus, $orderId]);
            }

            DB::query(
                'INSERT INTO order_status_history (order_id, old_status, new_status, ttn_code, changed_by, changed_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [
                    $orderId,
                    $currentStatus,
                    $newStatus,
                    $ttnCode !== '' ? $ttnCode : null,
                    isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null,
                ]
            );

            DB::$pdo->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Статус оновлено',
                'status' => $newStatus,
                'ttn_code' => $ttnCode,
            ]);
        } catch (\Throwable $e) {
            if (DB::$pdo->inTransaction()) {
                DB::$pdo->rollBack();
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function reserveOrderItems(int $orderId): void
    {
        $items = DB::query('SELECT product_id, qty FROM order_items WHERE order_id = ?', [$orderId])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = max(0, (int) ($item['qty'] ?? 0));

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            $statement = DB::query('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?', [$qty, $productId, $qty]);
            if ($statement->rowCount() !== 1) {
                throw new \RuntimeException('Недостатньо залишків для резервування товару ID ' . $productId);
            }
        }
    }

    private function returnOrderItemsToStock(int $orderId): void
    {
        $items = DB::query('SELECT product_id, qty FROM order_items WHERE order_id = ?', [$orderId])->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = max(0, (int) ($item['qty'] ?? 0));

            if ($productId <= 0 || $qty <= 0) {
                continue;
            }

            DB::query('UPDATE products SET stock = stock + ? WHERE id = ?', [$qty, $productId]);
        }
    }

    private function ensureStatusHistoryTable(): void
    {
        DB::query(
            'CREATE TABLE IF NOT EXISTS order_status_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                old_status VARCHAR(50) NULL,
                new_status VARCHAR(50) NOT NULL,
                ttn_code VARCHAR(100) NULL,
                changed_by INT NULL,
                changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_status_history_order_id (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
        );
    }

    private function hasTtnCodeColumn(): bool
    {
        if ($this->hasTtnCodeColumn !== null) {
            return $this->hasTtnCodeColumn;
        }

        $statement = DB::query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'ttn_code'"
        );

        $this->hasTtnCodeColumn = ((int) $statement->fetchColumn()) > 0;
        return $this->hasTtnCodeColumn;
    }
}
