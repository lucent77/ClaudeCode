<?php
/**
 * Simple Router
 *
 * Handles URL routing to controllers and actions
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middleware = [];

    /**
     * Add GET route
     */
    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add route for any method
     */
    public function any(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('*', $path, $handler, $middleware);
    }

    /**
     * Add route to routes array
     */
    private function addRoute(string $method, string $path, $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->convertPathToRegex($path)
        ];
    }

    /**
     * Convert path with parameters to regex pattern
     * Example: /cases/{id} -> /^\/cases\/([^\/]+)$/
     */
    private function convertPathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash except for root
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        foreach ($this->routes as $route) {
            // Check method match
            if ($route['method'] !== '*' && $route['method'] !== $method) {
                continue;
            }

            // Check path match
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches); // Remove full match

                // Execute middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $middleware->handle();
                }

                // Execute handler
                $this->executeHandler($route['handler'], $matches);
                return;
            }
        }

        // No route found
        http_response_code(404);
        $this->render404();
    }

    /**
     * Execute route handler
     */
    private function executeHandler($handler, array $params): void
    {
        if (is_callable($handler)) {
            // Handler is a closure
            call_user_func_array($handler, $params);
        } elseif (is_string($handler)) {
            // Handler is "Controller@method" format
            [$controller, $method] = explode('@', $handler);

            $controllerClass = "App\\Controllers\\{$controller}";

            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller not found: {$controllerClass}");
            }

            $controllerInstance = new $controllerClass();

            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method not found: {$method} in {$controller}");
            }

            call_user_func_array([$controllerInstance, $method], $params);
        } elseif (is_array($handler)) {
            // Handler is [Controller::class, 'method']
            [$controllerClass, $method] = $handler;
            $controllerInstance = new $controllerClass();
            call_user_func_array([$controllerInstance, $method], $params);
        }
    }

    /**
     * Render 404 page
     */
    private function render404(): void
    {
        echo json_encode([
            'success' => false,
            'message' => 'Route not found',
            'code' => 404
        ]);
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    /**
     * Get current URL
     */
    public static function getCurrentUrl(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Get base URL
     */
    public static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return "{$protocol}://{$host}";
    }
}
