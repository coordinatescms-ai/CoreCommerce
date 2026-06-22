<?php
/**
 * Cron-скрипт генерації Sitemap.
 *
 * Запуск вручну:
 *   php tasks/generate_sitemap.php
 *
 * Приклад запису в cron (щодня о 03:00):
 *   0 3 * * * php /var/www/mysite/tasks/generate_sitemap.php >> /var/log/sitemap.log 2>&1
 *
 * Режими:
 *   php tasks/generate_sitemap.php            — генерація
 *   php tasks/generate_sitemap.php --dry-run  — перевірка з'єднання без запису файлів
 */

declare(strict_types=1);

// ── 1. Завантаження середовища ────────────────────────────────────────────────

$root = dirname(__DIR__);

// Захист: скрипт лише для CLI
if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    exit('This script must be run from the command line.');
}

require_once $root . '/vendor/autoload.php';

// Завантажуємо конфігурацію БД і хелпери як в index.php
$config = require $root . '/config/database.php';
\App\Core\Database\DB::connect($config);

// Завантажуємо settings (потрібно для site_url)
require_once $root . '/app/helpers.php';

// ── 2. Параметри ──────────────────────────────────────────────────────────────

$dryRun    = in_array('--dry-run', $argv ?? [], true);
$outputDir = $root . '/public/sitemaps/';
$logPrefix = '[Sitemap ' . date('Y-m-d H:i:s') . '] ';

// ── 3. Генерація ──────────────────────────────────────────────────────────────

echo $logPrefix . "Start\n";

if ($dryRun) {
    echo $logPrefix . "DRY RUN — no files will be written.\n";
    echo $logPrefix . "Output dir: {$outputDir}\n";
    echo $logPrefix . "Site URL: " . \App\Models\Setting::get('site_url', '[not set]') . "\n";
    exit(0);
}

try {
    $result = \App\Services\SitemapService::generate($outputDir);

    echo $logPrefix . "Done in {$result['time']}s\n";
    echo $logPrefix . "Files: " . implode(', ', $result['files']) . "\n";

    foreach ($result['counts'] as $type => $count) {
        echo $logPrefix . "  {$type}: {$count} URLs\n";
    }

    // Оновлюємо час останньої генерації в налаштуваннях
    \App\Models\Setting::setWithMeta('sitemap_last_generated', date('Y-m-d H:i:s'), 'system', 'text');

    exit(0);

} catch (\Throwable $e) {
    echo $logPrefix . "ERROR: " . $e->getMessage() . "\n";
    echo $logPrefix . $e->getTraceAsString() . "\n";
    exit(1);
}
