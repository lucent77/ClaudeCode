<?php
/**
 * Authentication and Authorization Manager
 */

class Auth
{
    private static ?Auth $instance = null;
    private ?array $user = null;
    private Database $db;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->startSession();
        $this->loadUser();
    }

    public static function getInstance(): Auth
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'secure' => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY,
                'samesite' => 'Strict'
            ]);
            session_start();
        }

        // Regenerate session ID periodically for security
        if (!isset($_SESSION['_last_regenerate'])) {
            $_SESSION['_last_regenerate'] = time();
        } elseif (time() - $_SESSION['_last_regenerate'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = time();
        }
    }

    private function loadUser(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ? AND is_active = 1",
                [$_SESSION['user_id']]
            );

            if (!$this->user) {
                $this->logout();
            }
        }
    }

    /**
     * Attempt to log in a user
     */
    public function login(string $username, string $password): bool
    {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
            [$username, $username]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['_created'] = time();

        // Regenerate session ID on login
        session_regenerate_id(true);

        $this->user = $user;

        // Log the login
        $this->logAudit('users', $user['id'], 'LOGIN');

        return true;
    }

    /**
     * Log out the current user
     */
    public function logout(): void
    {
        if ($this->user) {
            $this->logAudit('users', $this->user['id'], 'LOGOUT');
        }

        $this->user = null;
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return $this->user !== null;
    }

    /**
     * Get current user data
     */
    public function user(): ?array
    {
        return $this->user;
    }

    /**
     * Get current user ID
     */
    public function userId(): ?int
    {
        return $this->user['id'] ?? null;
    }

    /**
     * Get current user's initials
     */
    public function userInitials(): ?string
    {
        return $this->user['initials'] ?? null;
    }

    /**
     * Get current user's role
     */
    public function userRole(): ?string
    {
        return $this->user['role'] ?? null;
    }

    /**
     * Get current user's department
     */
    public function userDepartment(): ?string
    {
        return $this->user['department'] ?? null;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->user['role'] === ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is department manager
     */
    public function isDepartmentManager(): bool
    {
        return $this->user['role'] === ROLE_DEPT_MANAGER || $this->isSuperAdmin();
    }

    /**
     * Check if user is operator
     */
    public function isOperator(): bool
    {
        return $this->user['role'] === ROLE_OPERATOR;
    }

    /**
     * Check if user has access to a specific department
     */
    public function hasAccessToDepartment(string $department): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->user['department'] === 'ALL') {
            return true;
        }

        return $this->user['department'] === $department;
    }

    /**
     * Check if user can work on a specific step
     */
    public function canWorkOnStep(string $department, string $stepCode): bool
    {
        if ($this->isSuperAdmin() || $this->isDepartmentManager()) {
            return $this->hasAccessToDepartment($department);
        }

        if (!$this->hasAccessToDepartment($department)) {
            return false;
        }

        $assignedSteps = json_decode($this->user['assigned_steps'] ?? '[]', true);
        return empty($assignedSteps) || in_array($stepCode, $assignedSteps);
    }

    /**
     * Get user's assigned steps
     */
    public function getAssignedSteps(): array
    {
        return json_decode($this->user['assigned_steps'] ?? '[]', true) ?: [];
    }

    /**
     * Require authentication - redirect if not logged in
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }

    /**
     * Require specific role
     */
    public function requireRole(string ...$roles): void
    {
        $this->requireLogin();

        if (!in_array($this->user['role'], $roles)) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * Require department access
     */
    public function requireDepartment(string $department): void
    {
        $this->requireLogin();

        if (!$this->hasAccessToDepartment($department)) {
            http_response_code(403);
            include APP_ROOT . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Validate CSRF token
     */
    public function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Require valid CSRF token (for POST requests)
     */
    public function requireCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$this->validateCsrfToken($token)) {
                http_response_code(403);
                if ($this->isAjaxRequest()) {
                    echo json_encode(['error' => 'Invalid CSRF token']);
                    exit;
                }
                die('Invalid CSRF token');
            }
        }
    }

    /**
     * Check if current request is AJAX
     */
    public function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Hash a password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): int
    {
        $data['password_hash'] = $this->hashPassword($data['password']);
        unset($data['password']);

        return $this->db->insert('users', $data);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        return $this->db->update(
            'users',
            ['password_hash' => $this->hashPassword($newPassword)],
            ['id' => $userId]
        ) > 0;
    }

    /**
     * Log audit event
     */
    private function logAudit(string $table, int $recordId, string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            $this->db->insert('audit_log', [
                'table_name' => $table,
                'record_id' => $recordId,
                'action' => $action,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'user_id' => $this->userId(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Don't let audit logging failures break the application
            error_log('Audit log error: ' . $e->getMessage());
        }
    }

    /**
     * Get redirect URL after login
     */
    public function getRedirectAfterLogin(): string
    {
        $redirect = $_SESSION['redirect_after_login'] ?? null;
        unset($_SESSION['redirect_after_login']);

        if ($redirect && strpos($redirect, '/') === 0) {
            return APP_URL . $redirect;
        }

        // Default redirects based on role
        if ($this->isSuperAdmin()) {
            return APP_URL . '/admin/dashboard.php';
        }

        if ($this->isDepartmentManager()) {
            return APP_URL . '/department/' . strtolower(str_replace('_', '-', $this->userDepartment())) . '/dashboard.php';
        }

        return APP_URL . '/operator/tasks.php';
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception('Cannot unserialize singleton'); }
}
