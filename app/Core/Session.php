<?php

namespace App\Core;

/**
 * Session Class
 *
 * Manages user sessions with security features
 * - CSRF token generation and validation
 * - Session hijacking prevention
 * - Secure session configuration
 */
class Session
{
    private static bool $started = false;
    private static ?array $config = null;

    /**
     * Start session with secure configuration
     */
    public static function start(array $config = []): void
    {
        if (self::$started) {
            return;
        }

        self::$config = $config;

        // Secure session configuration
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');

        // Use secure cookies if HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }

        // Set session lifetime
        $lifetime = $config['session_lifetime'] ?? 7200;
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        session_set_cookie_params($lifetime);

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::$started = true;

        // Initialize session security
        self::initializeSecurity();
    }

    /**
     * Initialize session security measures
     */
    private static function initializeSecurity(): void
    {
        // Check for session hijacking
        if (!self::has('_fingerprint')) {
            self::regenerateFingerprint();
        } else {
            $currentFingerprint = self::generateFingerprint();
            if (!hash_equals(self::get('_fingerprint'), $currentFingerprint)) {
                self::destroy();
                throw new \Exception('Session hijacking detected');
            }
        }

        // Regenerate session ID periodically
        $regenerateInterval = self::$config['session_regenerate_interval'] ?? 1800;
        if (!self::has('_last_regeneration')) {
            self::set('_last_regeneration', time());
            session_regenerate_id(true);
        } elseif (time() - self::get('_last_regeneration') > $regenerateInterval) {
            self::set('_last_regeneration', time());
            session_regenerate_id(true);
        }
    }

    /**
     * Generate session fingerprint
     */
    private static function generateFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'unknown';

        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }

    /**
     * Regenerate session fingerprint
     */
    private static function regenerateFingerprint(): void
    {
        self::set('_fingerprint', self::generateFingerprint());
    }

    /**
     * Set session value
     */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public static function all(): array
    {
        return $_SESSION ?? [];
    }

    /**
     * Clear all session data
     */
    public static function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * Destroy session completely
     */
    public static function destroy(): void
    {
        if (self::$started) {
            $_SESSION = [];

            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
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
            self::$started = false;
        }
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!self::has('_csrf_token')) {
            self::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('_csrf_token');
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token): bool
    {
        if (!self::has('_csrf_token')) {
            return false;
        }

        return hash_equals(self::get('_csrf_token'), $token);
    }

    /**
     * Flash message (store for one request)
     */
    public static function flash(string $key, $value): void
    {
        self::set('_flash_' . $key, $value);
    }

    /**
     * Get flash message
     */
    public static function getFlash(string $key, $default = null)
    {
        $value = self::get('_flash_' . $key, $default);
        self::remove('_flash_' . $key);
        return $value;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::has('user_id') && self::get('user_id') !== null;
    }

    /**
     * Get current user ID
     */
    public static function getUserId(): ?int
    {
        return self::get('user_id');
    }

    /**
     * Get current user data
     */
    public static function getUser(): ?array
    {
        return self::get('user');
    }

    /**
     * Set logged-in user
     */
    public static function setUser(array $user): void
    {
        self::set('user_id', $user['id']);
        self::set('user', $user);
        self::set('logged_in_at', time());

        // Regenerate session ID on login
        session_regenerate_id(true);
        self::regenerateFingerprint();
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        self::remove('user_id');
        self::remove('user');
        self::remove('logged_in_at');

        // Keep flash messages
        $flashData = [];
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, '_flash_')) {
                $flashData[$key] = $value;
            }
        }

        self::clear();

        // Restore flash messages
        foreach ($flashData as $key => $value) {
            $_SESSION[$key] = $value;
        }

        session_regenerate_id(true);
    }

    /**
     * Check if user has role
     */
    public static function hasRole(string $role): bool
    {
        $user = self::getUser();
        return $user && ($user['role'] ?? null) === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        $user = self::getUser();
        if (!$user) {
            return false;
        }

        return in_array($user['role'] ?? null, $roles, true);
    }

    /**
     * Get user role
     */
    public static function getUserRole(): ?string
    {
        $user = self::getUser();
        return $user['role'] ?? null;
    }

    /**
     * Get user department
     */
    public static function getUserDepartment(): ?int
    {
        $user = self::getUser();
        return $user['department_id'] ?? null;
    }
}
