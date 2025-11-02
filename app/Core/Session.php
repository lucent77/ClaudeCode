<?php
/**
 * Session Management
 *
 * Handles user sessions with database storage
 */

namespace App\Core;

class Session
{
    private static bool $started = false;
    private static ?Database $db = null;

    /**
     * Start session
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        $config = require __DIR__ . '/../../config/config.php';

        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.gc_maxlifetime', $config['app']['session_lifetime']);

        session_start();
        self::$started = true;
        self::$db = Database::getInstance();

        // Update session activity in database
        if (self::has('user_id')) {
            self::updateActivity();
        }
    }

    /**
     * Set session variable
     */
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get session variable
     */
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session variable exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session variable
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data
     */
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    /**
     * Destroy session
     */
    public static function destroy(): void
    {
        self::start();

        // Remove session from database
        if (self::has('user_id')) {
            self::$db->delete('sessions', ['id' => session_id()]);
        }

        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
        self::$started = false;
    }

    /**
     * Regenerate session ID (for security after login)
     */
    public static function regenerate(): void
    {
        self::start();
        $oldSessionId = session_id();
        session_regenerate_id(true);
        $newSessionId = session_id();

        // Update session ID in database
        if (self::has('user_id')) {
            self::$db->execute(
                "UPDATE sessions SET id = ? WHERE id = ?",
                [$newSessionId, $oldSessionId]
            );
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return self::has('user_id');
    }

    /**
     * Get logged in user ID
     */
    public static function getUserId(): ?int
    {
        return self::get('user_id');
    }

    /**
     * Get logged in user data
     */
    public static function getUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        $userId = self::getUserId();
        return self::$db->queryOne(
            "SELECT u.*, d.name as department_name, d.code as department_code
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.id = ?",
            [$userId]
        );
    }

    /**
     * Set user session after login
     */
    public static function setUser(array $user): void
    {
        self::set('user_id', $user['id']);
        self::set('username', $user['username']);
        self::set('role', $user['role']);
        self::set('department_id', $user['department_id']);

        // Save session to database
        self::saveToDatabase($user['id']);
    }

    /**
     * Save session to database
     */
    private static function saveToDatabase(int $userId): void
    {
        $sessionData = [
            'id' => session_id(),
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'payload' => json_encode($_SESSION),
            'last_activity' => date('Y-m-d H:i:s'),
        ];

        // Use INSERT ... ON DUPLICATE KEY UPDATE
        self::$db->execute(
            "INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             payload = VALUES(payload),
             last_activity = VALUES(last_activity)",
            array_values($sessionData)
        );
    }

    /**
     * Update session activity
     */
    private static function updateActivity(): void
    {
        self::$db->execute(
            "UPDATE sessions SET last_activity = ? WHERE id = ?",
            [date('Y-m-d H:i:s'), session_id()]
        );
    }

    /**
     * Clean old sessions (can be called by cron)
     */
    public static function cleanOldSessions(int $maxLifetime = 7200): void
    {
        self::$db->execute(
            "DELETE FROM sessions WHERE last_activity < ?",
            [date('Y-m-d H:i:s', time() - $maxLifetime)]
        );
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        self::start();

        if (!self::has('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            self::set('csrf_token', $token);
        }

        return self::get('csrf_token');
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        self::start();
        $sessionToken = self::get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Flash message (one-time message)
     */
    public static function flash(string $key, $value = null)
    {
        self::start();

        if ($value === null) {
            // Get flash message
            $message = self::get("flash_{$key}");
            self::remove("flash_{$key}");
            return $message;
        } else {
            // Set flash message
            self::set("flash_{$key}", $value);
        }
    }

    /**
     * Check user role
     */
    public static function hasRole(string $role): bool
    {
        return self::get('role') === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        $userRole = self::get('role');
        return in_array($userRole, $roles);
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        return self::hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Check if user is super admin
     */
    public static function isSuperAdmin(): bool
    {
        return self::hasRole('super_admin');
    }
}
