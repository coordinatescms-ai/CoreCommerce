<?php
// Захоплюємо будь-який випадковий output (PHP warnings, notices) щоб не ламати JSON
ob_start();

session_start();
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database\DB;

// Тепер очищаємо буфер і встановлюємо JSON header
ob_clean();
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
$id     = (int)($input['id'] ?? 0);

// Дія 'create' не потребує існуючого ID
if ($action !== 'create' && $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Некоректний ID']);
    exit;
}

try {
    // ── Створення нового завдання ────────────────────────────────────
    if ($action === 'create') {
        $name     = trim((string)($input['name']     ?? ''));
        $schedule = trim((string)($input['schedule'] ?? ''));
        $command  = trim((string)($input['command']  ?? ''));
        $params   = trim((string)($input['params']   ?? ''));
        $status   = in_array($input['status'] ?? '', ['active', 'disabled'], true)
                        ? $input['status']
                        : 'active';

        if ($name === '' || $schedule === '' || $command === '') {
            echo json_encode(['success' => false, 'message' => "Заповніть обов'язкові поля: Назва, Розклад, Файл скрипту"]);
            exit;
        }

        // Валідація cron-рядка: 5 частин
        if (count(preg_split('/\s+/', trim($schedule))) !== 5) {
            echo json_encode(['success' => false, 'message' => 'Невірний формат Cron-рядка. Потрібно 5 частин: хв год день місяць день_тижня']);
            exit;
        }

        // Валідація JSON params якщо вказано
        if ($params !== '' && json_decode($params) === null) {
            echo json_encode(['success' => false, 'message' => 'Params повинні бути валідним JSON або порожніми']);
            exit;
        }

        // Розраховуємо next_run на основі cron-рядка
        // Формат: "хв год день місяць день_тижня"
        $nextRun = null;
        $parts = preg_split('/\s+/', trim($schedule));
        if (is_array($parts) && count($parts) === 5) {
            // Мінімальний розрахунок: додаємо 1 хвилину і перевіряємо
            // Для простоти - встановлюємо next_run = NOW() щоб запустилось при найближчому cron
            $nextRun = date('Y-m-d H:i:s');
        }

        DB::query(
            'INSERT INTO cron_tasks (name, command, schedule, params, status, last_result, next_run)
             VALUES (:name, :command, :schedule, :params, :status, :result, :next_run)',
            [
                ':name'     => $name,
                ':command'  => $command,
                ':schedule' => $schedule,
                ':params'   => $params ?: null,
                ':status'   => $status,
                ':result'   => 'success',
                ':next_run' => $nextRun,
            ]
        );

        $newId = DB::lastInsertId();
        $task  = DB::query('SELECT * FROM cron_tasks WHERE id = :id', [':id' => $newId])
                     ->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'task' => $task]);
        exit;
    }

    // ── Видалення завдання ───────────────────────────────────────────
    if ($action === 'delete') {
        $task = DB::query('SELECT id FROM cron_tasks WHERE id = :id', [':id' => $id])->fetch(PDO::FETCH_ASSOC);
        if (!$task) { throw new RuntimeException('Завдання не знайдене'); }
        DB::query('DELETE FROM cron_tasks WHERE id = :id', [':id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update') {
        DB::query('UPDATE cron_tasks SET name = :name, command = :command, schedule = :schedule, params = :params WHERE id = :id', [
            ':name' => trim((string)($input['name'] ?? '')),
            ':command' => trim((string)($input['command'] ?? '')),
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
            // Дозволяємо task скриптам знати що їх запустили через веб-адмінку
            if (!defined('CRON_RUNNER')) {
                define('CRON_RUNNER', true);
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
