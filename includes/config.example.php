<?php
/**
 * Magic Rx Scanner - Configuration File (Example)
 *
 * Copy this file to config.php and update with your actual settings
 *
 * IMPORTANT: Never commit config.php with real credentials to Git!
 */

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'magic_rx_scanner');
define('DB_USER', 'your_db_username');        // ← UPDATE THIS
define('DB_PASS', 'your_db_password');        // ← UPDATE THIS
define('DB_CHARSET', 'utf8mb4');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Upload Directories (relative to project root)
define('UPLOAD_DIR', __DIR__ . '/../uploads/documents/');
define('TEMPLATE_DIR', __DIR__ . '/../uploads/templates/');
define('PROCESSED_DIR', __DIR__ . '/../uploads/processed/');

// Google Cloud Configuration
define('GOOGLE_APPLICATION_CREDENTIALS', __DIR__ . '/../credentials/google-service-account.json');
define('GOOGLE_PROJECT_ID', 'your-project-id');           // ← UPDATE THIS
define('GOOGLE_LOCATION', 'us');                          // ← UPDATE THIS (us, eu, asia-northeast1, etc.)
define('GOOGLE_PROCESSOR_ID', 'your-processor-id');       // ← UPDATE THIS

// Google Cloud API Endpoints
define('DOCUMENT_AI_ENDPOINT', 'https://' . GOOGLE_LOCATION . '-documentai.googleapis.com/v1');
define('VISION_API_ENDPOINT', 'https://vision.googleapis.com/v1');

// Application Settings
define('APP_NAME', 'Magic Rx Scanner');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Seoul');  // ← UPDATE THIS to your timezone

// Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');
define('ENABLE_CSRF_PROTECTION', false); // Set to true to enable CSRF protection (recommended for production)

// Image Processing Settings
define('IMAGE_MAX_WIDTH', 2000);
define('IMAGE_MAX_HEIGHT', 2000);
define('IMAGE_QUALITY', 85);

// Logging
define('LOG_DIR', __DIR__ . '/../logs/');
define('LOG_FILE', LOG_DIR . 'app.log');
define('ENABLE_LOGGING', true);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Create required directories if they don't exist
$required_dirs = [UPLOAD_DIR, TEMPLATE_DIR, PROCESSED_DIR, LOG_DIR];
foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
