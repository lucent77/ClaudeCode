<?php
/**
 * Authentication Functions
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

/**
 * Require specific role - redirect if user doesn't have it
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /index.php?error=access_denied');
        exit;
    }
}

/**
 * Require any of specified roles
 */
function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        header('Location: /index.php?error=access_denied');
        exit;
    }
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $db = getDB();

    try {
        $stmt = $db->prepare("
            SELECT user_id, username, password_hash, email, first_name, last_name, role, is_active
            FROM users
            WHERE username = ? AND is_active = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // Update last login
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);

            // Log the login
            logAction('login', 'users', $user['user_id'], 'User logged in');

            return true;
        }

        return false;
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        logAction('logout', 'users', $_SESSION['user_id'], 'User logged out');
    }

    session_unset();
    session_destroy();
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user full name
 */
function getCurrentUserName() {
    return $_SESSION['full_name'] ?? 'Unknown User';
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if current user is manager
 */
function isManager() {
    return hasRole('manager');
}

/**
 * Check if current user is sales rep
 */
function isSalesRep() {
    return hasRole('sales_rep');
}

/**
 * Log system action
 */
function logAction($action, $table_name = null, $record_id = null, $description = null) {
    $db = getDB();

    try {
        $stmt = $db->prepare("
            INSERT INTO system_logs (user_id, action, table_name, record_id, description, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $userId = getCurrentUserId();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

        $stmt->execute([
            $userId,
            $action,
            $table_name,
            $record_id,
            $description,
            $ipAddress
        ]);
    } catch (PDOException $e) {
        error_log("Log Action Error: " . $e->getMessage());
    }
}
?>
