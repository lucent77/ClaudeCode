<?php
/**
 * CREODENT HV Raffle 2025 - Configuration
 * Year-End Party Raffle System
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('America/New_York');

// Admin PIN (CHANGE THIS!)
define('ADMIN_PIN', '2025');

// Data paths
define('DATA_DIR', __DIR__ . '/data');
define('SEED_FILE', DATA_DIR . '/seed.json');
define('STATE_FILE', DATA_DIR . '/state.json');
define('LOCK_FILE', DATA_DIR . '/.lock');

// Raffle settings
define('MAX_SELECTIONS', 5);  // Number of prizes each employee can select
define('JACKPOT_LIMIT', 3);   // Number of jackpot prizes

// Default unlock spins for jackpot stages
define('DEFAULT_UNLOCK_SPINS', [25, 50, 75]);

// Session settings
session_start();

// CORS headers (if needed for API calls)
header('Content-Type: text/html; charset=UTF-8');

// Auto-create data directory if it doesn't exist
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0775, true);
}
