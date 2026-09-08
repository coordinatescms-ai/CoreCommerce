<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Services\SeoService;
use App\Models\Cart;
use App\Models\Setting;
use App\Models\CrmUserService;
use App\Services\StockServiceFactory;

class OrderController
{
    private function parseSelectedOptions($value): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveOptionStockLimit(int $productId, array $selectedOptions): ?int
    {
        $optionIds = array_values(array_unique(array_filter(array_map(static function ($option): int {
            return (int) ($option['option_id'] ?? 0);
        }, $selectedOptions), static fn (int $id): bool => $id > 0)));

        if (empty($optionIds)) {
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($optionIds), '?'));
        $rows = DB::query(
            "SELECT pa.attribute_id, pa.stock_quantity
             FROM product_attributes pa
             WHERE pa.product_id = ? AND pa.attribute_option_id IN ($placeholders)",
            array_merge([$productId], $optionIds)
        )->fetchAll();

        if (count($rows) !== count($optionIds)) {
            throw new \RuntimeException(__('checkout_selected_options_unavailable'));
        }

        $seenAttributes = [];
        $stockLimit = null;
        foreach ($rows as $row) {
            $attributeId = (int) ($row['attribute_id'] ?? 0);
            if ($attributeId <= 0 || isset($seenAttributes[$attributeId])) {
                throw new \RuntimeException(__('checkout_selected_options_invalid'));
            }
            $seenAttributes[$attributeId] = true;

            if ($row['stock_quantity'] !== null) {
                $stock = max(0, (int) $row['stock_quantity']);
                $stockLimit = $stockLimit === null ? $stock : min($stockLimit, $stock);
            }
        }

