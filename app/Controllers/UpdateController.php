<?php

namespace App\Controllers;

use App\Core\Database\DB;
use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Models\User;
use ZipArchive;

class UpdateController
{
    private array $config;
    private string $logFile;
    private string $rootPath;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/updater.php';
        $this->logFile = __DIR__ . '/../../storage/logs/update.log';
        $this->rootPath = (string) realpath(__DIR__ . '/../..');

        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    private function checkAdmin(): bool
    {
        return !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    private function requireAdmin(): void
    {
        if (!$this->checkAdmin()) {
            $this->jsonResponse(false, 'Доступ заборонено');
        }
    }

    private function requireCsrf(): void
    {
        if (!Csrf::isValid()) {
            $this->jsonResponse(false, 'CSRF token недійсний');
        }
    }

    private function requireUpdatesAllowed(): void
    {
        if (empty($this->config['allow_updates'])) {
            $this->jsonResponse(false, 'Оновлення вимкнені в конфігурації.');
        }
    }

    private function log(string $message): void
    {
        file_put_contents($this->logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }

    private function jsonResponse(bool $success, string $message, ?string $nextStep = null, array $data = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
            'next_step' => $nextStep,
        ], $data), JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function failUpdate(string $message): void
    {
        $this->log('Помилка оновлення: ' . $message);
        $this->disableMaintenanceMode();
        $this->jsonResponse(false, $message);
    }

    public function index(): void
    {
        if (!$this->checkAdmin()) {
            header('Location: /login');
            exit;
        }

        if (empty($this->config['allow_updates'])) {
            $_SESSION['error'] = 'Оновлення вимкнені в конфігурації.';
            header('Location: /admin/settings');
            exit;
        }

        View::render('admin/settings/tabs/update_page', [
            'current_version' => $this->config['current_version'],
            'allow_updates' => $this->config['allow_updates'],
        ], 'admin');
    }

    public function check(): void
    {
        $this->requireAdmin();

        if (empty($this->config['allow_updates'])) {
            $this->jsonResponse(true, 'Оновлення вимкнені в конфігурації.', null, [
                'update_available' => false,
            ]);
        }

        [$manifest, $error] = $this->loadLocalManifest();
        if ($error !== null) {
            $this->jsonResponse(true, $error, null, [
                'update_available' => false,
                'source' => $this->config['source'] ?? 'local',
            ]);
        }

        $currentVersion = (string) ($this->config['current_version'] ?? '0.0.0');
        $newVersion = (string) $manifest['version'];

        if (!version_compare($newVersion, $currentVersion, '>')) {
            $this->jsonResponse(true, 'Оновлень немає. Поточна версія актуальна.', null, [
                'update_available' => false,
                'current_version' => $currentVersion,
                'new_version' => $newVersion,
            ]);
        }

        $packagePath = $this->getLocalPackagePath($manifest);
        if (!is_file($packagePath)) {
            $this->jsonResponse(true, 'ZIP-пакет оновлення не знайдено: ' . basename($packagePath), null, [
                'update_available' => false,
            ]);
        }

        $minPhp = trim((string) ($manifest['min_php'] ?? ''));
        if ($minPhp !== '' && version_compare(PHP_VERSION, $minPhp, '<')) {
            $this->jsonResponse(true, 'Потрібен PHP ' . $minPhp . ' або новіший. Поточна версія: ' . PHP_VERSION, null, [
                'update_available' => false,
            ]);
        }

        $this->jsonResponse(true, 'Локальне оновлення знайдено.', 'init', [
            'update_available' => true,
            'source' => 'local',
            'new_version' => $newVersion,
            'changelog' => (string) ($manifest['changelog'] ?? ''),
            'package' => basename($packagePath),
            'sha256' => (string) $manifest['sha256'],
        ]);
    }

    public function init(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();

        if (!$this->verifyAdminPassword((string) ($_POST['password'] ?? ''))) {
            $this->jsonResponse(false, 'Невірний пароль адміністратора');
        }

        [$manifest, $error] = $this->loadLocalManifest();
        if ($error !== null) {
            $this->jsonResponse(false, $error);
        }

        if (!version_compare((string) $manifest['version'], (string) ($this->config['current_version'] ?? '0.0.0'), '>')) {
            $this->jsonResponse(false, 'Оновлення не новіше за поточну версію.');
        }

        $minPhp = trim((string) ($manifest['min_php'] ?? ''));
        if ($minPhp !== '' && version_compare(PHP_VERSION, $minPhp, '<')) {
            $this->jsonResponse(false, 'Потрібен PHP ' . $minPhp . ' або новіший. Поточна версія: ' . PHP_VERSION);
        }

        $packagePath = $this->getLocalPackagePath($manifest);
        if (!is_file($packagePath)) {
            $this->jsonResponse(false, 'ZIP-пакет оновлення не знайдено: ' . basename($packagePath));
        }

        $dirsToCheck = [
            $this->rootPath,
            (string) $this->config['backup_dir'],
            (string) $this->config['temp_dir'],
            (string) $this->config['staging_dir'],
        ];

        foreach ($dirsToCheck as $dir) {
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
                $this->jsonResponse(false, 'Не вдалося створити папку: ' . basename($dir));
            }

            if (!is_writable($dir)) {
                $this->jsonResponse(false, 'Папка не доступна для запису: ' . basename($dir));
            }
        }

        file_put_contents((string) $this->config['maintenance_file'], (string) time());
        $_SESSION['update_target_version'] = (string) $manifest['version'];
        $this->log('Початок локального оновлення до версії ' . $manifest['version']);

        $this->jsonResponse(true, 'Ініціалізація успішна. Режим обслуговування увімкнено.', 'backup');
    }

