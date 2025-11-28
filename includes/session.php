<?php
/**
 * Session Management
 * 세션 및 인증 관리
 */

session_start();

require_once __DIR__ . '/../classes/User.php';

class Session {
    private static $user = null;

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user
     */
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }

        if (self::$user === null) {
            self::$user = new User();
            if (!self::$user->loadById($_SESSION['user_id'])) {
                self::logout();
                return null;
            }
        }

        return self::$user;
    }

    /**
     * Login user
     */
    public static function login($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Logout user
     */
    public static function logout() {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        self::$user = null;
    }

    /**
     * Get CSRF token
     */
    public static function getCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Require authentication
     */
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * Redirect if authenticated
     */
    public static function redirectIfAuth() {
        if (self::isLoggedIn()) {
            header('Location: /dashboard.php');
            exit;
        }
    }

    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

/**
 * Helper functions
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
