<?php
/**
 * Simple Router
 */

declare(strict_types=1);

namespace App\Middleware;

use PDO;

class Router
{
    private ?PDO $db;
    private array $routes;
    private array $params = [];

    public function __construct(?PDO $db)
    {
        $this->db = $db;
        $this->routes = require BASE_PATH . '/config/routes.php';
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Find matching route
        foreach ($this->routes as $route) {
            [$routeMethod, $routePath, $controller, $action] = $route;

            if ($method !== $routeMethod) {
                continue;
            }

            if ($this->matchRoute($routePath, $uri)) {
                $this->executeController($controller, $action);
                return;
            }
        }

        // No route found
        $this->sendNotFound();
    }

    private function matchRoute(string $routePath, string $uri): bool
    {
        // Convert route path to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    private function executeController(string $controller, string $action): void
    {
        // Build full controller class name
        $controllerClass = 'App\\Controllers\\' . str_replace('/', '\\', $controller);

        if (!class_exists($controllerClass)) {
            $this->sendNotFound("Controller not found: {$controllerClass}");
            return;
        }

        $instance = new $controllerClass($this->db);

        if (!method_exists($instance, $action)) {
            $this->sendNotFound("Action not found: {$action}");
            return;
        }

        // Call the action with parameters
        call_user_func_array([$instance, $action], $this->params);
    }

    private function sendNotFound(string $message = 'Page not found'): void
    {
        http_response_code(404);

        if ($this->isJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
        } else {
            require BASE_PATH . '/src/Views/errors/404.php';
        }
    }

    private function isJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') ||
            str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
