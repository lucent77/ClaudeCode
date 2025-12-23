<?php
/**
 * URL Collector - Configuration File
 *
 * Copy this file and rename to config.php
 * Update all values with your actual credentials
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

return [
    // ============================================
    // Database Configuration (MySQL/MariaDB)
    // ============================================
    'db' => [
        'host'     => 'localhost',
        'name'     => 'your_database_name',
        'user'     => 'your_database_user',
        'pass'     => 'your_database_password',
        'charset'  => 'utf8mb4',
        'port'     => 3306,
    ],

    // ============================================
    // Application Security
    // ============================================
    'app' => [
        // API Key for ingest endpoint (generate a strong random string)
        // Used in X-APP-KEY header or Authorization: Bearer <key>
        'api_key'          => 'CHANGE_THIS_TO_A_SECURE_RANDOM_STRING_32_CHARS_MIN',

        // Rate limiting (requests per minute per IP)
        'rate_limit'       => 30,

        // Session name for dashboard
        'session_name'     => 'url_collector_session',

        // Session lifetime in seconds (2 hours)
        'session_lifetime' => 7200,
    ],

    // ============================================
    // Dashboard Admin Credentials
    // ============================================
    'admin' => [
        'username' => 'admin',
        // Use password_hash() to generate this value
        // Example: password_hash('your_password', PASSWORD_DEFAULT)
        'password_hash' => '$2y$10$CHANGE_THIS_WITH_YOUR_HASHED_PASSWORD',
    ],

    // ============================================
    // Gemini API Configuration
    // ============================================
    'gemini' => [
        // Get your API key from https://aistudio.google.com/app/apikey
        'api_key'   => 'YOUR_GEMINI_API_KEY_HERE',

        // Model to use (gemini-1.5-flash recommended for speed/cost)
        'model'     => 'gemini-1.5-flash',

        // API endpoint
        'endpoint'  => 'https://generativelanguage.googleapis.com/v1beta/models/',

        // Max tokens for response
        'max_tokens' => 1024,

        // Temperature (0.0 - 1.0, lower = more deterministic)
        'temperature' => 0.3,
    ],

    // ============================================
    // Analysis Settings
    // ============================================
    'analyze' => [
        // Number of URLs to process per cron run
        'batch_size'      => 10,

        // Max retry attempts for failed URLs
        'max_retries'     => 3,

        // Max characters to extract from page text
        'max_text_length' => 8000,
    ],

    // ============================================
    // HTTP/cURL Settings
    // ============================================
    'http' => [
        // Request timeout in seconds
        'timeout'          => 8,

        // Connection timeout in seconds
        'connect_timeout'  => 5,

        // Max redirects to follow
        'max_redirects'    => 5,

        // User agent string
        'user_agent'       => 'URLCollector/1.0 (compatible; cURL)',
    ],

    // ============================================
    // Categories (fixed list)
    // ============================================
    'categories' => [
        'news',
        'video',
        'shopping',
        'social',
        'tech',
        'business',
        'design',
        'music',
        'dental',
        'education',
        'other',
    ],

    // ============================================
    // Review Statuses
    // ============================================
    'review_statuses' => [
        'inbox',
        'keep',
        'archive',
    ],

    // ============================================
    // Interest Score Settings
    // ============================================
    'score' => [
        'min' => 0,
        'max' => 100,
    ],

    // ============================================
    // Logging
    // ============================================
    'logging' => [
        'enabled'   => true,
        'file'      => APP_ROOT . '/logs/app.log',
        'max_size'  => 10485760, // 10MB
    ],

    // ============================================
    // Timezone
    // ============================================
    'timezone' => 'Asia/Seoul',
];
