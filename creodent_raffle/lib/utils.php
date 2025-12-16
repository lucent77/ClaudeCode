<?php
/**
 * Utility Functions Library
 */

/**
 * Safely escape HTML output
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a secure random string
 */
function generateRandomString($length = 16) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send error JSON response
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * Get JSON input from POST request
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

/**
 * Validate employee ID format
 */
function isValidEmployeeId($id) {
    return preg_match('/^E\d{3}$/', $id);
}

/**
 * Validate prize ID format
 */
function isValidPrizeId($id) {
    return preg_match('/^P\d{3}$/', $id);
}

/**
 * Format timestamp for display
 */
function formatTimestamp($timestamp) {
    return date('Y-m-d H:i:s', strtotime($timestamp));
}

/**
 * Calculate jackpot unlock status
 */
function getJackpotUnlockStatus($state) {
    $spinCount = $state['spin_count'];
    $unlockSpins = $state['settings']['unlock_spins'] ?? DEFAULT_UNLOCK_SPINS;
    $jackpotOrder = $state['settings']['jackpot_order'] ?? [];

    $status = [];
    for ($i = 0; $i < count($unlockSpins); $i++) {
        $threshold = $unlockSpins[$i];
        $jackpotId = $jackpotOrder[$i] ?? null;

        // Check if this jackpot has been won
        $isWon = false;
        if ($jackpotId) {
            foreach ($state['prizes'] as $prize) {
                if ($prize['id'] === $jackpotId && $prize['status'] === 'won') {
                    $isWon = true;
                    break;
                }
            }
        }

        $status[] = [
            'tier' => $i + 1,
            'threshold' => $threshold,
            'unlocked' => $spinCount >= $threshold,
            'jackpot_id' => $jackpotId,
            'won' => $isWon
        ];
    }

    return $status;
}

/**
 * Check if a specific jackpot tier is unlocked and available
 */
function isJackpotAvailable($state, $tier) {
    $status = getJackpotUnlockStatus($state);

    if (!isset($status[$tier - 1])) {
        return false;
    }

    $tierStatus = $status[$tier - 1];
    return $tierStatus['unlocked'] && !$tierStatus['won'] && $tierStatus['jackpot_id'];
}

/**
 * Get the next available jackpot (if any)
 */
function getNextAvailableJackpot($state) {
    $status = getJackpotUnlockStatus($state);

    foreach ($status as $tierStatus) {
        if ($tierStatus['unlocked'] && !$tierStatus['won'] && $tierStatus['jackpot_id']) {
            return $tierStatus;
        }
    }

    return null;
}

/**
 * Sort prizes by selector count (ascending)
 */
function sortPrizesBySelectors($prizes, $selectionCounts) {
    usort($prizes, function($a, $b) use ($selectionCounts) {
        $countA = $selectionCounts[$a['id']] ?? 0;
        $countB = $selectionCounts[$b['id']] ?? 0;

        if ($countA === $countB) {
            return $a['num'] - $b['num'];
        }

        return $countA - $countB;
    });

    return $prizes;
}

/**
 * Get statistics summary
 */
function getStatistics($state) {
    $totalEmployees = count($state['employees']);
    $pendingEmployees = count(getPendingEmployees($state));
    $completedEmployees = $totalEmployees - $pendingEmployees;

    $totalPrizes = count($state['prizes']);
    $availablePrizes = count(array_filter($state['prizes'], fn($p) => $p['status'] === 'available'));
    $wonPrizes = $totalPrizes - $availablePrizes;

    $employeesWithSelections = count($state['employee_selections']);

    return [
        'total_employees' => $totalEmployees,
        'pending_employees' => $pendingEmployees,
        'completed_employees' => $completedEmployees,
        'total_prizes' => $totalPrizes,
        'available_prizes' => $availablePrizes,
        'won_prizes' => $wonPrizes,
        'spin_count' => $state['spin_count'],
        'employees_with_selections' => $employeesWithSelections,
        'jackpot_status' => getJackpotUnlockStatus($state)
    ];
}
