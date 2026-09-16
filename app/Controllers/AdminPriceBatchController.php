<?php

namespace App\Controllers;

use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Models\PriceBatch;

class AdminPriceBatchController
{
    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
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

    private function readScopeFilters(array $source): array
    {
        return [
            'category_id' => isset($source['category_id']) && $source['category_id'] !== ''
                ? (int) $source['category_id'] : null,
            'include_subcategories' => !empty($source['include_subcategories']),
            'vendor' => trim((string) ($source['vendor'] ?? '')),
        ];
    }

    /**
     * GET /admin/products/price-batch/preview-count
     * Живий підрахунок кількості товарів під поточну вибірку — для модального вікна,
     * щоб менеджер бачив масштаб дії ще ДО застосування.
     */
    public function previewCount(): never
    {
        $this->checkAdmin();

        $filters = $this->readScopeFilters($_GET);
        $count = PriceBatch::countMatching($filters);

        $this->json(['success' => true, 'count' => $count]);
    }

    /**
     * POST /admin/products/price-batch/apply
     */
    public function apply(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => __('csrf_token_invalid')], 419);
        }

        $filters = $this->readScopeFilters($_POST);
        $mode = (string) ($_POST['mode'] ?? '');

        $params = [];
        if ($mode === PriceBatch::MODE_PERCENT || $mode === PriceBatch::MODE_FIXED) {
            $params['value'] = (float) str_replace(',', '.', (string) ($_POST['value'] ?? '0'));
            $params['direction'] = ($_POST['direction'] ?? 'increase') === 'decrease' ? 'decrease' : 'increase';

            if ($params['value'] <= 0) {
                $this->json(['success' => false, 'message' => __('price_batch_invalid_value')]);
            }
        } elseif ($mode === PriceBatch::MODE_CURRENCY) {
            $params['from_currency'] = strtoupper(trim((string) ($_POST['from_currency'] ?? '')));

            if ($params['from_currency'] === '') {
                $this->json(['success' => false, 'message' => __('price_batch_invalid_value')]);
            }
        } else {
            $this->json(['success' => false, 'message' => __('price_batch_invalid_mode')]);
        }

        // Заокруглення до цілого числа (без копійок/центів) — застосовується незалежно
        // від обраного режиму розрахунку, тому читаємо його окремо від гілок вище.
        $params['round_price'] = !empty($_POST['round_price']);

        $adminId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
        $result = PriceBatch::apply($filters, $mode, $params, $adminId);

        if (!$result['success']) {
            $this->json(['success' => false, 'message' => __((string) $result['message'])]);
        }

        $message = $result['skipped'] > 0
            ? sprintf(__('price_batch_applied_with_skipped'), $result['affected'], $result['skipped'])
            : sprintf(__('price_batch_applied'), $result['affected']);

        $this->json([
            'success' => true,
            'message' => $message,
            'affected' => $result['affected'],
            'skipped' => $result['skipped'],
            'batch_id' => $result['batch_id'],
        ]);
    }

    /**
     * GET /admin/products/price-batch/history
     */
    public function history(): void
    {
        $this->checkAdmin();

        View::render('admin/products/price_batch_history', [
            'batches' => PriceBatch::recent(30),
            'csrf' => $_SESSION['csrf'] ?? '',
        ], 'admin');
    }

    /**
     * POST /admin/products/price-batch/undo/{id}
     */
    public function undo($id): void
    {
        $this->checkAdmin();
        Csrf::abortIfInvalid();

        $result = PriceBatch::undo((int) $id);

        if ($result['success']) {
            $_SESSION['success'] = sprintf(__('price_batch_reverted'), $result['affected']);
        } else {
            $_SESSION['error'] = __((string) $result['message']);
        }

        header('Location: /admin/products/price-batch/history');
        exit;
    }

    /**
     * GET /admin/products/price-batch/form-options
     * Дані для селектів модального вікна (бренди + валюти) — окремий AJAX-запит,
     * щоб не роздувати основну сторінку товарів зайвими запитами при кожному завантаженні.
     */
    public function formOptions(): never
    {
        $this->checkAdmin();

        $this->json([
            'success' => true,
            'vendors' => PriceBatch::getVendors(),
            'currencies' => PriceBatch::getCurrencies(),
        ]);
    }
}
