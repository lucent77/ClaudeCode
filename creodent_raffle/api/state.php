<?php
/**
 * API: Get Current State
 * GET /api/state.php
 *
 * Returns the current state of the raffle system
 */

require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/utils.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

$lock = acquireLock();
if (!$lock) {
    jsonError('System busy, please try again', 503);
}

try {
    $state = loadState();

    // Calculate additional statistics
    $stats = getStatistics($state);
    $selectionCounts = countPrizeSelections($state);

    $response = [
        'success' => true,
        'state' => $state,
        'statistics' => $stats,
        'selection_counts' => $selectionCounts
    ];

    releaseLock($lock);
    jsonResponse($response);

} catch (Exception $e) {
    releaseLock($lock);
    jsonError('Failed to load state: ' . $e->getMessage(), 500);
}
