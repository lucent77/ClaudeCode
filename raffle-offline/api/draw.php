<?php
/**
 * Prize Drawing API
 * No email input required, no winner storage
 */

require_once 'config.php';

// Set headers for CORS (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'error' => 'Invalid request method'], 405);
}

// Read prizes data
$prizesData = readJSONFile(PRIZES_FILE);
if (!$prizesData) {
    sendJSON(['success' => false, 'error' => 'Failed to load prizes data'], 500);
}

// Read special prize settings
$specialSettings = readJSONFile(SPECIAL_PRIZE_SETTINGS_FILE);

// Get active prizes
$activePrizes = array_filter($prizesData['prizes'], function($prize) {
    return $prize['is_active'] == 1 && ($prize['stock'] > 0 || $prize['stock'] == -1);
});

if (empty($activePrizes)) {
    sendJSON(['success' => false, 'error' => 'No active prizes available'], 400);
}

// Validate probabilities
if (!validateProbabilities($activePrizes)) {
    sendJSON(['success' => false, 'error' => 'Invalid probability configuration. Total must equal 100%.'], 400);
}

// Check special prize milestone
$isSpecialPrize = false;
$specialPrizeWon = null;

if ($specialSettings && $specialSettings['is_active'] == 1) {
    $specialSettings['current_count']++;

    if ($specialSettings['current_count'] >= $specialSettings['milestone_count']) {
        // Check if special prize exists and has stock
        foreach ($prizesData['prizes'] as $prize) {
            if ($prize['id'] == $specialSettings['special_prize_id'] &&
                $prize['is_active'] == 1 &&
                ($prize['stock'] > 0 || $prize['stock'] == -1)) {
                $isSpecialPrize = true;
                $specialPrizeWon = $prize;
                break;
            }
        }

        // Reset counter
        $specialSettings['current_count'] = 0;
    }

    // Update special settings
    $specialSettings['updated_at'] = date('Y-m-d H:i:s');
    writeJSONFile(SPECIAL_PRIZE_SETTINGS_FILE, $specialSettings);
}

// Determine winning prize
$wonPrize = null;

if ($isSpecialPrize && $specialPrizeWon) {
    $wonPrize = $specialPrizeWon;
} else {
    // Normal probability-based drawing
    $random = mt_rand(0, 9999) / 100; // Random number 0.00 - 99.99
    $cumulative = 0;

    foreach ($activePrizes as $prize) {
        $cumulative += floatval($prize['probability']);
        if ($random < $cumulative) {
            $wonPrize = $prize;
            break;
        }
    }
}

if (!$wonPrize) {
    sendJSON(['success' => false, 'error' => 'Drawing failed'], 500);
}

// Update stock (if not unlimited)
if ($wonPrize['stock'] > 0) {
    foreach ($prizesData['prizes'] as &$prize) {
        if ($prize['id'] == $wonPrize['id']) {
            $prize['stock']--;
            break;
        }
    }

    // Save updated prizes
    if (!writeJSONFile(PRIZES_FILE, $prizesData)) {
        error_log("Failed to update prize stock");
    }
}

// Return result
sendJSON([
    'success' => true,
    'prize' => [
        'id' => $wonPrize['id'],
        'name' => $wonPrize['name'],
        'color' => $wonPrize['color'],
        'is_special' => $isSpecialPrize ? 1 : 0
    ],
    'special_prize_progress' => $specialSettings ? [
        'current_count' => $specialSettings['current_count'],
        'milestone_count' => $specialSettings['milestone_count'],
        'is_active' => $specialSettings['is_active']
    ] : null
]);
