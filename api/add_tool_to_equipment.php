<?php
/**
 * Add Tool to Equipment API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $equipmentId = $input['equipment_id'] ?? null;
    $toolId = $input['tool_id'] ?? null;

    if (!$equipmentId || !$toolId) {
        jsonResponse(false, 'Equipment ID and Tool ID are required');
    }

    $pdo = getDbConnection();

    // Check if tool is already installed on this equipment
    $checkStmt = $pdo->prepare("
        SELECT id FROM equipment_tool_settings
        WHERE equipment_id = ? AND tool_id = ? AND is_active = 1
    ");
    $checkStmt->execute([$equipmentId, $toolId]);
    if ($checkStmt->fetch()) {
        jsonResponse(false, 'This tool is already installed on this equipment');
    }

    $stmt = $pdo->prepare("
        INSERT INTO equipment_tool_settings (
            equipment_id, tool_id, slot_number, usage_ratio, notes
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $equipmentId,
        $toolId,
        $input['slot_number'] ?? null,
        $input['usage_ratio'] ?? 100,
        $input['notes'] ?? null
    ]);

    // Update tool status to in_use
    $updateStmt = $pdo->prepare("UPDATE tools SET status = 'in_use' WHERE id = ?");
    $updateStmt->execute([$toolId]);

    jsonResponse(true, 'Tool installed successfully');

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
