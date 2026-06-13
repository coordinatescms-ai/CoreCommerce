<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Models\Setting;
use App\Services\PromApiClient;

/**
 * Обробник вебхуків від Prom.ua.
 *
 * Prom надсилає POST-запит з JSON на URL:
 *   POST /prom/webhook
 *
 * Структура payload від Prom:
 * {
 *   "order": {
 *     "id": 123456789,
 *     "status": "pending",
 *     "price": "1500.00",
 *     "full_price": "1500.00",
 *     "client": { "name": "...", "phones": ["380..."], "email": "..." },
 *     "delivery": { "delivery_method_name": "...", "city": "...", "postcode": "..." },
 *     "payment": { "type": "cod" },
 *     "comment": "...",
 *     "products": [
 *       { "id": 987, "external_id": "15", "name": "...", "quantity": 2, "price": "750.00" }
 *     ]
 *   }
 * }
 *
 * `external_id` — це наш internal product ID який ми передаємо при синхронізації товарів.
 */
class PromWebhookController
{
    // Маппінг статусів Prom → наші статуси
    private const STATUS_MAP = [
        'pending'    => 'new',
        'received'   => 'confirmed',
        'delivering' => 'shipped',
        'delivered'  => 'delivered',
        'canceled'   => 'cancelled',
        'paid'       => 'completed',
        'draft'      => 'new',
    ];

    private string $logFile;

    public function __construct()
    {
        $this->logFile = dirname(__DIR__, 2) . '/storage/logs/prom.log';
    }

