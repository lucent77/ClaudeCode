<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, null, '인증이 필요합니다.');
}

// Only admin can update equipment status
if ($_SESSION['role'] !== 'admin') {
    jsonResponse(false, null, '권한이 없습니다.');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, null, '잘못된 요청입니다.');
}

$equipmentId = $input['equipment_id'] ?? null;
$status = $input['status'] ?? null;

if (!$equipmentId || !$status) {
    jsonResponse(false, null, '필수 항목이 누락되었습니다.');
}

// Validate status
$validStatuses = ['running', 'idle', 'maintenance', 'error'];
if (!in_array($status, $validStatuses)) {
    jsonResponse(false, null, '유효하지 않은 상태입니다.');
}

try {
    // Update equipment status
    $result = dbExecute(
        "UPDATE equipment SET status = ?, updated_at = NOW() WHERE id = ?",
        [$status, $equipmentId]
    );

    if ($result) {
        jsonResponse(true, ['equipment_id' => $equipmentId, 'status' => $status], '장비 상태가 업데이트되었습니다.');
    } else {
        jsonResponse(false, null, '장비를 찾을 수 없습니다.');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    jsonResponse(false, null, '상태 업데이트 중 오류가 발생했습니다.');
}
?>
