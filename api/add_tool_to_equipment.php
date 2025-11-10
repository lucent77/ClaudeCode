<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(false, null, '권한이 없습니다.');
}

$input = json_decode(file_get_contents('php://input'), true);
$equipmentId = $input['equipment_id'] ?? null;
$toolId = $input['tool_id'] ?? null;
$usageRatio = $input['usage_ratio'] ?? 100;
$slotNumber = $input['slot_number'] ?? null;
$notes = $input['notes'] ?? '';

if (!$equipmentId || !$toolId) {
    jsonResponse(false, null, '필수 항목이 누락되었습니다.');
}

try {
    dbBeginTransaction();

    // Insert tool setting
    $settingId = dbExecute("
        INSERT INTO equipment_tool_settings
        (equipment_id, tool_id, slot_number, usage_ratio, installed_at, is_active, notes)
        VALUES (?, ?, ?, ?, NOW(), 1, ?)
    ", [$equipmentId, $toolId, $slotNumber, $usageRatio, $notes]);

    // Update tool status to in_use
    dbExecute("UPDATE tools SET status = 'in_use' WHERE id = ?", [$toolId]);

    dbCommit();
    jsonResponse(true, ['setting_id' => $settingId], '공구가 장착되었습니다.');
} catch (Exception $e) {
    dbRollback();
    error_log($e->getMessage());
    jsonResponse(false, null, '공구 장착 중 오류가 발생했습니다.');
}
?>