    /**
     * GET /prom/feed.xml
     * Віддає згенерований XML-фід для Prom (Підхід А).
     */
    public function feed(): never
    {
        $feedPath = dirname(__DIR__, 2) . '/storage/prom_feed.xml';

        if (!is_file($feedPath)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Feed not found. Generate it from admin panel first.';
            exit;
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Length: ' . filesize($feedPath));
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($feedPath)) . ' GMT');
        readfile($feedPath);
        exit;
    }

    /**
     * POST /prom/webhook
     */
    public function handle(): never
    {
        // Тільки POST
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->respond(405, ['error' => 'Method Not Allowed']);
        }

        // Якщо інтеграція вимкнена — мовчки повертаємо 200 (щоб Prom не повторював)
        if (!\App\Services\PromApiClient::isEnabled()) {
            $this->respond(200, ['status' => 'disabled']);
        }

        $rawBody = file_get_contents('php://input') ?: '';

        // Верифікація підпису (якщо secret налаштований)
        $secret = trim((string) Setting::get('prom_webhook_secret', ''));
        if ($secret !== '') {
            $signature = $_SERVER['HTTP_X_PROM_SIGNATURE'] ?? '';
            if (!$this->verifySignature($rawBody, $signature, $secret)) {
                $this->log('WEBHOOK_AUTH', 'Невірний підпис вебхука');
                $this->respond(401, ['error' => 'Invalid signature']);
            }
        }

        $payload = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            $this->log('WEBHOOK_ERROR', 'Некоректний JSON: ' . $rawBody);
            $this->respond(400, ['error' => 'Invalid JSON']);
        }

        // Prom чекає відповідь 200 якомога швидше —
        // відповідаємо одразу, потім обробляємо
        $this->respond(200, ['status' => 'ok'], false);

        // Обробка замовлення
        try {
            $this->processOrder($payload);
        } catch (\Throwable $e) {
            $this->log('WEBHOOK_EXCEPTION', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }

        exit;
    }

    // =========================================================================
    // Обробка замовлення
    // =========================================================================

    private function processOrder(array $payload): void
    {
        $orderData = $payload['order'] ?? null;

        if (!is_array($orderData)) {
            $this->log('WEBHOOK_SKIP', 'Payload не містить ключа order');
            return;
        }

        $promOrderId = (int)($orderData['id'] ?? 0);

        if ($promOrderId === 0) {
            $this->log('WEBHOOK_SKIP', 'Відсутній order.id');
            return;
        }

        // Перевірка на дублікат — чи вже є це замовлення в БД
        $existing = DB::query(
            'SELECT id FROM orders WHERE prom_order_id = ? LIMIT 1',
            [$promOrderId]
        )->fetch(\PDO::FETCH_ASSOC);

        if ($existing) {
            $this->log('WEBHOOK_SKIP', "Замовлення Prom #{$promOrderId} вже існує (order #{$existing['id']})");
            return;
        }

        // Парсимо дані клієнта
        $client   = $orderData['client']   ?? [];
        $delivery = $orderData['delivery'] ?? [];
        $payment  = $orderData['payment']  ?? [];

        $customerName  = trim((string)($client['name']  ?? ''));
        $customerPhone = $this->normalizePhone((string)(($client['phones'] ?? [])[0] ?? ''));
        $customerEmail = trim((string)($client['email'] ?? ''));

        $deliveryMethod    = trim((string)($delivery['delivery_method_name'] ?? 'nova_poshta'));
        $deliveryCity      = trim((string)($delivery['city']                 ?? ''));
        $deliveryWarehouse = trim((string)($delivery['postcode']             ?? ''));
        $deliveryAddress   = trim((string)($delivery['address']              ?? ''));

        $paymentMethod = trim((string)($payment['type'] ?? 'cod'));
        $comment       = trim((string)($orderData['comment'] ?? ''));

        $promStatus  = (string)($orderData['status'] ?? 'pending');
        $ourStatus   = self::STATUS_MAP[$promStatus] ?? 'new';
        $totalPrice  = (float)($orderData['full_price'] ?? $orderData['price'] ?? 0);

        // Парсимо товари
        $products = $orderData['products'] ?? [];
        $items    = $this->resolveOrderItems($products);

        if (empty($items)) {
            $this->log('WEBHOOK_WARN', "Prom #{$promOrderId}: жодного товару не розпізнано");
        }

        // Зберігаємо в транзакції
        DB::beginTransaction();

        try {
            DB::query(
                'INSERT INTO orders
                    (prom_order_id, prom_source, total, customer_name, customer_phone,
                     customer_email, delivery_method, delivery_city, delivery_warehouse,
                     delivery_address, payment_method, comment, status, created_at)
                 VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $promOrderId,
                    round($totalPrice, 2),
                    $customerName,
                    $customerPhone,
                    $customerEmail,
                    $deliveryMethod,
                    $deliveryCity,
                    $deliveryWarehouse,
                    $deliveryAddress,
                    $paymentMethod,
                    $comment,
                    $ourStatus,
                ]
            );

            $orderId = DB::lastInsertId();

            foreach ($items as $item) {
                DB::query(
                    'INSERT INTO order_items (order_id, product_id, qty, price, selected_options)
                     VALUES (?, ?, ?, ?, NULL)',
                    [$orderId, $item['product_id'], $item['qty'], $item['price']]
                );
            }

            DB::commit();

            $this->log(
                'WEBHOOK_OK',
                "Замовлення Prom #{$promOrderId} → order #{$orderId}, товарів: " . count($items)
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // Допоміжні методи
    // =========================================================================

    /**
     * Зіставити товари Prom з нашими товарами.
     *
     * Prom передає external_id (наш product.id) та prom product id.
     * Спочатку шукаємо за external_id, потім за prom_product_id.
     */
    private function resolveOrderItems(array $promProducts): array
    {
        $items = [];

        foreach ($promProducts as $pp) {
            $externalId    = (int)($pp['external_id'] ?? 0);
            $promProductId = (int)($pp['id']          ?? 0);
            $qty           = max(1, (int)($pp['quantity'] ?? 1));
            $price         = (float)($pp['price']     ?? 0);

            $product = null;

            // Спроба 1: за нашим ID (external_id = product.id)
            if ($externalId > 0) {
                $product = DB::query(
                    'SELECT id, price FROM products WHERE id = ? LIMIT 1',
                    [$externalId]
                )->fetch(\PDO::FETCH_ASSOC);
            }

            // Спроба 2: за prom_product_id
            if (!$product && $promProductId > 0) {
                $product = DB::query(
                    'SELECT id, price FROM products WHERE prom_product_id = ? LIMIT 1',
                    [$promProductId]
                )->fetch(\PDO::FETCH_ASSOC);
            }

            if (!$product) {
                $this->log(
                    'WEBHOOK_WARN',
                    "Товар не знайдено: external_id={$externalId}, prom_product_id={$promProductId}, name=" . ($pp['name'] ?? '?')
                );
                continue;
            }

            $items[] = [
                'product_id' => (int)$product['id'],
                'qty'        => $qty,
                'price'      => $price > 0 ? round($price, 2) : round((float)$product['price'], 2),
            ];
        }

        return $items;
    }

    /**
     * Верифікація підпису вебхука.
     * Prom підписує тіло запиту через HMAC-SHA256 з секретним ключем.
     */
    private function verifySignature(string $body, string $signature, string $secret): bool
    {
        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Привести телефон до формату +380XXXXXXXXX.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '+38' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '38')) {
            return '+' . $digits;
        }

        return $phone !== '' ? '+' . $digits : '';
    }

    /**
     * Відправити JSON-відповідь.
     *
     * @param bool $exit  false — не зупиняти скрипт (відповідаємо Prom і продовжуємо обробку)
     */
    private function respond(int $code, array $data, bool $exit = true): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        if ($exit) {
            exit;
        }

        // Флашимо буфер щоб Prom отримав 200 одразу
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function log(string $level, string $message): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $level, $message);
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
