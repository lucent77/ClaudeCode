<?php
/**
 * Authentication Controller
 *
 * Handles user authentication and session management
 */

class AuthController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * User login
     * POST /api/auth/login
     */
    public function login()
    {
        $data = Router::getRequestData();

        // Validate required fields
        if (empty($data['username']) || empty($data['password'])) {
            Router::error('Username and password are required', 400);
            return;
        }

        $username = trim($data['username']);
        $password = $data['password'];

        // Find user by username
        $user = $this->db->fetch(
            "SELECT id, username, email, password_hash, role, is_active, slack_member_id
             FROM users
             WHERE username = ?",
            [$username]
        );

        if (!$user) {
            Router::error('Invalid username or password', 401);
            return;
        }

        // Check if user is active
        if (!$user['is_active']) {
            Router::error('Account is disabled. Please contact administrator.', 403);
            return;
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            Router::error('Invalid username or password', 401);
            return;
        }

        // Update last login timestamp
        $this->db->update(
            "UPDATE users SET last_login_at = NOW() WHERE id = ?",
            [$user['id']]
        );

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        // Remove sensitive data
        unset($user['password_hash']);

        Router::success([
            'user' => $user,
            'session_id' => session_id(),
        ], 'Login successful');
    }

    /**
     * User logout
     * POST /api/auth/logout
     */
    public function logout()
    {
        // Clear session
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();

        Router::success(null, 'Logout successful');
    }

    /**
     * Get current user
     * GET /api/auth/me
     */
    public function me()
    {
        if (!$this->isAuthenticated()) {
            Router::error('Not authenticated', 401);
            return;
        }

        $userId = $_SESSION['user_id'];

        $user = $this->db->fetch(
            "SELECT id, username, email, role, slack_member_id, is_active, last_login_at, created_at
             FROM users
             WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            Router::error('User not found', 404);
            return;
        }

        Router::success(['user' => $user]);
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated()
    {
        return isset($_SESSION['logged_in']) &&
               $_SESSION['logged_in'] === true &&
               isset($_SESSION['user_id']);
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        return self::isAuthenticated() &&
               isset($_SESSION['role']) &&
               $_SESSION['role'] === 'Admin';
    }

    /**
     * Get current user ID
     */
    public static function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Require authentication (middleware helper)
     */
    public static function requireAuth()
    {
        if (!self::isAuthenticated()) {
            Router::error('Authentication required', 401);
            exit;
        }
    }

    /**
     * Require admin role (middleware helper)
     */
    public static function requireAdmin()
    {
        self::requireAuth();

        if (!self::isAdmin()) {
            Router::error('Admin access required', 403);
            exit;
        }
    }
}
