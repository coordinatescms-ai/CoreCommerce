<?php

namespace App\Services;

use App\Core\Database\DB;

/**
 * Передача змін статусів замовлень та ТТН назад у Prom.ua.
 *
 * Підключається через хук 'order.status_changed' який спрацьовує
 * в AdminOrderController при збереженні замовлення.
 *
 * Маппінг наших статусів → статуси Prom:
 *   new        → pending
 *   confirmed  → received
 *   processing → received
 *   shipped    → delivering
 *   delivered  → delivered
 *   completed  → paid
 *   cancelled  → canceled
 *   returned   → canceled
 */
class PromStatusService
{
    // Маппінг наших статусів → статуси Prom API
    private const STATUS_MAP = [
        'new'        => 'pending',
        'confirmed'  => 'received',
        'processing' => 'received',
        'shipped'    => 'delivering',
        'delivered'  => 'delivered',
        'completed'  => 'paid',
        'cancelled'  => 'canceled',
        'returned'   => 'canceled',
    ];

    private PromApiClient $api;
    private string        $logFile;

    public function __construct()
    {
        $this->api     = new PromApiClient();
        $this->logFile = dirname(__DIR__, 2) . '/storage/logs/prom.log';
    }

    // =========================================================================
    // Публічний API сервісу
    // =========================================================================

    /**
     * Обробити зміну статусу замовлення.
     * Викликається з хука 'order.status_changed'.
     *
     * @param int    $orderId   Наш internal order.id
     * @param string $newStatus Новий статус (наш формат)
     * @param string $ttnCode   ТТН Нової Пошти (якщо є)
     */
    public function onStatusChanged(int $orderId, string $newStatus, string $ttnCode = ''): void
    {
        if (!PromApiClient::isEnabled()) {
            return;
        }

        // Отримуємо prom_order_id для цього замовлення
        $order = DB::query(
            'SELECT prom_order_id, prom_source FROM orders WHERE id = ? LIMIT 1',
            [$orderId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$order || empty($order['prom_order_id'])) {
            // Замовлення не з Prom — нічого не надсилаємо
            return;
        }

        $promOrderId = (int)$order['prom_order_id'];

        // Оновлюємо статус
        $this->syncStatus($promOrderId, $newStatus, $orderId);

        // Якщо статус "shipped" і є ТТН — передаємо ТТН
        if ($newStatus === 'shipped' && $ttnCode !== '') {
            $this->syncTtn($promOrderId, $ttnCode, $orderId);
        }
    }

    /**
     * Вручну надіслати ТТН для замовлення (наприклад з форми редагування).
     *
     * @param int    $orderId  Наш internal order.id
     * @param string $ttnCode  Номер ТТН
     * @param string $provider Провайдер доставки
     */
    public function sendTtn(int $orderId, string $ttnCode, string $provider = 'nova_poshta'): array
    {
        if (!PromApiClient::isEnabled()) {
            return ['success' => false, 'message' => 'Інтеграція з Prom.ua вимкнена.'];
        }

        if ($ttnCode === '') {
            return ['success' => false, 'message' => 'ТТН не може бути порожнім.'];
        }

        $order = DB::query(
            'SELECT prom_order_id FROM orders WHERE id = ? LIMIT 1',
            [$orderId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$order || empty($order['prom_order_id'])) {
            return ['success' => false, 'message' => 'Замовлення не прив\'язане до Prom.'];
        }

        $promOrderId = (int)$order['prom_order_id'];
        $result      = $this->api->setDeliveryTtn($promOrderId, $ttnCode, $provider);

        if (isset($result['error']) || isset($result['errors'])) {
            $error = $result['error'] ?? json_encode($result['errors']);
            $this->log('TTN_FAIL', "order #{$orderId}, prom #{$promOrderId}: {$error}");
            return ['success' => false, 'message' => 'Помилка Prom API: ' . $error];
        }

        $this->log('TTN_OK', "order #{$orderId}, prom #{$promOrderId}, TTN: {$ttnCode}");
        return ['success' => true, 'message' => 'ТТН передано в Prom.ua.'];
    }

    // =========================================================================
    // Приватні методи
    // =========================================================================

    private function syncStatus(int $promOrderId, string $ourStatus, int $orderId): void
    {
        $promStatus = self::STATUS_MAP[$ourStatus] ?? null;

        if ($promStatus === null) {
            $this->log(
                'STATUS_SKIP',
                "order #{$orderId}: статус «{$ourStatus}» не має маппінгу в Prom"
            );
            return;
        }

        $result = $this->api->setOrderStatus([$promOrderId], $promStatus);

        if (isset($result['error']) || isset($result['errors'])) {
            $error = $result['error'] ?? json_encode($result['errors']);
            $this->log(
                'STATUS_FAIL',
                "order #{$orderId}, prom #{$promOrderId}: {$ourStatus}→{$promStatus} | {$error}"
            );
            return;
        }

        $this->log(
            'STATUS_OK',
            "order #{$orderId}, prom #{$promOrderId}: {$ourStatus} → {$promStatus}"
        );
    }

    private function syncTtn(int $promOrderId, string $ttnCode, int $orderId): void
    {
        $result = $this->api->setDeliveryTtn($promOrderId, $ttnCode);

        if (isset($result['error']) || isset($result['errors'])) {
            $error = $result['error'] ?? json_encode($result['errors']);
            $this->log('TTN_FAIL', "order #{$orderId}, prom #{$promOrderId}, TTN:{$ttnCode} | {$error}");
            return;
        }

        $this->log('TTN_OK', "order #{$orderId}, prom #{$promOrderId}, TTN: {$ttnCode}");
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
