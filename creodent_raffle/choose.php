<?php
/**
 * CREODENT HV Raffle 2025 - Employee Prize Selection Page
 *
 * Employees select their preferred 5 prizes
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/storage.php';

// Get employee ID
$employeeId = getParam('id');

if (!$employeeId || !isValidEmployeeId($employeeId)) {
    redirect('index.php?tab=employees');
}

$employee = getEmployee($employeeId);
if (!$employee) {
    redirect('index.php?tab=employees');
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPrizes = postParam('selected_prizes', '');
    $prizeIds = array_filter(explode(',', $selectedPrizes));

    // Validate selections
    if (empty($prizeIds)) {
        $message = 'Please select at least one prize.';
        $messageType = 'warning';
    } elseif (count($prizeIds) > MAX_SELECTIONS) {
        $message = 'You can only select up to ' . MAX_SELECTIONS . ' prizes.';
        $messageType = 'warning';
    } else {
        $result = saveEmployeeSelections($employeeId, $prizeIds);

        if ($result) {
            $message = 'Your selections have been saved successfully!';
            $messageType = 'success';
            // Reload employee data
            $employee = getEmployee($employeeId);
        } else {
            $message = 'Failed to save selections. Please try again.';
            $messageType = 'danger';
        }
    }
}

// Get current selections
$currentSelections = getEmployeeSelections($employeeId);

// Get available prizes (excluding jackpots if hidden)
$settings = getSettings();
$prizes = getPrizes();
$availablePrizes = [];

foreach ($prizes as $prize) {
    // Skip jackpot if hidden from picks
    if ($settings['hide_jackpot_from_picks'] && $prize['tier'] === 'jackpot') {
        continue;
    }
    // Skip already won prizes
    if ($prize['status'] === 'won') {
        continue;
    }
    $availablePrizes[$prize['id']] = $prize;
}

// Sort by number
uasort($availablePrizes, function($a, $b) {
    return $a['num'] - $b['num'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Prizes - <?= h($employee['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/confetti.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>Select Your Prizes</h1>
            <p class="subtitle">
                Welcome, <strong><?= h($employee['name']) ?></strong>!
                Choose up to <?= MAX_SELECTIONS ?> prizes you'd like to win.
            </p>
        </header>

        <!-- Back Link -->
        <div class="mb-3">
            <a href="index.php?tab=employees" class="btn btn-secondary">
                &larr; Back to Employee List
            </a>
        </div>

        <!-- Alert Message -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> mb-3">
            <?= h($message) ?>
        </div>
        <?php endif; ?>

        <!-- Already Won Message -->
        <?php if ($employee['won_prize_id']): ?>
        <div class="alert alert-success mb-3">
            <strong>Congratulations!</strong> You have already won:
            <strong><?= h($employee['won_prize_name']) ?></strong>
        </div>
        <?php endif; ?>

        <!-- Selection Counter -->
        <div class="card mb-3">
            <div class="flex justify-between items-center">
                <div>
                    <h3>Your Selections</h3>
                    <p class="text-muted">Click on prizes to select or deselect them.</p>
                </div>
                <div class="text-center">
                    <div class="stat-value accent" id="selection-counter">
                        <?= count($currentSelections) ?> / <?= MAX_SELECTIONS ?>
                    </div>
                    <div class="stat-label">Selected</div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="search-box">
            <input type="text" id="search-input" class="search-input"
                   placeholder="Search prizes...">
        </div>

        <!-- Prize Selection Form -->
        <form method="POST" id="selection-form">
            <input type="hidden" name="selected_prizes" id="selected-prizes"
                   value="<?= h(implode(',', $currentSelections)) ?>">

            <div class="grid grid-4" id="prize-selection">
                <?php foreach ($availablePrizes as $prize):
                    $isSelected = in_array($prize['id'], $currentSelections);
                    $selectors = getPrizeSelections($prize['id']);
                ?>
                <div class="card prize-card prize-item selectable <?= $isSelected ? 'selected' : '' ?>"
                     data-prize-id="<?= h($prize['id']) ?>"
                     data-searchable="<?= h($prize['name'] . ' ' . $prize['display_name']) ?>">
                    <div class="prize-num"><?= $prize['num'] ?></div>
                    <div class="prize-name"><?= h($prize['display_name']) ?></div>
                    <div class="prize-selectors">
                        <?= count($selectors) ?> other employee<?= count($selectors) !== 1 ? 's' : '' ?> want this
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Submit Button -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-large" id="submit-selections"
                        <?= count($currentSelections) === 0 ? 'disabled' : '' ?>>
                    Save My Selections
                </button>
            </div>
        </form>

        <!-- Current Selections Summary -->
        <?php if (!empty($currentSelections)): ?>
        <div class="card mt-4">
            <h3>Your Current Selections:</h3>
            <ol class="mt-2">
                <?php foreach ($currentSelections as $prizeId):
                    $prize = $prizes[$prizeId] ?? null;
                    if (!$prize) continue;
                ?>
                <li style="margin-bottom: 0.5rem;">
                    <strong>#<?= $prize['num'] ?></strong> - <?= h($prize['display_name']) ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

    </div>

    <script src="assets/app.js"></script>
    <script>
        // Initialize selection system with current selections
        document.addEventListener('DOMContentLoaded', function() {
            App.selectedPrizes = <?= json_encode(array_values($currentSelections)) ?>;
        });
    </script>
</body>
</html>
