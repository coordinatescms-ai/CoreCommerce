<?php
/**
 * Cron: авто-перевірка нових версій CoreCommerce.
 *
 * Запуск вручну:
 *   php tasks/check_updates.php
 *
 * Приклад cron (щодня о 04:30):
 *   30 4 * * * php /var/www/mysite/tasks/check_updates.php >> /var/log/corecommerce_updates.log 2>&1
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !defined('CRON_RUNNER')) {
    header('HTTP/1.1 403 Forbidden');
    exit('CLI only.');
}

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

$config = require $root . '/config/database.php';
\App\Core\Database\DB::connect($config);
require_once $root . '/app/helpers.php';

$cfg = require $root . '/config/updater.php';

$log = fn(string $msg) => print('[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL);

if (empty($cfg['allow_updates']) || empty($cfg['auto_check_enabled'])) {
    $log('Авто-перевірка вимкнена в config/updater.php.');
    exit(0);
}

$log('Перевірка оновлень (source: ' . ($cfg['source'] ?? 'local') . ')…');

// Виконуємо HTTP-запит до власного ендпоінта
$siteUrl = rtrim((string) \App\Models\Setting::get('site_url', ''), '/');
if ($siteUrl === '') {
    $log('ПОМИЛКА: site_url не налаштовано.');
    exit(1);
}

// Виконуємо внутрішній check напряму через контролер (без HTTP)
// Щоб не залежати від доступності сайту з localhost
$source = $cfg['source'] ?? 'local';

if ($source === 'remote') {
    $serverUrl = rtrim((string) ($cfg['update_server'] ?? ''), '/');
    if ($serverUrl === '') {
        $log('ПОМИЛКА: update_server не вказано.');
        exit(1);
    }

    $currentVersion = (string) ($cfg['current_version'] ?? '0.0.0');
    $url = $serverUrl . '/check?' . http_build_query([
        'version' => $currentVersion,
        'php'     => PHP_VERSION,
    ]);

    $context = stream_context_create([
        'http' => ['timeout' => (int) ($cfg['remote_timeout'] ?? 15), 'ignore_errors' => true],
        'ssl'  => ['verify_peer' => true],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        $log('ПОМИЛКА: Не вдалося підключитися до update-сервера.');
        exit(1);
    }

    $manifest = json_decode($body, true);
    if (!is_array($manifest) || empty($manifest['version'])) {
        $log('ПОМИЛКА: Некоректна відповідь сервера.');
        exit(1);
    }
} else {
    $manifestPath = (string) ($cfg['local_manifest'] ?? '');
    if (!is_file($manifestPath)) {
        $log('Manifest не знайдено — local-оновлення відсутнє.');
        \App\Models\Setting::setWithMeta('update_last_checked', date('Y-m-d H:i:s'), 'system', 'text');
        \App\Models\Setting::setWithMeta('update_check_result', '', 'system', 'text');
        exit(0);
    }
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
}

$currentVersion = (string) ($cfg['current_version'] ?? '0.0.0');
$newVersion     = (string) ($manifest['version'] ?? '');
$hasUpdate      = $newVersion !== '' && version_compare($newVersion, $currentVersion, '>');

\App\Models\Setting::setWithMeta('update_last_checked', date('Y-m-d H:i:s'), 'system', 'text');
\App\Models\Setting::setWithMeta('update_check_result', $hasUpdate ? $newVersion : '', 'system', 'text');

if ($hasUpdate) {
    $log("🆕 Доступна нова версія: {$newVersion} (поточна: {$currentVersion}).");
    $log('Зайдіть в адмінку → Налаштування → Оновлення щоб встановити.');
} else {
    $log("✅ Версія актуальна ({$currentVersion}).");
}

exit(0);
