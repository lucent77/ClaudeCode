<?php
/**
 * API: Bulk Complete Steps
 * POST /api/workflow/bulk-complete.php
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

$department = $input['department'] ?? '';
$items = $input['items'] ?? [];
$machineName = $input['machine_name'] ?? null;

if (empty($items)) {
    jsonError('No items provided');
}

if (!in_array($department, ALL_DEPARTMENTS)) {
    jsonError('Invalid department');
}

$results = [
    'success' => [],
    'failed' => []
];

foreach ($items as $item) {
    $entityId = (int) ($item['id'] ?? 0);
    $stepCode = $item['step'] ?? '';
    $entityType = $item['type'] ?? 'CASE';

    if (!$entityId || !$stepCode) {
        $results['failed'][] = [
            'id' => $entityId,
            'error' => 'Missing required fields'
        ];
        continue;
    }

    if (!auth()->canWorkOnStep($department, $stepCode)) {
        $results['failed'][] = [
            'id' => $entityId,
            'error' => 'Permission denied'
        ];
        continue;
    }

    try {
        $result = workflow()->completeStep(
            $department,
            $entityId,
            $stepCode,
            $machineName,
            null,
            strtoupper($entityType)
        );

        $results['success'][] = [
            'id' => $entityId,
            'completed' => $result['completed'],
            'next_step' => $result['next_step']['step_code'] ?? 'COMPLETED'
        ];
    } catch (Exception $e) {
        $results['failed'][] = [
            'id' => $entityId,
            'error' => $e->getMessage()
        ];
    }
}

jsonSuccess($results, count($results['success']) . ' item(s) completed');
