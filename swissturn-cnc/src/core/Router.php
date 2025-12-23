<?php

namespace SwissTurn\Core;

/**
 * Simple URL Router
 */
class Router
{
    private array $routes = [];
    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
    }

    /**
     * Register a GET route
     */
    public function get(string $path, callable|array $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable|array $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable|array $handler): self
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable|array $handler): self
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    /**
     * Add route to registry
     */
    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $pattern = $this->convertToPattern($path);
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertToPattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $this->basePath . $pattern . '$#';
    }

    /**
     * Dispatch request to matching route
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Handle PUT/DELETE via POST with _method field
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // No route matched
        Response::notFound('Route not found: ' . $uri);
    }

    /**
     * Execute route handler
     */
    private function executeHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
        } else {
            call_user_func_array($handler, $params);
        }
    }
}
