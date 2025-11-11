<?php
/**
 * Get Previous Working Time API
 * Retrieves the most recent working time for an equipment before a given date
 */

require_once '../config/database.php';
checkAuth();

header('Content-Type: application/json');

try {
    $equipmentId = $_GET['equipment_id'] ?? null;
    $currentDate = $_GET['current_date'] ?? null;

    if (!$equipmentId || !$currentDate) {
        jsonResponse(false, 'Equipment ID and current date are required');
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        SELECT working_time
        FROM production_records
        WHERE equipment_id = ? AND record_date < ?
        ORDER BY record_date DESC
        LIMIT 1
    ");

    $stmt->execute([$equipmentId, $currentDate]);
    $result = $stmt->fetch();

    if ($result && $result['working_time'] !== null) {
        jsonResponse(true, 'Previous working time found', [
            'previous_working_time' => $result['working_time']
        ]);
    } else {
        jsonResponse(true, 'No previous working time found', [
            'previous_working_time' => null
        ]);
    }

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
