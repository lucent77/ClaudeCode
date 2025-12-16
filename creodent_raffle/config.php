<?php
/**
 * CREODENT HV Raffle 2025 - Configuration
 *
 * IMPORTANT: Change the ADMIN_PIN before deployment!
 */

// Admin PIN for authentication (CHANGE THIS!)
define('ADMIN_PIN', '2025');

// Data directory path
define('DATA_DIR', __DIR__ . '/data');

// Seed file (initial data)
define('SEED_FILE', DATA_DIR . '/seed.json');

// State file (current state)
define('STATE_FILE', DATA_DIR . '/state.json');

// Lock file for concurrent access
define('LOCK_FILE', DATA_DIR . '/.lock');

// Maximum selections per employee
define('MAX_SELECTIONS', 5);

// Default unlock spins for jackpot
define('DEFAULT_UNLOCK_SPINS', [25, 50, 75]);

// Jackpot limit
define('JACKPOT_LIMIT', 3);

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('America/New_York');
