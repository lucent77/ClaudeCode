<?php
/**
 * CREODENT Integrated Work Management System
 * Configuration File Template
 *
 * Copy this file to config.php and fill in your actual credentials
 */

return [
    // Application Settings
    'app' => [
        'name' => 'CREODENT Work Manager',
        'version' => '1.0.0',
        'env' => 'production', // development, production
        'debug' => false,
        'timezone' => 'America/New_York',
        'session_lifetime' => 7200, // 2 hours in seconds
        'app_key' => 'CHANGE_THIS_TO_RANDOM_32_CHAR_STRING', // Used for CSRF token encryption
    ],

    // Database Configuration (Hostinger MySQL)
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'u359033001_TOOL',
        'username' => 'u359033001_TOOL',
        'password' => 'Creo$10001',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],

    // Evolution Web Portal V18 Integration
    'evolution' => [
        'base_url' => 'https://YOUR_EVOLUTION_PORTAL_URL',
        'username' => 'YOUR_EVO_USERNAME',
        'password' => 'YOUR_EVO_PASSWORD',
        'timeout' => 30, // seconds
        'retry_count' => 3,
        'retry_delay' => 2, // seconds
        'events' => [
            'account_login' => 'account_login',
            'cases_caselist' => 'cases_caselist',
            'case_caseinformation' => 'case_caseinformation',
            'case_noteget' => 'case_noteget',
            'case_noteadd' => 'case_noteadd',
            'case_imagelist' => 'case_imagelist',
        ],
    ],

    // Google Sheets Integration (Optional - for backward compatibility)
    'google_sheets' => [
        'enabled' => false,
        'credentials_path' => __DIR__ . '/../storage/google_credentials.json',
        'solidex_sheet_id' => '',
        'print3d_sheet_id' => '',
        'cocr_sheet_id' => '',
        'sync_direction' => 'db_to_sheets', // db_to_sheets, sheets_to_db, bidirectional
    ],

    // Paths
    'paths' => [
        'root' => dirname(__DIR__),
        'storage' => dirname(__DIR__) . '/storage',
        'logs' => dirname(__DIR__) . '/storage/logs',
        'cache' => dirname(__DIR__) . '/storage/cache',
        'uploads' => dirname(__DIR__) . '/storage/uploads',
    ],

    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => 'app.log',
        'max_files' => 30,
    ],

    // Security
    'security' => [
        'password_min_length' => 8,
        'password_hash_algo' => PASSWORD_BCRYPT,
        'csrf_token_length' => 32,
        'api_token_length' => 64,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],

    // Department Codes
    'departments' => [
        'SOLIDEX' => 'Solidex Department',
        '3DPRINT' => '3D Print Department',
        'COCR' => 'CoCr/ZEST Department',
        'KOREA' => 'Korea Branch',
        'QC' => 'Quality Control',
        'ADMIN' => 'Administration',
    ],

    // User Roles
    'roles' => [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'worker' => 'Worker',
    ],

    // Case Statuses
    'case_statuses' => [
        'new' => 'New',
        'in_progress' => 'In Progress',
        'done' => 'Done',
        'on_hold' => 'On Hold',
        'canceled' => 'Canceled',
        'archived' => 'Archived',
    ],

    // Item Statuses
    'item_statuses' => [
        'pending' => 'Pending',
        'assigned' => 'Assigned',
        'working' => 'Working',
        'done' => 'Done',
        'remake' => 'Remake',
        'rejected' => 'Rejected',
    ],

    // Cron Jobs
    'cron' => [
        'import_evo_enabled' => true,
        'import_interval' => 3600, // 1 hour
        'sync_sheets_enabled' => false,
        'sync_interval' => 7200, // 2 hours
    ],

    // Pagination
    'pagination' => [
        'default_per_page' => 50,
        'max_per_page' => 200,
    ],

    // API
    'api' => [
        'enabled' => true,
        'rate_limit' => 100, // requests per minute
        'require_token' => true,
    ],
];
