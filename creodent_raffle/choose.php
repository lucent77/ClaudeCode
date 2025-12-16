<?php
/**
 * CREODENT HV Raffle 2025 - Employee Prize Selection Page
 * Optimized for vertical/portrait monitors
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/utils.php';

// Get employee ID
$employeeId = getParam('id');

if (!$employeeId || !isValidEmployeeId($employeeId)) {
    redirect('index.php?tab=employees&error=invalid_employee');
}

// Load state
Storage::acquireLock();
$state = Storage::loadState();

// Get employee
$employee = Storage::getEmployee($state, $employeeId);

if (!$employee) {
    Storage::releaseLock();
    redirect('index.php?tab=employees&error=employee_not_found');
}

// Check if already won
if ($employee['status'] === 'done') {
    Storage::releaseLock();
    redirect('index.php?tab=employees&error=already_won');
}

// Handle form submission
$message = null;
$messageType = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPrizes = postParam('selected_prizes');

    if ($selectedPrizes) {
        $prizeIds = json_decode($selectedPrizes, true);

        if (is_array($prizeIds) && count($prizeIds) > 0 && count($prizeIds) <= MAX_SELECTIONS) {
            // Validate prize IDs
            $validPrizes = true;
            foreach ($prizeIds as $prizeId) {
                if (!isValidPrizeId($prizeId)) {
                    $validPrizes = false;
                    break;
                }
                $prize = Storage::getPrize($state, $prizeId);
                if (!$prize || $prize['status'] !== 'available' || $prize['tier'] === 'jackpot') {
                    $validPrizes = false;
                    break;
                }
            }

            if ($validPrizes) {
                Storage::saveSelections($state, $employeeId, $prizeIds);
                Storage::saveState($state);
                $message = 'Your selections have been saved successfully!';
                $messageType = 'success';
            } else {
                $message = 'Invalid prize selection. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = 'Please select between 1 and ' . MAX_SELECTIONS . ' prizes.';
            $messageType = 'error';
        }
    }
}

// Get existing selections
$existingSelections = $state['employee_selections'][$employeeId] ?? [];

// Get available prizes (excluding jackpot)
$availablePrizes = array_values(array_filter($state['prizes'], function($prize) {
    return $prize['status'] === 'available' && $prize['tier'] !== 'jackpot';
}));
usort($availablePrizes, fn($a, $b) => $a['num'] - $b['num']);

Storage::releaseLock();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Prizes - <?= h($employee['name']) ?> | CREODENT HV Raffle 2025</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Portrait Mode Specific Styles */
        body {
            min-height: 100vh;
        }

        .portrait-container {
            max-width: 100%;
            padding: 20px;
        }

        .employee-header {
            text-align: center;
            padding: 24px;
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 2px solid var(--accent);
            margin-bottom: 24px;
        }

        .employee-header h2 {
            font-size: 36px;
            margin-bottom: 8px;
        }

        .instructions {
            background: var(--bg3);
            padding: 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 24px;
            text-align: center;
        }

        .instructions p {
            font-size: 22px;
            margin: 0;
        }

        .prize-grid-portrait {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .prize-card-select {
            padding: 20px;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .prize-card-select .prize-name {
            font-size: 16px;
            margin-top: 28px;
        }

        .selection-panel {
            position: sticky;
            top: 0;
            background: var(--bg1);
            padding: 16px 0;
            z-index: 50;
            border-bottom: 2px solid var(--border);
            margin-bottom: 24px;
        }

        .selection-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .save-btn-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(transparent, var(--bg1) 20%);
            z-index: 100;
        }

        .save-btn-container .btn {
            width: 100%;
            padding: 24px;
            font-size: 26px;
        }

        /* Add padding at bottom for fixed button */
        .prize-grid-portrait {
            padding-bottom: 120px;
        }

        @media (min-width: 800px) {
            .portrait-container {
                max-width: 700px;
                margin: 0 auto;
            }

            .prize-grid-portrait {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="portrait-container">
        <!-- Back Button -->
        <div style="margin-bottom: 16px;">
            <a href="index.php?tab=employees" class="btn btn-sm">← Back to Employee List</a>
        </div>

        <!-- Employee Header -->
        <div class="employee-header">
            <h2>👤 <?= h($employee['name']) ?></h2>
            <p class="text-muted"><?= h($employee['id']) ?></p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
            <?= h($message) ?>
        </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="instructions">
            <p>🎁 Select up to <strong><?= MAX_SELECTIONS ?></strong> prizes you want to win!</p>
        </div>

        <!-- Selection Panel (Sticky) -->
        <div class="selection-panel">
            <div class="selection-summary">
                <div id="selection-list" class="selection-list">
                    <?php if (empty($existingSelections)): ?>
                    <div class="no-selection">No prizes selected yet. Tap on prizes below to select.</div>
                    <?php else: ?>
                        <?php foreach ($existingSelections as $prizeId):
                            $prize = Storage::getPrize($state, $prizeId);
                            if ($prize):
                        ?>
                        <div class="selection-item" data-prize-id="<?= h($prizeId) ?>">
                            <span><?= h($prize['display_name']) ?></span>
                            <span class="remove" onclick="removePrizeSelection('<?= h($prizeId) ?>')">&times;</span>
                        </div>
                        <?php endif; endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Selection Counter -->
        <div id="selection-counter" class="selection-counter <?= count($existingSelections) >= MAX_SELECTIONS ? 'full' : '' ?>">
            <span id="selection-count" class="count"><?= count($existingSelections) ?></span>
            <span class="label">/ <?= MAX_SELECTIONS ?></span>
        </div>

        <!-- Hidden form -->
        <form id="selection-form" method="POST" style="display: none;">
            <input type="hidden" id="employee-id" name="employee_id" value="<?= h($employeeId) ?>">
            <input type="hidden" id="selected-prizes-input" name="selected_prizes" value="">
        </form>

        <!-- Saved selections data -->
        <div id="saved-selections" data-selections='<?= json_encode($existingSelections) ?>' style="display:none;"></div>

        <!-- Search -->
        <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="search-input" class="search-input"
                   placeholder="Search prizes..." autocomplete="off">
        </div>

        <!-- Prize Grid -->
        <div class="prize-grid-portrait">
            <?php foreach ($availablePrizes as $prize): ?>
            <div class="prize-card prize-card-select selectable <?= in_array($prize['id'], $existingSelections) ? 'selected' : '' ?>"
                 data-prize-id="<?= h($prize['id']) ?>"
                 data-prize-name="<?= h($prize['display_name']) ?>"
                 data-searchable="<?= h($prize['name'] . ' ' . $prize['display_name']) ?>">
                <span class="prize-num">#<?= $prize['num'] ?></span>
                <div class="prize-name"><?= h($prize['display_name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Save Button (Fixed at bottom) -->
        <div class="save-btn-container">
            <button type="button" class="btn btn-success btn-lg" onclick="saveSelections()">
                ✓ Save My Selections
            </button>
        </div>
    </div>

    <script src="assets/app.js"></script>
    <script>
        // Set max selections from PHP
        RaffleApp.maxSelections = <?= MAX_SELECTIONS ?>;
    </script>
</body>
</html>
