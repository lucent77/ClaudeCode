<?php
/**
 * API: Save Employee Selections
 * POST /api/select.php
 *
 * Body: { "employee_id": "E001", "prize_ids": ["P001", "P002", "P003", "P004", "P005"] }
 */

require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/utils.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$input = getJsonInput();

// Validate input
if (empty($input['employee_id'])) {
    jsonError('Employee ID is required');
}

if (empty($input['prize_ids']) || !is_array($input['prize_ids'])) {
    jsonError('Prize IDs must be an array');
}

$employeeId = $input['employee_id'];
$prizeIds = $input['prize_ids'];

// Validate employee ID format
if (!isValidEmployeeId($employeeId)) {
    jsonError('Invalid employee ID format');
}

// Validate prize count
if (count($prizeIds) !== MAX_SELECTIONS) {
    jsonError('Must select exactly ' . MAX_SELECTIONS . ' prizes');
}

// Validate prize ID formats
foreach ($prizeIds as $prizeId) {
    if (!isValidPrizeId($prizeId)) {
        jsonError('Invalid prize ID format: ' . $prizeId);
    }
}

// Check for duplicates
if (count($prizeIds) !== count(array_unique($prizeIds))) {
    jsonError('Duplicate prize selections are not allowed');
}

$lock = acquireLock();
if (!$lock) {
    jsonError('System busy, please try again', 503);
}

try {
    $state = loadState();

    // Verify employee exists
    $employee = getEmployee($state, $employeeId);
    if (!$employee) {
        releaseLock($lock);
        jsonError('Employee not found');
    }

    // Check if employee already won
    if ($employee['status'] === 'done') {
        releaseLock($lock);
        jsonError('Employee has already won a prize');
    }

    // Verify all prizes exist and are available
    foreach ($prizeIds as $prizeId) {
        $prize = getPrize($state, $prizeId);
        if (!$prize) {
            releaseLock($lock);
            jsonError('Prize not found: ' . $prizeId);
        }
        if ($prize['status'] !== 'available') {
            releaseLock($lock);
            jsonError('Prize is no longer available: ' . $prize['name']);
        }
        // Check if trying to select a jackpot (if hidden)
        if ($prize['tier'] === 'jackpot' && ($state['settings']['hide_jackpot_from_picks'] ?? true)) {
            releaseLock($lock);
            jsonError('Jackpot prizes cannot be selected');
        }
    }

    // Remove old selections for this employee
    $oldSelections = $state['employee_selections'][$employeeId] ?? [];
    foreach ($oldSelections as $oldPrizeId) {
        if (isset($state['prize_selections'][$oldPrizeId])) {
            $state['prize_selections'][$oldPrizeId] = array_values(
                array_filter($state['prize_selections'][$oldPrizeId], fn($id) => $id !== $employeeId)
            );
        }
    }

    // Save new selections
    $state['employee_selections'][$employeeId] = $prizeIds;

    // Update prize_selections (reverse index)
    foreach ($prizeIds as $prizeId) {
        if (!isset($state['prize_selections'][$prizeId])) {
            $state['prize_selections'][$prizeId] = [];
        }
        if (!in_array($employeeId, $state['prize_selections'][$prizeId])) {
            $state['prize_selections'][$prizeId][] = $employeeId;
        }
    }

    saveState($state);
    releaseLock($lock);

    jsonResponse([
        'success' => true,
        'message' => 'Selections saved successfully',
        'employee_id' => $employeeId,
        'selections' => $prizeIds
    ]);

} catch (Exception $e) {
    releaseLock($lock);
    jsonError('Failed to save selections: ' . $e->getMessage(), 500);
}
