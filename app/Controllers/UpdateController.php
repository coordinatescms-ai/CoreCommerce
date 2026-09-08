<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Models\Setting;
use App\Models\User;
use ZipArchive;

/**
 * UpdateController — керування оновленнями CoreCommerce.
 *
 * Підтримує два джерела (config/updater.php → source):
 *   'local'  — manifest.json + ZIP з папки storage/local_updates/
 *   'remote' — manifest і ZIP завантажуються з update_server через HTTP
 *
 * Алгоритм оновлення (кожен крок — окремий AJAX POST):
 *   check → init → backup → download → extract → database → finish
 *
 * Rollback (відкат) — окремий маршрут /admin/update/rollback.
 * Виконується автоматично при failUpdate() якщо backup вже створено.
 */
class UpdateController
{
    private array  $config;
    private string $logFile;
    private string $rootPath;

    public function __construct()
    {
        $this->config   = require __DIR__ . '/../../config/updater.php';
        $this->logFile  = __DIR__ . '/../../storage/logs/update.log';
        $this->rootPath = (string) realpath(__DIR__ . '/../..');

        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    // ── Допоміжні методи ─────────────────────────────────────────────────────

    private function checkAdmin(): bool
    {
        return !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    private function requireAdmin(): void
    {
        if (!$this->checkAdmin()) {
            $this->jsonResponse(false, __('update_access_denied'));
        }
    }

    private function requireCsrf(): void
    {
        if (!Csrf::isValid()) {
            $this->jsonResponse(false, __('csrf_token_invalid'));
        }
    }

    private function requireUpdatesAllowed(): void
    {
        if (empty($this->config['allow_updates'])) {
            $this->jsonResponse(false, __('update_disabled'));
        }
    }

    private function log(string $message): void
    {
        file_put_contents(
            $this->logFile,
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    private function jsonResponse(bool $success, string $message, ?string $nextStep = null, array $data = []): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success'   => $success,
            'message'   => $message,
            'next_step' => $nextStep,
        ], $data), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Аварійна зупинка: логуємо, вимикаємо maintenance, намагаємось rollback.
     */
    private function failUpdate(string $message, bool $tryRollback = true): never
    {
        $this->log('ПОМИЛКА: ' . $message);
        $this->disableMaintenanceMode();

        if ($tryRollback && $this->hasRollbackMarker()) {
            $rollbackResult = $this->performRollback();
            $rollbackMsg    = $rollbackResult
                ? __('update_rollback_success_suffix')
                : __('update_rollback_failed_suffix');
            $this->jsonResponse(false, $message . $rollbackMsg);
        }

        $this->jsonResponse(false, $message);
    }

    // ── Публічні ендпоінти ────────────────────────────────────────────────────

    public function index(): void
    {
        if (!$this->checkAdmin()) {
            header('Location: /login');
            exit;
        }

        View::render('admin/settings/tabs/update_page', [
            'current_version' => $this->config['current_version'],
            'allow_updates'   => $this->config['allow_updates'],
            'source'          => $this->config['source'] ?? 'local',
        ], 'admin');
    }

    /**
     * Перевірка наявності оновлень.
     * Працює для обох джерел: local та remote.
     */
    public function check(): void
    {
        $this->requireAdmin();

        if (empty($this->config['allow_updates'])) {
            $this->jsonResponse(true, __('update_disabled'), null, [
                'update_available' => false,
            ]);
        }

        $source = $this->config['source'] ?? 'local';

        [$manifest, $error] = match ($source) {
            'remote' => $this->loadRemoteManifest(),
            default  => $this->loadLocalManifest(),
        };

        if ($error !== null) {
            $this->jsonResponse(true, $error, null, [
                'update_available' => false,
                'source'           => $source,
            ]);
        }

        $currentVersion = (string) ($this->config['current_version'] ?? '0.0.0');
        $newVersion     = (string) $manifest['version'];

        if (!version_compare($newVersion, $currentVersion, '>')) {
            $this->jsonResponse(true, __('update_no_updates'), null, [
                'update_available' => false,
                'current_version'  => $currentVersion,
                'new_version'      => $newVersion,
                'source'           => $source,
            ]);
        }

        $minPhp = trim((string) ($manifest['min_php'] ?? ''));
        if ($minPhp !== '' && version_compare(PHP_VERSION, $minPhp, '<')) {
            $this->jsonResponse(true, sprintf(__('update_php_required'), $minPhp, PHP_VERSION), null, [
                'update_available' => false,
            ]);
        }

        // Для local — перевіряємо наявність ZIP
        if ($source === 'local') {
            $packagePath = $this->getLocalPackagePath($manifest);
            if (!is_file($packagePath)) {
                $this->jsonResponse(true, sprintf(__('update_zip_missing'), basename($packagePath)), null, [
                    'update_available' => false,
                ]);
            }
        }

        // Зберігаємо час перевірки
        Setting::setWithMeta('update_last_checked', date('Y-m-d H:i:s'), 'system', 'text');

        $this->jsonResponse(true, sprintf(__('update_available'), $newVersion), 'init', [
            'update_available' => true,
            'source'           => $source,
            'new_version'      => $newVersion,
            'changelog'        => (string) ($manifest['changelog'] ?? ''),
            'sha256'           => (string) ($manifest['sha256'] ?? ''),
            'download_url'     => $source === 'remote' ? (string) ($manifest['download_url'] ?? '') : '',
        ]);
    }

    /**
     * Ініціалізація оновлення: перевірка прав, пароль, увімкнення maintenance.
     */
    public function init(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();

        if (!$this->verifyAdminPassword((string) ($_POST['password'] ?? ''))) {
            $this->jsonResponse(false, __('update_invalid_admin_password'));
        }

        $source = $this->config['source'] ?? 'local';
        [$manifest, $error] = match ($source) {
            'remote' => $this->loadRemoteManifest(),
            default  => $this->loadLocalManifest(),
        };

        if ($error !== null) {
            $this->jsonResponse(false, $error);
        }

        if (!version_compare((string) $manifest['version'], (string) ($this->config['current_version'] ?? '0.0.0'), '>')) {
            $this->jsonResponse(false, __('update_version_not_newer'));
        }

        // Перевірка прав на запис
        $dirsToCheck = [
            $this->rootPath,
            (string) $this->config['backup_dir'],
            (string) $this->config['temp_dir'],
            (string) $this->config['staging_dir'],
        ];

        foreach ($dirsToCheck as $dir) {
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                $this->jsonResponse(false, sprintf(__('update_create_directory_failed'), basename($dir)));
            }
            if (!is_writable($dir)) {
                $this->jsonResponse(false, sprintf(__('update_directory_not_writable'), basename($dir)));
            }
        }

        // Увімкнення maintenance
        file_put_contents((string) $this->config['maintenance_file'], (string) time());

        // Зберігаємо стан в сесії
        $_SESSION['update_target_version'] = (string) $manifest['version'];
        $_SESSION['update_source']         = $source;
        $_SESSION['update_sha256']         = (string) ($manifest['sha256'] ?? '');
        $_SESSION['update_download_url']   = (string) ($manifest['download_url'] ?? '');

        $this->log('Початок оновлення ' . ($this->config['current_version'] ?? '') . ' → ' . $manifest['version'] . ' (source: ' . $source . ')');
        $this->jsonResponse(true, __('update_initialization_success'), 'backup');
    }

    /**
     * Крок 1: Backup поточної версії.
     */
    public function backup(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();

        $version    = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($this->config['current_version'] ?? 'unknown'));
        $backupPath = rtrim((string) $this->config['backup_dir'], '/\\') . "/v_{$version}_" . date('Ymd_His');

        if (!is_dir($backupPath) && !mkdir($backupPath, 0755, true)) {
            $this->failUpdate(__('update_backup_directory_failed'), false);
        }

        foreach (['app', 'public', 'resources', 'routes', 'lang', 'config'] as $dir) {
            $source = $this->rootPath . '/' . $dir;
            if (!is_dir($source)) continue;
            $this->copyRecursive($source, $backupPath . '/' . $dir, $dir === 'public' ? ['uploads'] : []);
        }

        // Backup БД (SQL-дамп основних таблиць)
        $this->backupDatabase($backupPath . '/database.sql');

        // Записуємо rollback-маркер — шлях до backup
        $this->saveRollbackMarker($backupPath);

        $this->log('Backup створено: ' . basename($backupPath));
        $this->jsonResponse(true, sprintf(__('update_backup_created'), basename($backupPath)), 'download');
    }

