<?php

namespace App\Core\Routing;

class Router
{
    protected array $routes = [];

    public function get(string $uri, array $action): void
    {
        $this->routes[] = ['GET', $uri, $action];
    }

    public function post(string $uri, array $action): void
    {
        $this->routes[] = ['POST', $uri, $action];
    }

    public function delete(string $uri, array $action): void
    {
        $this->routes[] = ['DELETE', $uri, $action];
    }

    private function matchRoute(string $pattern, string $uri, array &$params): bool
    {
        // Підтримка {param:regex} — напр. {path:.+} для вкладених ЧПУ
        $pattern = preg_replace_callback(
            '#\{([^}]+)\}#',
            function (array $m): string {
                if (str_contains($m[1], ':')) {
                    [, $regex] = explode(':', $m[1], 2);
                    return '(' . $regex . ')';
                }
                return '([^/]+)';
            },
            $pattern
        );
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            $params = $matches;
            return true;
        }

        return false;
    }

    /**
     * Обслуговує файли плагінів за URL /plugins/{slug}/{...path}.
     *
     * Дозволяє плагінам мати власні AJAX-ендпоінти, admin-сторінки та
     * статичні ассети (css/js/зображення) без реєстрації окремого
     * маршруту в routes/web.php для кожного плагіна.
     *
     * Безпека:
     *  - жорсткий захист від directory traversal (".." заборонено);
     *  - службові файли плагіна (info.json, plugin.php) ніколи не віддаються;
     *  - .php файли виконуються ЛИШЕ для активних плагінів;
     *  - статичні файли віддаються тільки з білого списку розширень
     *    з коректним Content-Type.
     */
    private function servePluginFile(string $uri): bool
    {
        $prefix = '/plugins/';
        if (!str_starts_with($uri, $prefix)) {
            return false;
        }

        $relative = trim(substr($uri, strlen($prefix)), '/');
        if ($relative === '') {
            return false;
        }

        $segments = explode('/', $relative);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        $slug = $segments[0];
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            return false;
        }

        $pluginManager = \App\Core\Plugin\PluginManager::getInstance();
        $pluginsRoot = realpath($pluginManager->getPluginsPath());
        if ($pluginsRoot === false) {
            return false;
        }

        $filePath = $pluginsRoot . '/' . $relative;
        $realFilePath = realpath($filePath);

        // Файл має реально існувати всередині pluginsRoot (захист від symlink-виходу за межі теки)
        if ($realFilePath === false || !str_starts_with($realFilePath, $pluginsRoot . DIRECTORY_SEPARATOR)) {
            return false;
        }

        if (!is_file($realFilePath)) {
            return false;
        }

        $basename = basename($realFilePath);
        if (in_array($basename, ['info.json', 'plugin.php'], true)) {
            return false;
        }

        // Файли всередині теки lang/ (переклади) не віддаються напряму
        if (in_array('lang', $segments, true)) {
            return false;
        }

        $extension = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));

        if ($extension === 'php') {
            if (!$pluginManager->isPluginActive($slug)) {
                return false;
            }
            require $realFilePath;
            return true;
        }

        $mimeTypes = [
            'css'  => 'text/css; charset=utf-8',
            'js'   => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'ico'  => 'image/x-icon',
            'txt'  => 'text/plain; charset=utf-8',
            'md'   => 'text/plain; charset=utf-8',
        ];

        if (!isset($mimeTypes[$extension])) {
            return false;
        }

        header('Content-Type: ' . $mimeTypes[$extension]);
        header('Cache-Control: public, max-age=3600');
        readfile($realFilePath);
        return true;
    }

    /**
     * Обслуговує файли тем за URL /resources/themes/{theme}/{...path}.
     *
     * Оскільки Document Root вказує на /public, а файли тем знаходяться
     * в /resources/themes, Apache перенаправляє запити до них на index.php.
     * Цей метод дозволяє віддавати статичні файли тем з коректним Content-Type.
     */
    private function serveThemeFile(string $uri): bool
    {
        $prefix = '/resources/themes/';
        if (!str_starts_with($uri, $prefix)) {
            return false;
        }

        $relative = trim(substr($uri, strlen($prefix)), '/');
        if ($relative === '') {
            return false;
        }

        $segments = explode('/', $relative);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        $themesRoot = realpath(dirname(__DIR__, 3) . '/resources/themes');
        if ($themesRoot === false) {
            return false;
        }

        $filePath = $themesRoot . '/' . $relative;
        $realFilePath = realpath($filePath);

        // Захист від виходу за межі директорії тем
        if ($realFilePath === false || !str_starts_with($realFilePath, $themesRoot . DIRECTORY_SEPARATOR)) {
            return false;
        }

        if (!is_file($realFilePath)) {
            return false;
        }

        $extension = strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));

        // Забороняємо прямий доступ до PHP файлів тем через цей механізм
        if ($extension === 'php') {
            return false;
        }

        $mimeTypes = [
            'css'  => 'text/css; charset=utf-8',
            'js'   => 'application/javascript; charset=utf-8',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'ico'  => 'image/x-icon',
            'svg'  => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf',
            'eot'  => 'application/vnd.ms-fontobject',
        ];

        if (!isset($mimeTypes[$extension])) {
            return false;
        }

        header('Content-Type: ' . $mimeTypes[$extension]);
        header('Cache-Control: public, max-age=86400'); // Кешування на добу
        readfile($realFilePath);
        return true;
    }

    public function dispatch(string $method, string $uri): void
    {
        foreach ($this->routes as [$routeMethod, $routeUri, $action]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $params = [];
            if ($this->matchRoute($routeUri, $uri, $params)) {
                $controller = new $action[0]();
                call_user_func_array([$controller, $action[1]], $params);
                return;
            }
        }

        // Фолбек: власні файли плагінів на /plugins/{slug}/...
        // (AJAX-ендпоінти, admin-сторінки, статичні css/js/картинки плагінів).
        // web_root вказує на /public, а фізично плагіни лежать поза ним
        // (/plugins), тому без цього фолбеку будь-який запит до файлу
        // плагіна (напр. /plugins/CallbackWidget/callback.php) повертав 404.
        if ($this->servePluginFile($uri)) {
            return;
        }

        // Фолбек для статичних файлів тем
        if ($this->serveThemeFile($uri)) {
            return;
        }

        // Маршрут не знайдено — повертаємо справжній HTTP 404
        http_response_code(404);
        $viewPath = dirname(__DIR__, 3) . '/resources/views/errors/404.php';
        if (is_file($viewPath)) {
            require $viewPath;
        } else {
            echo '<h1>404 — Сторінку не знайдено</h1>';
        }
    }
}
