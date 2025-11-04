<?php

namespace App\Core;

/**
 * Router Class
 *
 * Handles URL routing, parameter extraction, and controller dispatching
 */
class Router
{
    private array $routes = [];
    private array $namedRoutes = [];

    /**
     * Add a GET route
     */
    public function get(string $path, string $handler, string $name = null): void
    {
        $this->addRoute('GET', $path, $handler, $name);
    }

    /**
     * Add a POST route
     */
    public function post(string $path, string $handler, string $name = null): void
    {
        $this->addRoute('POST', $path, $handler, $name);
    }

    /**
     * Add a PUT route
     */
    public function put(string $path, string $handler, string $name = null): void
    {
        $this->addRoute('PUT', $path, $handler, $name);
    }

    /**
     * Add a DELETE route
     */
    public function delete(string $path, string $handler, string $name = null): void
    {
        $this->addRoute('DELETE', $path, $handler, $name);
    }

    /**
     * Add a route for any method
     */
    public function any(string $path, string $handler, string $name = null): void
    {
        $this->addRoute('*', $path, $handler, $name);
    }

    /**
     * Add route to collection
     */
    private function addRoute(string $method, string $path, string $handler, ?string $name): void
    {
        // Convert route pattern to regex
        $pattern = $this->convertToRegex($path);

        $route = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
        }
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex(string $path): string
    {
        // Escape forward slashes
        $pattern = preg_replace('#/#', '\\/', $path);

        // Convert {param} to named capture groups
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);

        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch the request to appropriate controller
     */
    public function dispatch(string $url): void
    {
        // Remove query string if present
        $url = strtok($url, '?');

        // Remove trailing slash (except for root)
        if ($url !== '/' && str_ends_with($url, '/')) {
            $url = rtrim($url, '/');
        }

        // Ensure URL starts with /
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Check for _method override (for PUT/DELETE via POST)
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $requestMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $url, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Execute handler
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // No route found - 404
        $this->notFound();
    }

    /**
     * Execute route handler
     */
    private function executeHandler(string $handler, array $params): void
    {
        // Parse handler string (ControllerName@methodName)
        if (!str_contains($handler, '@')) {
            throw new \Exception("Invalid handler format: {$handler}");
        }

        [$controllerName, $methodName] = explode('@', $handler, 2);

        // Build full controller class name
        $controllerClass = "App\\Controllers\\{$controllerName}";

        // Check if controller exists
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller not found: {$controllerClass}");
        }

        // Instantiate controller
        $controller = new $controllerClass();

        // Check if method exists
        if (!method_exists($controller, $methodName)) {
            throw new \Exception("Method {$methodName} not found in {$controllerClass}");
        }

        // Call method with parameters
        call_user_func_array([$controller, $methodName], $params);
    }

    /**
     * Generate URL for named route
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Named route not found: {$name}");
        }

        $path = $this->namedRoutes[$name]['path'];

        // Replace parameters
        foreach ($params as $key => $value) {
            $path = str_replace("{{$key}}", $value, $path);
        }

        return $path;
    }

    /**
     * 404 Not Found handler
     */
    private function notFound(): void
    {
        http_response_code(404);

        // Check if it's an API request
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($requestUri, '/api/')) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint not found',
                'status' => 404
            ]);
            exit;
        }

        // HTML 404 page
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .container {
            text-align: center;
            padding: 2rem;
        }
        h1 {
            font-size: 8rem;
            margin: 0;
            font-weight: bold;
        }
        p {
            font-size: 1.5rem;
            margin: 1rem 0;
        }
        a {
            color: white;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Page Not Found</p>
        <p><a href="/">Return to Home</a></p>
    </div>
</body>
</html>';
        exit;
    }
}
