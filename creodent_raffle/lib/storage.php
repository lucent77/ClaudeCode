<?php
/**
 * Storage Library - JSON File-based Data Management
 */

require_once __DIR__ . '/../config.php';

class Storage {
    private static $lockHandle = null;

    /**
     * Acquire file lock for concurrent access
     */
    public static function acquireLock() {
        if (self::$lockHandle === null) {
            self::$lockHandle = fopen(LOCK_FILE, 'c+');
        }
        return flock(self::$lockHandle, LOCK_EX);
    }

    /**
     * Release file lock
     */
    public static function releaseLock() {
        if (self::$lockHandle !== null) {
            flock(self::$lockHandle, LOCK_UN);
            fclose(self::$lockHandle);
            self::$lockHandle = null;
        }
    }

    /**
     * Load seed data (initial employees and prizes)
     */
    public static function loadSeed() {
        if (!file_exists(SEED_FILE)) {
            return null;
        }
        $content = file_get_contents(SEED_FILE);
        return json_decode($content, true);
    }

    /**
     * Load current state
     */
    public static function loadState() {
        if (!file_exists(STATE_FILE)) {
            return self::initializeState();
        }
        $content = file_get_contents(STATE_FILE);
        $state = json_decode($content, true);

        // Ensure all required fields exist
        if (!isset($state['version']) || $state['version'] < 2) {
            $state = self::migrateState($state);
        }

        return $state;
    }

    /**
     * Save current state
     */
    public static function saveState($state) {
        $state['last_updated'] = date('Y-m-d H:i:s');
        $content = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents(STATE_FILE, $content, LOCK_EX) !== false;
    }

