<?php

namespace App\Controllers;

use App\Core\Database\MigrationRunner;
use App\Core\Http\Csrf;
use App\Core\View\View;

class AdminMigrationController
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
     * GET /admin/migrations
     */
    public function index(): void
    {
        $this->checkAdmin();

        $runner   = new MigrationRunner();
        $statuses = $runner->status();

        $pending  = array_filter($statuses, fn($m) => $m['status'] === 'pending');
        $applied  = array_filter($statuses, fn($m) => $m['status'] === 'applied');
        $missing  = array_filter($statuses, fn($m) => $m['status'] === 'missing_file');

        View::render('admin/migrations/index', [
            'statuses' => $statuses,
            'pending'  => count($pending),
            'applied'  => count($applied),
            'missing'  => count($missing),
            'csrf'     => Csrf::token(),
        ], 'admin');
    }

    /**
     * POST /admin/migrations/run
     */
    public function run(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний'], 419);
        }

        $result = (new MigrationRunner())->run();

        $this->json([
            'success' => empty($result['failed']),
            'run'     => $result['run'],
            'skipped' => $result['skipped'],
            'failed'  => $result['failed'],
            'message' => empty($result['failed'])
                ? 'Виконано міграцій: ' . count($result['run'])
                : 'Є помилки: ' . implode(', ', array_keys($result['failed'])),
        ]);
    }

    /**
     * POST /admin/migrations/reset
     */
    public function reset(): never
    {
        $this->checkAdmin();

        if (!Csrf::isValid()) {
            $this->json(['success' => false, 'message' => 'CSRF токен недійсний'], 419);
        }

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $this->json(['success' => false, 'message' => 'Назву міграції не вказано'], 422);
        }

        (new MigrationRunner())->reset($name);
        $this->json(['success' => true, 'message' => "Міграцію «{$name}» скинуто"]);
    }
}
