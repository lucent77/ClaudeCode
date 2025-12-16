<?php
/**
 * CREODENT HV Raffle 2025 - Configuration File
 *
 * IMPORTANT: Change the admin PIN before deployment!
 */

// Admin authentication
define('ADMIN_PIN', '2025');  // CHANGE THIS IN PRODUCTION!

// File paths
define('DATA_DIR', __DIR__ . '/data');
define('SEED_FILE', DATA_DIR . '/seed.json');
define('STATE_FILE', DATA_DIR . '/state.json');
define('LOCK_FILE', DATA_DIR . '/.lock');

// Application settings
define('MAX_SELECTIONS', 5);  // Number of gifts each employee can select
define('DEFAULT_UNLOCK_SPINS', [25, 50, 75]);  // Default jackpot unlock thresholds
define('JACKPOT_LIMIT', 3);  // Number of jackpot prizes

// Session settings
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('America/New_York');

// CORS headers for API
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
