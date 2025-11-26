<?php
/**
 * Magic Rx Scanner - CSRF Protection Helper
 *
 * Session-based CSRF token generation and validation
 */

class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $tokenExpiry = 3600; // 1 hour

    /**
     * Initialize session if not started
     */
    private static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Generate a new CSRF token
     *
     * @return string Generated token
     */
    public static function generateToken() {
        self::initSession();

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$tokenName] = $token;
        $_SESSION[self::$tokenName . '_time'] = time();

        return $token;
    }

    /**
     * Get current CSRF token (generate if doesn't exist)
     *
     * @return string Current token
     */
    public static function getToken() {
        self::initSession();

        // Check if token exists and is not expired
        if (isset($_SESSION[self::$tokenName]) && isset($_SESSION[self::$tokenName . '_time'])) {
            $tokenAge = time() - $_SESSION[self::$tokenName . '_time'];

            if ($tokenAge < self::$tokenExpiry) {
                return $_SESSION[self::$tokenName];
            }
        }

        // Generate new token if expired or doesn't exist
        return self::generateToken();
    }

    /**
     * Validate CSRF token
     *
     * @param string|null $token Token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token = null) {
        self::initSession();

        // If no token provided, try to get from various sources
        if ($token === null) {
            // Try POST data
            if (isset($_POST[self::$tokenName])) {
                $token = $_POST[self::$tokenName];
            }
            // Try headers
            elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            }
            // Try JSON body
            else {
                $input = json_decode(file_get_contents('php://input'), true);
                if (isset($input[self::$tokenName])) {
                    $token = $input[self::$tokenName];
                }
            }
        }

        // Check if session token exists
        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }

        // Check if token matches
        if (!hash_equals($_SESSION[self::$tokenName], $token)) {
            return false;
        }

        // Check if token is not expired
        if (isset($_SESSION[self::$tokenName . '_time'])) {
            $tokenAge = time() - $_SESSION[self::$tokenName . '_time'];
            if ($tokenAge >= self::$tokenExpiry) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate CSRF token or send error response
     *
     * @param bool $jsonResponse Send JSON error response if validation fails
     * @return bool True if valid
     */
    public static function validateOrDie($jsonResponse = true) {
        if (!self::validateToken()) {
            if ($jsonResponse) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'CSRF token validation failed',
                    'error' => 'CSRF_INVALID'
                ]);
                exit;
            } else {
                http_response_code(403);
                die('CSRF token validation failed');
            }
        }
        return true;
    }

    /**
     * Clear CSRF token from session
     */
    public static function clearToken() {
        self::initSession();
        unset($_SESSION[self::$tokenName]);
        unset($_SESSION[self::$tokenName . '_time']);
    }

    /**
     * Get token name for use in forms/headers
     *
     * @return string Token name
     */
    public static function getTokenName() {
        return self::$tokenName;
    }
}
