<?php

use App\Core\Plugin\PluginInterface;
use App\Core\Plugin\PluginManager;
use App\Core\Payment\PaymentManager;
use App\Core\Payment\PaymentGatewayInterface;
use App\Core\Payment\PaymentResult;
use App\Core\Payment\WebhookResult;
use App\Core\Database\DB;

/**
 * Платіжний плагін LiqPay.
 *
 * Ключі читаються з shop_methods.settings де code = 'liqpay'
 * (зворотна сумісність з тими хто вже зберіг ключі через старий UI).
 *
 * Webhook URL для кабінету LiqPay:
 *   https://your-site.com/payment/webhook/liqpay
 */
return new class implements PluginInterface {

    public function getName(): string    { return 'LiqPayGateway'; }
    public function getVersion(): string { return '1.0.0'; }

    public function register(PluginManager $pluginManager): void
    {
        // Реєструємо шлюз у PaymentManager ядра
        PaymentManager::register(new class implements PaymentGatewayInterface {

            public function getName(): string  { return 'liqpay'; }
            public function getLabel(): string { return 'LiqPay — оплата карткою'; }

            // ── Ініціація платежу ────────────────────────────────────────────

            public function initiate(int $orderId, float $amount, array $meta = []): PaymentResult
            {
                [$publicKey, $privateKey, $isTest] = $this->loadKeys();

                if ($publicKey === '' || $privateKey === '') {
                    return PaymentResult::error(
                        'LiqPay не налаштовано: відсутні Public Key або Private Key.'
                    );
                }

                $params = [
                    'public_key'  => $publicKey,
                    'version'     => '3',
                    'action'      => 'pay',
                    'amount'      => number_format($amount, 2, '.', ''),
                    'currency'    => 'UAH',
                    'description' => $meta['description'] ?? 'Замовлення #' . $orderId,
                    'order_id'    => (string)$orderId,
                    'result_url'  => $this->siteUrl() . '/thank-you?order_id=' . $orderId,
                    'server_url'  => $this->siteUrl() . '/payment/webhook/liqpay',
                    'sandbox'     => $isTest ? '1' : '0',
                ];

                $data      = base64_encode(json_encode($params));
                $signature = base64_encode(sha1($privateKey . $data . $privateKey, true));

                // Повертаємо HTML-форму з автосабмітом
                $html = sprintf(
                    '<form method="POST" action="https://www.liqpay.ua/api/3/checkout" id="liqpay-form" accept-charset="utf-8">
                        <input type="hidden" name="data"      value="%s">
                        <input type="hidden" name="signature" value="%s">
                        <p style="text-align:center; padding:1rem; color:#64748b;">
                            Перенаправляємо на сторінку оплати LiqPay…
                        </p>
                    </form>
                    <script>document.getElementById("liqpay-form").submit();</script>',
                    htmlspecialchars($data),
                    htmlspecialchars($signature)
                );

                return PaymentResult::render($html, ['order_id' => $orderId]);
            }

            // ── Вебхук від LiqPay ────────────────────────────────────────────

            public function handleWebhook(array $postData, string $rawBody, array $headers): WebhookResult
            {
                [, $privateKey] = $this->loadKeys();

                $data      = $postData['data']      ?? '';
                $signature = $postData['signature'] ?? '';

                if ($data === '' || $signature === '') {
                    return WebhookResult::invalid('Missing data or signature');
                }

                // Верифікація підпису LiqPay
                $expectedSignature = base64_encode(sha1($privateKey . $data . $privateKey, true));
                if (!hash_equals($expectedSignature, $signature)) {
                    return WebhookResult::invalid('Invalid LiqPay signature');
                }

                $decoded = json_decode(base64_decode($data), true);
                if (!is_array($decoded)) {
                    return WebhookResult::invalid('Cannot decode LiqPay data');
                }

                $status  = (string)($decoded['status']   ?? '');
                $orderId = (int)($decoded['order_id']     ?? 0);
                $amount  = (float)($decoded['amount']     ?? 0);

                $this->log(sprintf(
                    'Webhook: order_id=%d status=%s amount=%.2f',
                    $orderId, $status, $amount
                ));

                // Успішні статуси LiqPay
                if (in_array($status, ['success', 'sandbox'], true)) {
                    return WebhookResult::paid(
                        $orderId,
                        "LiqPay: оплата підтверджена (status={$status})"
                    );
                }

                // Невдалі статуси
                if (in_array($status, ['failure', 'error', 'reversed'], true)) {
                    return WebhookResult::failed(
                        $orderId,
                        "LiqPay: оплата відхилена (status={$status})"
                    );
                }

                // Проміжні статуси (wait_*, processing) — нічого не робимо
                return WebhookResult::failed(
                    $orderId,
                    "LiqPay: проміжний статус {$status}, очікуємо",
                    'OK',
                    200
                );
            }

            public function getSettingsSchema(): array { return []; }

            // ── Private helpers ───────────────────────────────────────────────

            private function loadKeys(): array
            {
                // Читаємо з shop_methods (зворотна сумісність зі старим UI)
                $row = DB::query(
                    "SELECT settings, is_test_mode FROM shop_methods
                     WHERE code = 'liqpay' AND type = 'payment' LIMIT 1"
                )->fetch(\PDO::FETCH_ASSOC);

                if (!$row) {
                    return ['', '', false];
                }

                $settings   = json_decode((string)($row['settings'] ?? '{}'), true) ?: [];
                $publicKey  = trim((string)($settings['public_key']  ?? ''));
                $privateKey = trim((string)($settings['private_key'] ?? ''));
                $isTest     = (bool)($row['is_test_mode'] ?? false);

                return [$publicKey, $privateKey, $isTest];
            }

            private function siteUrl(): string
            {
                return rtrim((string)\App\Models\Setting::get('site_url', ''), '/');
            }

            private function log(string $message): void
            {
                $logFile = dirname(__DIR__, 2) . '/storage/logs/payment.log';
                $dir     = dirname($logFile);
                if (!is_dir($dir)) { mkdir($dir, 0755, true); }
                file_put_contents(
                    $logFile,
                    sprintf("[%s] [LIQPAY] %s\n", date('Y-m-d H:i:s'), $message),
                    FILE_APPEND | LOCK_EX
                );
            }
        });
    }

    public function getSettingsSchema(): array
    {
        return []; // Ключі налаштовуються через вкладку Оплата в адмінці
    }
};
