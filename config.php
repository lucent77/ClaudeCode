<?php
/**
 * Scanbody File Manager Configuration
 *
 * IMPORTANT: Update these values according to your Hostinger setup
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'scanbody_manager');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Scanbody File Manager');
define('APP_VERSION', '1.0.0');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB in bytes
define('ALLOWED_EXTENSIONS', ['stl', 'STL']);

// URL Settings (Update this to your actual domain)
define('BASE_URL', 'http://localhost');
define('UPLOAD_URL', BASE_URL . '/uploads/');

// Timezone
date_default_timezone_set('Asia/Seoul');

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Helper function to sanitize input
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Helper function to generate unique filename
function generate_unique_filename($original_filename) {
    $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    return uniqid('scanbody_', true) . '.' . $extension;
}
