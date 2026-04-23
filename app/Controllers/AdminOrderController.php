<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\View\View;
use App\Models\Setting;

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

    private function respondJson(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function getJsonPayload(): array
    {
        $rawInput = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawInput, true);

        return is_array($payload) ? $payload : $_POST;
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
            'SELECT id, customer_name, customer_phone, total, status, delivery_method, payment_method, created_at FROM orders ORDER BY created_at DESC'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $orders = $this->attachMethodNames($orders);

        View::render('admin/orders/index', [
            'kanbanColumns' => $kanbanColumns,
            'orders' => $orders,
            'allStatuses' => $this->allowedStatuses,
        ], 'admin');
    }

    public function updateStatus(): void
    {
        $this->checkAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respondJson(['success' => false, 'message' => 'Метод не підтримується'], 405);
            return;
        }

        $payload = $this->getJsonPayload();
        $orderId = (int) ($payload['order_id'] ?? 0);
        $newStatus = trim((string) ($payload['status'] ?? ''));
        $ttnCode = trim((string) ($payload['ttn_code'] ?? ''));

        if ($orderId <= 0 || $newStatus === '') {
            $this->respondJson(['success' => false, 'message' => 'Некоректні дані'], 422);
            return;
        }

        if (!in_array($newStatus, $this->allowedStatuses, true)) {
            $this->respondJson(['success' => false, 'message' => 'Невідомий статус'], 422);
            return;
        }

        if ($newStatus === 'shipped' && $ttnCode === '') {
            $this->respondJson(['success' => false, 'message' => 'Для статусу "Відправлено" вкажіть ТТН'], 422);
            return;
        }

        try {
            $this->ensureStatusHistoryTable();
            DB::$pdo->beginTransaction();

            $order = DB::query('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId])->fetch(\PDO::FETCH_ASSOC);
            if (!$order) {
                throw new \RuntimeException('Замовлення не знайдено');
            }

            $currentStatus = (string) ($order['status'] ?? 'new');
            if ($currentStatus === $newStatus) {
                DB::$pdo->commit();
                $this->respondJson(['success' => true, 'message' => 'Статус не змінено', 'status' => $currentStatus]);
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

            $this->insertStatusHistory($orderId, $currentStatus, $newStatus, $ttnCode !== '' ? $ttnCode : null);

            DB::$pdo->commit();
            $this->respondJson([
                'success' => true,
                'message' => 'Статус оновлено',
                'status' => $newStatus,
                'ttn_code' => $ttnCode,
            ]);
        } catch (\Throwable $e) {
            if (DB::$pdo->inTransaction()) {
                DB::$pdo->rollBack();
            }

            $this->respondJson([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function orderDetails($id): void
    {
        $this->checkAdmin();

        $orderId = (int) $id;
        if ($orderId <= 0) {
            $this->respondJson(['success' => false, 'message' => 'Некоректний ID замовлення'], 422);
            return;
        }

        $this->ensureStatusHistoryTable();

        $order = DB::query('SELECT * FROM orders WHERE id = ?', [$orderId])->fetch(\PDO::FETCH_ASSOC);
        if (!$order) {
            $this->respondJson(['success' => false, 'message' => 'Замовлення не знайдено'], 404);
            return;
        }

        $order = $this->attachMethodNamesToOrder($order);

        $items = DB::query(
            'SELECT oi.id, oi.product_id, oi.qty, oi.price, p.name AS product_name, p.stock
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC',
            [$orderId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $history = DB::query(
            'SELECT id, old_status, new_status, ttn_code, changed_by, changed_at
             FROM order_status_history
             WHERE order_id = ?
             ORDER BY changed_at DESC, id DESC',
            [$orderId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $total = 0.0;
        foreach ($items as $item) {
            $total += ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0));
        }

        $this->respondJson([
            'success' => true,
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'computed_total' => round($total, 2),
            'allowed_statuses' => $this->allowedStatuses,
        ]);
    }

    private function attachMethodNames(array $orders): array
    {
        if (empty($orders)) {
            return $orders;
        }

        $shippingMethods = Setting::getShopMethods('shipping');
        $paymentMethods = Setting::getShopMethods('payment');

        $shippingMap = [];
        foreach ($shippingMethods as $method) {
            $code = (string) ($method['code'] ?? '');
            if ($code !== '') {
                $shippingMap[$code] = (string) ($method['name'] ?? $code);
            }
        }

        $paymentMap = [];
        foreach ($paymentMethods as $method) {
            $code = (string) ($method['code'] ?? '');
            if ($code !== '') {
                $paymentMap[$code] = (string) ($method['name'] ?? $code);
            }
        }

        foreach ($orders as &$order) {
            $deliveryCode = (string) ($order['delivery_method'] ?? '');
            $paymentCode = (string) ($order['payment_method'] ?? '');

            $order['delivery_method_name'] = $shippingMap[$deliveryCode] ?? $deliveryCode;
            $order['payment_method_name'] = $paymentMap[$paymentCode] ?? $paymentCode;
        }
        unset($order);

        return $orders;
    }

    private function attachMethodNamesToOrder(array $order): array
    {
        $ordersWithNames = $this->attachMethodNames([$order]);
        return $ordersWithNames[0] ?? $order;
    }

    public function saveOrder(): void
    {
        $this->checkAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respondJson(['success' => false, 'message' => 'Метод не підтримується'], 405);
            return;
        }

        $payload = $this->getJsonPayload();

        try {
            $normalized = $this->normalizeOrderInput($payload);
            $this->ensureStatusHistoryTable();

            DB::$pdo->beginTransaction();

            $isUpdate = $normalized['id'] > 0;
            $oldStatus = null;
            if ($isUpdate) {
                $currentOrder = DB::query('SELECT status FROM orders WHERE id = ? FOR UPDATE', [$normalized['id']])->fetch(\PDO::FETCH_ASSOC);
                if (!$currentOrder) {
                    throw new \InvalidArgumentException('Замовлення для редагування не знайдено');
                }

                $oldStatus = (string) ($currentOrder['status'] ?? 'new');

                DB::query(
                    'UPDATE orders SET customer_name = ?, customer_phone = ?, customer_email = ?,
                     delivery_method = ?, delivery_city = ?, delivery_warehouse = ?, delivery_address = ?,
                     payment_method = ?, comment = ?, status = ?, total = ? WHERE id = ?',
                    [
                        $normalized['customer_name'],
                        $normalized['customer_phone'],
                        $normalized['customer_email'],
                        $normalized['delivery_method'],
                        $normalized['delivery_city'],
                        $normalized['delivery_warehouse'],
                        $normalized['delivery_address'],
                        $normalized['payment_method'],
                        $normalized['comment'],
                        $normalized['status'],
                        $normalized['total'],
                        $normalized['id'],
                    ]
                );

                DB::query('DELETE FROM order_items WHERE order_id = ?', [$normalized['id']]);
                $orderId = $normalized['id'];
            } else {
                DB::query(
                    'INSERT INTO orders (user_id, total, customer_name, customer_phone, customer_email, delivery_method,
                     delivery_city, delivery_warehouse, delivery_address, payment_method, status, comment, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                    [
                        null,
                        $normalized['total'],
                        $normalized['customer_name'],
                        $normalized['customer_phone'],
                        $normalized['customer_email'],
                        $normalized['delivery_method'],
                        $normalized['delivery_city'],
                        $normalized['delivery_warehouse'],
                        $normalized['delivery_address'],
                        $normalized['payment_method'],
                        $normalized['status'],
                        $normalized['comment'],
                    ]
                );

                $orderId = (int) DB::$pdo->lastInsertId();
                $oldStatus = null;
            }

            foreach ($normalized['items'] as $item) {
                DB::query(
                    'INSERT INTO order_items (order_id, product_id, qty, price, selected_options) VALUES (?, ?, ?, ?, NULL)',
                    [$orderId, $item['product_id'], $item['qty'], $item['price']]
                );
            }

            if ($oldStatus !== $normalized['status']) {
                $this->insertStatusHistory($orderId, $oldStatus, $normalized['status'], null);
            }

            DB::$pdo->commit();

            $this->respondJson([
                'success' => true,
                'message' => $isUpdate ? 'Замовлення оновлено' : 'Замовлення створено',
                'order_id' => $orderId,
            ]);
        } catch (\InvalidArgumentException $e) {
            if (DB::$pdo->inTransaction()) {
                DB::$pdo->rollBack();
            }
            $this->respondJson(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if (DB::$pdo->inTransaction()) {
                DB::$pdo->rollBack();
            }
            $this->respondJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function syncLogistics(): void
    {
        $this->checkAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respondJson(['success' => false, 'message' => 'Метод не підтримується'], 405);
            return;
        }

        if (!$this->hasTtnCodeColumn()) {
            $this->respondJson(['success' => true, 'updated' => [], 'message' => 'Колонка ttn_code відсутня']);
            return;
        }

        try {
            $this->ensureStatusHistoryTable();
            $shippedOrders = DB::query(
                'SELECT id, status, ttn_code FROM orders WHERE status = ? AND ttn_code IS NOT NULL AND ttn_code <> ""',
                ['shipped']
            )->fetchAll(\PDO::FETCH_ASSOC);

            $updated = [];
            DB::$pdo->beginTransaction();
            foreach ($shippedOrders as $order) {
                $carrierStatus = $this->mockCarrierStatus((string) $order['ttn_code']);
                $nextStatus = $carrierStatus === 'received' ? 'completed' : 'shipped';

                if ($nextStatus !== ($order['status'] ?? '')) {
                    DB::query('UPDATE orders SET status = ? WHERE id = ?', [$nextStatus, (int) $order['id']]);
                    $this->insertStatusHistory((int) $order['id'], (string) $order['status'], $nextStatus, (string) $order['ttn_code']);

                    $updated[] = [
                        'order_id' => (int) $order['id'],
                        'from' => (string) $order['status'],
                        'to' => $nextStatus,
                        'carrier_status' => $carrierStatus,
                    ];
                }
            }
            DB::$pdo->commit();

            $this->respondJson([
                'success' => true,
                'updated' => $updated,
                'message' => empty($updated) ? 'Нових змін статусів не знайдено' : 'Статуси оновлено',
            ]);
        } catch (\Throwable $e) {
            if (DB::$pdo->inTransaction()) {
                DB::$pdo->rollBack();
            }
            $this->respondJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function normalizeOrderInput(array $payload): array
    {
        $id = (int) ($payload['id'] ?? 0);
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        $customerPhone = trim((string) ($payload['customer_phone'] ?? ''));
        $customerEmail = trim((string) ($payload['customer_email'] ?? ''));
        $deliveryMethod = trim((string) ($payload['delivery_method'] ?? ''));
        $deliveryCity = trim((string) ($payload['delivery_city'] ?? ''));
        $deliveryWarehouse = trim((string) ($payload['delivery_warehouse'] ?? ''));
        $deliveryAddress = trim((string) ($payload['delivery_address'] ?? ''));
        $paymentMethod = trim((string) ($payload['payment_method'] ?? ''));
        $comment = trim((string) ($payload['comment'] ?? ''));
        $status = trim((string) ($payload['status'] ?? 'new'));

        if ($customerName === '' || mb_strlen($customerName) < 2) {
            throw new \InvalidArgumentException('Вкажіть коректне імʼя клієнта');
        }

        if (!preg_match('/^[0-9+\-()\s]{8,20}$/', $customerPhone)) {
            throw new \InvalidArgumentException('Вкажіть коректний номер телефону');
        }

        if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Невірний формат email');
        }

        if (!in_array($status, $this->allowedStatuses, true)) {
            throw new \InvalidArgumentException('Невідомий статус замовлення');
        }

        $itemsPayload = $payload['items'] ?? [];
        if (!is_array($itemsPayload) || count($itemsPayload) === 0) {
            throw new \InvalidArgumentException('Додайте хоча б один товар');
        }

        $normalizedItems = [];
        $total = 0.0;

        foreach ($itemsPayload as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Помилка формату товарів у рядку ' . ($index + 1));
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                throw new \InvalidArgumentException('Некоректні дані товару у рядку ' . ($index + 1));
            }

            $product = DB::query('SELECT id, price FROM products WHERE id = ?', [$productId])->fetch(\PDO::FETCH_ASSOC);
            if (!$product) {
                throw new \InvalidArgumentException('Товар ID ' . $productId . ' не знайдено');
            }

            $price = isset($item['price']) ? (float) $item['price'] : (float) ($product['price'] ?? 0);
            if ($price < 0) {
                throw new \InvalidArgumentException('Ціна не може бути відʼємною');
            }

            $normalizedItems[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'price' => round($price, 2),
            ];

            $total += $qty * $price;
        }

        return [
            'id' => $id,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'customer_email' => $customerEmail,
            'delivery_method' => $deliveryMethod,
            'delivery_city' => $deliveryCity,
            'delivery_warehouse' => $deliveryWarehouse,
            'delivery_address' => $deliveryAddress,
            'payment_method' => $paymentMethod,
            'comment' => $comment,
            'status' => $status,
            'items' => $normalizedItems,
            'total' => round($total, 2),
        ];
    }

    private function mockCarrierStatus(string $ttnCode): string
    {
        $normalized = preg_replace('/\s+/', '', mb_strtolower($ttnCode));
        $hash = abs(crc32($normalized));
        $bucket = $hash % 3;

        if ($bucket === 0) {
            return 'received';
        }

        if ($bucket === 1) {
            return 'in_transit';
        }

        return 'sorted_at_hub';
    }

    private function insertStatusHistory(int $orderId, ?string $oldStatus, string $newStatus, ?string $ttnCode): void
    {
        DB::query(
            'INSERT INTO order_status_history (order_id, old_status, new_status, ttn_code, changed_by, changed_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [
                $orderId,
                $oldStatus,
                $newStatus,
                $ttnCode,
                isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null,
            ]
        );
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
