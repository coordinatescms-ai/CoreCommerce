<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\Payment\PaymentManager;

/**
 * Універсальний обробник вебхуків від платіжних систем.
 *
 * Маршрут: POST /payment/webhook/{gateway_name}
 *
 * Платіжна система надсилає POST на:
 *   https://site.com/payment/webhook/liqpay
 *   https://site.com/payment/webhook/wayforpay
 *   https://site.com/payment/webhook/monobank
 *
 * Ядро знаходить потрібний шлюз за {gateway_name},
 * передає йому дані і оновлює статус замовлення.
 */
class PaymentWebhookController
{
    private string $logFile;

    public function __construct()
    {
        $this->logFile = dirname(__DIR__, 2) . '/storage/logs/payment.log';
    }

    /**
     * POST /payment/webhook/{gateway_name}
     */
    public function handle(string $gatewayName): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->respond(405, 'Method Not Allowed');
        }

        $gatewayName = preg_replace('/[^a-z0-9_\-]/', '', strtolower($gatewayName));

        if ($gatewayName === '') {
            $this->respond(400, 'Invalid gateway name');
        }

        $gateway = PaymentManager::get($gatewayName);

        if ($gateway === null) {
            $this->log('WEBHOOK_UNKNOWN', "Шлюз «{$gatewayName}» не зареєстровано");
            // Відповідаємо 200 щоб платіжна система не повторювала запит
            $this->respond(200, 'Gateway not found');
        }

        $rawBody  = file_get_contents('php://input') ?: '';
        $postData = $_POST;

        // Якщо прийшов JSON — розбираємо його
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $postData = $decoded;
            }
        }

        $headers = $this->extractHeaders();

        try {
            $result = $gateway->handleWebhook($postData, $rawBody, $headers);

            // Логуємо результат
            $this->log(
                $result->success ? 'WEBHOOK_OK' : 'WEBHOOK_FAIL',
                sprintf(
                    "[%s] order_id=%d status=%s message=%s",
                    strtoupper($gatewayName),
                    $result->orderId,
                    $result->status,
                    $result->message
                )
            );

            // Оновлюємо статус замовлення якщо є order_id
            if ($result->orderId > 0 && $result->status !== '') {
                $this->updateOrderStatus($result->orderId, $result->status);
            }

            // Відповідаємо платіжній системі тим що повернув плагін
            http_response_code($result->httpCode);
            echo $result->responseBody;
            exit;

        } catch (\Throwable $e) {
            $this->log('WEBHOOK_EXCEPTION', "[{$gatewayName}] " . $e->getMessage());
            $this->respond(500, 'Internal error');
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function updateOrderStatus(int $orderId, string $status): void
    {
        $allowed = ['new','confirmed','processing','shipped','delivered','completed','cancelled','returned','pending'];

        if (!in_array($status, $allowed, true)) {
            $this->log('WEBHOOK_WARN', "Невідомий статус: {$status} для order #{$orderId}");
            return;
        }

        $order = DB::query(
            'SELECT id, status FROM orders WHERE id = ? LIMIT 1',
            [$orderId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            $this->log('WEBHOOK_WARN', "Замовлення #{$orderId} не знайдено");
            return;
        }

        DB::query(
            'UPDATE orders SET status = ? WHERE id = ?',
            [$status, $orderId]
        );

        // Хук для інших плагінів (наприклад Prom синхронізація)
        do_action('order.status_changed', $orderId, $status, '');

        $this->log('ORDER_UPDATE', "order #{$orderId}: {$order['status']} → {$status}");
    }

    private function extractHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name           = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    private function respond(int $code, string $message): never
    {
        http_response_code($code);
        echo $message;
        exit;
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
