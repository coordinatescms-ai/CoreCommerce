<?php

namespace App\Core\Plugin;

use App\Core\Database\DB;

class PluginManager
{
    private const ALLOWED_UPLOAD_EXTENSIONS = [
        'css',
        'gif',
        'ico',
        'jpeg',
        'jpg',
        'js',
        'json',
        'md',
        'php',
        'png',
        'sql',
        'txt',
        'webp',
    ];

    private static ?self $instance = null;

    private string $pluginsPath;

    private string $cacheFile;

    /** @var array<string, array<int, array{callback: callable, priority: int, accepted_args: int}>> */
    private array $actions = [];

    /** @var array<string, array<int, array{callback: callable, priority: int, accepted_args: int}>> */
    private array $filters = [];

    /** @var array<string, PluginInterface> */
    private array $loadedPlugins = [];

    private function __construct(?string $pluginsPath = null, ?string $cacheFile = null)
    {
        $this->pluginsPath = $pluginsPath ?? dirname(__DIR__, 3) . '/plugins';
        $this->cacheFile = $cacheFile ?? dirname(__DIR__, 3) . '/storage/cache/active_plugins.json';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function load(): void
    {
        self::getInstance()->boot();
    }

    public function boot(): void
    {
        foreach ($this->getActivePlugins() as $pluginName) {
            $this->loadPlugin($pluginName);
        }
    }

    public function getPluginsForAdmin(): array
    {
        $this->syncPluginsTable();
        $rows = DB::query('SELECT slug, is_active FROM plugins ORDER BY slug ASC')->fetchAll();
        $plugins = [];

        foreach ($rows as $row) {
            $slug = (string) ($row['slug'] ?? '');
            $meta = $this->readPluginMetadata($slug);
            $exists = $meta !== null;

            // Перевіряємо чи є у плагіна налаштування
            $hasSettings = false;
            if ($exists) {
                $mainFile = $this->pluginsPath . '/' . $slug . '/plugin.php';
                if (is_file($mainFile)) {
                    $pluginInstance = require $mainFile;
                    if ($pluginInstance instanceof PluginInterface) {
                        $hasSettings = !empty($pluginInstance->getSettingsSchema());
                    }
                }
            }

            $plugins[] = [
                'slug'          => $slug,
                'name'          => $meta['name']         ?? $slug,
                'description'   => $meta['description']  ?? 'Опис відсутній.',
                'version'       => $meta['version']       ?? 'n/a',
                'author'        => $meta['author']        ?? 'Unknown',
                'requires_php'  => $meta['requires_php']  ?? '',
                'requires_core' => $meta['requires_core'] ?? '',
                'requires'      => $meta['requires']      ?? [],
                'is_active'     => (int) ($row['is_active'] ?? 0),
                'is_missing'    => !$exists,
                'has_settings'  => $hasSettings,
            ];
        }

        return $plugins;
    }

    public function togglePlugin(string $slug, bool $activate): array
    {
        $this->syncPluginsTable();
        $plugin = DB::query('SELECT slug FROM plugins WHERE slug = ? LIMIT 1', [$slug])->fetch();

        if (!$plugin) {
            return ['success' => false, 'message' => 'Плагін не знайдено.'];
        }

        if ($activate) {
            $meta = $this->readPluginMetadata($slug);
            if ($meta === null) {
                return ['success' => false, 'message' => 'Папка плагіна або info.json не знайдені.'];
            }

            $compatibilityError = $this->validateCompatibility($meta);
            if ($compatibilityError !== null) {
                return ['success' => false, 'message' => $compatibilityError];
            }

            // Перевірка залежностей
            $depsError = $this->validateDependencies($meta);
            if ($depsError !== null) {
                return ['success' => false, 'message' => $depsError];
            }
        } else {
            // Перевірка зворотних залежностей — чи є активні плагіни що залежать від цього
            $reverseDepsError = $this->validateReverseDependencies($slug);
            if ($reverseDepsError !== null) {
                return ['success' => false, 'message' => $reverseDepsError];
            }
        }

        DB::query('UPDATE plugins SET is_active = ?, updated_at = NOW() WHERE slug = ?', [$activate ? 1 : 0, $slug]);
        $this->clearCache();

        return ['success' => true, 'message' => $activate ? 'Плагін активовано.' : 'Плагін вимкнено.'];
    }

    public function uploadPlugin(array $uploadedFile, int $maxSizeBytes = 10485760): array
    {
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Помилка завантаження файлу.'];
        }

        if (($uploadedFile['size'] ?? 0) > $maxSizeBytes) {
            return ['success' => false, 'message' => 'Файл перевищує допустимий розмір.'];
        }

        $originalName = (string) ($uploadedFile['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            return ['success' => false, 'message' => 'Дозволені тільки ZIP-файли.'];
        }

        $tmpName = (string) ($uploadedFile['tmp_name'] ?? '');

        // MIME-валідація через finfo (захист від підміни розширення)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
        if ($finfo) { finfo_close($finfo); }

        if ($realMime !== 'application/zip' && $realMime !== 'application/x-zip-compressed') {
            return ['success' => false, 'message' => 'Файл не є ZIP-архівом (перевірено за вмістом).'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($tmpName) !== true) {
            return ['success' => false, 'message' => 'Архів пошкоджений або не є ZIP.'];
        }

        $zipError = $this->validateZipEntries($zip);
        if ($zipError !== null) {
            $zip->close();
            return ['success' => false, 'message' => $zipError];
        }

        $extractRoot = sys_get_temp_dir() . '/plugin_upload_' . bin2hex(random_bytes(8));
        mkdir($extractRoot, 0775, true);
        if (!$zip->extractTo($extractRoot)) {
            $zip->close();
            $this->deleteDirectory($extractRoot);
            return ['success' => false, 'message' => 'Не вдалося розпакувати ZIP-файл.'];
        }
        $zip->close();

        $pluginDir = $this->resolvePluginRootDir($extractRoot);
        if ($pluginDir === null) {
            $this->deleteDirectory($extractRoot);
            return ['success' => false, 'message' => 'Не знайдено обов’язкові файли info.json та plugin.php.'];
        }

        $infoPath = $pluginDir . '/info.json';
        $meta = json_decode((string) file_get_contents($infoPath), true);
        if (!is_array($meta) || empty($meta['slug'])) {
            $this->deleteDirectory($extractRoot);
            return ['success' => false, 'message' => 'Некоректний info.json (поле slug обовʼязкове).'];
        }

        $safeSlug = $this->sanitizeSlug((string) $meta['slug']);
        if ($safeSlug === '' || $safeSlug !== (string) $meta['slug']) {
            $this->deleteDirectory($extractRoot);
            return ['success' => false, 'message' => 'Некоректний slug плагіна.'];
        }

        $compatibilityError = $this->validateCompatibility($meta);
        if ($compatibilityError !== null) {
            $this->deleteDirectory($extractRoot);
            return ['success' => false, 'message' => $compatibilityError];
        }

        $targetDir = $this->pluginsPath . '/' . $safeSlug;
        if (is_dir($targetDir)) {
            $this->deleteDirectory($extractRoot);
            return ['success' => false, 'message' => 'Плагін з таким slug вже існує.'];
        }

        if (!rename($pluginDir, $targetDir)) {
            $this->deleteDirectory($extractRoot);
            return ['success' => false, 'message' => 'Не вдалося зберегти плагін.'];
        }
        $this->deleteDirectory($extractRoot);

        $this->syncPluginsTable();
        $this->clearCache();

        return ['success' => true, 'message' => 'Плагін успішно завантажено.'];
    }

    // ── Налаштування плагіна ─────────────────────────────────────────────────

    /**
     * Отримати схему налаштувань + поточні збережені значення для плагіна.
     */
    public function getPluginSettings(string $slug): array
    {
        $plugin = $this->loadedPlugins[$slug] ?? null;

        // Якщо плагін не завантажений (неактивний) — завантажуємо тимчасово
        if ($plugin === null) {
            $mainFile = $this->pluginsPath . '/' . $slug . '/plugin.php';
            if (!is_file($mainFile)) {
                return [];
            }
            $plugin = require $mainFile;
        }

        if (!$plugin instanceof PluginInterface) {
            return [];
        }

        $schema = $plugin->getSettingsSchema();
        if (empty($schema)) {
            return [];
        }

        // Підтягуємо збережені значення з БД
        $saved = DB::query(
            'SELECT `key`, `value` FROM plugin_settings WHERE plugin_slug = ?',
            [$slug]
        )->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Мержимо: schema + saved значення (або default)
        $result = [];
        foreach ($schema as $key => $field) {
            $result[$key] = [
                'label'    => $field['label']    ?? $key,
                'type'     => $field['type']     ?? 'text',
                'default'  => $field['default']  ?? '',
                'options'  => $field['options']  ?? [],
                'required' => $field['required'] ?? false,
                'hint'     => $field['hint']     ?? '',
                'value'    => $saved[$key]       ?? $field['default'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Зберегти налаштування плагіна в plugin_settings.
     */
    public function savePluginSettings(string $slug, array $data): array
    {
        $settings = $this->getPluginSettings($slug);
        if (empty($settings)) {
            return ['success' => false, 'message' => 'Плагін не має налаштувань.'];
        }

        foreach ($settings as $key => $field) {
            if ($field['required'] && empty($data[$key])) {
                return [
                    'success' => false,
                    'message' => "Поле «{$field['label']}» є обов'язковим.",
                ];
            }

            $value = isset($data[$key]) ? (string)$data[$key] : '';

            DB::query(
                "INSERT INTO plugin_settings (plugin_slug, `key`, `value`, updated_at)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE `value` = ?, updated_at = NOW()",
                [$slug, $key, $value, $value]
            );
        }

        return ['success' => true, 'message' => 'Налаштування збережено.'];
    }

    // ── Залежності ───────────────────────────────────────────────────────────

    /**
     * Перевіряє що всі залежні плагіни з info.json.requires є активними.
     *
     * Формат info.json:
     *   "requires": { "other-plugin-slug": ">=1.2.0", "another-plugin": "*" }
     */
    private function validateDependencies(array $meta): ?string
    {
        $requires = $meta['requires'] ?? [];
        if (empty($requires) || !is_array($requires)) {
            return null;
        }

        $activePlugins = $this->getActivePluginsWithVersions();

        foreach ($requires as $depSlug => $versionConstraint) {
            if (!isset($activePlugins[$depSlug])) {
                $depMeta = $this->readPluginMetadata($depSlug);
                $depName = $depMeta['name'] ?? $depSlug;
                return "Залежність не виконана: потрібен активний плагін «{$depName}».";
            }

            // Перевірка версії якщо вказано не '*'
            if ($versionConstraint !== '*') {
                $installedVersion = $activePlugins[$depSlug];
                if (!$this->versionSatisfies($installedVersion, $versionConstraint)) {
                    $depMeta = $this->readPluginMetadata($depSlug);
                    $depName = $depMeta['name'] ?? $depSlug;
                    return "Залежність «{$depName}»: потрібна версія {$versionConstraint}, встановлена {$installedVersion}.";
                }
            }
        }

        return null;
    }

    /**
     * Перевіряє чи немає активних плагінів що залежать від цього (при деактивації).
     */
    private function validateReverseDependencies(string $slug): ?string
    {
        $activePlugins = $this->getActivePluginsFromDatabase();

        foreach ($activePlugins as $activeSlug) {
            if ($activeSlug === $slug) {
                continue;
            }
            $meta     = $this->readPluginMetadata($activeSlug);
            $requires = $meta['requires'] ?? [];
            if (isset($requires[$slug])) {
                $activeName = $meta['name'] ?? $activeSlug;
                return "Не можна вимкнути: плагін «{$activeName}» залежить від цього.";
            }
        }

        return null;
    }

    /**
     * Отримати масив [slug => version] активних плагінів.
     */
    private function getActivePluginsWithVersions(): array
    {
        $rows = DB::query(
            'SELECT slug, version FROM plugins WHERE is_active = 1'
        )->fetchAll(\PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['slug']] = $row['version'];
        }
        return $result;
    }

    /**
     * Перевірити чи версія задовольняє обмеження (>=1.2.0, <=2.0.0, =1.5.0).
     */
    private function versionSatisfies(string $installed, string $constraint): bool
    {
        if (preg_match('/^(>=|<=|>|<|=|!=)\s*(.+)$/', trim($constraint), $m)) {
            $operator        = $m[1];
            $requiredVersion = trim($m[2]);
            return version_compare($installed, $requiredVersion, $operator);
        }
        // Без оператора — точна відповідність
        return version_compare($installed, trim($constraint), '=');
    }

    /**
     * Отримати PluginDB для плагіна (обмежений проксі до БД).
     */
    public function getPluginDB(string $slug): PluginDB
    {
        return new PluginDB($slug);
    }

    public function clearCache(): void
    {
        if (is_file($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
    {
        $this->actions[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        ];

        $this->sortCallbacks($this->actions[$hook]);
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        foreach ($this->actions[$hook] ?? [] as $action) {
            $callArgs = array_slice($args, 0, $action['accepted_args']);
            call_user_func_array($action['callback'], $callArgs);
        }
    }

    public function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 99): void
    {
        $this->filters[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $acceptedArgs,
        ];

        $this->sortCallbacks($this->filters[$hook]);
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $filteredValue = $value;

        foreach ($this->filters[$hook] ?? [] as $filter) {
            $callArgs = array_merge([$filteredValue], array_slice($args, 0, max(0, $filter['accepted_args'] - 1)));
            $filteredValue = call_user_func_array($filter['callback'], $callArgs);
        }

        return $filteredValue;
    }

    private function getActivePlugins(): array
    {
        if (is_file($this->cacheFile)) {
            $cached = json_decode((string) file_get_contents($this->cacheFile), true);
            if (is_array($cached)) {
                return array_values(array_filter($cached, 'is_string'));
            }
        }

        $active = $this->getActivePluginsFromDatabase();
        $this->writeCache($active);

        return $active;
    }

    private function getActivePluginsFromDatabase(): array
    {
        $this->syncPluginsTable();

        $rows = DB::query('SELECT slug FROM plugins WHERE is_active = 1')->fetchAll();
        $result = [];

        foreach ($rows as $row) {
            if (!empty($row['slug']) && is_string($row['slug'])) {
                $result[] = $row['slug'];
            }
        }

        return $result;
    }

    private function syncPluginsTable(): void
    {
        if (!is_dir($this->pluginsPath)) {
            return;
        }

        $pluginDirs = glob($this->pluginsPath . '/*', GLOB_ONLYDIR) ?: [];
        $diskSlugs = [];

        foreach ($pluginDirs as $dir) {
            $slug = basename($dir);
            $diskSlugs[] = $slug;
            $mainFile = $dir . '/plugin.php';
            $meta = $this->readPluginMetadata($slug);
            $name = $meta['name'] ?? $slug;
            $version = $meta['version'] ?? '1.0.0';

            $exists = DB::query('SELECT id FROM plugins WHERE slug = ? LIMIT 1', [$slug])->fetch();
            if (!$exists) {
                DB::query(
                    'INSERT INTO plugins (name, slug, main_file, version, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())',
                    [$name, $slug, $mainFile, $version]
                );
            } else {
                DB::query('UPDATE plugins SET name = ?, main_file = ?, version = ?, updated_at = NOW() WHERE slug = ?', [$name, $mainFile, $version, $slug]);
            }
        }

        $dbRows = DB::query('SELECT slug FROM plugins')->fetchAll();
        foreach ($dbRows as $row) {
            $slug = (string) ($row['slug'] ?? '');
            if ($slug !== '' && !in_array($slug, $diskSlugs, true)) {
                DB::query('DELETE FROM plugins WHERE slug = ?', [$slug]);
            }
        }
    }

    private function readPluginMetadata(string $slug): ?array
    {
        $path = $this->pluginsPath . '/' . $slug . '/info.json';
        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function validateCompatibility(array $meta): ?string
    {
        $requiresPhp = (string) ($meta['requires_php'] ?? '');
        if ($requiresPhp !== '' && !version_compare(PHP_VERSION, $requiresPhp, '>=')) {
            return sprintf('Плагін вимагає PHP %s, у вас %s.', $requiresPhp, PHP_VERSION);
        }

        $requiresCore = (string) ($meta['requires_core'] ?? '');
        $currentCore = defined('CORE_VERSION') ? (string) CORE_VERSION : '0.0.0';
        if ($requiresCore !== '' && !version_compare($currentCore, $requiresCore, '>=')) {
            return sprintf('Плагін вимагає Core %s, у вас %s.', $requiresCore, $currentCore);
        }

        return null;
    }

    private function validateZipEntries(\ZipArchive $zip): ?string
    {
        if ($zip->numFiles < 1) {
            return 'ZIP-архів порожній.';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name)) {
                return 'ZIP-архів містить некоректний шлях.';
            }

            $isDirectory = str_ends_with(str_replace('\\', '/', $name), '/');
            $path = $this->normalizeArchivePath($name);
            if ($path === null) {
                return 'ZIP-архів містить небезпечний шлях.';
            }

            if (!$isDirectory && !$this->hasAllowedUploadExtension($path)) {
                return 'ZIP-архів містить заборонений тип файлу.';
            }
        }

        return null;
    }

    private function normalizeArchivePath(string $path): ?string
    {
        $path = str_replace('\\', '/', $path);
        $path = trim($path);

        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path)) {
            return null;
        }

        $path = rtrim($path, '/');
        if ($path === '') {
            return null;
        }

        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '..') {
                return null;
            }
        }

        return $path;
    }

    private function hasAllowedUploadExtension(string $path): bool
    {
        $basename = basename($path);
        if ($basename === '' || str_starts_with($basename, '.')) {
            return false;
        }

        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        return $extension !== '' && in_array($extension, self::ALLOWED_UPLOAD_EXTENSIONS, true);
    }

    private function resolvePluginRootDir(string $extractRoot): ?string
    {
        $dirs = glob($extractRoot . '/*', GLOB_ONLYDIR) ?: [];
        $candidateDirs = $dirs;

        if (is_file($extractRoot . '/info.json') && is_file($extractRoot . '/plugin.php')) {
            return $extractRoot;
        }

        foreach ($candidateDirs as $dir) {
            if (is_file($dir . '/info.json') && is_file($dir . '/plugin.php')) {
                return $dir;
            }
        }

        return null;
    }

    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';
        return trim($slug, '-');
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function loadPlugin(string $pluginName): void
    {
        $mainFile = $this->pluginsPath . '/' . $pluginName . '/plugin.php';
        if (!is_file($mainFile)) {
            return;
        }

        $plugin = require $mainFile;

        if (!$plugin instanceof PluginInterface) {
            return;
        }

        $plugin->register($this);
        $this->loadedPlugins[$plugin->getName()] = $plugin;
    }

    private function writeCache(array $activePlugins): void
    {
        $directory = dirname($this->cacheFile);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($this->cacheFile, json_encode(array_values($activePlugins), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function sortCallbacks(array &$callbacks): void
    {
        usort($callbacks, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);
    }
}