    public function backup(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();
        $this->log('Створення резервної копії');

        $version = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($this->config['current_version'] ?? 'unknown'));
        $backupPath = rtrim((string) $this->config['backup_dir'], '/\\') . "/v_$version" . '_' . date('Ymd_His');

        if (!is_dir($backupPath) && !mkdir($backupPath, 0755, true)) {
            $this->failUpdate('Не вдалося створити папку backup.');
        }

        foreach (['app', 'public', 'resources', 'routes', 'config'] as $dir) {
            $source = $this->rootPath . '/' . $dir;
            if (!is_dir($source)) {
                continue;
            }

            $exclude = $dir === 'public' ? ['uploads'] : [];
            $this->copyRecursive($source, $backupPath . '/' . $dir, $exclude);
        }

        $this->jsonResponse(true, 'Резервна копія створена: ' . basename($backupPath), 'download');
    }

    public function download(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();
        $this->log('Підготовка локального ZIP-пакета');

        [$manifest, $error] = $this->loadLocalManifest();
        if ($error !== null) {
            $this->failUpdate($error);
        }

        $packagePath = $this->getLocalPackagePath($manifest);
        if (!is_file($packagePath)) {
            $this->failUpdate('ZIP-пакет оновлення не знайдено: ' . basename($packagePath));
        }

        if (!is_dir((string) $this->config['temp_dir'])) {
            mkdir((string) $this->config['temp_dir'], 0755, true);
        }

        $tempZip = $this->getTempZipPath();
        if (!copy($packagePath, $tempZip)) {
            $this->failUpdate('Не вдалося скопіювати ZIP-пакет у temp.');
        }

        $expectedHash = strtolower((string) $manifest['sha256']);
        $actualHash = strtolower((string) hash_file('sha256', $tempZip));
        if (!hash_equals($expectedHash, $actualHash)) {
            @unlink($tempZip);
            $this->failUpdate('SHA256 не збігається. Очікувано: ' . $expectedHash . ', отримано: ' . $actualHash);
        }

        $this->jsonResponse(true, 'ZIP-пакет скопійовано та перевірено.', 'extract');
    }

    public function extract(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();
        $this->log('Розпакування архіву у staging');

        $tempZip = $this->getTempZipPath();
        if (!is_file($tempZip)) {
            $this->failUpdate('Temp ZIP не знайдено. Повторіть крок download.');
        }

        if (!class_exists(ZipArchive::class)) {
            $this->failUpdate('PHP ZipArchive недоступний.');
        }

        $stagingDir = (string) $this->config['staging_dir'];
        $this->deleteDirectory($stagingDir);
        if (!mkdir($stagingDir, 0755, true) && !is_dir($stagingDir)) {
            $this->failUpdate('Не вдалося створити staging-папку.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempZip) !== true) {
            $this->failUpdate('ZIP-пакет пошкоджений або не відкривається.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string) $zip->getNameIndex($i);
            $relativePath = $this->normalizeArchivePath($entryName);
            if ($relativePath === null) {
                $zip->close();
                $this->failUpdate('Небезпечний шлях в архіві: ' . $entryName);
            }
        }

        if (!$zip->extractTo($stagingDir)) {
            $zip->close();
            $this->failUpdate('Не вдалося розпакувати ZIP у staging.');
        }
        $zip->close();

        $this->applyStagedFiles($stagingDir);
        $this->jsonResponse(true, 'Файли оновлення застосовано зі staging.', 'database');
    }

    public function database(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();
        $this->log('Міграція бази даних');

        $sqlPath = rtrim((string) $this->config['staging_dir'], '/\\') . '/update.sql';
        if (!is_file($sqlPath)) {
            $this->jsonResponse(true, 'update.sql відсутній, міграцію пропущено.', 'finish');
        }

        $sql = trim((string) file_get_contents($sqlPath));
        if ($sql === '') {
            $this->jsonResponse(true, 'update.sql порожній, міграцію пропущено.', 'finish');
        }

        try {
            DB::exec($sql);
        } catch (\Throwable $e) {
            $this->failUpdate('Помилка SQL-міграції: ' . $e->getMessage());
        }

        $this->jsonResponse(true, 'SQL-міграцію виконано.', 'finish');
    }

    public function finish(): void
    {
        $this->requireAdmin();
        $this->requireCsrf();
        $this->requireUpdatesAllowed();
        $this->log('Завершення оновлення');

        [$manifest, $error] = $this->loadLocalManifest();
        if ($error !== null) {
            $this->failUpdate($error);
        }

        $this->updateCurrentVersion((string) $manifest['version']);
        $this->disableMaintenanceMode();
        unset($_SESSION['update_target_version']);

        $this->jsonResponse(true, 'Оновлення успішно завершено. Поточна версія: ' . $manifest['version'], null);
    }

    private function loadLocalManifest(): array
    {
        if (($this->config['source'] ?? 'local') !== 'local') {
            return [null, 'Поки підтримується тільки локальний dev-mode updater-а.'];
        }

        $manifestPath = (string) ($this->config['local_manifest'] ?? '');
        if ($manifestPath === '' || !is_file($manifestPath)) {
            return [null, 'manifest.json не знайдено у storage/local_updates.'];
        }

        $decoded = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            return [null, 'manifest.json має некоректний JSON.'];
        }

        foreach (['version', 'changelog', 'package', 'sha256', 'min_php'] as $field) {
            if (trim((string) ($decoded[$field] ?? '')) === '') {
                return [null, 'У manifest.json відсутнє поле: ' . $field];
            }
        }

        if (!preg_match('/^[a-f0-9]{64}$/i', (string) $decoded['sha256'])) {
            return [null, 'Поле sha256 у manifest.json має містити 64 hex-символи.'];
        }

        return [$decoded, null];
    }

    private function getLocalPackagePath(array $manifest): string
    {
        $package = str_replace('\\', '/', trim((string) $manifest['package']));
        $package = basename($package);

        return rtrim((string) $this->config['local_package_dir'], '/\\') . '/' . $package;
    }

    private function getTempZipPath(): string
    {
        return rtrim((string) $this->config['temp_dir'], '/\\') . '/update.zip';
    }

    private function verifyAdminPassword(string $password): bool
    {
        if ($password === '' || empty($_SESSION['user']['id'])) {
            return false;
        }

        $user = User::findById((int) $_SESSION['user']['id']);
        if (!$user || empty($user['password'])) {
            return false;
        }

        return User::verifyPassword($password, (string) $user['password']);
    }

    private function normalizeArchivePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === '' || $path === '.' || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:\//', $path)) {
            return null;
        }

        $path = ltrim($path, '/');

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                return null;
            }

            $segments[] = $segment;
        }

        if (empty($segments)) {
            return null;
        }

        $normalized = implode('/', $segments);
        if ($this->isForbiddenUpdatePath($normalized)) {
            return null;
        }

        return $normalized;
    }

    private function isForbiddenUpdatePath(string $relativePath): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $firstSegment = explode('/', $relativePath)[0] ?? '';

        if ($relativePath === 'update.sql') {
            return false;
        }

        if ($relativePath === '.env' || str_starts_with($relativePath, '.env.')) {
            return true;
        }

        if (in_array($firstSegment, ['config', 'storage', 'backups'], true)) {
            return true;
        }

        return $relativePath === 'public/uploads' || str_starts_with($relativePath, 'public/uploads/');
    }

    private function applyStagedFiles(string $stagingDir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($stagingDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = str_replace('\\', '/', $item->getPathname());
            $relativePath = ltrim(substr($sourcePath, strlen(str_replace('\\', '/', rtrim($stagingDir, '/\\')))), '/');
            $relativePath = $this->normalizeArchivePath($relativePath);

            if ($relativePath === null) {
                $this->failUpdate('Небезпечний шлях у staging: ' . $sourcePath);
            }

            if ($relativePath === 'update.sql') {
                continue;
            }

            $targetPath = $this->rootPath . '/' . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true)) {
                    $this->failUpdate('Не вдалося створити папку: ' . $relativePath);
                }
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                $this->failUpdate('Не вдалося створити папку: ' . dirname($relativePath));
            }

            if (!copy($item->getPathname(), $targetPath)) {
                $this->failUpdate('Не вдалося скопіювати файл: ' . $relativePath);
            }
        }
    }

    private function updateCurrentVersion(string $newVersion): void
    {
        $configPath = __DIR__ . '/../../config/updater.php';
        $configContent = (string) file_get_contents($configPath);
        $escapedVersion = str_replace("'", "\\'", $newVersion);
        $newContent = preg_replace("/'current_version'\s*=>\s*'[^']*'/", "'current_version' => '$escapedVersion'", $configContent, 1);

        if (!is_string($newContent) || $newContent === $configContent) {
            $this->failUpdate('Не вдалося оновити current_version у config/updater.php.');
        }

        file_put_contents($configPath, $newContent);
    }

    private function disableMaintenanceMode(): void
    {
        $maintenanceFile = (string) ($this->config['maintenance_file'] ?? '');
        if ($maintenanceFile !== '' && is_file($maintenanceFile)) {
            @unlink($maintenanceFile);
        }
    }

    private function copyRecursive(string $source, string $dest, array $exclude = []): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        foreach (scandir($source) ?: [] as $file) {
            if ($file === '.' || $file === '..' || in_array($file, $exclude, true)) {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;
            if (is_dir($sourcePath)) {
                $this->copyRecursive($sourcePath, $destPath, $exclude);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}