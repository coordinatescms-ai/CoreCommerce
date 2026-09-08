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
            $this->json(['success' => false, 'message' => __('csrf_token_invalid')], 419);
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

        $this->json(['success' => true, 'message' => __('admin_prom_settings_saved')]);
    }

    /**
     * POST /admin/prom/test
     * Перевірити з'єднання з Prom API.
     */
    public function test(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => __('csrf_token_invalid')], 419);
        }

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
            $this->json(['success' => false, 'message' => __('csrf_token_invalid')], 419);
        }

        if (!PromApiClient::isEnabled()) {
            $this->json(['success' => false, 'message' => __('admin_prom_integration_disabled')]);
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
            $this->json(['success' => false, 'message' => __('csrf_token_invalid')], 419);
        }

        if (!PromApiClient::isEnabled()) {
            $this->json(['success' => false, 'message' => __('admin_prom_integration_disabled')]);
        }

        $action = in_array($_POST['action'] ?? '', ['price', 'quantity', 'both'], true)
            ? $_POST['action']
            : 'both';

        $count = (new PromSyncService())->enqueueProducts([], $action);

        $this->json([
            'success' => true,
            'message' => sprintf(__('admin_prom_enqueued'), $count),
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
            $this->json(['success' => false, 'message' => __('csrf_token_invalid')], 419);
        }

        if (!PromApiClient::isEnabled()) {
            $this->json(['success' => false, 'message' => __('admin_prom_integration_disabled')]);
        }

        $stats = (new PromSyncService())->processQueue();

        $this->json([
            'success' => true,
            'message' => sprintf(
                __('admin_prom_queue_processed'),
                $stats['processed'],
                $stats['success'],
                $stats['failed'],
                $stats['remaining']
            ),
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
            $this->json(['success' => false, 'message' => __('csrf_token_invalid')], 419);
        }

        $status = in_array($_POST['status'] ?? '', ['done', 'failed', 'all'], true)
            ? $_POST['status']
            : 'done';

        $count = (new PromSyncService())->clearQueue($status);

        $this->json([
            'success' => true,
            'message' => sprintf(__('admin_prom_queue_cleared'), $count),
            'count'   => $count,
        ]);
    }
}
