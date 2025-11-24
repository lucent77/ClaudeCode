<?php
/**
 * Get All Prizes API (Admin)
 * Returns all prizes including inactive and out-of-stock
 */

require_once 'config.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Read prizes data
$prizesData = readJSONFile(PRIZES_FILE);

if (!$prizesData) {
    sendJSON(['success' => false, 'error' => 'Failed to load prizes data'], 500);
}

// Calculate total probability for active prizes
$totalProbability = 0;
foreach ($prizesData['prizes'] as $prize) {
    if ($prize['is_active'] == 1) {
        $totalProbability += floatval($prize['probability']);
    }
}

sendJSON([
    'success' => true,
    'prizes' => $prizesData['prizes'],
    'total_probability' => $totalProbability,
    'is_valid' => abs($totalProbability - 100.0) < 0.01,
    'last_id' => $prizesData['last_id']
]);
