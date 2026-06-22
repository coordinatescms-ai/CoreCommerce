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
