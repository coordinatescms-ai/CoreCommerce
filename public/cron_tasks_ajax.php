<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database\DB;

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config/database.php';
DB::connect($config['dsn'], $config['user'], $config['pass']);

if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Недостатньо прав']);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (($input['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$action = (string)($input['action'] ?? '');
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Некоректний ID']);
    exit;
}

try {
    if ($action === 'update') {
        DB::query('UPDATE cron_tasks SET name = :name, schedule = :schedule, params = :params WHERE id = :id', [
            ':name' => trim((string)($input['name'] ?? '')),
            ':schedule' => trim((string)($input['schedule'] ?? '')),
            ':params' => (string)($input['params'] ?? ''),
            ':id' => $id,
        ]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'toggle') {
        $task = DB::query('SELECT status FROM cron_tasks WHERE id = :id', [':id' => $id])->fetch(PDO::FETCH_ASSOC);
        if (!$task) { throw new RuntimeException('Завдання не знайдене'); }
        $status = $task['status'] === 'active' ? 'disabled' : 'active';
        DB::query('UPDATE cron_tasks SET status = :status WHERE id = :id', [':status' => $status, ':id' => $id]);
        echo json_encode(['success' => true, 'task' => ['status' => $status]]);
        exit;
    }

    if ($action === 'run_now') {
        $task = DB::query('SELECT * FROM cron_tasks WHERE id = :id', [':id' => $id])->fetch(PDO::FETCH_ASSOC);
        if (!$task) { throw new RuntimeException('Завдання не знайдене'); }
        DB::query("UPDATE cron_tasks SET last_result='running', error_message=NULL WHERE id = :id", [':id' => $id]);

        $root = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        $commandPath = $root . '/' . ltrim((string)$task['command'], '/');
        $result = 'success';
        $error = null;

        try {
            if (!is_file($commandPath)) {
                throw new RuntimeException('Скрипт не знайдений: ' . $task['command']);
            }
            require $commandPath;
        } catch (Throwable $e) {
            $result = 'failed';
            $error = $e->getMessage();
        }

        DB::query("UPDATE cron_tasks SET last_run = NOW(), last_result = :result, error_message = :err WHERE id = :id", [
            ':result' => $result,
            ':err' => $error,
            ':id' => $id,
        ]);
        echo json_encode(['success' => true, 'task' => ['last_result' => $result, 'error_message' => $error]]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Невідома дія']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
