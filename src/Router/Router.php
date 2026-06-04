<?php

declare(strict_types=1);

namespace App\Router;

final class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];
    private array $routeMiddleware = [];

    public function addGlobalMiddleware(callable $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function addRouteMiddleware(string $name, callable $middleware): void
    {
        $this->routeMiddleware[$name] = $middleware;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function addRoute(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($this->globalMiddleware as $mw) {
                    $result = $mw();
                    if ($result !== null) {
                        return;
                    }
                }

                foreach ($route['middleware'] as $mwName) {
                    if (isset($this->routeMiddleware[$mwName])) {
                        $result = ($this->routeMiddleware[$mwName])();
                        if ($result !== null) {
                            return;
                        }
                    }
                }

                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    $controller->$method($params);
                } elseif (is_callable($handler)) {
                    $handler($params);
                }

                return;
            }
        }

        http_response_code(404);
        $this->renderError(404, 'Not Found');
    }

    private function renderError(int $code, string $message): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $isApi = str_starts_with($uri, '/v1/');

        if ($isApi || str_starts_with((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'message' => $message,
                    'type' => 'not_found_error',
                    'code' => $code,
                ],
            ]);
        } else {
            $viewPath = dirname(__DIR__, 2) . '/views/errors/' . $code . '.php';
            if (file_exists($viewPath)) {
                require $viewPath;
            } else {
                echo "<h1>{$code} - {$message}</h1>";
            }
        }
        exit;
    }
}
