<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\View\View;
use App\Models\Setting;
use App\Services\StockServiceFactory;

class AdminOrderController
{
    private array $allowedStatuses = [
        'pending',
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
    private ?array $activeShippingMethodsByCode = null;

    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    private function respondJson(array $payload, int $statusCode = 200): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getJsonPayload(): array
    {
        $rawInput = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawInput, true);

        return is_array($payload) ? $payload : $_POST;
    }

    private const PER_PAGE = 24;

    public function index(): void
    {
        $this->checkAdmin();

        $kanbanColumns = [
            'new'        => __('status_new'),
            'confirmed'  => __('status_confirmed'),
            'processing' => __('status_processing'),
            'shipped'    => __('status_shipped'),
        ];

        $view         = $_GET['view']  ?? 'kanban';
        $statusFilter = $_GET['status'] ?? '';
        $searchFilter = trim($_GET['search'] ?? '');

        // ── ТАБЛИЦЯ ──────────────────────────────────────────────────────────
        $tableWhere  = [];
        $tableParams = [];

        if ($statusFilter !== '') {
            $tableWhere[]  = 'status = ?';
            $tableParams[] = $statusFilter;
        }
        if ($searchFilter !== '') {
            $tableWhere[]  = '(customer_name LIKE ? OR customer_phone LIKE ? OR id = ?)';
            $like = '%' . $searchFilter . '%';
            array_push($tableParams, $like, $like, (int)$searchFilter);
        }

        $tableWhereSql = $tableWhere ? 'WHERE ' . implode(' AND ', $tableWhere) : '';

        [$tableOrders, $tablePager] = \App\Core\Pagination\Paginator::paginate(
            "SELECT id, customer_name, customer_phone, total, status,
                    delivery_method, payment_method, created_at
             FROM orders $tableWhereSql ORDER BY created_at DESC",
            $tableParams,
            "SELECT COUNT(*) FROM orders $tableWhereSql",
            $tableParams,
            self::PER_PAGE,
            'tpage'
        );

        $tableOrders = $this->attachMethodNames($tableOrders);

        // ── КАНБАН ───────────────────────────────────────────────────────────
        $kanbanStatuses = array_keys($kanbanColumns);
        $placeholders   = implode(',', array_fill(0, count($kanbanStatuses), '?'));

        [$kanbanOrders, $kanbanPager] = \App\Core\Pagination\Paginator::paginate(
            "SELECT id, customer_name, customer_phone, total, status,
                    delivery_method, payment_method, created_at
             FROM orders WHERE status IN ($placeholders) ORDER BY created_at DESC",
            $kanbanStatuses,
            "SELECT COUNT(*) FROM orders WHERE status IN ($placeholders)",
            $kanbanStatuses,
            self::PER_PAGE,
            'kpage'
        );

        $kanbanOrders = $this->attachMethodNames($kanbanOrders);

        View::render('admin/orders/index', [
            'kanbanColumns'        => $kanbanColumns,
            'kanbanOrders'         => $kanbanOrders,
            'kanbanPager'          => $kanbanPager,
            'tableOrders'          => $tableOrders,
            'tablePager'           => $tablePager,
            'allStatuses'          => $this->allowedStatuses,
            'statusFilter'         => $statusFilter,
            'searchFilter'         => $searchFilter,
            'activeView'           => $view,
            'orders'               => $kanbanOrders,
            'activeCurrencySymbol' => DB::query('SELECT symbol FROM currencies WHERE is_active = 1 LIMIT 1')->fetchColumn() ?: '₴',
        ], 'admin');
    }

    public function updateStatus(): void
    {
        $this->checkAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respondJson(['success' => false, 'message' => __('admin_order_method_not_supported')], 405);
            return;
        }

        $payload = $this->getJsonPayload();
        $orderId = (int) ($payload['order_id'] ?? 0);
        $newStatus = trim((string) ($payload['status'] ?? ''));
        $ttnCode = trim((string) ($payload['ttn_code'] ?? ''));

        if ($orderId <= 0 || $newStatus === '') {
            $this->respondJson(['success' => false, 'message' => __('admin_order_invalid_data')], 422);
            return;
        }