    /**
     * Крок 2: Завантаження ZIP-пакета (local → copy, remote → HTTP).
     */
    public function download(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();

        $source  = $_SESSION['update_source'] ?? ($this->config['source'] ?? 'local');
        $tempZip = $this->getTempZipPath();

        if ($source === 'remote') {
            $downloadUrl = $_SESSION['update_download_url'] ?? '';
            if ($downloadUrl === '') {
                $this->failUpdate(__('update_package_url_missing'));
            }
            $this->log('Завантаження з: ' . $downloadUrl);
            $this->downloadRemoteFile($downloadUrl, $tempZip);
        } else {
            [$manifest, $error] = $this->loadLocalManifest();
            if ($error !== null) {
                $this->failUpdate($error);
            }
            $packagePath = $this->getLocalPackagePath($manifest);
            if (!copy($packagePath, $tempZip)) {
                $this->failUpdate(__('update_zip_copy_failed'));
            }
        }

        // Перевірка SHA256
        $expectedHash = strtolower($_SESSION['update_sha256'] ?? '');
        if ($expectedHash !== '') {
            $actualHash = strtolower((string) hash_file('sha256', $tempZip));
            if (!hash_equals($expectedHash, $actualHash)) {
                @unlink($tempZip);
                $this->failUpdate(sprintf(__('update_hash_mismatch'), $expectedHash, $actualHash));
            }
        }

        $this->log('ZIP отримано та перевірено.');
        $this->jsonResponse(true, __('update_package_downloaded'), 'extract');
    }

