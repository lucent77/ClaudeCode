<?php
/**
 * Slack Synchronization API Endpoint
 * Syncs Slack Unified Files to local database
 * Creodent AoX Elevate Dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/SlackClient.php';
require_once __DIR__ . '/../includes/CaseModel.php';
require_once __DIR__ . '/../includes/FileClassifier.php';

$startTime = microtime(true);
$db = Database::getInstance();
$slackClient = new SlackClient();
$caseModel = new CaseModel();

// Statistics
$stats = [
    'total_files' => 0,
    'matched_files' => 0,
    'unmatched_files' => 0,
    'new_cases' => 0,
    'new_files' => 0,
    'updated_files' => 0,
    'errors' => []
];

try {
    // Test Slack connection first
    if (!$slackClient->testConnection()) {
        throw new Exception('Failed to connect to Slack API. Please check your bot token.');
    }

    // Fetch all files from Slack
    $slackFiles = $slackClient->getFiles([
        'limit' => SYNC_BATCH_SIZE,
        'types' => 'all'
    ]);

    $stats['total_files'] = count($slackFiles);

    if (APP_DEBUG) {
        error_log("Starting sync: " . $stats['total_files'] . " files from Slack");
    }

    // Process each file
    foreach ($slackFiles as $slackFile) {
        try {
            // Parse file metadata
            $fileMetadata = SlackClient::parseFileMetadata($slackFile);
            $fileName = $fileMetadata['file_name'];

            if (APP_DEBUG) {
                error_log("Processing file: " . $fileName);
            }

            // Extract case code from filename
            $caseCode = FileClassifier::extractCaseCode($fileName);

            if (!$caseCode) {
                // No valid case code found
                $stats['unmatched_files']++;
                if (APP_DEBUG) {
                    error_log("Could not extract case code from: " . $fileName);
                }
                continue;
            }

            $stats['matched_files']++;

            // Check if case exists, if not create it
            $case = $caseModel->getCaseByCaseCode($caseCode);

            if (!$case) {
                // Create new case
                $patientName = FileClassifier::extractPatientName($caseCode);
                $surgeryDate = FileClassifier::extractSurgeryDate($caseCode);
                $arch = FileClassifier::extractArch($fileName);

                $caseData = [
                    'case_code' => $caseCode,
                    'patient_name' => $patientName,
                    'surgery_date' => $surgeryDate,
                    'arch' => $arch,
                    'status' => 'open'
                ];

                $caseId = $caseModel->createCase($caseData);

                if ($caseId) {
                    $stats['new_cases']++;
                    if (APP_DEBUG) {
                        error_log("Created new case: " . $caseCode);
                    }
                } else {
                    $stats['errors'][] = "Failed to create case: " . $caseCode;
                    continue;
                }
            } else {
                $caseId = $case['id'];
            }

            // Classify file type
            $fileType = FileClassifier::classify($fileName);
            $fileMetadata['file_type'] = $fileType;

            // Add attachment to case
            $attachmentId = $caseModel->addAttachment($caseId, $fileMetadata);

            if ($attachmentId) {
                if (is_int($attachmentId)) {
                    $stats['new_files']++;
                } else {
                    $stats['updated_files']++;
                }
            } else {
                $stats['errors'][] = "Failed to add attachment: " . $fileName;
            }

        } catch (Exception $e) {
            $stats['errors'][] = "Error processing file: " . ($fileName ?? 'unknown') . " - " . $e->getMessage();
            error_log("Sync error: " . $e->getMessage());
        }
    }

    // Calculate execution time
    $executionTime = microtime(true) - $startTime;

    // Determine sync status
    $syncStatus = 'success';
    if (count($stats['errors']) > 0) {
        $syncStatus = 'partial';
    }
    if ($stats['matched_files'] === 0 && $stats['total_files'] > 0) {
        $syncStatus = 'error';
    }

    // Log sync results
    $logSql = "INSERT INTO sync_logs (
        total_files, matched_files, unmatched_files, new_cases, new_files, status, detail, execution_time
    ) VALUES (
        :total_files, :matched_files, :unmatched_files, :new_cases, :new_files, :status, :detail, :execution_time
    )";

    $db->execute($logSql, [
        'total_files' => $stats['total_files'],
        'matched_files' => $stats['matched_files'],
        'unmatched_files' => $stats['unmatched_files'],
        'new_cases' => $stats['new_cases'],
        'new_files' => $stats['new_files'],
        'status' => $syncStatus,
        'detail' => json_encode($stats),
        'execution_time' => $executionTime
    ]);

    // Return response
    echo json_encode([
        'success' => true,
        'status' => $syncStatus,
        'stats' => $stats,
        'execution_time' => round($executionTime, 2) . 's',
        'message' => "Sync completed: {$stats['new_files']} new files, {$stats['new_cases']} new cases"
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Sync failed: " . $e->getMessage());

    // Log error
    $db->execute(
        "INSERT INTO sync_logs (total_files, status, error_message) VALUES (:total, :status, :error)",
        [
            'total' => $stats['total_files'] ?? 0,
            'status' => 'error',
            'error' => $e->getMessage()
        ]
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Sync failed',
        'stats' => $stats
    ], JSON_PRETTY_PRINT);
}

?>
