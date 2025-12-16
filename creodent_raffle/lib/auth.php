<?php
/**
 * Authentication Library
 * Simple PIN-based authentication for admin access
 */

require_once __DIR__ . '/../config.php';

/**
 * Check if current session is authenticated as admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Authenticate with admin PIN
 */
function authenticateAdmin($pin) {
    if ($pin === ADMIN_PIN) {
        $_SESSION['is_admin'] = true;
        return true;
    }
    return false;
}

/**
 * Logout admin session
 */
function logoutAdmin() {
    $_SESSION['is_admin'] = false;
    unset($_SESSION['is_admin']);
}

/**
 * Require admin authentication for a page/action
 */
function requireAdmin() {
    if (!isAdmin()) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        } else {
            header('Location: admin.php?login=1');
            exit;
        }
    }
}
