<?php
/**
 * API: Export Results to CSV
 * GET /api/export.php
 *
 * Returns CSV file of all raffle results
 */

require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

// Require admin authentication
requireAdmin();

$lock = acquireLock();
if (!$lock) {
    jsonError('System busy, please try again', 503);
}

try {
    $state = loadState();
    $csv = exportResultsCSV($state);

    releaseLock($lock);

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="raffle_results_' . date('Y-m-d_His') . '.csv"');
    header('Cache-Control: no-cache, must-revalidate');

    echo $csv;
    exit;

} catch (Exception $e) {
    releaseLock($lock);
    jsonError('Failed to export results: ' . $e->getMessage(), 500);
}
