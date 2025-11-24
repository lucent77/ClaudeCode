<?php
/**
 * Update Special Prize Settings API
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendJSON(['success' => false, 'error' => 'Invalid request method'], 405);
}

// Read current settings
$settings = readJSONFile(SPECIAL_PRIZE_SETTINGS_FILE);

if (!$settings) {
    sendJSON(['success' => false, 'error' => 'Failed to load settings'], 500);
}

// Get input
$input = getJSONInput();

// Update fields
if (isset($input['special_prize_id'])) {
    $settings['special_prize_id'] = intval($input['special_prize_id']);
}

if (isset($input['milestone_count'])) {
    $settings['milestone_count'] = intval($input['milestone_count']);
}

if (isset($input['current_count'])) {
    $settings['current_count'] = intval($input['current_count']);
}

if (isset($input['is_active'])) {
    $settings['is_active'] = intval($input['is_active']);
}

$settings['updated_at'] = date('Y-m-d H:i:s');

// Save updated settings
if (writeJSONFile(SPECIAL_PRIZE_SETTINGS_FILE, $settings)) {
    sendJSON([
        'success' => true,
        'settings' => $settings
    ]);
} else {
    sendJSON(['success' => false, 'error' => 'Failed to save settings'], 500);
}
