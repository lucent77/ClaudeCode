#!/usr/bin/env php
<?php

/**
 * CREODENT Automated Import - Evolution Portal
 *
 * This script runs periodically to import cases from Evolution Portal
 * Usage: php cron/import-evolution.php [location] [days]
 *
 * Examples:
 *   php cron/import-evolution.php HV 1    # Import HV cases from yesterday
 *   php cron/import-evolution.php NYC 7   # Import NYC cases from last 7 days
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load configuration
$config = require BASE_PATH . '/config/config.php';

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'America/New_York');

// Load Composer autoloader
require BASE_PATH . '/vendor/autoload.php';

// Import required classes
use App\Core\Database;
use App\Services\EvolutionClient;
use App\Services\CaseService;

// Parse command line arguments
$location = $argv[1] ?? 'HV';
$daysBack = isset($argv[2]) ? (int) $argv[2] : 1;

// Validate location
if (!in_array($location, ['HV', 'NYC'])) {
    echo "Error: Invalid location. Must be HV or NYC\n";
    exit(1);
}

// Validate days
if ($daysBack < 1 || $daysBack > 60) {
    echo "Error: Days must be between 1 and 60\n";
    exit(1);
}

// Calculate date range
$toDate = date('Y-m-d');
$fromDate = date('Y-m-d', strtotime("-{$daysBack} days"));

echo "========================================\n";
echo "CREODENT Evolution Import\n";
echo "========================================\n";
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "Location: {$location}\n";
echo "Date Range: {$fromDate} to {$toDate}\n";
echo "----------------------------------------\n\n";

try {
    // Initialize database
    Database::getInstance($config['database']);
    echo "[✓] Database connected\n";

    // Initialize services
    $evolutionClient = new EvolutionClient($config);
    $caseService = new CaseService();
    echo "[✓] Services initialized\n\n";

    // Get case list from Evolution Portal
    echo "Fetching cases from Evolution Portal...\n";
    $cases = $evolutionClient->getCaseList($fromDate, $toDate);
    echo "[✓] Found " . count($cases) . " cases\n\n";

    if (empty($cases)) {
        echo "No cases found for import.\n";
        echo "Completed: " . date('Y-m-d H:i:s') . "\n";
        exit(0);
    }

    // Process each case
    $results = [
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    $systemUserId = 1; // System user for cron jobs

    foreach ($cases as $index => $caseData) {
        $caseNo = $caseData['external_case_no'];
        $progress = ($index + 1) . '/' . count($cases);

        try {
            // Check if case already exists
            $db = Database::getInstance();
            $existingCase = $db->queryOne(
                'SELECT id, version FROM cases WHERE external_case_no = :case_no',
                [':case_no' => $caseNo]
            );

            if ($existingCase) {
                // Update existing case
                $updateData = [
                    'patient_name' => $caseData['patient_name'] ?? null,
                    'lab_name' => $caseData['lab_name'] ?? null,
                    'due_date' => $caseData['due_date'] ?? null,
                    'status' => $caseData['status'] ?? 'new',
                    'location' => $location,
                    'source' => 'evolution_web_portal',
                    'last_imported_at' => date('Y-m-d H:i:s')
                ];

                $caseService->updateCase(
                    $existingCase['id'],
                    $updateData,
                    (int) $existingCase['version'],
                    $systemUserId
                );

                $results['updated']++;
                echo "[{$progress}] Updated: {$caseNo}\n";
            } else {
                // Create new case
                $newCaseData = [
                    'external_case_no' => $caseNo,
                    'patient_name' => $caseData['patient_name'] ?? null,
                    'lab_name' => $caseData['lab_name'] ?? null,
                    'due_date' => $caseData['due_date'] ?? null,
                    'status' => $caseData['status'] ?? 'new',
                    'priority' => 'normal',
                    'location' => $location,
                    'source' => 'evolution_web_portal',
                    'last_imported_at' => date('Y-m-d H:i:s')
                ];

                $caseService->createCase($newCaseData, $systemUserId);

                $results['imported']++;
                echo "[{$progress}] Imported: {$caseNo}\n";
            }

        } catch (\Exception $e) {
            $results['errors'][] = [
                'case_no' => $caseNo,
                'error' => $e->getMessage()
            ];
            echo "[{$progress}] Error: {$caseNo} - {$e->getMessage()}\n";
        }
    }

    echo "\n========================================\n";
    echo "Import Summary\n";
    echo "========================================\n";
    echo "Total Cases:    " . count($cases) . "\n";
    echo "Imported:       " . $results['imported'] . "\n";
    echo "Updated:        " . $results['updated'] . "\n";
    echo "Errors:         " . count($results['errors']) . "\n";
    echo "----------------------------------------\n";

    if (!empty($results['errors'])) {
        echo "\nErrors:\n";
        foreach ($results['errors'] as $error) {
            echo "  - {$error['case_no']}: {$error['error']}\n";
        }
    }

    echo "\nCompleted: " . date('Y-m-d H:i:s') . "\n";
    echo "========================================\n";

    // Exit with error code if there were errors
    exit(empty($results['errors']) ? 0 : 1);

} catch (\Exception $e) {
    echo "\n[✗] Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
