<?php
/**
 * Authentication Handler
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Auth {
    private $db;
    private static $currentUser = null;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->initSession();
    }

    /**
     * Initialize secure session
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/app.php';
            session_name($config['session']['name']);

            session_set_cookie_params([
                'lifetime' => $config['session']['lifetime'],
                'path' => $config['session']['path'],
                'secure' => $config['session']['secure'],
                'httponly' => $config['session']['httponly'],
                'samesite' => $config['session']['samesite']
            ]);

            session_start();
        }
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        $email = Security::sanitizeEmail($email);

        $sql = "SELECT u.*, r.name as role_name, d.code as department_code
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id
                LEFT JOIN departments d ON u.department_id = d.id
                WHERE u.email = ? AND u.is_active = 1
                LIMIT 1";

        $user = $this->db->queryOne($sql, [$email]);

        if (!$user || !Security::verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Update last login
        $this->db->execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );

        // Set session data
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['department_code'] = $user['department_code'];
        $_SESSION['logged_in'] = true;

        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout user
     */
    public function logout() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        if (self::$currentUser === null) {
            $sql = "SELECT u.*, r.name as role_name, d.name as department_name, d.code as department_code
                    FROM users u
                    INNER JOIN roles r ON u.role_id = r.id
                    LEFT JOIN departments d ON u.department_id = d.id
                    WHERE u.id = ?
                    LIMIT 1";

            self::$currentUser = $this->db->queryOne($sql, [$_SESSION['user_id']]);
        }

        return self::$currentUser;
    }

    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user has role
     */
    public function hasRole($roleName) {
        return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $roleName;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is front desk
     */
    public function isFrontDesk() {
        return $this->hasRole('front_desk') || $this->isAdmin();
    }

    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Require admin role
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: /index.php');
            exit;
        }
    }

    /**
     * Check if user can access department
     */
    public function canAccessDepartment($departmentId) {
        // Admin and Front Desk can access all departments
        if ($this->isAdmin() || $this->isFrontDesk()) {
            return true;
        }

        // Department users can only access their own department
        return isset($_SESSION['department_id']) && $_SESSION['department_id'] == $departmentId;
    }

    /**
     * Get user's department ID
     */
    public function getDepartmentId() {
        return $_SESSION['department_id'] ?? null;
    }

    /**
     * Register new user (admin only)
     */
    public function register($name, $email, $password, $roleId, $departmentId = null) {
        $name = Security::sanitizeString($name);
        $email = Security::sanitizeEmail($email);

        if (!Security::validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }

        // Check if email already exists
        $existing = $this->db->queryOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $passwordHash = Security::hashPassword($password);

        $sql = "INSERT INTO users (name, email, password_hash, role_id, department_id)
                VALUES (?, ?, ?, ?, ?)";

        try {
            $this->db->execute($sql, [$name, $email, $passwordHash, $roleId, $departmentId]);
            return ['success' => true, 'message' => 'User registered successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }
}
