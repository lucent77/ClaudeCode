<?php
/**
 * CREODENT HV Raffle 2025 - Data Storage
 *
 * Handles all data operations with file locking for concurrency
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/utils.php';

/**
 * Acquire file lock for data operations
 */
function acquireLock($timeout = 10) {
    $lockFile = fopen(LOCK_FILE, 'c');
    $startTime = time();

    while (!flock($lockFile, LOCK_EX | LOCK_NB)) {
        if (time() - $startTime >= $timeout) {
            fclose($lockFile);
            return false;
        }
        usleep(100000); // 100ms
    }

    return $lockFile;
}

/**
 * Release file lock
 */
function releaseLock($lockFile) {
    flock($lockFile, LOCK_UN);
    fclose($lockFile);
}

/**
 * Load seed data
 */
function loadSeed() {
    if (!file_exists(SEED_FILE)) {
        return null;
    }

    $content = file_get_contents(SEED_FILE);
    return json_decode($content, true);
}

/**
 * Load current state
 */
function loadState() {
    if (!file_exists(STATE_FILE)) {
        return initializeState();
    }

    $content = file_get_contents(STATE_FILE);
    $state = json_decode($content, true);

    if (!$state) {
        return initializeState();
    }

    return $state;
}

/**
 * Save current state
 */
function saveState($state) {
    $state['last_updated'] = time();

    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(STATE_FILE, $json) !== false;
}

/**
 * Initialize state from seed data
 */
function initializeState() {
    $seed = loadSeed();

    if (!$seed) {
        return createEmptyState();
    }

    $state = [
        'version' => 2,
        'settings' => [
            'unlock_spins' => DEFAULT_UNLOCK_SPINS,
            'hide_jackpot_from_picks' => true,
            'jackpot_order' => ['P005', 'P006', 'P080'],
            'jackpot_limit' => JACKPOT_LIMIT
        ],
        'spin_count' => 0,
        'employees' => [],
        'prizes' => [],
        'spins' => [],
        'employee_selections' => [],
        'prize_selections' => [],
        'created_at' => time(),
        'last_updated' => time()
    ];

    // Initialize employees
    foreach ($seed['employees'] as $emp) {
        $state['employees'][$emp['id']] = [
            'id' => $emp['id'],
            'name' => $emp['name'],
            'status' => 'pending',
            'won_prize_id' => null,
            'won_prize_name' => null
        ];
        $state['employee_selections'][$emp['id']] = [];
    }

    // Initialize prizes
    foreach ($seed['prizes'] as $prize) {
        $state['prizes'][$prize['id']] = [
            'id' => $prize['id'],
            'num' => $prize['num'],
            'name' => $prize['name'],
            'display_name' => $prize['display_name'] ?? $prize['name'],
            'tier' => $prize['tier'],
            'status' => 'available',
            'won_by_employee_id' => null
        ];
        $state['prize_selections'][$prize['id']] = [];
    }

    saveState($state);
    return $state;
}

/**
 * Create empty state structure
 */
function createEmptyState() {
    return [
        'version' => 2,
        'settings' => [
            'unlock_spins' => DEFAULT_UNLOCK_SPINS,
            'hide_jackpot_from_picks' => true,
            'jackpot_order' => [],
            'jackpot_limit' => JACKPOT_LIMIT
        ],
        'spin_count' => 0,
        'employees' => [],
        'prizes' => [],
        'spins' => [],
        'employee_selections' => [],
        'prize_selections' => [],
        'created_at' => time(),
        'last_updated' => time()
    ];
}

/**
 * Get all employees
 */
function getEmployees() {
    $state = loadState();
    return $state['employees'];
}

/**
 * Get employee by ID
 */
function getEmployee($id) {
    $state = loadState();
    return isset($state['employees'][$id]) ? $state['employees'][$id] : null;
}

/**
 * Get all prizes
 */
function getPrizes() {
    $state = loadState();
    return $state['prizes'];
}

/**
 * Get prize by ID
 */
function getPrize($id) {
    $state = loadState();
    return isset($state['prizes'][$id]) ? $state['prizes'][$id] : null;
}

