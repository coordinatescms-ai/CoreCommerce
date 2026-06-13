<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\Http\Csrf;
use App\Services\PromApiClient;
use App\Services\PromSyncService;

class AdminPromController
{
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

    /**
     * POST /admin/prom/save
     * Зберегти налаштування Prom.ua з вкладки Інтеграції.
     */
    public function save(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний.'], 419);
        }

        $fields = [
            'prom_enabled'        => (string)(int)!empty($_POST['prom_enabled']),
            'prom_api_key'        => trim((string)($_POST['prom_api_key']        ?? '')),
            'prom_sync_method'    => in_array($_POST['prom_sync_method'] ?? '', ['xml', 'api'], true)
                                        ? $_POST['prom_sync_method']
                                        : 'xml',
            'prom_webhook_secret' => trim((string)($_POST['prom_webhook_secret'] ?? '')),
        ];

        foreach ($fields as $key => $value) {
            DB::query(
                "INSERT INTO settings (`key`, `value`, `group`, `type`, updated_at)
                 VALUES (?, ?, 'prom', 'text', NOW())
                 ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()",
                [$key, $value, $value]
            );
        }

        $this->json(['success' => true, 'message' => 'Налаштування Prom.ua збережено.']);
    }

    /**
     * POST /admin/prom/test
     * Перевірити з'єднання з Prom API.
     */
    public function test(): never
    {
        $this->checkAdmin();

        // Якщо передали ключ прямо з форми — тестуємо його, не чекаючи збереження
        $apiKey = trim((string)($_POST['prom_api_key'] ?? ''));
        $client = new PromApiClient($apiKey ?: null);
        $result = $client->testConnection();

        $this->json($result);
    }

    /**
     * POST /admin/prom/generate-feed
     * Підхід А: згенерувати XML-фід.
     */
    public function generateFeed(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний.'], 419);
        }

        if (!PromApiClient::isEnabled()) {
            $this->json(['success' => false, 'message' => 'Інтеграцію з Prom.ua вимкнено.']);
        }

        $result = (new PromSyncService())->generateXmlFeed();
        $this->json($result);
    }

    /**
     * POST /admin/prom/enqueue
     * Підхід Б: поставити всі товари в чергу на оновлення через API.
     */
    public function enqueue(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний.'], 419);
        }

        if (!PromApiClient::isEnabled()) {
            $this->json(['success' => false, 'message' => 'Інтеграцію з Prom.ua вимкнено.']);
        }

        $action = in_array($_POST['action'] ?? '', ['price', 'quantity', 'both'], true)
            ? $_POST['action']
            : 'both';

        $count = (new PromSyncService())->enqueueProducts([], $action);

        $this->json([
            'success' => true,
            'message' => "Додано в чергу: {$count} товарів.",
            'count'   => $count,
        ]);
    }

    /**
     * POST /admin/prom/process-queue
     * Підхід Б: обробити порцію черги (50 товарів).
     */
    public function processQueue(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний.'], 419);
        }

        if (!PromApiClient::isEnabled()) {
            $this->json(['success' => false, 'message' => 'Інтеграцію з Prom.ua вимкнено.']);
        }

        $stats = (new PromSyncService())->processQueue();

        $this->json([
            'success' => true,
            'message' => "Оброблено: {$stats['processed']}, успішно: {$stats['success']}, "
                       . "помилок: {$stats['failed']}, залишок у черзі: {$stats['remaining']}.",
            'stats'   => $stats,
        ]);
    }

    /**
     * POST /admin/prom/clear-queue
     * Очистити виконані або провалені записи черги.
     */
    public function clearQueue(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний.'], 419);
        }

        $status = in_array($_POST['status'] ?? '', ['done', 'failed', 'all'], true)
            ? $_POST['status']
            : 'done';

        $count = (new PromSyncService())->clearQueue($status);

        $this->json([
            'success' => true,
            'message' => "Видалено з черги: {$count} записів.",
            'count'   => $count,
        ]);
    }
}
