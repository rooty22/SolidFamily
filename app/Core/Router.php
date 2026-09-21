<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $prevPrefix = $this->groupPrefix;
        $prevMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $prevPrefix . $prefix;
        $this->groupMiddleware = array_merge($prevMiddleware, $middleware);

        $callback($this);

        $this->groupPrefix = $prevPrefix;
        $this->groupMiddleware = $prevMiddleware;
    }

    private function add(string $method, string $path, $handler, array $middleware): void
    {
        $fullPath = rtrim($this->groupPrefix . $path, '/');
        $fullPath = $fullPath === '' ? '/' : $fullPath;

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'pattern' => $this->toPattern($fullPath),
        ];
    }

    private function toPattern(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#u';
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $basePath = base_path();
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        // 1. If accessed with /public in the path, 301 redirect to clean direct URL
        if ($uri === '/public' || str_starts_with($uri, '/public/')) {
            $cleanPath = ltrim(substr($uri, 7), '/');
            $query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
            $redirectUrl = url($cleanPath) . $query;
            header('Location: ' . $redirectUrl, true, 301);
            exit;
        }

        // 2. If accessed through index.php directly, strip it
        if ($uri === '/index.php' || str_starts_with($uri, '/index.php/')) {
            $uri = substr($uri, 10);
        }

        $uri = rtrim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);

                foreach ($route['middleware'] as $middleware) {
                    $result = call_user_func($middleware);
                    if ($result === false) {
                        return;
                    }
                }

                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        view('errors/404');
    }

    private function callHandler($handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
            return;
        }

        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        }
    }
}
