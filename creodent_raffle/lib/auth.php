<?php
/**
 * Authentication Library
 */

require_once __DIR__ . '/../config.php';

/**
 * Check if admin is authenticated
 */
function isAdminAuthenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

/**
 * Authenticate admin with PIN
 */
function authenticateAdmin($pin) {
    if ($pin === ADMIN_PIN) {
        $_SESSION['admin_authenticated'] = true;
        return true;
    }
    return false;
}

/**
 * Logout admin
 */
function logoutAdmin() {
    unset($_SESSION['admin_authenticated']);
}

/**
 * Require admin authentication
 */
function requireAdmin() {
    if (!isAdminAuthenticated()) {
        if (isAjax()) {
            jsonResponse(['error' => 'Authentication required'], 401);
        }
        redirect('admin.php?action=login');
    }
}

/**
 * Admin login form HTML
 */
function renderAdminLoginForm($error = null) {
    $errorHtml = $error ? '<div class="error-message">' . h($error) . '</div>' : '';

    return <<<HTML
    <div class="login-container">
        <div class="login-box">
            <h2>🔐 Admin Access</h2>
            {$errorHtml}
            <form method="POST" action="admin.php?action=login">
                <div class="form-group">
                    <label for="pin">Enter PIN:</label>
                    <input type="password" id="pin" name="pin" placeholder="••••"
                           maxlength="10" autocomplete="off" autofocus required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </form>
        </div>
    </div>
HTML;
}
