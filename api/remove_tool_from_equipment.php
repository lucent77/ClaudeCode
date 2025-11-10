<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(false, null, '권한이 없습니다.');
}

$input = json_decode(file_get_contents('php://input'), true);
$settingId = $input['setting_id'] ?? null;

if (!$settingId) {
    jsonResponse(false, null, '필수 항목이 누락되었습니다.');
}

try {
    dbBeginTransaction();

    // Get tool ID before removing
    $setting = dbQueryOne("SELECT tool_id FROM equipment_tool_settings WHERE id = ?", [$settingId]);

    if (!$setting) {
        jsonResponse(false, null, '설정을 찾을 수 없습니다.');
    }

    // Update setting to inactive
    dbExecute("
        UPDATE equipment_tool_settings
        SET is_active = 0, removed_at = NOW()
        WHERE id = ?
    ", [$settingId]);

    // Check if tool is used in any other equipment
    $otherSettings = dbQuery("
        SELECT id FROM equipment_tool_settings
        WHERE tool_id = ? AND is_active = 1
    ", [$setting['tool_id']]);

    // If not used elsewhere, update status to 'used'
    if (empty($otherSettings)) {
        dbExecute("UPDATE tools SET status = 'used' WHERE id = ?", [$setting['tool_id']]);
    }

    dbCommit();
    jsonResponse(true, null, '공구가 제거되었습니다.');
} catch (Exception $e) {
    dbRollback();
    error_log($e->getMessage());
    jsonResponse(false, null, '공구 제거 중 오류가 발생했습니다.');
}
?>
