<?php
/**
 * Creodent Dashboard - Session Management
 */

namespace Creodent\Core;

class Session
{
    private static bool $started = false;

    /**
     * Start session with secure settings
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        // Configure session before starting
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }

        session_name(SESSION_NAME);
        session_start();
        self::$started = true;

        // Check session timeout
        self::checkTimeout();
    }

    /**
     * Check and handle session timeout
     */
    private static function checkTimeout(): void
    {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive > SESSION_TIMEOUT) {
                self::destroy();
                return;
            }
        }
        $_SESSION['last_activity'] = time();
    }

    /**
     * Set session value
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Regenerate session ID (for security)
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Destroy session completely
     */
    public static function destroy(): void
    {
        self::start();

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
        self::$started = false;
    }

    /**
     * Flash message - set
     */
    public static function flash(string $key, mixed $value): void
    {
        self::set('_flash.' . $key, $value);
    }

    /**
     * Flash message - get and remove
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = self::get('_flash.' . $key, $default);
        self::remove('_flash.' . $key);
        return $value;
    }

    /**
     * Cache data in session with TTL
     */
    public static function cache(string $key, callable $callback, int $ttl = CACHE_TTL_SHORT): mixed
    {
        self::start();

        $cacheKey = 'cache.' . $key;
        $cached = $_SESSION[$cacheKey] ?? null;

        if ($cached && isset($cached['expires']) && $cached['expires'] > time()) {
            return $cached['data'];
        }

        $data = $callback();
        $_SESSION[$cacheKey] = [
            'data' => $data,
            'expires' => time() + $ttl
        ];

        return $data;
    }

    /**
     * Clear cache
     */
    public static function clearCache(?string $key = null): void
    {
        self::start();

        if ($key) {
            unset($_SESSION['cache.' . $key]);
        } else {
            foreach (array_keys($_SESSION) as $sessionKey) {
                if (str_starts_with($sessionKey, 'cache.')) {
                    unset($_SESSION[$sessionKey]);
                }
            }
        }
    }
}