    /**
     * Крок 3: Розпакування та застосування файлів.
     */
    public function extract(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();

        $tempZip = $this->getTempZipPath();
        if (!is_file($tempZip)) {
            $this->failUpdate(__('update_temp_zip_missing'));
        }

        if (!class_exists(ZipArchive::class)) {
            $this->failUpdate(__('update_ziparchive_unavailable'));
        }

        $stagingDir = (string) $this->config['staging_dir'];
        $this->deleteDirectory($stagingDir);
        if (!mkdir($stagingDir, 0755, true) && !is_dir($stagingDir)) {
            $this->failUpdate(__('update_staging_directory_failed'));
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZip) !== true) {
            $this->failUpdate(__('update_zip_corrupted'));
        }

        // Перевіряємо шляхи всіх файлів у архіві перед розпакуванням
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);
            if ($this->normalizeArchivePath($entryName) === null) {
                $zip->close();
                $this->failUpdate(sprintf(__('update_unsafe_archive_path'), $entryName));
            }
        }

        if (!$zip->extractTo($stagingDir)) {
            $zip->close();
            $this->failUpdate(__('update_extract_failed'));
        }
        $zip->close();

        $this->applyStagedFiles($stagingDir);
        $this->log('Файли оновлення застосовано.');
        $this->jsonResponse(true, __('update_files_applied'), 'database');
    }

    /**
     * Крок 4: SQL-міграція.
     */
    public function database(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();

        $sqlPath = rtrim((string) $this->config['staging_dir'], '/\\') . '/update.sql';

        if (!is_file($sqlPath)) {
            $this->jsonResponse(true, __('update_sql_missing'), 'finish');
        }

        $sql = trim((string) file_get_contents($sqlPath));
        if ($sql === '') {
            $this->jsonResponse(true, __('update_sql_empty'), 'finish');
        }

        try {
            DB::exec($sql);
        } catch (\Throwable $e) {
            $this->failUpdate(sprintf(__('update_sql_error'), $e->getMessage()));
        }

        $this->log('SQL-міграцію виконано.');
        $this->jsonResponse(true, __('update_sql_applied'), 'finish');
    }

    /**
     * Крок 5: Завершення — оновлюємо версію, вимикаємо maintenance.
     */
    public function finish(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();

        $newVersion = $_SESSION['update_target_version'] ?? '';

        if ($newVersion !== '') {
            $this->updateCurrentVersion($newVersion);
            Setting::setWithMeta('engine_version', $newVersion, 'system', 'text');
        }

        // Очищаємо тимчасові файли (staging, zip)
        // Rollback-маркер НЕ видаляємо тут — саме зараз він потрібен найбільше:
        // якщо після оновлення сайт почне давати збої, адмін має змогу відкотитись.
        // Маркер видаляється в rollback() (після використання) або coreClearRollback()
        // при явному підтвердженні адміном, що оновлення відпрацювало нормально.
        @unlink($this->getTempZipPath());
        $this->deleteDirectory((string) $this->config['staging_dir']);

        $this->disableMaintenanceMode();
        unset(
            $_SESSION['update_target_version'],
            $_SESSION['update_source'],
            $_SESSION['update_sha256'],
            $_SESSION['update_download_url']
        );

        $this->log('Оновлення до ' . $newVersion . ' завершено успішно.');
        $this->jsonResponse(true, sprintf(__('update_finished'), $newVersion), null, [
            'new_version' => $newVersion,
        ]);
    }

    /**
     * Rollback — відновлення з backup.
     * Викликається вручну або автоматично при failUpdate().
     */
    /**
     * Адмін явно підтверджує що оновлення відпрацювало нормально —
     * після цього шлях відкату більше не потрібен і backup можна прибрати з rollback-маркера
     * (сам backup у backups/ залишається на диску для ручного відновлення за потреби).
     */
    public function confirmUpdate(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        if (!$this->hasRollbackMarker()) {
            $this->jsonResponse(false, __('update_rollback_marker_already_missing'));
        }

        $this->clearRollbackMarker();
        $this->log('Оновлення підтверджено адміністратором, rollback-маркер очищено.');
        $this->jsonResponse(true, __('update_confirmed'));
    }

    public function rollback(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();

        if (!$this->hasRollbackMarker()) {
            $this->jsonResponse(false, __('update_rollback_marker_missing'));
        }

        $success = $this->performRollback();

        if ($success) {
            $this->disableMaintenanceMode();
            $this->clearRollbackMarker();
            $this->log('Rollback виконано успішно.');
            $this->jsonResponse(true, __('update_rollback_success'));
        } else {
            $this->jsonResponse(false, __('update_rollback_failed'));
        }
    }

    /**
     * Авто-перевірка нових версій (викликається з адмін-панелі через AJAX).
     */
    public function autoCheck(): void
    {
        $this->requireAdmin();

        if (empty($this->config['allow_updates']) || empty($this->config['auto_check_enabled'])) {
            $this->jsonResponse(true, __('update_auto_check_disabled'), null, ['update_available' => false]);
        }

        $intervalHours = (int) ($this->config['auto_check_interval_hours'] ?? 24);
        $lastChecked   = (string) Setting::get('update_last_checked', '');

        // Не перевіряємо частіше ніж раз на $intervalHours годин
        if ($lastChecked !== '') {
            $nextCheck = strtotime($lastChecked) + $intervalHours * 3600;
            if (time() < $nextCheck) {
                $cachedResult = (string) Setting::get('update_check_result', '');
                $this->jsonResponse(true, __('update_checked_earlier'), null, [
                    'update_available' => !empty($cachedResult),
                    'cached'           => true,
                    'new_version'      => $cachedResult,
                    'next_check'       => date('Y-m-d H:i', $nextCheck),
                ]);
            }
        }

        // Перевіряємо
        $source = $this->config['source'] ?? 'local';
        [$manifest, $error] = match ($source) {
            'remote' => $this->loadRemoteManifest(),
            default  => $this->loadLocalManifest(),
        };

        Setting::setWithMeta('update_last_checked', date('Y-m-d H:i:s'), 'system', 'text');

        if ($error !== null || $manifest === null) {
            $this->jsonResponse(true, sprintf(__('update_check_failed'), $error ?? __('update_unknown_error')), null, [
                'update_available' => false,
            ]);
        }

        $currentVersion = (string) ($this->config['current_version'] ?? '0.0.0');
        $newVersion     = (string) $manifest['version'];
        $hasUpdate      = version_compare($newVersion, $currentVersion, '>');

        Setting::setWithMeta('update_check_result', $hasUpdate ? $newVersion : '', 'system', 'text');

        $this->jsonResponse(true, $hasUpdate ? sprintf(__('update_version_available'), $newVersion) : __('update_version_current'), null, [
            'update_available' => $hasUpdate,
            'new_version'      => $hasUpdate ? $newVersion : '',
            'changelog'        => $hasUpdate ? (string) ($manifest['changelog'] ?? '') : '',
        ]);
    }

    // ── Rollback ──────────────────────────────────────────────────────────────

    private function saveRollbackMarker(string $backupPath): void
    {
        $markerPath = (string) ($this->config['rollback_marker'] ?? '');
        if ($markerPath === '') return;

        $dir = dirname($markerPath);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        file_put_contents($markerPath, json_encode([
            'backup_path'     => $backupPath,
            'version_before'  => $this->config['current_version'] ?? '0.0.0',
            'created_at'      => date('Y-m-d H:i:s'),
        ]));
    }

    private function hasRollbackMarker(): bool
    {
        $markerPath = (string) ($this->config['rollback_marker'] ?? '');
        return $markerPath !== '' && is_file($markerPath);
    }

    private function clearRollbackMarker(): void
    {
        $markerPath = (string) ($this->config['rollback_marker'] ?? '');
        if ($markerPath !== '' && is_file($markerPath)) {
            @unlink($markerPath);
        }
    }

    /**
     * Виконати rollback: відновити файли з backup, оновити версію.
     */
    private function performRollback(): bool
    {
        $markerPath = (string) ($this->config['rollback_marker'] ?? '');
        if (!is_file($markerPath)) return false;

        $marker = json_decode((string) file_get_contents($markerPath), true);
        if (!is_array($marker)) return false;

        $backupPath    = (string) ($marker['backup_path']    ?? '');
        $versionBefore = (string) ($marker['version_before'] ?? '');

        if ($backupPath === '' || !is_dir($backupPath)) {
            $this->log('Rollback: backup-папка не знайдена: ' . $backupPath);
            return false;
        }

        try {
            // Відновлюємо файли з backup.
            // КРИТИЧНО: public/uploads/ ніколи не потрапляє в backup (див. backup() —
            // copyRecursive виключає 'uploads' для 'public'), тому видаляти його тут
            // не можна — це знищить усі завантажені товарами зображення без жодної
            // копії. Видаляємо і замінюємо вміст public/ вибірково, зберігаючи uploads/.
            foreach (['app', 'resources', 'routes', 'lang'] as $dir) {
                $source = $backupPath . '/' . $dir;
                if (!is_dir($source)) continue;
                $this->deleteDirectory($this->rootPath . '/' . $dir);
                $this->copyRecursive($source, $this->rootPath . '/' . $dir);
            }

            // public/ обробляємо окремо: видаляємо все КРІМ uploads/, потім копіюємо
            // з backup усе КРІМ uploads/ (бо backup і не містить uploads/).
            $publicSource = $backupPath . '/public';
            if (is_dir($publicSource)) {
                $this->deleteDirectoryExcept($this->rootPath . '/public', ['uploads']);
                $this->copyRecursive($publicSource, $this->rootPath . '/public', ['uploads']);
            }

            // Відновлюємо config (крім updater.php — він може оновитися)
            if (is_dir($backupPath . '/config')) {
                foreach (glob($backupPath . '/config/*.php') ?: [] as $cfgFile) {
                    $basename = basename($cfgFile);
                    if ($basename === 'updater.php') continue;
                    @copy($cfgFile, $this->rootPath . '/config/' . $basename);
                }
            }

            // Відновлюємо версію у updater.php
            if ($versionBefore !== '') {
                $this->updateCurrentVersion($versionBefore);
            }

            $this->log('Rollback виконано до версії ' . $versionBefore . ' з ' . basename($backupPath));
            return true;

        } catch (\Throwable $e) {
            $this->log('Rollback помилка: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Видаляє вміст директорії рекурсивно, окрім вказаних піддиректорій верхнього рівня.
     * Використовується для public/ щоб зберегти uploads/ при rollback.
     */
    private function deleteDirectoryExcept(string $dir, array $exceptTopLevel = []): void
    {
        if (!is_dir($dir)) return;

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $exceptTopLevel, true)) continue;

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
    }

    // ── Remote-джерело ────────────────────────────────────────────────────────

    /**
     * Завантажити manifest.json з remote update-сервера.
     * Сервер повинен повернути JSON із полями: version, changelog, sha256, download_url, min_php
     */
    private function loadRemoteManifest(): array
    {
        $serverUrl = rtrim((string) ($this->config['update_server'] ?? ''), '/');
        if ($serverUrl === '') {
            return [null, __('update_server_missing')];
        }

        $currentVersion = (string) ($this->config['current_version'] ?? '0.0.0');
        $apiKey         = (string) ($this->config['api_key'] ?? '');
        $timeout        = (int)    ($this->config['remote_timeout'] ?? 15);

        // Формуємо URL запиту
        $url = $serverUrl . '/check?' . http_build_query([
            'version' => $currentVersion,
            'php'     => PHP_VERSION,
            'domain'  => $_SERVER['HTTP_HOST'] ?? '',
        ]);

        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => $timeout,
                'ignore_errors'   => true,
                'header'          => implode("\r\n", array_filter([
                    'Accept: application/json',
                    'User-Agent: CoreCommerce/' . $currentVersion,
                    $apiKey !== '' ? 'X-Api-Key: ' . $apiKey : '',
                ])),
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            return [null, sprintf(__('update_server_connection_failed'), $serverUrl)];
        }

        // Перевіряємо HTTP-статус
        $statusLine = $http_response_header[0] ?? '';
        if (!str_contains($statusLine, '200')) {
            return [null, sprintf(__('update_server_error'), $statusLine)];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return [null, __('update_server_invalid_json')];
        }

        // Валідація обов'язкових полів
        foreach (['version', 'sha256', 'download_url'] as $field) {
            if (empty($decoded[$field])) {
                return [null, sprintf(__('update_server_missing_field'), $field)];
            }
        }

        if (!preg_match('/^[a-f0-9]{64}$/i', (string) $decoded['sha256'])) {
            return [null, __('update_server_invalid_sha256')];
        }

        // Додаємо min_php якщо не прийшло
        $decoded['min_php']    = $decoded['min_php']    ?? '8.1';
        $decoded['changelog']  = $decoded['changelog']  ?? '';

        return [$decoded, null];
    }

    /**
     * Завантажити ZIP-файл з URL і записати на диск.
     * Використовує fopen-потоки щоб не вантажити весь файл в RAM.
     */
    private function downloadRemoteFile(string $url, string $destPath): void
    {
        if (!is_dir(dirname($destPath))) {
            @mkdir(dirname($destPath), 0755, true);
        }

        $timeout = (int) ($this->config['remote_timeout'] ?? 15);
        $apiKey  = (string) ($this->config['api_key'] ?? '');

        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => max($timeout, 120), // ZIP може бути великим
                'ignore_errors' => true,
                'header'        => implode("\r\n", array_filter([
                    'User-Agent: CoreCommerce/' . ($this->config['current_version'] ?? ''),
                    $apiKey !== '' ? 'X-Api-Key: ' . $apiKey : '',
                ])),
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $in  = @fopen($url, 'rb', false, $context);
        $out = @fopen($destPath, 'wb');

        if (!$in || !$out) {
            $this->failUpdate(__('update_download_stream_failed'));
        }

        // Потокове копіювання — мінімальне споживання RAM
        while (!feof($in)) {
            fwrite($out, fread($in, 65536));
        }

        fclose($in);
        fclose($out);

        if (!is_file($destPath) || filesize($destPath) === 0) {
            $this->failUpdate(__('update_download_empty'));
        }
    }

    // ── Local-джерело ─────────────────────────────────────────────────────────

    private function loadLocalManifest(): array
    {
        $manifestPath = (string) ($this->config['local_manifest'] ?? '');
        if ($manifestPath === '' || !is_file($manifestPath)) {
            return [null, __('update_local_manifest_missing')];
        }

        $decoded = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            return [null, __('update_local_manifest_invalid_json')];
        }

        foreach (['version', 'package', 'sha256', 'min_php'] as $field) {
            if (empty($decoded[$field])) {
                return [null, sprintf(__('update_local_manifest_missing_field'), $field)];
            }
        }

        if (!preg_match('/^[a-f0-9]{64}$/i', (string) $decoded['sha256'])) {
            return [null, __('update_local_manifest_invalid_sha256')];
        }

        $decoded['changelog'] = $decoded['changelog'] ?? '';
        return [$decoded, null];
    }

    private function getLocalPackagePath(array $manifest): string
    {
        $package = basename(str_replace('\\', '/', trim((string) $manifest['package'])));
        return rtrim((string) $this->config['local_package_dir'], '/\\') . '/' . $package;
    }

    // ── Database Backup ────────────────────────────────────────────────────────

    /**
     * Мінімальний SQL-дамп ключових таблиць (структура не потрібна — лише дані).
     * Для повного дампа використовуйте mysqldump.
     */
    private function backupDatabase(string $outputPath): void
    {
        try {
            $tables  = ['settings', 'users', 'orders', 'products', 'categories'];
            $handle  = fopen($outputPath, 'wb');
            if (!$handle) return;

            fwrite($handle, "-- CoreCommerce DB Backup " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "SET NAMES utf8mb4;\n\n");

            foreach ($tables as $table) {
                try {
                    $rows = DB::query("SELECT * FROM `$table` LIMIT 50000")->fetchAll(\PDO::FETCH_ASSOC);
                    if (empty($rows)) continue;

                    fwrite($handle, "-- Table: $table\n");
                    foreach ($rows as $row) {
                        $values = array_map(
                            fn($v) => $v === null ? 'NULL' : "'" . addslashes((string)$v) . "'",
                            $row
                        );
                        fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
                    }
                    fwrite($handle, "\n");
                } catch (\Throwable) {
                    // Таблиця може не існувати в певних версіях — пропускаємо
                }
            }

            fclose($handle);
        } catch (\Throwable $e) {
            $this->log('DB backup warning: ' . $e->getMessage());
        }
    }

    // ── Утиліти ───────────────────────────────────────────────────────────────

    private function verifyAdminPassword(string $password): bool
    {
        if ($password === '' || empty($_SESSION['user']['id'])) return false;

        $user = User::findById((int) $_SESSION['user']['id']);
        if (!$user || empty($user['password'])) return false;

        return User::verifyPassword($password, (string) $user['password']);
    }

    private function normalizeArchivePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:\//', $path)) {
            return null;
        }

        $path     = ltrim($path, '/');
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') continue;
            if ($segment === '..') return null;
            $segments[] = $segment;
        }

        if (empty($segments)) return null;

        $normalized = implode('/', $segments);
        return $this->isForbiddenUpdatePath($normalized) ? null : $normalized;
    }

    private function isForbiddenUpdatePath(string $relativePath): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $firstSegment = explode('/', $relativePath)[0] ?? '';

        // update.sql — спеціальний файл міграції, завжди дозволений
        if ($relativePath === 'update.sql') return false;

        // .env завжди захищений
        if ($relativePath === '.env' || str_starts_with($relativePath, '.env.')) return true;

        // storage/ та backups/ — ніколи не перезаписуємо
        if (in_array($firstSegment, ['storage', 'backups'], true)) return true;

        // public/uploads/ — файли користувачів
        if ($relativePath === 'public/uploads' || str_starts_with($relativePath, 'public/uploads/')) return true;

        // config/ — захищаємо від перезапису (містить database.php, keys тощо)
        // Виняток: config/updater.php — може оновлюватись системою
        if ($firstSegment === 'config' && $relativePath !== 'config/updater.php') return true;

        return false;
    }

    private function applyStagedFiles(string $stagingDir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($stagingDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath   = str_replace('\\', '/', $item->getPathname());
            $relativePath = ltrim(substr($sourcePath, strlen(str_replace('\\', '/', rtrim($stagingDir, '/\\')))), '/');
            $relativePath = $this->normalizeArchivePath($relativePath);

            if ($relativePath === null) {
                $this->failUpdate(sprintf(__('update_unsafe_staging_path'), $sourcePath));
            }
            if ($relativePath === 'update.sql') continue;

            $targetPath = $this->rootPath . '/' . $relativePath;
            $targetDir  = dirname($targetPath);

            if ($item->isDir()) {
                if (!is_dir($targetPath)) @mkdir($targetPath, 0755, true);
                continue;
            }

            if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
            if (!copy($item->getPathname(), $targetPath)) {
                $this->failUpdate(sprintf(__('update_file_copy_failed'), $relativePath));
            }
        }
    }

    private function updateCurrentVersion(string $newVersion): void
    {
        $configPath    = __DIR__ . '/../../config/updater.php';
        $configContent = (string) file_get_contents($configPath);
        $escaped       = str_replace("'", "\\'", $newVersion);
        $newContent    = preg_replace("/'current_version'\s*=>\s*'[^']*'/", "'current_version' => '$escaped'", $configContent, 1);

        if (is_string($newContent) && $newContent !== $configContent) {
            file_put_contents($configPath, $newContent);
        }
    }

    private function disableMaintenanceMode(): void
    {
        $f = (string) ($this->config['maintenance_file'] ?? '');
        if ($f !== '' && is_file($f)) @unlink($f);
    }

    private function getTempZipPath(): string
    {
        return rtrim((string) $this->config['temp_dir'], '/\\') . '/update.zip';
    }

    private function copyRecursive(string $source, string $dest, array $exclude = []): void
    {
        if (!is_dir($dest)) mkdir($dest, 0755, true);
        foreach (scandir($source) ?: [] as $file) {
            if ($file === '.' || $file === '..' || in_array($file, $exclude, true)) continue;
            $s = $source . '/' . $file;
            $d = $dest   . '/' . $file;
            is_dir($s) ? $this->copyRecursive($s, $d) : copy($s, $d);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
