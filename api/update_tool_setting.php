<?php
/**
 * Update Tool Setting API
 */

require_once '../config/database.php';
checkAdmin();

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $settingId = $input['setting_id'] ?? null;

    if (!$settingId) {
        jsonResponse(false, 'Setting ID is required');
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare("
        UPDATE equipment_tool_settings
        SET slot_number = ?,
            usage_ratio = ?,
            notes = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $input['slot_number'] ?? null,
        $input['usage_ratio'] ?? 100,
        $input['notes'] ?? null,
        $settingId
    ]);

    jsonResponse(true, 'Tool setting updated successfully');

} catch (Exception $e) {
    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
