<?php
/**
 * Data Storage Library
 * Handles JSON file-based data storage with file locking
 */

require_once __DIR__ . '/../config.php';

/**
 * Acquire a file lock for safe concurrent access
 */
function acquireLock($timeout = 5) {
    $lockFile = fopen(LOCK_FILE, 'c');
    if (!$lockFile) {
        return false;
    }

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
 * Release a file lock
 */
function releaseLock($lockFile) {
    if ($lockFile) {
        flock($lockFile, LOCK_UN);
        fclose($lockFile);
    }
}

/**
 * Load seed data (initial employees and prizes)
 */
function loadSeedData() {
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
    $state['last_updated'] = date('Y-m-d H:i:s');
    $content = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents(STATE_FILE, $content) !== false;
}

/**
 * Initialize state from seed data
 */
function initializeState() {
    $seed = loadSeedData();

    if (!$seed) {
        return createDefaultState();
    }

    $state = [
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
        'last_updated' => date('Y-m-d H:i:s')
    ];

    // Initialize employees
    foreach ($seed['employees'] as $emp) {
        $state['employees'][] = [
            'id' => $emp['id'],
            'name' => $emp['name'],
            'status' => 'pending',
            'won_prize_id' => null,
            'won_prize_name' => null
        ];
    }

    // Initialize prizes
    foreach ($seed['prizes'] as $prize) {
        $state['prizes'][] = [
            'id' => $prize['id'],
            'num' => $prize['num'],
            'name' => $prize['name'],
            'display_name' => $prize['display_name'] ?? $prize['name'],
            'tier' => $prize['tier'] ?? 'regular',
            'status' => 'available',
            'won_by_employee_id' => null
        ];
    }

    saveState($state);
    return $state;
}

/**
 * Create default state structure
 */
function createDefaultState() {
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
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

/**
 * Reset state to initial seed data
 */
function resetState() {
    if (file_exists(STATE_FILE)) {
        unlink(STATE_FILE);
    }
    return initializeState();
}

/**
 * Get employee by ID
 */
function getEmployee($state, $employeeId) {
    foreach ($state['employees'] as $emp) {
        if ($emp['id'] === $employeeId) {
            return $emp;
        }
    }
    return null;
}

/**
 * Get prize by ID
 */
function getPrize($state, $prizeId) {
    foreach ($state['prizes'] as $prize) {
        if ($prize['id'] === $prizeId) {
            return $prize;
        }
    }
    return null;
}

/**
 * Update employee in state
 */
function updateEmployee(&$state, $employeeId, $updates) {
    foreach ($state['employees'] as &$emp) {
        if ($emp['id'] === $employeeId) {
            foreach ($updates as $key => $value) {
                $emp[$key] = $value;
            }
            return true;
        }
    }
    return false;
}

/**
 * Update prize in state
 */
function updatePrize(&$state, $prizeId, $updates) {
    foreach ($state['prizes'] as &$prize) {
        if ($prize['id'] === $prizeId) {
            foreach ($updates as $key => $value) {
                $prize[$key] = $value;
            }
            return true;
        }
    }
    return false;
}

/**
 * Get available prizes (not yet won)
 */
function getAvailablePrizes($state, $includeJackpots = true) {
    $available = [];
    $jackpotOrder = $state['settings']['jackpot_order'] ?? [];

    foreach ($state['prizes'] as $prize) {
        if ($prize['status'] === 'available') {
            // Filter out jackpots if not included
            if (!$includeJackpots && $prize['tier'] === 'jackpot') {
                continue;
            }
            // Filter out unordered jackpots
            if ($prize['tier'] === 'jackpot' && !in_array($prize['id'], $jackpotOrder)) {
                continue;
            }
            $available[] = $prize;
        }
    }

    return $available;
}

/**
 * Get pending employees (haven't won yet)
 */
function getPendingEmployees($state) {
    $pending = [];
    foreach ($state['employees'] as $emp) {
        if ($emp['status'] === 'pending') {
            $pending[] = $emp;
        }
    }
    return $pending;
}

/**
 * Get employees who selected a specific prize
 */
function getSelectorsForPrize($state, $prizeId) {
    $selectors = $state['prize_selections'][$prizeId] ?? [];
    $employees = [];

    foreach ($selectors as $empId) {
        $emp = getEmployee($state, $empId);
        if ($emp && $emp['status'] === 'pending') {
            $employees[] = $emp;
        }
    }

    return $employees;
}

/**
 * Count selections for each prize
 */
function countPrizeSelections($state) {
    $counts = [];
    foreach ($state['prizes'] as $prize) {
        $selectors = getSelectorsForPrize($state, $prize['id']);
        $counts[$prize['id']] = count($selectors);
    }
    return $counts;
}

/**
 * Export raffle results to CSV format
 */
function exportResultsCSV($state) {
    $csv = "Spin #,Employee ID,Employee Name,Prize ID,Prize Name,Timestamp\n";

    foreach ($state['spins'] as $spin) {
        $csv .= sprintf(
            "%d,%s,\"%s\",%s,\"%s\",%s\n",
            $spin['spin_number'],
            $spin['employee_id'],
            str_replace('"', '""', $spin['employee_name']),
            $spin['prize_id'],
            str_replace('"', '""', $spin['prize_name']),
            $spin['timestamp']
        );
    }

    return $csv;
}