/**
 * Get available prizes (not won yet)
 */
function getAvailablePrizes($includeJackpot = true) {
    $state = loadState();
    $available = [];

    foreach ($state['prizes'] as $prize) {
        if ($prize['status'] === 'available') {
            if (!$includeJackpot && $prize['tier'] === 'jackpot') {
                continue;
            }
            $available[$prize['id']] = $prize;
        }
    }

    return $available;
}

/**
 * Get jackpot prizes
 */
function getJackpotPrizes() {
    $state = loadState();
    $jackpots = [];

    foreach ($state['prizes'] as $prize) {
        if ($prize['tier'] === 'jackpot') {
            $jackpots[$prize['id']] = $prize;
        }
    }

    return $jackpots;
}

/**
 * Get regular prizes (non-jackpot)
 */
function getRegularPrizes() {
    $state = loadState();
    $regular = [];

    foreach ($state['prizes'] as $prize) {
        if ($prize['tier'] === 'regular') {
            $regular[$prize['id']] = $prize;
        }
    }

    return $regular;
}

/**
 * Get employee selections
 */
function getEmployeeSelections($employeeId) {
    $state = loadState();
    return isset($state['employee_selections'][$employeeId])
        ? $state['employee_selections'][$employeeId]
        : [];
}

/**
 * Get prize selections (employees who selected this prize)
 */
function getPrizeSelections($prizeId) {
    $state = loadState();
    return isset($state['prize_selections'][$prizeId])
        ? $state['prize_selections'][$prizeId]
        : [];
}

/**
 * Save employee selections
 */
function saveEmployeeSelections($employeeId, $prizeIds) {
    $lock = acquireLock();
    if (!$lock) {
        return false;
    }

    try {
        $state = loadState();

        // Validate employee
        if (!isset($state['employees'][$employeeId])) {
            releaseLock($lock);
            return false;
        }

        // Get old selections to remove from prize_selections
        $oldSelections = $state['employee_selections'][$employeeId] ?? [];

        // Remove old selections from prize_selections
        foreach ($oldSelections as $oldPrizeId) {
            if (isset($state['prize_selections'][$oldPrizeId])) {
                $state['prize_selections'][$oldPrizeId] = array_values(
                    array_diff($state['prize_selections'][$oldPrizeId], [$employeeId])
                );
            }
        }

        // Validate prizes and add new selections
        $validPrizeIds = [];
        foreach ($prizeIds as $prizeId) {
            if (isset($state['prizes'][$prizeId])) {
                $validPrizeIds[] = $prizeId;

                // Add to prize_selections
                if (!in_array($employeeId, $state['prize_selections'][$prizeId])) {
                    $state['prize_selections'][$prizeId][] = $employeeId;
                }
            }
        }

        // Limit to MAX_SELECTIONS
        $validPrizeIds = array_slice($validPrizeIds, 0, MAX_SELECTIONS);

        // Update employee selections
        $state['employee_selections'][$employeeId] = $validPrizeIds;

        // Mark employee as done if they have selections
        if (count($validPrizeIds) > 0) {
            $state['employees'][$employeeId]['status'] = 'done';
        }

        $result = saveState($state);
        releaseLock($lock);
        return $result;

    } catch (Exception $e) {
        releaseLock($lock);
        return false;
    }
}

/**
 * Get eligible employees for a prize
 * (those who selected it and haven't won yet)
 */
function getEligibleEmployees($prizeId) {
    $state = loadState();

    $selectors = $state['prize_selections'][$prizeId] ?? [];
    $eligible = [];

    foreach ($selectors as $employeeId) {
        $employee = $state['employees'][$employeeId] ?? null;
        if ($employee && $employee['won_prize_id'] === null) {
            $eligible[] = $employee;
        }
    }

    return $eligible;
}

/**
 * Get employees who haven't won yet
 */
function getPendingEmployees() {
    $state = loadState();
    $pending = [];

    foreach ($state['employees'] as $employee) {
        if ($employee['won_prize_id'] === null) {
            $pending[$employee['id']] = $employee;
        }
    }

    return $pending;
}

