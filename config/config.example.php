<?php
/**
 * Creodent AoX Elevate Dashboard
 * Configuration File Template
 *
 * Copy this file to config.php and fill in your actual credentials
 */

// ============================================
// Database Configuration
// ============================================
define('DB_HOST', 'localhost');           // Your MySQL host (usually localhost on Hostinger)
define('DB_NAME', 'your_database_name');  // Your database name
define('DB_USER', 'your_database_user');  // Your database username
define('DB_PASS', 'your_database_pass');  // Your database password
define('DB_CHARSET', 'utf8mb4');

// ============================================
// Slack API Configuration
// ============================================
define('SLACK_BOT_TOKEN', 'xoxb-your-slack-bot-token');     // Slack Bot User OAuth Token
define('SLACK_WORKSPACE_ID', 'YOUR_WORKSPACE_ID');          // Your Slack Workspace ID (optional)
define('SLACK_CHANNEL_ID', 'YOUR_CHANNEL_ID');              // Primary channel for file sync (optional)

// ============================================
// Application Settings
// ============================================
define('APP_NAME', 'Creodent AoX Elevate Dashboard');
define('APP_URL', 'https://yourdomain.com');               // Your application URL
define('APP_ENV', 'production');                           // production or development
define('APP_DEBUG', false);                                // Set to true for debugging

// ============================================
// File Upload Settings
// ============================================
define('UPLOAD_DIR', __DIR__ . '/../uploads/');            // Local file storage directory
define('MAX_UPLOAD_SIZE', 100 * 1024 * 1024);              // 100MB max file size
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf,stl,dcm,zip,rar,obj,ply,3ds');

// ============================================
// Sync Settings
// ============================================
define('SYNC_ENABLED', true);                              // Enable/disable automatic sync
define('SYNC_INTERVAL', 300);                              // Sync interval in seconds (300 = 5 minutes)
define('SYNC_BATCH_SIZE', 100);                            // Number of files to process per sync batch

// ============================================
// Timezone
// ============================================
define('TIMEZONE', 'America/New_York');                    // Your timezone
date_default_timezone_set(TIMEZONE);

// ============================================
// Error Reporting
// ============================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================
// Session Configuration
// ============================================
define('SESSION_LIFETIME', 86400);                         // 24 hours

// ============================================
// API Rate Limiting
// ============================================
define('API_RATE_LIMIT', 100);                             // Max API calls per minute
define('SLACK_API_RATE_LIMIT', 50);                        // Slack API calls per minute

// ============================================
// File Type Classification Patterns
// ============================================
define('FILE_PATTERNS', [
    'photo' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'heic'],
        'keywords' => ['photo', 'img_', 'image', 'face', 'intraoral', 'extraoral']
    ],
    'stl' => [
        'extensions' => ['stl', 'obj', 'ply'],
        'keywords' => ['stl', 'scan', 'model', '3d']
    ],
    'preop_scan' => [
        'extensions' => ['stl', 'obj', 'ply', 'dcm'],
        'keywords' => ['preop', 'pre-op', 'pre_op', 'initial', 'before']
    ],
    'postop_scan' => [
        'extensions' => ['stl', 'obj', 'ply', 'dcm'],
        'keywords' => ['postop', 'post-op', 'post_op', 'final', 'after']
    ],
    'preop_cbct' => [
        'extensions' => ['dcm', 'zip', 'rar'],
        'keywords' => ['cbct', 'ct', 'preop', 'pre-op']
    ],
    'postop_cbct' => [
        'extensions' => ['dcm', 'zip', 'rar'],
        'keywords' => ['cbct', 'ct', 'postop', 'post-op']
    ],
    'design' => [
        'extensions' => ['3shape', 'dcm', 'zpr', 'exocad', 'blend', 'stl'],
        'keywords' => ['design', 'planning', 'guide', 'final']
    ],
    'radiograph' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'dcm'],
        'keywords' => ['xray', 'x-ray', 'radiograph', 'panoramic', 'pano']
    ]
]);

?>