        if (!in_array($newStatus, $this->allowedStatuses, true)) {
            $this->respondJson(['success' => false, 'message' => __('admin_order_unknown_status')], 422);
            return;
        }

        if ($newStatus === 'shipped' && $ttnCode === '') {
            $this->respondJson(['success' => false, 'message' => __('admin_order_ttn_required')], 422);
            return;
        }

        try {
            $this->ensureStatusHistoryTable();
            DB::beginTransaction();

            $order = DB::query('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId])->fetch(\PDO::FETCH_ASSOC);
            if (!$order) {
                throw new \RuntimeException(__('admin_order_not_found'));
            }

            $currentStatus = (string) ($order['status'] ?? 'new');
            if ($currentStatus === $newStatus) {
                DB::commit();
                $this->respondJson(['success' => true, 'message' => __('admin_order_status_unchanged'), 'status' => $currentStatus]);
                return;
            }

            if ($newStatus === 'completed' && $currentStatus !== 'completed') {
                $this->completeOrderItems($orderId);
            }

            if (!in_array($currentStatus, ['cancelled', 'returned'], true)
                && in_array($newStatus, ['cancelled', 'returned'], true)
            ) {
                $this->releaseOrderReserve($orderId);
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

            DB::commit();

            // Синхронізація статусу з Prom.ua (якщо інтеграція увімкнена)
            if (\App\Services\PromApiClient::isEnabled()) {
                try {
                    (new \App\Services\PromStatusService())->onStatusChanged(
                        $orderId,
                        $newStatus,
                        $ttnCode
                    );
                } catch (\Throwable $e) {
                    // Не блокуємо основну відповідь при помилці Prom
                    error_log('PromStatusService error: ' . $e->getMessage());
                }
            }

            $this->respondJson([
                'success'  => true,
                'message'  => __('admin_order_status_updated'),
                'status'   => $newStatus,
                'ttn_code' => $ttnCode,
            ]);
        } catch (\Throwable $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
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
        $previousDisplayErrors = ini_get('display_errors');

        try {
            ini_set('display_errors', '0');

            $orderId = (int) $id;
            if ($orderId <= 0) {
                $this->respondJson(['success' => false, 'message' => __('admin_order_id_invalid')], 422);
                return;
            }

            $order = DB::query('SELECT * FROM orders WHERE id = ?', [$orderId])->fetch(\PDO::FETCH_ASSOC);
            if (!$order) {
                $this->respondJson(['success' => false, 'message' => __('admin_order_not_found')], 404);
                return;
            }

            $order = $this->attachMethodNamesToOrder($order);

            $items = DB::query(
                'SELECT
                    oi.id,
                    oi.product_id,
                    oi.qty,
                    oi.price,
                    oi.selected_options,
                    p.name AS product_name,
                    COALESCE(ps.quantity, 0) AS stock
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 LEFT JOIN product_stocks ps
                    ON ps.sku COLLATE utf8mb4_general_ci = p.sku COLLATE utf8mb4_general_ci
                    AND ps.option_id IS NULL
                 WHERE oi.order_id = ?
                 ORDER BY oi.id ASC',
                [$orderId]
            )->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($items as &$item) {
                $decodedOptions = json_decode((string) ($item['selected_options'] ?? ''), true);
                $item['selected_options'] = is_array($decodedOptions) ? $decodedOptions : [];
            }
            unset($item);

            $history = [];
            if ($this->hasStatusHistoryTable()) {
                $history = DB::query(
                    'SELECT id, old_status, new_status, ttn_code, changed_by, changed_at
                     FROM order_status_history
                     WHERE order_id = ?
                     ORDER BY changed_at DESC, id DESC',
                    [$orderId]
                )->fetchAll(\PDO::FETCH_ASSOC);
            }

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
        } catch (\Throwable $e) {
            error_log(sprintf(
                '[AdminOrderController::orderDetails] %s in %s:%d\n%s',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));

            $this->respondJson([
                'success' => false,
                'message' => __('admin_order_details_load_error'),
            ], 500);
        } finally {
            if ($previousDisplayErrors !== false) {
                ini_set('display_errors', (string) $previousDisplayErrors);
            }
        }
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
            $this->respondJson(['success' => false, 'message' => __('admin_order_method_not_supported')], 405);
            return;
        }

        $payload = $this->getJsonPayload();

        try {
            $normalized = $this->normalizeOrderInput($payload);
            $this->ensureStatusHistoryTable();

            DB::beginTransaction();

            $isUpdate = $normalized['id'] > 0;
            $oldStatus = null;
            if ($isUpdate) {
                $currentOrder = DB::query('SELECT status FROM orders WHERE id = ? FOR UPDATE', [$normalized['id']])->fetch(\PDO::FETCH_ASSOC);
                if (!$currentOrder) {
                    throw new \InvalidArgumentException(__('admin_order_edit_not_found'));
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

                $orderId = (int) DB::lastInsertId();
                $oldStatus = null;
            }

            foreach ($normalized['items'] as $item) {
                DB::query(
                    'INSERT INTO order_items (order_id, product_id, qty, price, selected_options) VALUES (?, ?, ?, ?, ?)',
                    [
                        $orderId,
                        $item['product_id'],
                        $item['qty'],
                        $item['price'],
                        json_encode($item['selected_options'], JSON_UNESCAPED_UNICODE),
                    ]
                );
            }

            if ($oldStatus !== $normalized['status']) {
                $this->insertStatusHistory($orderId, $oldStatus, $normalized['status'], null);
            }

            DB::commit();

            $this->respondJson([
                'success' => true,
                'message' => $isUpdate ? __('admin_order_updated') : __('admin_order_created'),
                'order_id' => $orderId,
            ]);
        } catch (\InvalidArgumentException $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }
            $this->respondJson(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }
            $this->respondJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function syncLogistics(): void
    {
        $this->checkAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respondJson(['success' => false, 'message' => __('admin_order_method_not_supported')], 405);
            return;
        }

        if (!$this->hasTtnCodeColumn()) {
            $this->respondJson(['success' => true, 'updated' => [], 'message' => __('admin_order_ttn_column_missing')]);
            return;
        }

        try {
            $this->ensureStatusHistoryTable();
            $shippedOrders = DB::query(
                'SELECT id, status, ttn_code, delivery_method, customer_phone
                 FROM orders
                 WHERE status = ? AND ttn_code IS NOT NULL AND ttn_code <> ""',
                ['shipped']
            )->fetchAll(\PDO::FETCH_ASSOC);

            $updated = [];
            $skipped = 0;
            DB::beginTransaction();
            foreach ($shippedOrders as $order) {
                $carrierStatus = $this->fetchCarrierStatusForOrder($order);
                if ($carrierStatus === null) {
                    $skipped++;
                    continue;
                }

                // Плагіни повертають внутрішній статус замовлення. Старий адаптер
                // Нової пошти лишається сумісним і повертає службове "received".
                $nextStatus = in_array($carrierStatus, $this->allowedStatuses, true)
                    ? $carrierStatus
                    : ($carrierStatus === 'received' ? 'completed' : 'shipped');

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
            DB::commit();

            $this->respondJson([
                'success' => true,
                'updated' => $updated,
                'message' => empty($updated)
                    ? ($skipped > 0 ? __('admin_logistics_no_changes_skipped') : __('admin_logistics_no_changes'))
                    : __('admin_logistics_statuses_updated'),
                'skipped' => $skipped,
            ]);
        } catch (\Throwable $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }
            $this->respondJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteOrder($id): void
    {
        $this->checkAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->respondJson(['success' => false, 'message' => __('admin_order_method_not_supported')], 405);
            return;
        }

        $orderId = (int) $id;
        if ($orderId <= 0) {
            $this->respondJson(['success' => false, 'message' => __('admin_order_id_invalid')], 422);
            return;
        }

        try {
            DB::beginTransaction();

            // Перевіряємо чи існує замовлення
            $order = DB::query('SELECT id, status FROM orders WHERE id = ? FOR UPDATE', [$orderId])->fetch(\PDO::FETCH_ASSOC);
            if (!$order) {
                throw new \RuntimeException(__('admin_order_not_found'));
            }

            // Видаляємо елементи замовлення
            DB::query('DELETE FROM order_items WHERE order_id = ?', [$orderId]);

            // Видаляємо історію статусів якщо існує таблиця
            if ($this->hasStatusHistoryTable()) {
                DB::query('DELETE FROM order_status_history WHERE order_id = ?', [$orderId]);
            }

            // Видаляємо саме замовлення
            DB::query('DELETE FROM orders WHERE id = ?', [$orderId]);

            DB::commit();

            $this->respondJson([
                'success' => true,
                'message' => __('order_delete_success'),
            ]);
        } catch (\Throwable $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }

            $this->respondJson([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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
            throw new \InvalidArgumentException(__('admin_order_customer_name_invalid'));
        }

        $phoneMask = normalize_phone_mask((string) Setting::get('phone_mask', '+38 (###) ###-##-##'));
        if (!is_phone_matching_mask($customerPhone, $phoneMask)) {
            throw new \InvalidArgumentException(__('admin_order_phone_invalid'));
        }

        if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException(__('admin_order_email_invalid'));
        }

        if (!in_array($status, $this->allowedStatuses, true)) {
            throw new \InvalidArgumentException(__('admin_order_status_invalid'));
        }

        $itemsPayload = $payload['items'] ?? [];
        if (!is_array($itemsPayload) || count($itemsPayload) === 0) {
            throw new \InvalidArgumentException(__('admin_order_items_required'));
        }

        $normalizedItems = [];
        $total = 0.0;

        foreach ($itemsPayload as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException(sprintf(__('admin_order_item_format_error'), $index + 1));
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($productId <= 0 || $qty <= 0) {
                throw new \InvalidArgumentException(sprintf(__('admin_order_item_invalid'), $index + 1));
            }

            $product = DB::query('SELECT id, price FROM products WHERE id = ?', [$productId])->fetch(\PDO::FETCH_ASSOC);
            if (!$product) {
                throw new \InvalidArgumentException(sprintf(__('admin_order_product_not_found'), $productId));
            }

            $price = isset($item['price']) ? (float) $item['price'] : (float) ($product['price'] ?? 0);
            if ($price < 0) {
                throw new \InvalidArgumentException(__('admin_order_price_negative'));
            }

            $normalizedItems[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'price' => round($price, 2),
                'selected_options' => $this->normalizeSelectedOptions($item['selected_options'] ?? []),
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

    private function fetchCarrierStatusForOrder(array $order): ?string
    {
        $deliveryCode = trim((string) ($order['delivery_method'] ?? ''));
        $ttnCode = trim((string) ($order['ttn_code'] ?? ''));
        $customerPhone = (string) ($order['customer_phone'] ?? '');

        if ($deliveryCode === '' || $ttnCode === '') {
            return null;
        }

        $method = $this->getActiveShippingMethodByCode($deliveryCode);
        if ($method === null) {
            return null;
        }

        if ($deliveryCode === 'nova_poshta') {
            return $this->fetchNovaPoshtaStatus($method, $ttnCode, $customerPhone);
        }

        /**
         * Плагіни повертають внутрішній статус замовлення або null, якщо
         * синхронізація для цього перевізника не виконується.
         */
        $status = apply_filters('logistics.carrier_status', null, $order, $method);
        return is_string($status) && $status !== '' ? $status : null;
    }

    private function fetchNovaPoshtaStatus(array $method, string $ttnCode, string $phone): ?string
    {
        $settings = $this->decodeMethodSettings($method['settings'] ?? null);
        $apiKey = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            return null;
        }

        $normalizedPhone = preg_replace('/\D+/', '', $phone);
        $payload = [
            'apiKey' => $apiKey,
            'modelName' => 'TrackingDocument',
            'calledMethod' => 'getStatusDocuments',
            'methodProperties' => [
                'Documents' => [[
                    'DocumentNumber' => $ttnCode,
                    'Phone' => $normalizedPhone ?: '',
                ]],
            ],
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            // SSL для Windows/OSPanel де може бути відсутній cacert
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $responseBody = @file_get_contents('https://api.novaposhta.ua/v2.0/json/', false, $context);
        if (!is_string($responseBody) || $responseBody === '') {
            return null;
        }

        $response = json_decode($responseBody, true);
        if (!is_array($response) || empty($response['success'])) {
            return null;
        }

        $first = $response['data'][0] ?? null;
        if (!is_array($first)) {
            return null;
        }

        return $this->mapCarrierStatusToInternal(
            (string) ($first['Status'] ?? ''),
            (string) ($first['StatusCode'] ?? '')
        );
    }

    private function mapCarrierStatusToInternal(string $status, string $statusCode): string
    {
        $normalized = mb_strtolower(trim($status));

        if (in_array($statusCode, ['9', '10', '11'], true)) {
            return 'received';
        }

        if ($normalized !== '') {
            $receivedMarkers = ['отриман', 'вручено', 'доставлен', 'видано', 'получен'];
            foreach ($receivedMarkers as $marker) {
                if (mb_strpos($normalized, $marker) !== false) {
                    return 'received';
                }
            }
        }

        return 'in_transit';
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

    private function releaseOrderReserve(int $orderId): void
    {
        $items = DB::query('SELECT oi.qty, p.sku FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [$orderId])->fetchAll(\PDO::FETCH_ASSOC);
        $stockService = StockServiceFactory::make();

        foreach ($items as $item) {
            $qty = max(0, (int) ($item['qty'] ?? 0));
            $sku = (string) ($item['sku'] ?? '');
            if ($qty > 0 && $sku !== '') {
                $stockService->releaseReserve($sku, $qty);
            }
        }
    }

    private function completeOrderItems(int $orderId): void
    {
        $items = DB::query('SELECT oi.qty, p.sku FROM order_items oi INNER JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [$orderId])->fetchAll(\PDO::FETCH_ASSOC);
        $stockService = StockServiceFactory::make();

        foreach ($items as $item) {
            $qty = max(0, (int) ($item['qty'] ?? 0));
            $sku = (string) ($item['sku'] ?? '');
            if ($qty <= 0 || $sku === '') {
                continue;
            }

            if (!$stockService->removeStock($sku, $qty, sprintf(__('admin_order_stock_deduction_reason'), $orderId))) {
                throw new \RuntimeException(sprintf(__('admin_order_insufficient_stock'), $sku));
            }
            $stockService->releaseReserve($sku, $qty);
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


    private function hasStatusHistoryTable(): bool
    {
        $statement = DB::query(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_status_history'"
        );

        return ((int) $statement->fetchColumn()) > 0;
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

    private function getActiveShippingMethodByCode(string $code): ?array
    {
        if ($this->activeShippingMethodsByCode === null) {
            $this->activeShippingMethodsByCode = [];
            $methods = Setting::getShopMethods('shipping');

            foreach ($methods as $method) {
                if ((int) ($method['is_active'] ?? 0) !== 1) {
                    continue;
                }

                $methodCode = trim((string) ($method['code'] ?? ''));
                if ($methodCode === '') {
                    continue;
                }

                $this->activeShippingMethodsByCode[$methodCode] = $method;
            }
        }

        return $this->activeShippingMethodsByCode[$code] ?? null;
    }

    private function decodeMethodSettings($settings): array
    {
        if (is_array($settings)) {
            return $settings;
        }

        if ($settings === null || $settings === '') {
            return [];
        }

        $decoded = json_decode((string) $settings, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeSelectedOptions($selectedOptions): array
    {
        if (is_string($selectedOptions) && $selectedOptions !== '') {
            $decoded = json_decode($selectedOptions, true);
            $selectedOptions = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($selectedOptions)) {
            return [];
        }

        $normalized = [];
        foreach ($selectedOptions as $option) {
            if (!is_array($option)) {
                continue;
            }

            $name = trim((string) ($option['name'] ?? ''));
            $value = trim((string) ($option['value'] ?? ''));

            if ($name === '' && $value === '') {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'value' => $value,
            ];
        }

        return $normalized;
    }
}
