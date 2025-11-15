<?php
/**
 * Authentication Handler
 */

require_once __DIR__ . '/../config/config.php';

class Auth {

    /**
     * Authenticate user with username and password
     */
    public static function login($username, $password) {
        try {
            $db = getDB();

            $stmt = $db->prepare("
                SELECT id, username, email, password_hash, role, full_name, is_active
                FROM users
                WHERE username = ? OR email = ?
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is disabled'];
            }

            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();

            // Update last login
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);

            // Log activity
            logActivity($user['id'], 'login', 'user', $user['id'], 'User logged in');

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name']
                ]
            ];

        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }

    /**
     * Logout current user
     */
    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
        }

        session_unset();
        session_destroy();

        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Register new user (admin only)
     */
    public static function register($username, $email, $password, $role = 'worker', $fullName = null) {
        try {
            $db = getDB();

            // Check if username or email already exists
            $checkStmt = $db->prepare("
                SELECT COUNT(*) as count FROM users
                WHERE username = ? OR email = ?
            ");
            $checkStmt->execute([$username, $email]);
            $result = $checkStmt->fetch();

            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }

            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, role, full_name)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $email, $passwordHash, $role, $fullName]);

            $userId = $db->lastInsertId();

            // Log activity
            if (isset($_SESSION['user_id'])) {
                logActivity($_SESSION['user_id'], 'create_user', 'user', $userId, "Created user: $username");
            }

            return [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $userId
            ];

        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Change user password
     */
    public static function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $db = getDB();

            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            // Update password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->execute([$newPasswordHash, $userId]);

            logActivity($userId, 'change_password', 'user', $userId, 'Password changed');

            return ['success' => true, 'message' => 'Password changed successfully'];

        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }

    /**
     * Get all users (admin only)
     */
    public static function getAllUsers() {
        try {
            $db = getDB();
            $stmt = $db->query("
                SELECT id, username, email, role, full_name, is_active, created_at, last_login
                FROM users
                ORDER BY created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update user status (admin only)
     */
    public static function updateUserStatus($userId, $isActive) {
        try {
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive ? 1 : 0, $userId]);

            if (isset($_SESSION['user_id'])) {
                $action = $isActive ? 'activated' : 'deactivated';
                logActivity($_SESSION['user_id'], 'update_user_status', 'user', $userId, "User $action");
            }

            return ['success' => true, 'message' => 'User status updated'];
        } catch (PDOException $e) {
            error_log("Update user status error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update user status'];
        }
    }

    /**
     * Check if session is still valid
     */
    public static function validateSession() {
        if (!isLoggedIn()) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            if ($elapsed > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
        }

        return true;
    }
}
