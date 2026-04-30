<?php

namespace App\Core\Plugin;

use App\Core\Database\DB;

class PluginManager
{
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

        foreach ($pluginDirs as $dir) {
            $slug = basename($dir);
            $mainFile = $dir . '/plugin.php';

            $exists = DB::query('SELECT id FROM plugins WHERE slug = ? LIMIT 1', [$slug])->fetch();
            if (!$exists) {
                DB::query(
                    'INSERT INTO plugins (name, slug, main_file, is_active, created_at, updated_at) VALUES (?, ?, ?, 0, NOW(), NOW())',
                    [$slug, $slug, $mainFile]
                );
            }
        }
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
