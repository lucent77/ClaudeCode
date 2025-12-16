<?php
/**
 * API: Undo Last Spin
 * POST /api/undo.php
 *
 * Reverts the last raffle spin
 */

require_once __DIR__ . '/../lib/storage.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Require admin authentication
requireAdmin();

$lock = acquireLock();
if (!$lock) {
    jsonError('System busy, please try again', 503);
}

try {
    $state = loadState();

    if (count($state['spins']) === 0) {
        releaseLock($lock);
        jsonError('No spins to undo');
    }

    // Get the last spin
    $lastSpin = array_pop($state['spins']);

    // Restore employee status
    updateEmployee($state, $lastSpin['employee_id'], [
        'status' => 'pending',
        'won_prize_id' => null,
        'won_prize_name' => null
    ]);

    // Restore prize status
    updatePrize($state, $lastSpin['prize_id'], [
        'status' => 'available',
        'won_by_employee_id' => null
    ]);

    // Decrement spin count
    $state['spin_count']--;

    // Note: We cannot restore the employee's selections as they were cleared
    // This is a limitation of the undo feature

    saveState($state);
    releaseLock($lock);

    jsonResponse([
        'success' => true,
        'message' => 'Last spin undone successfully',
        'undone_spin' => $lastSpin,
        'current_spin_count' => $state['spin_count'],
        'statistics' => getStatistics($state)
    ]);

} catch (Exception $e) {
    releaseLock($lock);
    jsonError('Failed to undo spin: ' . $e->getMessage(), 500);
}