    /**
     * Initialize state from seed data
     */
    public static function initializeState() {
        $seed = self::loadSeed();
        if ($seed === null) {
            return self::createDefaultState();
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

        // Process employees
        foreach ($seed['employees'] as $emp) {
            $state['employees'][] = [
                'id' => $emp['id'],
                'name' => $emp['name'],
                'status' => 'pending',
                'won_prize_id' => null,
                'won_prize_name' => null
            ];
        }

        // Process prizes
        foreach ($seed['prizes'] as $prize) {
            $state['prizes'][] = [
                'id' => $prize['id'],
                'num' => $prize['num'],
                'name' => $prize['name'],
                'display_name' => $prize['display_name'] ?? $prize['name'],
                'tier' => $prize['tier'] ?? 'regular',
                'status' => 'available',
                'won_by_employee_id' => null,
                'won_by_employee_name' => null
            ];
        }

        // Set default jackpot order
        $jackpotPrizes = array_filter($state['prizes'], fn($p) => $p['tier'] === 'jackpot');
        $state['settings']['jackpot_order'] = array_values(array_map(fn($p) => $p['id'], $jackpotPrizes));

        self::saveState($state);
        return $state;
    }

    /**
     * Create default empty state
     */
    private static function createDefaultState() {
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
     * Migrate old state format to new version
     */
    private static function migrateState($oldState) {
        $newState = self::createDefaultState();

        // Copy over existing data
        if (isset($oldState['employees'])) $newState['employees'] = $oldState['employees'];
        if (isset($oldState['prizes'])) $newState['prizes'] = $oldState['prizes'];
        if (isset($oldState['spins'])) $newState['spins'] = $oldState['spins'];
        if (isset($oldState['spin_count'])) $newState['spin_count'] = $oldState['spin_count'];
        if (isset($oldState['employee_selections'])) $newState['employee_selections'] = $oldState['employee_selections'];
        if (isset($oldState['prize_selections'])) $newState['prize_selections'] = $oldState['prize_selections'];
        if (isset($oldState['settings'])) {
            $newState['settings'] = array_merge($newState['settings'], $oldState['settings']);
        }

        return $newState;
    }

    /**
     * Reset state to initial seed data
     */
    public static function resetState() {
        if (file_exists(STATE_FILE)) {
            unlink(STATE_FILE);
        }
        return self::initializeState();
    }

    /**
     * Get employee by ID
     */
    public static function getEmployee($state, $employeeId) {
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
    public static function getPrize($state, $prizeId) {
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
    public static function updateEmployee(&$state, $employeeId, $updates) {
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
    public static function updatePrize(&$state, $prizeId, $updates) {
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
     * Save employee selections
     */
    public static function saveSelections(&$state, $employeeId, $prizeIds) {
        // Clear old selections for this employee
        if (isset($state['employee_selections'][$employeeId])) {
            $oldSelections = $state['employee_selections'][$employeeId];
            foreach ($oldSelections as $oldPrizeId) {
                if (isset($state['prize_selections'][$oldPrizeId])) {
                    $state['prize_selections'][$oldPrizeId] = array_values(
                        array_filter($state['prize_selections'][$oldPrizeId], fn($e) => $e !== $employeeId)
                    );
                }
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

        return true;
    }

    /**
     * Get employees who selected a specific prize
     */
    public static function getPrizeSelectors($state, $prizeId) {
        if (!isset($state['prize_selections'][$prizeId])) {
            return [];
        }

        $selectors = [];
        foreach ($state['prize_selections'][$prizeId] as $empId) {
            $emp = self::getEmployee($state, $empId);
            if ($emp && $emp['status'] === 'pending') {
                $selectors[] = $emp;
            }
        }

        return $selectors;
    }

    /**
     * Get all pending employees (haven't won yet)
     */
    public static function getPendingEmployees($state) {
        return array_filter($state['employees'], fn($emp) => $emp['status'] === 'pending');
    }

    /**
     * Get available prizes
     */
    public static function getAvailablePrizes($state, $includeJackpot = true) {
        return array_filter($state['prizes'], function($prize) use ($includeJackpot, $state) {
            if ($prize['status'] !== 'available') return false;
            if (!$includeJackpot && $prize['tier'] === 'jackpot') return false;

            // Check if jackpot is unlocked
            if ($prize['tier'] === 'jackpot') {
                $jackpotOrder = $state['settings']['jackpot_order'] ?? [];
                $unlockSpins = $state['settings']['unlock_spins'] ?? DEFAULT_UNLOCK_SPINS;
                $jackpotIndex = array_search($prize['id'], $jackpotOrder);

                if ($jackpotIndex !== false && isset($unlockSpins[$jackpotIndex])) {
                    if ($state['spin_count'] < $unlockSpins[$jackpotIndex]) {
                        return false; // Jackpot not yet unlocked
                    }
                }
            }

            return true;
        });
    }

    /**
     * Perform a spin (raffle draw)
     */
    public static function performSpin(&$state, $prizeId, $winnerId) {
        $prize = self::getPrize($state, $prizeId);
        $winner = self::getEmployee($state, $winnerId);

        if (!$prize || !$winner) {
            return false;
        }

        // Update prize
        self::updatePrize($state, $prizeId, [
            'status' => 'won',
            'won_by_employee_id' => $winnerId,
            'won_by_employee_name' => $winner['name']
        ]);

        // Update employee
        self::updateEmployee($state, $winnerId, [
            'status' => 'done',
            'won_prize_id' => $prizeId,
            'won_prize_name' => $prize['name']
        ]);

        // Clear winner's selections
        if (isset($state['employee_selections'][$winnerId])) {
            $oldSelections = $state['employee_selections'][$winnerId];
            foreach ($oldSelections as $oldPrizeId) {
                if (isset($state['prize_selections'][$oldPrizeId])) {
                    $state['prize_selections'][$oldPrizeId] = array_values(
                        array_filter($state['prize_selections'][$oldPrizeId], fn($e) => $e !== $winnerId)
                    );
                }
            }
            unset($state['employee_selections'][$winnerId]);
        }

        // Record spin
        $spin = [
            'spin_number' => $state['spin_count'] + 1,
            'prize_id' => $prizeId,
            'prize_name' => $prize['name'],
            'winner_id' => $winnerId,
            'winner_name' => $winner['name'],
            'is_jackpot' => $prize['tier'] === 'jackpot',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $state['spins'][] = $spin;
        $state['spin_count']++;

        return $spin;
    }

    /**
     * Undo last spin
     */
    public static function undoLastSpin(&$state) {
        if (empty($state['spins'])) {
            return false;
        }

        $lastSpin = array_pop($state['spins']);
        $state['spin_count']--;

        // Restore prize
        self::updatePrize($state, $lastSpin['prize_id'], [
            'status' => 'available',
            'won_by_employee_id' => null,
            'won_by_employee_name' => null
        ]);

        // Restore employee
        self::updateEmployee($state, $lastSpin['winner_id'], [
            'status' => 'pending',
            'won_prize_id' => null,
            'won_prize_name' => null
        ]);

        return $lastSpin;
    }

    /**
     * Export results to CSV format
     */
    public static function exportCSV($state) {
        $csv = "Spin #,Prize ID,Prize Name,Winner ID,Winner Name,Is Jackpot,Timestamp\n";

        foreach ($state['spins'] as $spin) {
            $csv .= sprintf(
                "%d,%s,\"%s\",%s,\"%s\",%s,%s\n",
                $spin['spin_number'],
                $spin['prize_id'],
                str_replace('"', '""', $spin['prize_name']),
                $spin['winner_id'],
                str_replace('"', '""', $spin['winner_name']),
                $spin['is_jackpot'] ? 'Yes' : 'No',
                $spin['timestamp']
            );
        }

        return $csv;
    }

    /**
     * Get statistics
     */
    public static function getStats($state) {
        $totalEmployees = count($state['employees']);
        $doneEmployees = count(array_filter($state['employees'], fn($e) => $e['status'] === 'done'));
        $pendingEmployees = $totalEmployees - $doneEmployees;

        $totalPrizes = count($state['prizes']);
        $wonPrizes = count(array_filter($state['prizes'], fn($p) => $p['status'] === 'won'));
        $availablePrizes = $totalPrizes - $wonPrizes;

        $employeesWithSelections = count($state['employee_selections']);

        // Jackpot status
        $jackpotOrder = $state['settings']['jackpot_order'] ?? [];
        $unlockSpins = $state['settings']['unlock_spins'] ?? DEFAULT_UNLOCK_SPINS;
        $jackpotStatus = [];

        foreach ($jackpotOrder as $index => $jackpotId) {
            $prize = self::getPrize($state, $jackpotId);
            $unlockAt = $unlockSpins[$index] ?? 0;
            $isUnlocked = $state['spin_count'] >= $unlockAt;
            $isWon = $prize && $prize['status'] === 'won';

            $jackpotStatus[] = [
                'id' => $jackpotId,
                'name' => $prize ? $prize['name'] : 'Unknown',
                'stage' => $index + 1,
                'unlock_at' => $unlockAt,
                'is_unlocked' => $isUnlocked,
                'is_won' => $isWon,
                'spins_remaining' => max(0, $unlockAt - $state['spin_count'])
            ];
        }

        return [
            'total_employees' => $totalEmployees,
            'done_employees' => $doneEmployees,
            'pending_employees' => $pendingEmployees,
            'total_prizes' => $totalPrizes,
            'won_prizes' => $wonPrizes,
            'available_prizes' => $availablePrizes,
            'spin_count' => $state['spin_count'],
            'employees_with_selections' => $employeesWithSelections,
            'jackpot_status' => $jackpotStatus,
            'progress_percent' => $totalPrizes > 0 ? round(($wonPrizes / $totalPrizes) * 100, 1) : 0
        ];
    }
}
