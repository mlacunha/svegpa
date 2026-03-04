<?php
declare(strict_types=1);

class Router {
    private array $routes = [];
    private string $path;
    private string $method;
    private array $params = [];

    public function __construct() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = rawurldecode($uri);
        $base = defined('API_BASE_PATH') ? rtrim(API_BASE_PATH, '/') : '';
        if ($base && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $this->path = rtrim($path, '/') ?: '/';
        $this->method = $_SERVER['REQUEST_METHOD'];
    }

    public function get(string $pattern, callable $handler): void {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void {
        $this->add('DELETE', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch(): void {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) continue;
            if ($this->match($route['pattern'])) {
                call_user_func_array($route['handler'], $this->params);
                return;
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Rota não encontrada']);
    }

    private function match(string $pattern): bool {
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (preg_match($regex, $this->path, $m)) {
            array_shift($m);
            $this->params = $m;
            return true;
        }
        return false;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function getParam(int $index): ?string {
        return $this->params[$index] ?? null;
    }
}
