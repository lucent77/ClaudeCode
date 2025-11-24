<?php
/**
 * Get Active Prizes API
 * Returns only active prizes with available stock
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Read prizes data
$prizesData = readJSONFile(PRIZES_FILE);

if (!$prizesData) {
    sendJSON(['success' => false, 'error' => 'Failed to load prizes data'], 500);
}

// Filter active prizes with available stock
$activePrizes = array_filter($prizesData['prizes'], function($prize) {
    return $prize['is_active'] == 1 && ($prize['stock'] > 0 || $prize['stock'] == -1);
});

// Re-index array
$activePrizes = array_values($activePrizes);

// Calculate total probability
$totalProbability = 0;
foreach ($activePrizes as $prize) {
    $totalProbability += floatval($prize['probability']);
}

sendJSON([
    'success' => true,
    'prizes' => $activePrizes,
    'total_probability' => $totalProbability,
    'is_valid' => abs($totalProbability - 100.0) < 0.01
]);
