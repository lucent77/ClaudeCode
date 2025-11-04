<?php

/**
 * CREODENT Integrated Work Management System
 * Configuration File Template
 *
 * Copy this file to config.php and fill in your actual values
 */

return [
    // Application Settings
    'app' => [
        'name' => 'CREODENT Work Manager',
        'version' => '2.0.0',
        'env' => 'production', // 'development', 'staging', or 'production'
        'debug' => false, // Set to false in production
        'timezone' => 'America/New_York',
        'url' => 'https://yourdomain.com',
        'session_lifetime' => 7200, // 2 hours in seconds
    ],

    // Database Configuration
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'your_database_name',
        'username' => 'your_database_user',
        'password' => 'your_database_password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ],
    ],

    // Evolution Web Portal Configuration
    'evolution' => [
        'base_url' => 'https://your-evolution-portal-url.com',
        'username' => 'your_evolution_username',
        'password' => 'your_evolution_password',
        'timeout' => 30, // API timeout in seconds
        'retry_count' => 3, // Number of retry attempts
        'retry_delay' => 2, // Delay between retries in seconds

        // SQL Server Direct Connection (for NYC and HV locations)
        'sql_servers' => [
            'NYC' => [
                'enabled' => true,
                'host' => 'CDIMHN-SVR2-P,5000',
                'wan_ip' => '98.113.78.100',
                'database' => 'Evolution_Test',
                'username' => 'sa',
                'password' => 'your_nyc_password',
            ],
            'HV' => [
                'enabled' => true,
                'host' => 'CDIFSH-SVR2-P,8662',
                'wan_ip' => '71.169.7.90',
                'database' => 'Evolution_Test',
                'username' => 'sa',
                'password' => 'Cr@o3eetH',
            ],
        ],
    ],

    // Security Settings
    'security' => [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_special' => false,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes in seconds
        'session_regenerate_interval' => 1800, // 30 minutes
        'csrf_token_name' => '_csrf_token',
        'api_token_length' => 64,
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'level' => 'info', // 'debug', 'info', 'warning', 'error'
        'path' => 'storage/logs',
        'filename_format' => 'Y-m-d', // PHP date format
        'max_files' => 30, // Keep logs for 30 days
    ],

    // Import/Cron Settings
    'import' => [
        'enabled' => true,
        'auto_import_interval' => 3600, // 1 hour in seconds
        'batch_size' => 100, // Number of records per batch
        'date_range_days' => 30, // Import last N days of data
    ],

    // Department Configuration
    'departments' => [
        'solidex' => [
            'name' => 'Solidex',
            'enabled' => true,
            'work_types' => ['SOLIDEX'],
        ],
        'print3d' => [
            'name' => '3D Print',
            'enabled' => true,
            'work_types' => ['3DPRINT', 'PRINT3D'],
        ],
        'cocr' => [
            'name' => 'CoCr/ZEST',
            'enabled' => true,
            'work_types' => ['COCR', 'ZEST'],
        ],
    ],

    // File Upload Settings
    'upload' => [
        'max_size' => 20971520, // 20MB in bytes
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'stl', 'ply'],
        'path' => 'storage/uploads',
    ],

    // Pagination
    'pagination' => [
        'per_page' => 50,
        'max_per_page' => 200,
    ],

    // Email Configuration (for future use)
    'email' => [
        'enabled' => false,
        'from_address' => 'noreply@creodent.com',
        'from_name' => 'CREODENT Work Manager',
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
    ],

    // Windows App Integration
    'windows_app' => [
        'enabled' => true,
        'api_key' => '', // Generate a secure API key
        'allowed_ips' => [], // Empty array means all IPs allowed
    ],
];
