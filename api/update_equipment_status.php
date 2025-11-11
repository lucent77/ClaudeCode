<?php
/**
 * Update Equipment Status API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $equipmentId = $input['equipment_id'] ?? null;
    $status = $input['status'] ?? null;

    if (!$equipmentId || !$status) {
        jsonResponse(false, 'Equipment ID and status are required');
    }

    $validStatuses = ['running', 'idle', 'maintenance', 'error'];
    if (!in_array($status, $validStatuses)) {
        jsonResponse(false, 'Invalid status');
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("UPDATE equipment SET status = ? WHERE id = ?");
    $stmt->execute([$status, $equipmentId]);

    jsonResponse(true, 'Equipment status updated successfully');

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
