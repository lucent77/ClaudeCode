<?php
/**
 * API: Set Hold
 * POST /api/workflow/set-hold.php
 */

require_once __DIR__ . '/../../../includes/bootstrap.php';

header('Content-Type: application/json');

if (!auth()->isLoggedIn()) {
    jsonError('Unauthorized', 401);
}

if (!isPost()) {
    jsonError('Method not allowed', 405);
}

auth()->requireCsrf();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonError('Invalid request body');
}

$entityId = (int) ($input['entity_id'] ?? 0);
$entityType = $input['entity_type'] ?? 'CASE';
$department = $input['department'] ?? '';
$reason = trim($input['reason'] ?? '');

if (!$entityId || !$department || !$reason) {
    jsonError('Missing required fields');
}

if (!in_array($department, ALL_DEPARTMENTS)) {
    jsonError('Invalid department');
}

if (!auth()->hasAccessToDepartment($department)) {
    jsonError('Access denied', 403);
}

try {
    workflow()->setHold($department, $entityId, $reason, strtoupper($entityType));
    jsonSuccess(null, 'Case placed on hold');
} catch (Exception $e) {
    jsonError($e->getMessage());
}
