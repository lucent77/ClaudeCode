<?php
/**
 * Base Controller
 *
 * All controllers extend this base class
 */

namespace App\Core;

abstract class Controller
{
    protected Database $db;
    protected array $config;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../../config/config.php';
        Session::start();
    }

    /**
     * Render view
     */
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewPath = __DIR__ . '/../../views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }

        require $viewPath;
    }

    /**
     * Return JSON response
     */
    protected function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Success JSON response
     */
    protected function success($data = null, string $message = '', int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Error JSON response
     */
    protected function error(string $message, $errors = null, int $statusCode = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        Router::redirect($url);
    }

    /**
     * Get request input
     */
    protected function input(string $key = null, $default = null)
    {
        $input = $this->getAllInput();

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    /**
     * Get all request input
     */
    protected function getAllInput(): array
    {
        $input = [];

        // GET parameters
        $input = array_merge($input, $_GET);

        // POST parameters
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (strpos($contentType, 'application/json') !== false) {
                $json = file_get_contents('php://input');
                $input = array_merge($input, json_decode($json, true) ?? []);
            } else {
                $input = array_merge($input, $_POST);
            }
        }

        return $input;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $this->input('csrf_token') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$token || !Session::verifyCsrfToken($token)) {
            $this->error('Invalid CSRF token', null, 403);
            return false;
        }

        return true;
    }

    /**
     * Check if user is authenticated
     */
    protected function requireAuth(): void
    {
        if (!Session::isLoggedIn()) {
            if ($this->isAjaxRequest()) {
                $this->error('Unauthorized', null, 401);
            } else {
                $this->redirect('/login');
            }
        }
    }

    /**
     * Check if user has required role
     */
    protected function requireRole(array $roles): void
    {
        $this->requireAuth();

        if (!Session::hasAnyRole($roles)) {
            $this->error('Forbidden: Insufficient permissions', null, 403);
        }
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get current user
     */
    protected function getUser(): ?array
    {
        return Session::getUser();
    }

    /**
     * Get current user ID
     */
    protected function getUserId(): ?int
    {
        return Session::getUserId();
    }

    /**
     * Validate input data
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $error = $this->validateField($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Validate single field
     */
    private function validateField(string $field, $value, string $rule): ?string
    {
        [$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return ucfirst($field) . ' is required';
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ucfirst($field) . ' must be a valid email';
                }
                break;

            case 'min':
                if (strlen($value) < (int)$ruleParam) {
                    return ucfirst($field) . " must be at least {$ruleParam} characters";
                }
                break;

            case 'max':
                if (strlen($value) > (int)$ruleParam) {
                    return ucfirst($field) . " must not exceed {$ruleParam} characters";
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return ucfirst($field) . ' must be numeric';
                }
                break;

            case 'in':
                $allowed = explode(',', $ruleParam);
                if ($value && !in_array($value, $allowed)) {
                    return ucfirst($field) . ' must be one of: ' . implode(', ', $allowed);
                }
                break;
        }

        return null;
    }

    /**
     * Log action to audit log
     */
    protected function logAudit(int $caseId, string $action, string $description, ?array $before = null, ?array $after = null): void
    {
        $this->db->insert('case_audit_logs', [
            'case_id' => $caseId,
            'user_id' => $this->getUserId(),
            'action' => $action,
            'description' => $description,
            'before_json' => $before ? json_encode($before) : null,
            'after_json' => $after ? json_encode($after) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
