<?php
/**
 * Get Special Prize Settings API
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Read special prize settings
$settings = readJSONFile(SPECIAL_PRIZE_SETTINGS_FILE);

if (!$settings) {
    sendJSON(['success' => false, 'error' => 'Failed to load settings'], 500);
}

sendJSON([
    'success' => true,
    'settings' => $settings
]);