        return $stockLimit;
    }

    public function checkout()
    {
        $cartItems = Cart::getItems();

        if (empty($cartItems)) {
            $_SESSION['error'] = __('checkout_cart_empty_before_order');
            header('Location: /cart');
            exit;
        }

        $items = [];
        foreach ($cartItems as $row) {
            $items[] = [
                'id' => (int) $row['product_id'],
                'name' => $row['name'],
                'price' => (float) $row['price'],
                'stock' => (int) $row['stock'],
                'quantity' => (int) $row['quantity'],
                'selected_options' => $row['selected_options'] ?? [],
            ];
        }

        if (empty($items)) {
            $_SESSION['error'] = __('checkout_cart_products_unavailable');
            header('Location: /cart');
            exit;
        }

        $total = array_reduce($items, function ($sum, $item) {
            return $sum + ($item['price'] * $item['quantity']);
        }, 0.0);

        $deliveryMethods = Setting::getShopMethods('shipping');
        $paymentMethods = Setting::getShopMethods('payment');

        $deliveryMethods = array_values(array_filter($deliveryMethods, static function ($method): bool {
            return (int) ($method['is_active'] ?? 0) === 1;
        }));
        $paymentMethods = array_values(array_filter($paymentMethods, static function ($method): bool {
            return (int) ($method['is_active'] ?? 0) === 1;
        }));

        $deliveryMethods = array_map([$this, 'normalizeShopMethod'], $deliveryMethods);
        $paymentMethods = array_map([$this, 'normalizeShopMethod'], $paymentMethods);

        return View::render('checkout/index', [
            'seo' => SeoService::forSystem('checkout', '/checkout'),
            'items' => $items,
            'total' => $total,
            'csrf' => Csrf::token(),
            'user' => $_SESSION['user'] ?? null,
            'deliveryMethods' => $deliveryMethods,
            'paymentMethods' => $paymentMethods,
            'phoneMask' => normalize_phone_mask((string) get_setting('phone_mask', '+38 (###) ###-##-##')),
        ]);
    }

    public function success($orderId)
    {
        $orderId = (int) $orderId;
        $sessionOrderId = (int) ($_SESSION['checkout_success_order_id'] ?? 0);

        // Дозволяємо перегляд лише щойно створеного замовлення в поточній сесії.
        // Це не змінює доступ до замовлень в особистому кабінеті.
        if ($orderId <= 0 || $sessionOrderId !== $orderId) {
            header('Location: /');
            exit;
        }

        return View::render('checkout/success', [
            'seo' => SeoService::forSystem('checkout', '/checkout'),
            'orderNumber' => $orderId,
        ]);
    }

    public function placeOrder()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => __('admin_order_method_not_supported')]);
            return;
        }

        if (!Csrf::isValid()) {
            http_response_code(419);
            echo json_encode(['success' => false, 'message' => __('csrf_token_invalid')]);
            return;
        }

        $cartItems = Cart::getItems();
        if (empty($cartItems)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => __('cart_is_empty')]);
            return;
        }

        $items = [];
        foreach ($cartItems as $row) {
            $items[] = [
                'id' => (int) $row['product_id'],
                'name' => $row['name'],
                'price' => (float) $row['price'],
                'stock' => (int) $row['stock'],
                'quantity' => (int) $row['quantity'],
                'selected_options' => $row['selected_options'] ?? [],
            ];
        }

        if (empty($items)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => __('checkout_cart_products_unavailable')]);
            return;
        }

        $payload = $this->sanitizePayload($_POST);
        $errors = $this->validatePayload($payload);

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => __('checkout_validation_error'), 'errors' => $errors]);
            return;
        }

        try {
            DB::beginTransaction();

            $lockedItems = $this->loadAndLockProducts(array_column($items, 'id'));
            $lockedMap = [];
            foreach ($lockedItems as $locked) {
                $lockedMap[(int) $locked['id']] = $locked;
            }

            $stockService = StockServiceFactory::make();
            $total = 0.0;
            foreach ($items as $item) {
                $productId = (int) $item['id'];
                $quantity = (int) $item['quantity'];
                $product = $lockedMap[$productId] ?? null;

                if (!$product) {
                    throw new \RuntimeException(__('checkout_product_missing'));
                }

                $sku = (string) ($product['sku'] ?? '');
                if ($sku === '') {
                    throw new \RuntimeException(sprintf(__('checkout_product_sku_missing'), $product['name']));
                }

                if ($quantity > $stockService->getAvailableQuantity($sku)) {
                    throw new \RuntimeException(sprintf(__('checkout_product_out_of_stock'), $product['name']));
                }

                $optionStockLimit = $this->resolveOptionStockLimit($productId, (array) ($item['selected_options'] ?? []));
                if ($optionStockLimit !== null && $quantity > $optionStockLimit) {
                    throw new \RuntimeException(sprintf(__('checkout_option_stock_insufficient'), $product['name']));
                }

                $total += ((float) $item['price'] * $quantity);
            }

            // Визначаємо платіжний шлюз ДО створення замовлення, щоб встановити
            // коректний початковий статус:
            //   - CodGateway ("Оплата при отриманні") — гроші ще не рухались,
            //     клієнт просто оформив замовлення → статус 'new' (Новий)
            //   - Реальний онлайн-шлюз (LiqPay тощо) — очікуємо підтвердження
            //     оплати від платіжної системи → статус 'pending' (Очікує оплати)
            $gateway = \App\Core\Payment\PaymentManager::findForOrder(
                $payload['payment_method_code']
            );
            $isCodPayment = $gateway === null || $gateway->getName() === 'cod';
            $initialStatus = $isCodPayment ? 'new' : 'pending';

            DB::query(
                'INSERT INTO orders (user_id, total, customer_name, customer_phone, customer_email, delivery_method, delivery_city, delivery_warehouse, delivery_address, payment_method, payment_id, delivery_id, comment, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null,
                    $total,
                    $payload['full_name'],
                    $payload['phone'],
                    $payload['email'],
                    $payload['delivery_method_code'],
                    $payload['delivery_city'],
                    $payload['delivery_warehouse'],
                    $payload['delivery_address'],
                    $payload['payment_method_code'],
                    $payload['payment_id'],
                    $payload['delivery_id'],
                    $payload['comment'],
                    $initialStatus,
                ]
            );

            $orderId = (int) DB::lastInsertId();

            // Хук для плагінів — замовлення створено, ще не оплачено
            do_action('order.created', $orderId, $payload['payment_method_code'], $total);

            foreach ($items as $item) {
                $productId = (int) $item['id'];
                $quantity = (int) $item['quantity'];
                $selectedOptions = (array) ($item['selected_options'] ?? []);
                $price = (float) $item['price'];
                $selectedOptionsJson = empty($selectedOptions)
                    ? null
                    : json_encode($selectedOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                DB::query(
                    'INSERT INTO order_items (order_id, product_id, selected_options, qty, price) VALUES (?, ?, ?, ?, ?)',
                    [$orderId, $productId, $selectedOptionsJson, $quantity, $price]
                );

                $sku = (string) ($lockedMap[$productId]['sku'] ?? '');
                if (!$stockService->reserve($sku, $quantity)) {
                    throw new \RuntimeException(__('checkout_stock_unavailable'));
                }
            }

            DB::commit();

            if (!empty($_SESSION['user']['id'])) {
                CrmUserService::recordActivity((int) $_SESSION['user']['id'], 'order_created', __('order_created') . ' ' . $orderId);
            }

            // Очищаємо кошик у БД (поточний scope: user_id або session_id)
            Cart::clear();

            // Запам'ятовуємо створене замовлення для одноразового показу
            // сторінки успіху в поточній checkout-сесії.
            $_SESSION['checkout_success_order_id'] = $orderId;

            // ── Платіжний шлюз ──────────────────────────────────────────────
            // $gateway вже визначений вище (перед INSERT замовлення) —
            // використовується там же для встановлення початкового статусу.
            // Тут лише застосовуємо fallback до COD, якщо шлюз не знайдено.
            if ($gateway === null) {
                $gateway = new \App\Core\Payment\Gateways\CodGateway();
            }

            $paymentResult = $gateway->initiate(
                $orderId,
                $total,
                [
                    'email'       => $payload['email'],
                    'phone'       => $payload['phone'],
                    'name'        => $payload['full_name'],
                    'description' => __('order_created') . ' ' . $orderId,
                ]
            );

            echo json_encode([
                'success'        => true,
                'message'        => __('checkout_order_success'),
                'order_id'       => $orderId,
                'payment_action' => $paymentResult->action,
                'payment_url'    => $paymentResult->url,
                'payment_html'   => $paymentResult->html,
                'payment_message'=> $paymentResult->message,
            ]);
        } catch (\Throwable $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }

            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function sanitizePayload(array $input): array
    {
        $clean = [];

        $clean['full_name'] = trim(strip_tags((string) ($input['full_name'] ?? '')));
        $clean['phone'] = trim(strip_tags((string) ($input['phone'] ?? '')));
        $email = trim((string) ($input['email'] ?? ''));
        $clean['email'] = filter_var($email, FILTER_SANITIZE_EMAIL);
        $clean['delivery_id'] = (int) ($input['delivery_id'] ?? 0);
        $clean['delivery_city'] = trim(strip_tags((string) ($input['delivery_city'] ?? '')));
        $clean['delivery_warehouse'] = trim(strip_tags((string) ($input['delivery_warehouse'] ?? '')));
        $clean['delivery_address'] = trim(strip_tags((string) ($input['delivery_address'] ?? '')));
        $clean['payment_id'] = (int) ($input['payment_id'] ?? 0);
        $clean['comment'] = trim(strip_tags((string) ($input['comment'] ?? '')));
        $clean['delivery_method_code'] = '';
        $clean['payment_method_code'] = '';
        $clean['delivery_method_settings'] = [];

        return $clean;
    }

    private function validatePayload(array &$payload): array
    {
        $errors = [];

        if ($payload['full_name'] === '' || mb_strlen($payload['full_name']) < 5) {
            $errors['full_name'] = __('checkout_specify_pib');
        }

        $phoneMask = normalize_phone_mask((string) get_setting('phone_mask', '+38 (###) ###-##-##'));
        if (!is_phone_matching_mask($payload['phone'], $phoneMask)) {
            $errors['phone'] = __('checkout_specify_phone');
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('checkout_specify_email');
        }

        $deliveryMethod = $this->getActiveShopMethodById('shipping', (int) $payload['delivery_id']);
        if ($deliveryMethod === null) {
            $errors['delivery_id'] = __('checkout_specify_delivery');
        } else {
            $payload['delivery_method_code'] = (string) ($deliveryMethod['code'] ?? '');
            $payload['delivery_method_settings'] = $this->decodeSettings($deliveryMethod['settings'] ?? null);
        }

        $paymentMethod = $this->getActiveShopMethodById('payment', (int) $payload['payment_id']);
        if ($paymentMethod === null) {
            $errors['payment_id'] = __('checkout_specify_payment');
        } else {
            $payload['payment_method_code'] = (string) ($paymentMethod['code'] ?? '');
        }

        if (($payload['delivery_method_code'] ?? '') === 'nova_poshta') {
            if ($payload['delivery_city'] === '') {
                $errors['delivery_city'] = __('checkout_specify_city');
            }

            if ($payload['delivery_warehouse'] === '') {
                $errors['delivery_warehouse'] = __('checkout_specify_warehouse');
            }
        }

        if (($payload['delivery_method_code'] ?? '') === 'courier' && $payload['delivery_address'] === '') {
            $errors['delivery_address'] = __('checkout_specify_address');
        }

        return $errors;
    }

    private function getActiveShopMethodById(string $type, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $method = DB::query(
            'SELECT * FROM shop_methods WHERE id = ? AND type = ? AND is_active = 1 LIMIT 1',
            [$id, $type]
        )->fetch(\PDO::FETCH_ASSOC);

        return $method ?: null;
    }

    private function normalizeShopMethod(array $method): array
    {
        $method['settings'] = $this->decodeSettings($method['settings'] ?? null);
        return $method;
    }

    private function decodeSettings($settings): array
    {
        if ($settings === null || $settings === '') {
            return [];
        }

        if (is_array($settings)) {
            return $settings;
        }

        $decoded = json_decode((string) $settings, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function loadAndLockProducts(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return DB::query("SELECT id, sku, name, price FROM products WHERE id IN ($placeholders) FOR UPDATE", $ids)->fetchAll();
    }
}
