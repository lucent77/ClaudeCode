<?php

namespace App\Core;

/**
 * Base Controller Class
 *
 * All controllers extend this class
 * Provides common functionality:
 * - JSON response methods
 * - Authentication checks
 * - CSRF validation
 * - View rendering
 */
class Controller
{
    protected Database $db;
    protected array $config;

    public function __construct()
    {
        // Get database instance
        $this->db = Database::getInstance();

        // Load configuration
        $this->config = require BASE_PATH . '/config/config.php';

        // Ensure session is started
        if (!Session::isLoggedIn() && !$this->isPublicRoute()) {
            $this->redirectToLogin();
        }
    }

    /**
     * Check if current route is public (doesn't require auth)
     */
    protected function isPublicRoute(): bool
    {
        $publicRoutes = ['/login', '/api/import/'];
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';

        foreach ($publicRoutes as $route) {
            if (str_contains($currentUri, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require authentication (redirect to login if not authenticated)
     */
    protected function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirectToLogin();
        }
    }

    /**
     * Require specific role
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();

        if (!Session::hasRole($role)) {
            $this->forbidden('You do not have permission to access this resource');
        }
    }

    /**
     * Require any of the given roles
     */
    protected function requireAnyRole(array $roles): void
    {
        $this->requireAuth();

        if (!Session::hasAnyRole($roles)) {
            $this->forbidden('You do not have permission to access this resource');
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? null;

        if (!$token || !Session::validateCsrfToken($token)) {
            $this->error('Invalid CSRF token', 403);
        }
    }

    /**
     * Redirect to login page
     */
    protected function redirectToLogin(): void
    {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /login?return=' . urlencode($returnUrl));
        exit;
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Render view
     */
    protected function view(string $view, array $data = []): void
    {
        // Extract data to variables
        extract($data);

        // Start output buffering
        ob_start();

        // Include view file
        $viewPath = BASE_PATH . "/views/{$view}.php";

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }

        include $viewPath;

        // Get content
        $content = ob_get_clean();

        // If view uses layout, wrap content
        if (!isset($noLayout) || !$noLayout) {
            include BASE_PATH . '/views/layout.php';
        } else {
            echo $content;
        }
    }

    /**
     * Send JSON response
     */
    protected function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send success JSON response
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): void
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->json($response, $statusCode);
    }

    /**
     * Send error JSON response
     */
    protected function error(string $message = 'An error occurred', int $statusCode = 400, $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
            'status' => $statusCode,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // Add debug info if in debug mode
        if ($this->config['app']['debug'] ?? false) {
            $response['debug'] = [
                'file' => debug_backtrace()[0]['file'] ?? 'unknown',
                'line' => debug_backtrace()[0]['line'] ?? 0,
            ];
        }

        $this->json($response, $statusCode);
    }

    /**
     * 403 Forbidden response
     */
    protected function forbidden(string $message = 'Access forbidden'): void
    {
        // Check if API request
        if ($this->isApiRequest()) {
            $this->error($message, 403);
        }

        // HTML response
        http_response_code(403);
        $this->view('errors/403', ['message' => $message]);
        exit;
    }

    /**
     * 404 Not Found response
     */
    protected function notFound(string $message = 'Resource not found'): void
    {
        if ($this->isApiRequest()) {
            $this->error($message, 404);
        }

        http_response_code(404);
        $this->view('errors/404', ['message' => $message]);
        exit;
    }

    /**
     * Check if request is API request
     */
    protected function isApiRequest(): bool
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with($requestUri, '/api/');
    }

    /**
     * Get request input data
     */
    protected function input(string $key = null, $default = null)
    {
        // Get JSON input if content type is JSON
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $jsonData = json_decode(file_get_contents('php://input'), true);

            if ($key === null) {
                return $jsonData ?? [];
            }

            return $jsonData[$key] ?? $default;
        }

        // Get POST/GET data
        $data = array_merge($_GET, $_POST);

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * Validate required fields
     */
    protected function validate(array $rules): array
    {
        $errors = [];
        $data = $this->input();

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesArray = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($rulesArray as $rule) {
                // Parse rule (e.g., "min:8")
                $ruleName = $rule;
                $ruleValue = null;

                if (str_contains($rule, ':')) {
                    [$ruleName, $ruleValue] = explode(':', $rule, 2);
                }

                // Apply validation rule
                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field][] = ucfirst($field) . ' is required';
                        }
                        break;

                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = ucfirst($field) . ' must be a valid email';
                        }
                        break;

                    case 'min':
                        if (strlen($value ?? '') < (int)$ruleValue) {
                            $errors[$field][] = ucfirst($field) . " must be at least {$ruleValue} characters";
                        }
                        break;

                    case 'max':
                        if (strlen($value ?? '') > (int)$ruleValue) {
                            $errors[$field][] = ucfirst($field) . " must not exceed {$ruleValue} characters";
                        }
                        break;

                    case 'numeric':
                        if ($value && !is_numeric($value)) {
                            $errors[$field][] = ucfirst($field) . ' must be a number';
                        }
                        break;

                    case 'alpha':
                        if ($value && !ctype_alpha($value)) {
                            $errors[$field][] = ucfirst($field) . ' must contain only letters';
                        }
                        break;

                    case 'alphanumeric':
                        if ($value && !ctype_alnum($value)) {
                            $errors[$field][] = ucfirst($field) . ' must contain only letters and numbers';
                        }
                        break;
                }
            }
        }

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
        }

        return $data;
    }

    /**
     * Log message
     */
    protected function log(string $message, string $level = 'info'): void
    {
        $logConfig = $this->config['logging'] ?? [];

        if (!($logConfig['enabled'] ?? true)) {
            return;
        }

        $logFile = BASE_PATH . '/storage/logs/app_' . date($logConfig['filename_format'] ?? 'Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $userId = Session::getUserId() ?? 'guest';
        $logMessage = "[{$timestamp}] [{$level}] [User:{$userId}] {$message}" . PHP_EOL;

        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
