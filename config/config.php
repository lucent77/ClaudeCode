<?php
/**
 * CAD/CAM Workflow System - Main Configuration
 *
 * Copy this file to config.local.php and update with your settings
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Application Settings
define('APP_NAME', 'CAD/CAM Workflow System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV === 'development');

// Base URL (auto-detect or set manually)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('APP_URL', $protocol . '://' . $host . $basePath);

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'cadcam_workflow');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

// Session Configuration
define('SESSION_NAME', 'cadcam_session');
define('SESSION_LIFETIME', 60 * 60 * 8); // 8 hours
define('SESSION_SECURE', $protocol === 'https');
define('SESSION_HTTPONLY', true);

// Security
define('CSRF_TOKEN_NAME', '_csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Google API Configuration (optional - leave empty if not using)
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', APP_URL . '/auth/google-callback.php');
define('GOOGLE_DRIVE_ENABLED', !empty(GOOGLE_CLIENT_ID));
define('GMAIL_ENABLED', !empty(GOOGLE_CLIENT_ID));

// Google Drive folder structure template
define('DRIVE_BASE_FOLDER', getenv('DRIVE_BASE_FOLDER') ?: '');
define('DRIVE_COCR_FOLDER', getenv('DRIVE_COCR_FOLDER') ?: '');
define('DRIVE_SOLIDEX_FOLDER', getenv('DRIVE_SOLIDEX_FOLDER') ?: '');
define('DRIVE_3DPRINT_FOLDER', getenv('DRIVE_3DPRINT_FOLDER') ?: '');

// Pagination
define('ITEMS_PER_PAGE', 25);
define('MAX_ITEMS_PER_PAGE', 100);

// Date/Time formats
define('DATE_FORMAT', 'm/d/Y');
define('DATETIME_FORMAT', 'm/d/Y h:i A');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Timezone
date_default_timezone_set('America/New_York');

// File upload settings
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', ['stl', 'obj', 'ply', 'dcm', 'zip', 'rar', 'pdf', 'jpg', 'jpeg', 'png', 'gif']);

// Workflow step codes (for reference)
define('COCR_STEPS', ['TRANS', 'DESIGN', 'CAM', 'CNC', 'OVENS']);
define('SOLIDEX_STEPS', ['TRANSCAN', 'PRECAD', 'CAD', 'PRECAM', 'CNC', 'QC']);
define('PRINT_STEPS', ['TRANSSCAN', 'PRECAD', 'DESIGN', 'NESTING']);

// Department codes
define('DEPT_COCR', 'COCR');
define('DEPT_SOLIDEX', 'SOLIDEX');
define('DEPT_3D_PRINT', '3D_PRINT');
define('ALL_DEPARTMENTS', [DEPT_COCR, DEPT_SOLIDEX, DEPT_3D_PRINT]);

// User roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_DEPT_MANAGER', 'department_manager');
define('ROLE_OPERATOR', 'operator');

// Status codes
define('STATUS_ACTIVE', 'ACTIVE');
define('STATUS_ON_HOLD', 'ON_HOLD');
define('STATUS_COMPLETED', 'COMPLETED');
define('STATUS_CANCELLED', 'CANCELLED');

// Design confirm status
define('DESIGN_CONFIRM_NOT_REQUIRED', 'NOT_REQUIRED');
define('DESIGN_CONFIRM_PENDING', 'PENDING');
define('DESIGN_CONFIRM_SENT', 'SENT');
define('DESIGN_CONFIRM_APPROVED', 'APPROVED');
define('DESIGN_CONFIRM_CHANGES', 'CHANGES_REQUESTED');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// Include local config overrides if exists
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}
