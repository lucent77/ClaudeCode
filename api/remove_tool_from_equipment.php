<?php
/**
 * Remove Tool from Equipment API
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
    $pdo->beginTransaction();

    // Get setting details
    $getStmt = $pdo->prepare("
        SELECT equipment_id, tool_id, t.current_usage_hours
        FROM equipment_tool_settings ets
        JOIN tools t ON ets.tool_id = t.id
        WHERE ets.id = ?
    ");
    $getStmt->execute([$settingId]);
    $setting = $getStmt->fetch();

    if (!$setting) {
        jsonResponse(false, 'Setting not found');
    }

    // Deactivate the setting
    $stmt = $pdo->prepare("
        UPDATE equipment_tool_settings
        SET is_active = 0, removed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$settingId]);

    // Record tool change
    $changeStmt = $pdo->prepare("
        INSERT INTO tool_changes (
            equipment_id, old_tool_id, new_tool_id, change_reason,
            old_tool_usage_hours, changed_by, notes
        ) VALUES (?, ?, ?, 'worn', ?, ?, 'Tool removed from equipment')
    ");
    $changeStmt->execute([
        $setting['equipment_id'],
        $setting['tool_id'],
        $setting['tool_id'], // Same tool ID for removal
        $setting['current_usage_hours'],
        $_SESSION['user_id']
    ]);

    $pdo->commit();

    jsonResponse(true, 'Tool removed successfully');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (DEBUG_MODE) {
        jsonResponse(false, 'Error: ' . $e->getMessage());
    } else {
        jsonResponse(false, 'An error occurred');
    }
}
?>
