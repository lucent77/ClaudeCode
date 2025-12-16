<?php
/**
 * Router Class
 *
 * Simple HTTP router for API endpoints
 */

class Router
{
    private $routes = [];
    private $middleware = [];
    private $notFoundHandler = null;

    /**
     * Add GET route
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Add POST route
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * Add PUT route
     */
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }

    /**
     * Add DELETE route
     */
    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    /**
     * Add PATCH route
     */
    public function patch($path, $handler)
    {
        $this->addRoute('PATCH', $path, $handler);
        return $this;
    }

    /**
     * Add route
     */
    private function addRoute($method, $path, $handler)
    {
        // Convert path parameters to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * Add middleware
     */
    public function middleware($callback)
    {
        $this->middleware[] = $callback;
        return $this;
    }

    /**
     * Set 404 handler
     */
    public function notFound($handler)
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Get request method
     */
    private function getMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Support method override via header or POST parameter
        if ($method === 'POST') {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } elseif (isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
            }
        }

        return $method;
    }

    /**
     * Get request path
     */
    private function getPath()
    {
        $path = $_SERVER['REQUEST_URI'];

        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        // Remove trailing slash (except for root)
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Get request data
     */
    public static function getRequestData()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        $data = [];

        // GET parameters
        $data = array_merge($data, $_GET);

        // POST parameters
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            // JSON body
            if (strpos($contentType, 'application/json') !== false) {
                $json = file_get_contents('php://input');
                $jsonData = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $data = array_merge($data, $jsonData);
                }
            }
            // Form data
            elseif (strpos($contentType, 'multipart/form-data') !== false ||
                    strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                $data = array_merge($data, $_POST);
            }
            // Raw body
            else {
                $input = file_get_contents('php://input');
                if (!empty($input)) {
                    parse_str($input, $parsed);
                    $data = array_merge($data, $parsed);
                }
            }
        }

        return $data;
    }

    /**
     * Get uploaded files
     */
    public static function getFiles()
    {
        return $_FILES;
    }

    /**
     * Get request headers
     */
    public static function getHeaders()
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get authorization header
     */
    public static function getAuthorizationHeader()
    {
        $headers = self::getHeaders();
        return $headers['Authorization'] ?? null;
    }

    /**
     * Get bearer token from authorization header
     */
    public static function getBearerToken()
    {
        $header = self::getAuthorizationHeader();
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Send JSON response
     */
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send error response
     */
    public static function error($message, $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'error' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    /**
     * Send success response
     */
    public static function success($data = null, $message = null, $statusCode = 200)
    {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response, $statusCode);
    }

    /**
     * Run the router
     */
    public function run()
    {
        $method = $this->getMethod();
        $path = $this->getPath();

        // Run middleware
        foreach ($this->middleware as $middleware) {
            $result = call_user_func($middleware, $method, $path);
            if ($result === false) {
                return;
            }
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Call handler
                $handler = $route['handler'];

                if (is_callable($handler)) {
                    call_user_func($handler, $params);
                } elseif (is_string($handler) && strpos($handler, '@') !== false) {
                    // Controller@method format
                    list($controller, $action) = explode('@', $handler);
                    $controllerClass = $controller;

                    if (!class_exists($controllerClass)) {
                        self::error("Controller not found: {$controllerClass}", 500);
                        return;
                    }

                    $instance = new $controllerClass();

                    if (!method_exists($instance, $action)) {
                        self::error("Method not found: {$action}", 500);
                        return;
                    }

                    call_user_func([$instance, $action], $params);
                } elseif (is_array($handler) && count($handler) === 2) {
                    // [Controller::class, 'method'] format
                    list($controller, $action) = $handler;

                    if (is_string($controller)) {
                        $instance = new $controller();
                    } else {
                        $instance = $controller;
                    }

                    call_user_func([$instance, $action], $params);
                }

                return;
            }
        }

        // No route found
        if ($this->notFoundHandler) {
            call_user_func($this->notFoundHandler);
        } else {
            self::error('Endpoint not found', 404);
        }
    }
}
