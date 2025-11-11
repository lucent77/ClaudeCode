<?php
/**
 * Delete Tool API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $toolId = $input['tool_id'] ?? null;

    if (!$toolId) {
        jsonResponse(false, 'Tool ID is required');
    }

    $pdo = getDbConnection();

    // Check if tool is currently installed on any equipment
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM equipment_tool_settings
        WHERE tool_id = ? AND is_active = 1
    ");
    $checkStmt->execute([$toolId]);
    $result = $checkStmt->fetch();

    if ($result['count'] > 0) {
        jsonResponse(false, 'Cannot delete tool that is currently installed on equipment. Remove it first.');
    }

    $stmt = $pdo->prepare("DELETE FROM tools WHERE id = ?");
    $stmt->execute([$toolId]);

    jsonResponse(true, 'Tool deleted successfully');

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