/**
 * Perform a spin (draw a winner for a prize)
 */
function performSpin($prizeId, $winnerId = null) {
    $lock = acquireLock();
    if (!$lock) {
        return ['error' => 'Could not acquire lock'];
    }

    try {
        $state = loadState();

        // Validate prize
        if (!isset($state['prizes'][$prizeId])) {
            releaseLock($lock);
            return ['error' => 'Prize not found'];
        }

        $prize = $state['prizes'][$prizeId];

        if ($prize['status'] !== 'available') {
            releaseLock($lock);
            return ['error' => 'Prize already won'];
        }

        // Get eligible employees
        $eligible = [];
        $selectors = $state['prize_selections'][$prizeId] ?? [];

        foreach ($selectors as $employeeId) {
            $employee = $state['employees'][$employeeId] ?? null;
            if ($employee && $employee['won_prize_id'] === null) {
                $eligible[] = $employee;
            }
        }

        // If no selectors, use all pending employees
        $fromSelectors = true;
        if (empty($eligible)) {
            $fromSelectors = false;
            foreach ($state['employees'] as $employee) {
                if ($employee['won_prize_id'] === null) {
                    $eligible[] = $employee;
                }
            }
        }

        if (empty($eligible)) {
            releaseLock($lock);
            return ['error' => 'No eligible employees'];
        }

        // Select winner
        if ($winnerId !== null) {
            // Admin specified winner
            $winner = null;
            foreach ($eligible as $emp) {
                if ($emp['id'] === $winnerId) {
                    $winner = $emp;
                    break;
                }
            }
            if (!$winner) {
                releaseLock($lock);
                return ['error' => 'Specified winner not eligible'];
            }
        } else {
            // Random selection
            $winner = $eligible[array_rand($eligible)];
        }

        // Update state
        $state['prizes'][$prizeId]['status'] = 'won';
        $state['prizes'][$prizeId]['won_by_employee_id'] = $winner['id'];

        $state['employees'][$winner['id']]['won_prize_id'] = $prizeId;
        $state['employees'][$winner['id']]['won_prize_name'] = $prize['name'];

        $state['spin_count']++;

        // Record spin
        $spin = [
            'id' => count($state['spins']) + 1,
            'prize_id' => $prizeId,
            'prize_name' => $prize['name'],
            'winner_id' => $winner['id'],
            'winner_name' => $winner['name'],
            'from_selectors' => $fromSelectors,
            'eligible_count' => count($eligible),
            'timestamp' => time()
        ];
        $state['spins'][] = $spin;

        $result = saveState($state);
        releaseLock($lock);

        if ($result) {
            return [
                'success' => true,
                'spin' => $spin,
                'winner' => $state['employees'][$winner['id']],
                'prize' => $state['prizes'][$prizeId],
                'spin_count' => $state['spin_count']
            ];
        }

        return ['error' => 'Failed to save state'];

    } catch (Exception $e) {
        releaseLock($lock);
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Undo last spin
 */
function undoLastSpin() {
    $lock = acquireLock();
    if (!$lock) {
        return ['error' => 'Could not acquire lock'];
    }

    try {
        $state = loadState();

        if (empty($state['spins'])) {
            releaseLock($lock);
            return ['error' => 'No spins to undo'];
        }

        // Get last spin
        $lastSpin = array_pop($state['spins']);

        // Restore prize
        $prizeId = $lastSpin['prize_id'];
        if (isset($state['prizes'][$prizeId])) {
            $state['prizes'][$prizeId]['status'] = 'available';
            $state['prizes'][$prizeId]['won_by_employee_id'] = null;
        }

        // Restore employee
        $winnerId = $lastSpin['winner_id'];
        if (isset($state['employees'][$winnerId])) {
            $state['employees'][$winnerId]['won_prize_id'] = null;
            $state['employees'][$winnerId]['won_prize_name'] = null;
        }

        $state['spin_count'] = max(0, $state['spin_count'] - 1);

        $result = saveState($state);
        releaseLock($lock);

        if ($result) {
            return [
                'success' => true,
                'undone_spin' => $lastSpin,
                'spin_count' => $state['spin_count']
            ];
        }

        return ['error' => 'Failed to save state'];

    } catch (Exception $e) {
        releaseLock($lock);
        return ['error' => 'Exception: ' . $e->getMessage()];
    }
}

/**
 * Get current spin count
 */
function getSpinCount() {
    $state = loadState();
    return $state['spin_count'];
}

/**
 * Get settings
 */
function getSettings() {
    $state = loadState();
    return $state['settings'];
}

/**
 * Update settings
 */
function updateSettings($newSettings) {
    $lock = acquireLock();
    if (!$lock) {
        return false;
    }

    try {
        $state = loadState();
        $state['settings'] = array_merge($state['settings'], $newSettings);
        $result = saveState($state);
        releaseLock($lock);
        return $result;

    } catch (Exception $e) {
        releaseLock($lock);
        return false;
    }
}

/**
 * Check if jackpot is unlocked at current spin count
 */
function getUnlockedJackpots() {
    $state = loadState();
    $spinCount = $state['spin_count'];
    $unlockSpins = $state['settings']['unlock_spins'];
    $jackpotOrder = $state['settings']['jackpot_order'];

    $unlocked = [];
    for ($i = 0; $i < count($unlockSpins); $i++) {
        if ($spinCount >= $unlockSpins[$i] && isset($jackpotOrder[$i])) {
            $unlocked[] = $jackpotOrder[$i];
        }
    }

    return $unlocked;
}

/**
 * Get statistics
 */
function getStatistics() {
    $state = loadState();

    $totalEmployees = count($state['employees']);
    $employeesWithSelections = 0;
    $employeesWon = 0;

    foreach ($state['employees'] as $emp) {
        if (!empty($state['employee_selections'][$emp['id']])) {
            $employeesWithSelections++;
        }
        if ($emp['won_prize_id'] !== null) {
            $employeesWon++;
        }
    }

    $totalPrizes = count($state['prizes']);
    $prizesWon = 0;
    $jackpotTotal = 0;
    $jackpotWon = 0;

    foreach ($state['prizes'] as $prize) {
        if ($prize['status'] === 'won') {
            $prizesWon++;
        }
        if ($prize['tier'] === 'jackpot') {
            $jackpotTotal++;
            if ($prize['status'] === 'won') {
                $jackpotWon++;
            }
        }
    }

    return [
        'total_employees' => $totalEmployees,
        'employees_with_selections' => $employeesWithSelections,
        'employees_pending_selection' => $totalEmployees - $employeesWithSelections,
        'employees_won' => $employeesWon,
        'employees_pending_prize' => $totalEmployees - $employeesWon,
        'total_prizes' => $totalPrizes,
        'prizes_available' => $totalPrizes - $prizesWon,
        'prizes_won' => $prizesWon,
        'jackpot_total' => $jackpotTotal,
        'jackpot_won' => $jackpotWon,
        'spin_count' => $state['spin_count'],
        'unlocked_jackpots' => getUnlockedJackpots()
    ];
}

/**
 * Get all spins history
 */
function getSpinsHistory() {
    $state = loadState();
    return array_reverse($state['spins']);
}

/**
 * Export results to CSV format
 */
function exportResultsCSV() {
    $state = loadState();

    $headers = ['Employee ID', 'Employee Name', 'Won Prize ID', 'Won Prize Name', 'Selection Status'];
    $data = [];

    foreach ($state['employees'] as $emp) {
        $data[] = [
            $emp['id'],
            $emp['name'],
            $emp['won_prize_id'] ?? '',
            $emp['won_prize_name'] ?? '',
            $emp['status']
        ];
    }

    return arrayToCSV($data, $headers);
}

/**
 * Reset all data to seed state
 */
function resetData() {
    $lock = acquireLock();
    if (!$lock) {
        return false;
    }

    try {
        if (file_exists(STATE_FILE)) {
            unlink(STATE_FILE);
        }
        $state = initializeState();
        releaseLock($lock);
        return $state !== null;

    } catch (Exception $e) {
        releaseLock($lock);
        return false;
    }
}
