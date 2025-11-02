<?php
/**
 * Evolution Web Portal Import Cron Job
 *
 * This script should be run periodically (e.g., every hour) via cron
 * Example crontab entry:
 * 0 * * * * /usr/bin/php /path/to/cron/import_evo.php >> /path/to/storage/logs/cron.log 2>&1
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Load autoloader and configuration
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\ImportService;
use App\Core\Database;

// Start
$startTime = microtime(true);
$logFile = __DIR__ . '/../storage/logs/import_' . date('Y-m-d') . '.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] {$message}\n";
    echo $logLine;
    file_put_contents($logFile, $logLine, FILE_APPEND);
}

logMessage("=== Evolution Import Job Started ===");

try {
    // Load configuration
    $config = require __DIR__ . '/../config/config.php';

    // Check if import is enabled
    if (!$config['cron']['import_evo_enabled']) {
        logMessage("Import is disabled in configuration. Exiting.");
        exit(0);
    }

    // Initialize service
    $importService = new ImportService();

    // Import yesterday and today's cases
    $startDate = date('Y-m-d', strtotime('-1 day'));
    $endDate = date('Y-m-d');

    logMessage("Importing cases from {$startDate} to {$endDate}");

    $result = $importService->importFromEvolution($startDate, $endDate);

    // Log results
    logMessage("Status: {$result['status']}");
    logMessage("Processed: {$result['processed']} cases");
    logMessage("Success: {$result['success']}");
    logMessage("Failed: {$result['failed']}");
    logMessage("Message: {$result['message']}");

    // If there were failures, log error
    if ($result['failed'] > 0) {
        logMessage("WARNING: Some cases failed to import. Check import_jobs table for details.");
    }

    // Clean old sessions (once per day)
    if (date('H') === '00') {
        logMessage("Cleaning old sessions...");
        \App\Core\Session::cleanOldSessions($config['app']['session_lifetime']);
        logMessage("Old sessions cleaned");
    }

} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("Trace: " . $e->getTraceAsString());

    // Log to database
    try {
        $db = Database::getInstance();
        $db->insert('error_logs', [
            'level' => 'error',
            'message' => 'Cron job failed: ' . $e->getMessage(),
            'context' => json_encode([
                'script' => 'import_evo.php',
                'trace' => $e->getTraceAsString()
            ]),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    } catch (Exception $dbError) {
        logMessage("Failed to log error to database: " . $dbError->getMessage());
    }

    exit(1);
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);
logMessage("=== Import Job Completed in {$duration} seconds ===\n");

exit(0);
