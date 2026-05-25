<?php
require __DIR__ . '/vendor/autoload.php';

use App\Core\Database\DB;

$config = require __DIR__ . '/config/database.php';
DB::connect($config['dsn'], $config['user'], $config['pass']);

function cronFieldMatches(string $field, int $value, int $min, int $max): bool {
    if ($field === '*') return true;
    foreach (explode(',', $field) as $part) {
        if (preg_match('/^\*\/(\d+)$/', $part, $m)) {
            $step = (int)$m[1];
            if ($step > 0 && $value % $step === 0) return true;
        } elseif (preg_match('/^(\d+)-(\d+)(?:\/(\d+))?$/', $part, $m)) {
            $start = (int)$m[1]; $end = (int)$m[2]; $step = isset($m[3]) ? (int)$m[3] : 1;
            if ($value >= $start && $value <= $end && (($value - $start) % max(1, $step) === 0)) return true;
        } elseif (ctype_digit($part) && (int)$part === $value) {
            return true;
        }
    }
    return false;
}

function nextRunFromCron(string $cron, DateTime $from): ?string {
    $parts = preg_split('/\s+/', trim($cron));
    if (!is_array($parts) || count($parts) !== 5) return null;
    [$min, $hour, $day, $month, $week] = $parts;

    $dt = clone $from;
    $dt->modify('+1 minute');
    for ($i = 0; $i < 525600; $i++) {
        if (
            cronFieldMatches($min, (int)$dt->format('i'), 0, 59) &&
            cronFieldMatches($hour, (int)$dt->format('G'), 0, 23) &&
            cronFieldMatches($day, (int)$dt->format('j'), 1, 31) &&
            cronFieldMatches($month, (int)$dt->format('n'), 1, 12) &&
            cronFieldMatches($week, (int)$dt->format('w'), 0, 6)
        ) {
            return $dt->format('Y-m-d H:i:s');
        }
        $dt->modify('+1 minute');
    }

    return null;
}

$tasks = DB::query("SELECT * FROM cron_tasks WHERE status='active' AND next_run IS NOT NULL AND next_run <= NOW() ORDER BY next_run ASC")->fetchAll(PDO::FETCH_ASSOC);
$root = __DIR__;

foreach ($tasks as $task) {
    $id = (int)$task['id'];
    DB::query("UPDATE cron_tasks SET last_result='running', error_message=NULL WHERE id=:id", [':id' => $id]);
    $result = 'success';
    $error = null;

    try {
        $file = $root . '/' . ltrim((string)$task['command'], '/');
        if (!is_file($file)) {
            throw new RuntimeException('Команда не знайдена: ' . $task['command']);
        }
        require $file;
    } catch (Throwable $e) {
        $result = 'failed';
        $error = $e->getMessage();
    }

    $now = new DateTime('now');
    $nextRun = nextRunFromCron((string)$task['schedule'], $now);

    DB::query('UPDATE cron_tasks SET last_run = :last_run, next_run = :next_run, last_result = :result, error_message = :error WHERE id = :id', [
        ':last_run' => $now->format('Y-m-d H:i:s'),
        ':next_run' => $nextRun,
        ':result' => $result,
        ':error' => $error,
        ':id' => $id,
    ]);
}
