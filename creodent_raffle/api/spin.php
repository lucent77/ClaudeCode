<?php
/**
 * API: Execute Raffle Spin
 * POST /api/spin.php
 *
 * Body: { "prize_id": "P001" }
 *
 * Draws a winner from employees who selected this prize.
 * If no selectors, draws from all pending employees.
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

$input = getJsonInput();

if (empty($input['prize_id'])) {
    jsonError('Prize ID is required');
}

$prizeId = $input['prize_id'];

if (!isValidPrizeId($prizeId)) {
    jsonError('Invalid prize ID format');
}

$lock = acquireLock();
if (!$lock) {
    jsonError('System busy, please try again', 503);
}

try {
    $state = loadState();

    // Verify prize exists and is available
    $prize = getPrize($state, $prizeId);
    if (!$prize) {
        releaseLock($lock);
        jsonError('Prize not found');
    }

    if ($prize['status'] !== 'available') {
        releaseLock($lock);
        jsonError('Prize has already been won');
    }

    // Check jackpot restrictions
    if ($prize['tier'] === 'jackpot') {
        $jackpotOrder = $state['settings']['jackpot_order'] ?? [];
        $unlockSpins = $state['settings']['unlock_spins'] ?? DEFAULT_UNLOCK_SPINS;

        // Check if this jackpot is in the order
        $jackpotIndex = array_search($prizeId, $jackpotOrder);
        if ($jackpotIndex === false) {
            releaseLock($lock);
            jsonError('This jackpot prize has not been configured');
        }

        // Check if unlocked
        $requiredSpins = $unlockSpins[$jackpotIndex] ?? PHP_INT_MAX;
        if ($state['spin_count'] < $requiredSpins) {
            releaseLock($lock);
            jsonError("Jackpot tier " . ($jackpotIndex + 1) . " requires {$requiredSpins} spins to unlock. Current: {$state['spin_count']}");
        }

        // Check if previous jackpots are done
        for ($i = 0; $i < $jackpotIndex; $i++) {
            $prevJackpotId = $jackpotOrder[$i];
            $prevPrize = getPrize($state, $prevJackpotId);
            if ($prevPrize && $prevPrize['status'] === 'available') {
                releaseLock($lock);
                jsonError('Previous jackpot prizes must be won first');
            }
        }
    }

    // Get eligible employees
    $selectors = getSelectorsForPrize($state, $prizeId);
    $eligibleEmployees = [];

    if (count($selectors) > 0) {
        // Draw from employees who selected this prize
        $eligibleEmployees = $selectors;
    } else {
        // Draw from all pending employees who haven't selected any available prize
        // (or all pending if no other option)
        $pendingEmployees = getPendingEmployees($state);

        if (count($pendingEmployees) === 0) {
            releaseLock($lock);
            jsonError('No eligible employees remaining');
        }

        $eligibleEmployees = $pendingEmployees;
    }

    if (count($eligibleEmployees) === 0) {
        releaseLock($lock);
        jsonError('No eligible employees for this prize');
    }

    // Random selection
    $winnerIndex = random_int(0, count($eligibleEmployees) - 1);
    $winner = $eligibleEmployees[$winnerIndex];

    // Update state
    $state['spin_count']++;
    $spinNumber = $state['spin_count'];
    $timestamp = date('Y-m-d H:i:s');

    // Update employee status
    updateEmployee($state, $winner['id'], [
        'status' => 'done',
        'won_prize_id' => $prizeId,
        'won_prize_name' => $prize['name']
    ]);

    // Update prize status
    updatePrize($state, $prizeId, [
        'status' => 'won',
        'won_by_employee_id' => $winner['id']
    ]);

    // Remove winner's selections from prize_selections
    $winnerSelections = $state['employee_selections'][$winner['id']] ?? [];
    foreach ($winnerSelections as $selectedPrizeId) {
        if (isset($state['prize_selections'][$selectedPrizeId])) {
            $state['prize_selections'][$selectedPrizeId] = array_values(
                array_filter($state['prize_selections'][$selectedPrizeId], fn($id) => $id !== $winner['id'])
            );
        }
    }

    // Clear winner's employee selections
    unset($state['employee_selections'][$winner['id']]);

    // Record spin
    $state['spins'][] = [
        'spin_number' => $spinNumber,
        'prize_id' => $prizeId,
        'prize_name' => $prize['name'],
        'prize_tier' => $prize['tier'],
        'employee_id' => $winner['id'],
        'employee_name' => $winner['name'],
        'eligible_count' => count($eligibleEmployees),
        'was_selector' => in_array($winner['id'], array_map(fn($e) => $e['id'], $selectors)),
        'timestamp' => $timestamp
    ];

    saveState($state);
    releaseLock($lock);

    // Check for newly unlocked jackpots
    $jackpotStatus = getJackpotUnlockStatus($state);
    $newlyUnlocked = null;
    foreach ($jackpotStatus as $status) {
        if ($status['unlocked'] && !$status['won'] && $status['threshold'] === $spinNumber) {
            $newlyUnlocked = $status;
            break;
        }
    }

    jsonResponse([
        'success' => true,
        'spin_number' => $spinNumber,
        'winner' => [
            'id' => $winner['id'],
            'name' => $winner['name']
        ],
        'prize' => [
            'id' => $prizeId,
            'name' => $prize['name'],
            'tier' => $prize['tier']
        ],
        'eligible_count' => count($eligibleEmployees),
        'jackpot_unlocked' => $newlyUnlocked,
        'statistics' => getStatistics($state)
    ]);

} catch (Exception $e) {
    releaseLock($lock);
    jsonError('Failed to execute spin: ' . $e->getMessage(), 500);
}
