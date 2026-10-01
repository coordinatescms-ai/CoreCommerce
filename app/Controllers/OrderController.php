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

        // Контекст для фільтрів доступності чекауту. Плагіни можуть звузити
        // список методів (зона доставки, вага, сума кошика, ліміти післяплати
        // тощо) — фільтри checkout.delivery_methods / checkout.payment_methods.
        $checkoutContext = $this->buildCheckoutContext($items, (float) $total);

        $deliveryMethods = $this->resolveCheckoutMethods('shipping', $checkoutContext);
        $paymentMethods  = $this->resolveCheckoutMethods('payment', $checkoutContext);

        return View::render('checkout/index', [
            'seo' => SeoService::forSystem('checkout', '/checkout'),
            'items' => $items,
            'total' => $total,
            'csrf' => Csrf::token(),
            'user' => $_SESSION['user'] ?? null,
            'deliveryMethods' => $deliveryMethods,
            'paymentMethods' => $paymentMethods,
            'shippingIncludeInTotal' => (string) get_setting('shipping_include_in_total', '1') === '1',
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

        // Той самий набір методів, який бачив покупець на /checkout: плагіни
        // можуть зняти метод фільтром (правила доступності). Валідація нижче
        // звіряється САМЕ з цим списком, тому метод, прихований правилом,
        // неможливо надіслати прямим POST /place-order.
        $checkoutContext = $this->buildCheckoutContext($items, (float) $total, $payload);
        $deliveryMethods = $this->resolveCheckoutMethods('shipping', $checkoutContext);
        $paymentMethods  = $this->resolveCheckoutMethods('payment', $checkoutContext);

        $errors = $this->validatePayload($payload, $deliveryMethods, $paymentMethods);

        $checkoutContext['delivery_method'] = $this->findMethodById($deliveryMethods, (int) $payload['delivery_id']);
        $checkoutContext['payment_method'] = $this->findMethodById($paymentMethods, (int) $payload['payment_id']);

        // Серверна валідація полів, які рендерять плагіни (checkout.delivery_fields
        // тощо). Раніше такі перевірки жили лише в JS плагіна і обходились
        // прямим запитом без браузера.
        $filteredErrors = apply_filters('checkout.validate', $errors, $payload, $checkoutContext);
        if (is_array($filteredErrors)) {
            $errors = $filteredErrors;
        }

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

            // ── Вартість доставки ────────────────────────────────────────────
            // $total на цей момент — це subtotal, перерахований за замкненими
            // (FOR UPDATE) цінами товарів. Вартість доставки додається поверх:
            // вона потрапляє і в orders.total, і в суму, яку отримує платіжний
            // шлюз, тож клієнт реально оплачує доставку. Плагін може
            // перерахувати її фільтром checkout.shipping_cost (живий тариф
            // перевізника, безкоштовна доставка від суми тощо).
            //
            // Колонка orders.shipping_cost додається update.sql з пакета
            // оновлення. Якщо файли оновили, а SQL-крок не виконали, ядро
            // свідомо працює по-старому (доставка не додається до суми) і пише
            // про це в storage/logs/checkout.log, а не падає 500-ю на кожному
            // замовленні.
            $hasShippingCostColumn = $this->ordersHasShippingCostColumn();
            $shippingCost = 0.0;
            $selectedDelivery = $this->findMethodById($deliveryMethods, (int) $payload['delivery_id']);

            if ($hasShippingCostColumn) {
                $shippingContext = $this->buildCheckoutContext($items, (float) $total, $payload);
                $shippingContext['delivery_method'] = $selectedDelivery;
                $shippingContext['payment_method'] = $this->findMethodById($paymentMethods, (int) $payload['payment_id']);
                $shippingCost = $this->resolveShippingCost($shippingContext);
                if ((string) get_setting('shipping_include_in_total', '1') === '1') {
                    $total += $shippingCost;
                }
            } else {
                $this->logCheckout('УВАГА: немає колонки orders.shipping_cost — вартість доставки не додано в total. Виконайте SQL-крок оновлення (update.sql або /admin/migrations).');
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

            $preOrderContext = $this->buildCheckoutContext(
                $items,
                (float) ($total - (((string) get_setting('shipping_include_in_total', '1') === '1') ? $shippingCost : 0)),
                $payload
            );
            $preOrderContext['delivery_method'] = $selectedDelivery;
            $preOrderContext['payment_method'] = $this->findMethodById($paymentMethods, (int) $payload['payment_id']);
            $preOrderContext['total'] = round($total, 2);
            $preOrderContext['shipping_cost'] = $shippingCost;
            $preOrderErrors = apply_filters('checkout.before_order', [], $payload, $preOrderContext);
            if (is_array($preOrderErrors) && !empty($preOrderErrors)) {
                DB::rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => __('checkout_validation_error'), 'errors' => $preOrderErrors], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($hasShippingCostColumn) {
                $metaJson = json_encode($payload['custom_fields'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                DB::query(
                    'INSERT INTO orders (user_id, total, shipping_cost, meta, customer_name, customer_phone, customer_email, delivery_method, delivery_city, delivery_warehouse, delivery_address, payment_method, payment_id, delivery_id, comment, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                    [
                        isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null,
                        $total,
                        $shippingCost,
                        $metaJson,
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
            } else {
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
            }

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

        // Preserve plugin-owned fields for checkout.validate and orders.meta.
        // Core checkout keys remain normalized above and are excluded here.
        $coreKeys = ['csrf', 'full_name', 'phone', 'email', 'delivery_id', 'delivery_city', 'delivery_city_ref', 'delivery_warehouse', 'delivery_address', 'payment_id', 'comment', 'custom_fields'];
        $custom = is_array($input['custom_fields'] ?? null) ? $input['custom_fields'] : [];
        $custom += array_diff_key($input, array_fill_keys($coreKeys, true));
        $clean['custom_fields'] = $this->sanitizeCustomFields($custom);

        return $clean;
    }

    private function sanitizeCustomFields(array $fields, int $depth = 0): array
    {
        if ($depth > 4) {
            return [];
        }
        $clean = [];
        foreach (array_slice($fields, 0, 100, true) as $key => $value) {
            $key = substr((string) $key, 0, 100);
            if (is_array($value)) {
                $clean[$key] = $this->sanitizeCustomFields($value, $depth + 1);
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = trim(strip_tags((string) $value));
            }
        }
        return $clean;
    }

    /**
     * @param array<int, array> $deliveryMethods методи, ДОЗВОЛЕНІ фільтром
     *                                          checkout.delivery_methods
     * @param array<int, array> $paymentMethods  методи, дозволені checkout.payment_methods
     */
    private function validatePayload(array &$payload, array $deliveryMethods, array $paymentMethods): array
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

        $deliveryMethod = $this->findMethodById($deliveryMethods, (int) $payload['delivery_id']);
        if ($deliveryMethod === null) {
            // Метод або вимкнений адміністратором, або знятий правилом плагіна
            // (зона/вага/сума) — у будь-якому разі його не можна прийняти.
            $errors['delivery_id'] = __('checkout_specify_delivery');
        } else {
            $payload['delivery_method_code'] = (string) ($deliveryMethod['code'] ?? '');
            $payload['delivery_method_settings'] = $this->decodeSettings($deliveryMethod['settings'] ?? null);
        }

        $paymentMethod = $this->findMethodById($paymentMethods, (int) $payload['payment_id']);
        if ($paymentMethod === null) {
            $errors['payment_id'] = __('checkout_specify_payment');
        } else {
            $payload['payment_method_code'] = (string) ($paymentMethod['code'] ?? '');
        }

        if (($payload['delivery_method_code'] ?? '') === 'nova_poshta') {
            $npFields = (array) ($payload['custom_fields']['nova_poshta'] ?? []);
            if ($payload['delivery_city'] === '') {
                $errors['delivery_city'] = __('checkout_specify_city');
            }

            if ($payload['delivery_warehouse'] === '') {
                $errors['delivery_warehouse'] = __('checkout_specify_warehouse');
            }
            foreach (['city_ref', 'warehouse_ref'] as $reference) {
                if (!preg_match('/^[a-f0-9-]{36}$/i', (string) ($npFields[$reference] ?? ''))) {
                    $errors[$reference === 'city_ref' ? 'delivery_city' : 'delivery_warehouse'] = __('checkout_validation_error');
                }
            }
        }

        if (($payload['delivery_method_code'] ?? '') === 'courier' && $payload['delivery_address'] === '') {
            $errors['delivery_address'] = __('checkout_specify_address');
        }

        return $errors;
    }

    /**
     * Методи доставки/оплати, дозволені для ПОКАЗУ та ВИБОРУ в чекауті.
     *
     * Ядро самостійно фільтрує лише `is_active`. Додаткові правила (зона
     * доставки, вага/габарити, мінімальна/максимальна сума, ліміт післяплати,
     * доступність для гостей тощо) додають плагіни фільтром:
     *   checkout.delivery_methods  — (array $methods, array $context)
     *   checkout.payment_methods   — (array $methods, array $context)
     *
     * Той самий метод використовується і при показі сторінки, і при прийомі
     * замовлення — тому метод, прихований правилом, неможливо надіслати
     * в обхід UI (прямим POST /place-order).
     *
     * @param string $type    'shipping' | 'payment'
     * @param array  $context контекст кошика (buildCheckoutContext())
     * @return array<int, array>
     */
    private function resolveCheckoutMethods(string $type, array $context): array
    {
        $methods = Setting::getShopMethods($type);
        $methods = array_values(array_filter($methods, static function ($method): bool {
            return (int) ($method['is_active'] ?? 0) === 1;
        }));
        $methods = array_map([$this, 'normalizeShopMethod'], $methods);

        $hook = $type === 'payment' ? 'checkout.payment_methods' : 'checkout.delivery_methods';
        $filtered = apply_filters($hook, $methods, $context);

        if (!is_array($filtered)) {
            return $methods;
        }

        // Плагін міг повернути методи у зміненому порядку, зі своїми
        // налаштуваннями або навіть власний (віртуальний) метод. Беремо лише
        // записи з валідним числовим id і непорожнім code — саме вони
        // придатні для orders.delivery_id / orders.payment_id та для
        // подальшого PaymentManager::findForOrder($code).
        $allowed = [];
        foreach ($filtered as $method) {
            if (!is_array($method)) {
                continue;
            }
            $id = (int) ($method['id'] ?? 0);
            $code = trim((string) ($method['code'] ?? ''));
            if ($id <= 0 || $code === '') {
                continue;
            }
            $allowed[$id] = $this->normalizeShopMethod($method);
        }

        return array_values($allowed);
    }

    /**
     * Знайти метод у списку, дозволеному фільтрами (а не в БД напряму).
     */
    private function findMethodById(array $methods, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        foreach ($methods as $method) {
            if ((int) ($method['id'] ?? 0) === $id) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Контекст кошика, який отримують усі фільтри чекауту.
     *
     * Стабільний контракт — плагіни покладаються на ці ключі:
     *   items, subtotal, user, customer{name,phone,email,delivery_*}.
     * У фільтрі checkout.shipping_cost додатково є ключ delivery_method.
     *
     * @param array $items   позиції кошика (id, name, price, quantity, ...)
     * @param float $subtotal сума товарів без доставки
     * @param array $payload санітизовані дані форми (порожньо при рендері чекауту)
     */
    private function buildCheckoutContext(array $items, float $subtotal, array $payload = []): array
    {
        return [
            'items'           => $items,
            'subtotal'        => round($subtotal, 2),
            'user'            => $_SESSION['user'] ?? null,
            'customer'        => [
                'name'               => (string) ($payload['full_name'] ?? ''),
                'phone'              => (string) ($payload['phone'] ?? ''),
                'email'              => (string) ($payload['email'] ?? ''),
                'delivery_city'      => (string) ($payload['delivery_city'] ?? ''),
                'delivery_warehouse' => (string) ($payload['delivery_warehouse'] ?? ''),
                'delivery_address'   => (string) ($payload['delivery_address'] ?? ''),
            ],
            'delivery_method' => null,
            'payment_method'  => null,
            'custom_fields'   => $payload['custom_fields'] ?? [],
        ];
    }

    /**
     * Вартість доставки для замовлення.
     *
     * За замовчуванням — shop_methods.settings.cost вибраного методу.
     * Плагін може перерахувати її фільтром checkout.shipping_cost
     * (наприклад, живий тариф перевізника або безкоштовно від N грн).
     * Повертає невід'ємне число з точністю до цента.
     */
    private function resolveShippingCost(array $context): float
    {
        $method   = is_array($context['delivery_method'] ?? null) ? $context['delivery_method'] : [];
        $settings = is_array($method['settings'] ?? null) ? $method['settings'] : [];
        $cost     = (float) ($settings['cost'] ?? 0);

        $filtered = apply_filters('checkout.shipping_cost', $cost, $context);
        if (is_numeric($filtered)) {
            $cost = (float) $filtered;
        }

        return round(max(0.0, $cost), 2);
    }

    /**
     * Чи існує колонка orders.shipping_cost (додається update.sql з пакета
     * оновлення 1.1.5). Результат кешується на час запиту.
     */
    private function ordersHasShippingCostColumn(): bool
    {
        static $has = null;

        if ($has === null) {
            try {
                $has = (bool) DB::query(
                    "SELECT 1 FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'shipping_cost'
                     LIMIT 1"
                )->fetchColumn();
            } catch (\Throwable $e) {
                $has = false;
            }
        }

        return $has;
    }

    private function logCheckout(string $message): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        @file_put_contents(
            $logDir . '/checkout.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
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
