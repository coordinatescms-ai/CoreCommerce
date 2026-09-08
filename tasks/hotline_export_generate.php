<?php
/**
 * Cron-скрипт примусової перегенерації фіда Hotline.ua.
 *
 * Запуск вручну:
 *   php tasks/hotline_export_generate.php
 *
 * Приклад запису в cron (щогодини):
 *   0 * * * * php /var/www/mysite/tasks/hotline_export_generate.php >> /var/log/hotline_export.log 2>&1
 *
 * Навіщо окремий cron-скрипт, якщо feed.php і так перегенеровує кеш сам,
 * коли той застарів (за cache_minutes)? Без cron перший відвідувач ПІСЛЯ
 * застарівання кешу — це сам бот Hotline, і саме йому довелось би чекати,
 * поки плагін перебере весь каталог і перепакує XML+GZ. З cron регенерація
 * завжди відбувається у фоні за розкладом, а бот Hotline щоразу отримує
 * вже готовий свіжий файл миттєво.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

// Захист: лише CLI або запуск через cron_manager/адмінку
if (PHP_SAPI !== 'cli' && !defined('CRON_RUNNER')) {
    header('HTTP/1.1 403 Forbidden');
    exit('This script must be run from the command line.');
}

require_once $root . '/vendor/autoload.php';

$config = require $root . '/config/database.php';
\App\Core\Database\DB::connect($config['dsn'], $config['user'], $config['pass']);

require_once $root . '/app/helpers.php';

$logPrefix = '[HotlineExport ' . date('Y-m-d H:i:s') . '] ';

echo $logPrefix . "Start\n";

try {
    $plugin = require $root . '/plugins/HotlineExport/plugin.php';
    $stats = $plugin->forceRegenerateFeedCache();

    echo $logPrefix . "Done. Exported: " . ($stats['exported'] ?? '?') . "\n";
    foreach ($stats as $key => $value) {
        if ($key === 'exported' || $key === 'generated_at') {
            continue;
        }
        echo $logPrefix . "  {$key}: {$value}\n";
    }

    exit(0);
} catch (\Throwable $e) {
    echo $logPrefix . 'ERROR: ' . $e->getMessage() . "\n";
    echo $logPrefix . $e->getTraceAsString() . "\n";
    exit(1);
}
