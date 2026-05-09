<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Services\StockServiceFactory;

class StockController
{
    private function checkAdmin(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }
    }

    public function index(): void
    {
        $this->checkAdmin();

        $products = DB::query('SELECT p.id, p.name, p.sku, COALESCE(ps.quantity,0) as quantity, COALESCE(ps.reserved,0) as reserved FROM products p LEFT JOIN product_stocks ps ON ps.sku = p.sku ORDER BY p.id DESC')->fetchAll();
        $logs = DB::query('SELECT sku, event_type, qty, comment, created_at FROM inventory_log ORDER BY id DESC LIMIT 10')->fetchAll();

        View::render('admin/stocks/index', [
            'products' => $products,
            'logs' => $logs,
        ], 'admin');
    }

    public function adjust(): void
    {
        $this->checkAdmin();
        Csrf::abortIfInvalid();

        $sku = trim((string) ($_POST['sku'] ?? ''));
        $qty = (int) ($_POST['qty'] ?? 0);
        $type = (string) ($_POST['type'] ?? 'add');
        $comment = trim((string) ($_POST['comment'] ?? ''));

        $service = StockServiceFactory::make();
        $ok = $type === 'remove' ? $service->removeStock($sku, $qty, $comment) : $service->addStock($sku, $qty, $comment);

        $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Залишки оновлено.' : 'Не вдалося оновити залишки.';
        header('Location: /admin/stocks');
        exit;
    }

    public function syncApi(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $services = require __DIR__ . '/../../config/services.php';
        $source = strtolower((string) ($services['stock']['source'] ?? 'local'));

        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
            return;
        }

        $service = StockServiceFactory::make();
        $updated = $service->syncStock($payload);

        echo json_encode(['success' => true, 'source' => $source, 'updated' => $updated]);
    }
}
