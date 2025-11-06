<?php
/**
 * Configuration File
 * Purchase Management System
 *
 * Update these values with your Hostinger database credentials
 */

// Database Configuration
define('DB_HOST', 'localhost');           // Hostinger MySQL host (usually localhost)
define('DB_USER', 'your_db_username');    // Your database username
define('DB_PASS', 'your_db_password');    // Your database password
define('DB_NAME', 'purchase_management'); // Your database name

// Application Configuration
define('APP_NAME', 'Purchase Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://yourdomain.com'); // Update with your domain

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour in seconds

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting (Set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security
define('CSRF_TOKEN_LENGTH', 32);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Email Configuration (Optional - for notifications)
define('SMTP_ENABLED', false);
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@yourdomain.com');
define('SMTP_PASS', 'your_email_password');
define('SMTP_FROM', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'Purchase System');

?>
