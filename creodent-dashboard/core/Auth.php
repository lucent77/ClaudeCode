<?php
/**
 * Creodent Dashboard - Authentication Handler
 */

namespace Creodent\Core;

class Auth
{
    /**
     * Attempt login with credentials
     */
    public static function attempt(string $username, string $password): bool
    {
        try {
            $sql = "SELECT id, username, password_hash, role, is_active
                    FROM users
                    WHERE username = ? AND is_active = 1";

            $users = Database::query($sql, [$username], 'nyc');

            if (empty($users)) {
                return false;
            }

            $user = $users[0];

            if (!password_verify($password, $user['password_hash'])) {
                return false;
            }

            // Update last login
            Database::execute(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']],
                'nyc'
            );

            // Set session
            Session::regenerate();
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('role', $user['role']);
            Session::set('logged_in', true);

            return true;
        } catch (\Exception $e) {
            error_log('Auth error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        return Session::get('logged_in', false) === true;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    /**
     * Get current username
     */
    public static function username(): ?string
    {
        return Session::get('username');
    }

    /**
     * Get current user role
     */
    public static function role(): ?string
    {
        return Session::get('role');
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /**
     * Check if user has required role
     */
    public static function hasRole(array $roles): bool
    {
        $userRole = self::role();
        return $userRole && in_array($userRole, $roles);
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        Session::destroy();
    }

    /**
     * Require authentication - redirect if not logged in
     */
    public static function require(): void
    {
        if (!self::check()) {
            header('Location: index.php');
            exit;
        }
    }

    /**
     * Require specific role
     */
    public static function requireRole(array $roles): void
    {
        self::require();

        if (!self::hasRole($roles)) {
            header('Location: pages/dashboard.php');
            exit;
        }
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Create new user
     */
    public static function createUser(string $username, string $password, string $role = 'viewer'): int
    {
        $hash = self::hashPassword($password);

        Database::execute(
            "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)",
            [$username, $hash, $role],
            'nyc'
        );

        return (int) Database::lastInsertId('nyc');
    }

    /**
     * Get CSRF token
     */
    public static function csrfToken(): string
    {
        $token = Session::get('csrf_token');

        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set('csrf_token', $token);
        }

        return $token;
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrf(string $token): bool
    {
        $sessionToken = Session::get('csrf_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    /**
     * Generate CSRF input field
     */
    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token) . '">';
    }
}
