<?php
/**
 * Application Configuration
 */

return [
    'name' => 'Task Management System',
    'version' => '1.0.0',
    'timezone' => 'America/New_York',
    'base_url' => getenv('APP_URL') ?: 'http://localhost',

    // Session settings
    'session' => [
        'name' => 'TMS_SESSION',
        'lifetime' => 7200, // 2 hours in seconds
        'path' => '/',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ],

    // Security settings
    'security' => [
        'password_cost' => 10,
        'csrf_token_name' => 'csrf_token',
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 minutes
    ],

    // File upload settings
    'upload' => [
        'max_size' => 10485760, // 10MB in bytes
        'allowed_types' => ['json', 'pdf', 'jpg', 'jpeg', 'png', 'stl'],
        'upload_path' => __DIR__ . '/../uploads/'
    ],

    // Real-time updates settings
    'realtime' => [
        'method' => 'sse', // 'sse' or 'polling'
        'polling_interval' => 5000, // milliseconds
        'sse_retry' => 3000 // milliseconds
    ],

    // Pagination
    'pagination' => [
        'per_page' => 50,
        'max_per_page' => 200
    ]
];
