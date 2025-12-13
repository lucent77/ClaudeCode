<?php
/**
 * API: Bulk Set Hold
 * POST /api/workflow/bulk-hold.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

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

$entityIds = $input['entity_ids'] ?? [];
$department = $input['department'] ?? '';
$reason = trim($input['reason'] ?? '');
$entityType = $input['entity_type'] ?? 'CASE';

if (empty($entityIds) || !$department || !$reason) {
    jsonError('Missing required fields');
}

if (!in_array($department, ALL_DEPARTMENTS)) {
    jsonError('Invalid department');
}

if (!auth()->hasAccessToDepartment($department)) {
    jsonError('Access denied', 403);
}

$results = workflow()->bulkSetHold($department, $entityIds, $reason, strtoupper($entityType));

jsonSuccess($results, count($results['success']) . ' item(s) placed on hold');
