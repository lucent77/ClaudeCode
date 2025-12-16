<?php
/**
 * API: Update Settings
 * POST /api/settings.php
 *
 * Body: {
 *   "jackpot_order": ["P005", "P006", "P080"],
 *   "unlock_spins": [25, 50, 75],
 *   "hide_jackpot_from_picks": true
 * }
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

$lock = acquireLock();
if (!$lock) {
    jsonError('System busy, please try again', 503);
}

try {
    $state = loadState();

    // Update jackpot order
    if (isset($input['jackpot_order'])) {
        $jackpotOrder = $input['jackpot_order'];

        if (!is_array($jackpotOrder)) {
            releaseLock($lock);
            jsonError('jackpot_order must be an array');
        }

        if (count($jackpotOrder) > JACKPOT_LIMIT) {
            releaseLock($lock);
            jsonError('Cannot exceed ' . JACKPOT_LIMIT . ' jackpot prizes');
        }

        // Validate each prize ID
        foreach ($jackpotOrder as $prizeId) {
            if (!isValidPrizeId($prizeId)) {
                releaseLock($lock);
                jsonError('Invalid prize ID format: ' . $prizeId);
            }

            $prize = getPrize($state, $prizeId);
            if (!$prize) {
                releaseLock($lock);
                jsonError('Prize not found: ' . $prizeId);
            }

            if ($prize['tier'] !== 'jackpot') {
                releaseLock($lock);
                jsonError('Prize is not marked as jackpot: ' . $prize['name']);
            }
        }

        $state['settings']['jackpot_order'] = $jackpotOrder;
    }

    // Update unlock spins
    if (isset($input['unlock_spins'])) {
        $unlockSpins = $input['unlock_spins'];

        if (!is_array($unlockSpins)) {
            releaseLock($lock);
            jsonError('unlock_spins must be an array');
        }

        // Validate values are positive integers in ascending order
        $prev = 0;
        foreach ($unlockSpins as $spin) {
            if (!is_int($spin) || $spin <= $prev) {
                releaseLock($lock);
                jsonError('unlock_spins must be positive integers in ascending order');
            }
            $prev = $spin;
        }

        $state['settings']['unlock_spins'] = $unlockSpins;
    }

    // Update hide jackpot from picks
    if (isset($input['hide_jackpot_from_picks'])) {
        $state['settings']['hide_jackpot_from_picks'] = (bool)$input['hide_jackpot_from_picks'];
    }

    saveState($state);
    releaseLock($lock);

    jsonResponse([
        'success' => true,
        'message' => 'Settings updated successfully',
        'settings' => $state['settings']
    ]);

} catch (Exception $e) {
    releaseLock($lock);
    jsonError('Failed to update settings: ' . $e->getMessage(), 500);
}
