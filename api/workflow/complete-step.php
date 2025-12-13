<?php
/**
 * API: Complete Step
 * POST /api/workflow/complete-step.php
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

// Require authentication
if (!auth()->isLoggedIn()) {
    jsonError('Unauthorized', 401);
}

// Require POST method
if (!isPost()) {
    jsonError('Method not allowed', 405);
}

// Validate CSRF
auth()->requireCsrf();

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonError('Invalid request body');
}

$entityId = (int) ($input['entity_id'] ?? 0);
$entityType = $input['entity_type'] ?? 'CASE';
$department = $input['department'] ?? '';
$stepCode = $input['step_code'] ?? '';
$machineName = $input['machine_name'] ?? null;
$notes = $input['notes'] ?? null;

// Validate required fields
if (!$entityId || !$department || !$stepCode) {
    jsonError('Missing required fields');
}

// Validate department
if (!in_array($department, ALL_DEPARTMENTS)) {
    jsonError('Invalid department');
}

// Check permission
if (!auth()->canWorkOnStep($department, $stepCode)) {
    jsonError('You do not have permission to complete this step', 403);
}

try {
    $result = workflow()->completeStep(
        $department,
        $entityId,
        $stepCode,
        $machineName,
        $notes,
        strtoupper($entityType)
    );

    jsonSuccess($result, 'Step completed successfully');
} catch (ConcurrencyException $e) {
    jsonError($e->getMessage(), 409);
} catch (Exception $e) {
    jsonError($e->getMessage());
}
