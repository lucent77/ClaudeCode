<?php
/**
 * Statistics API Endpoint
 * Creodent AoX Elevate Dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/CaseModel.php';

$caseModel = new CaseModel();
$db = Database::getInstance();

try {
    // Get case statistics
    $stats = $caseModel->getStatistics();

    // Get recent sync logs
    $syncLogs = $db->fetchAll(
        "SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT 10"
    );

    $stats['recent_syncs'] = $syncLogs;

    // Last sync info
    if (count($syncLogs) > 0) {
        $lastSync = $syncLogs[0];
        $stats['last_sync'] = [
            'timestamp' => $lastSync['created_at'],
            'status' => $lastSync['status'],
            'total_files' => $lastSync['total_files'],
            'new_files' => $lastSync['new_files'] ?? 0,
            'new_cases' => $lastSync['new_cases'] ?? 0
        ];
    } else {
        $stats['last_sync'] = null;
    }

    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
    ]);
}

?>
