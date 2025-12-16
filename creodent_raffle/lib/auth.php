<?php
/**
 * CREODENT HV Raffle 2025 - Authentication
 */

require_once __DIR__ . '/../config.php';

/**
 * Check if user is authenticated as admin
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Authenticate admin with PIN
 */
function authenticateAdmin($pin) {
    if ($pin === ADMIN_PIN) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_login_time'] = time();
        return true;
    }
    return false;
}

/**
 * Logout admin
 */
function logoutAdmin() {
    unset($_SESSION['is_admin']);
    unset($_SESSION['admin_login_time']);
}

/**
 * Require admin authentication
 * Redirects to admin login if not authenticated
 */
function requireAdmin() {
    if (!isAdmin()) {
        if (isAjax()) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }
        redirect('admin.php?action=login');
    }
}

/**
 * Set current employee in session
 */
function setCurrentEmployee($employeeId) {
    $_SESSION['current_employee'] = $employeeId;
}

/**
 * Get current employee from session
 */
function getCurrentEmployee() {
    return isset($_SESSION['current_employee']) ? $_SESSION['current_employee'] : null;
}

/**
 * Clear current employee from session
 */
function clearCurrentEmployee() {
    unset($_SESSION['current_employee']);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . h(generateCSRFToken()) . '">';
}
