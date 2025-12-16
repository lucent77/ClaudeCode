<?php
/**
 * API: Reset All Data
 * POST /api/reset.php
 *
 * Resets all data to initial state from seed
 */

require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Require admin authentication
requireAdmin();

$input = getJsonInput();

// Require confirmation
if (empty($input['confirm']) || $input['confirm'] !== true) {
    jsonError('Confirmation required. Send { "confirm": true } to proceed.');
}

$lock = acquireLock();
if (!$lock) {
    jsonError('System busy, please try again', 503);
}

try {
    $state = resetState();
    releaseLock($lock);

    jsonResponse([
        'success' => true,
        'message' => 'All data has been reset to initial state',
        'statistics' => getStatistics($state)
    ]);

} catch (Exception $e) {
    releaseLock($lock);
    jsonError('Failed to reset data: ' . $e->getMessage(), 500);
}
