<?php
/**
 * Application Configuration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('America/Los_Angeles');

// Application settings
define('SITE_NAME', 'Dental CRM System');
define('SITE_URL', 'https://yourdomain.com'); // Update with your Hostinger domain

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['csv', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Pagination
define('RECORDS_PER_PAGE', 25);

// Date format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'm/d/Y');
define('DISPLAY_DATETIME_FORMAT', 'm/d/Y h:i A');

// Email settings (for future use)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@yourdomain.com');
define('SMTP_PASS', 'your-email-password');
define('SMTP_FROM', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', SITE_NAME);

// Error reporting
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {
    // Development environment
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    // Production environment
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Auto-logout on session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: /login.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();
?>
