<?php
/**
 * CREODENT HV Raffle 2025 - Employee Gift Selection Page
 *
 * Portrait monitor optimized UI for employees to select 5 desired gifts
 */

require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/utils.php';

// Get employee ID from URL
$employeeId = $_GET['id'] ?? '';

if (!isValidEmployeeId($employeeId)) {
    header('Location: index.php');
    exit;
}

// Load state
$state = loadState();
$employee = getEmployee($state, $employeeId);

if (!$employee) {
    header('Location: index.php');
    exit;
}

// Check if employee already won
if ($employee['status'] === 'done') {
    header('Location: index.php');
    exit;
}

// Get current selections
$currentSelections = $state['employee_selections'][$employeeId] ?? [];
$selectionCounts = countPrizeSelections($state);

// Get available prizes (exclude jackpots if hidden)
$availablePrizes = [];
foreach ($state['prizes'] as $prize) {
    if ($prize['status'] === 'available') {
        // Skip jackpots if hidden from picks
        if ($prize['tier'] === 'jackpot' && ($state['settings']['hide_jackpot_from_picks'] ?? true)) {
            continue;
        }
        $prize['selected'] = in_array($prize['id'], $currentSelections);
        $prize['selector_count'] = $selectionCounts[$prize['id']] ?? 0;
        $availablePrizes[] = $prize;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Gifts - <?= h($employee['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/confetti.css">
    <style>
        /* Portrait Monitor Optimization */
        .selection-header {
            position: sticky;
            top: 0;
            background: var(--bg1);
            z-index: 100;
            padding: 20px;
            border-bottom: 2px solid var(--bg4);
        }

        .selection-counter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin: 16px 0;
        }

        .counter-number {
            font-size: 48px;
            font-weight: 800;
            color: var(--accent);
        }

        .counter-text {
            font-size: 24px;
            color: var(--text-muted);
        }

        .prize-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
            padding: 20px;
        }

        .prize-select-card {
            background: var(--bg3);
            border: 3px solid var(--bg4);
            border-radius: 16px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .prize-select-card:hover {
            border-color: var(--accent);
            transform: scale(1.02);
        }

        .prize-select-card.selected {
            border-color: var(--good);
            background: rgba(52, 211, 153, 0.15);
        }

        .prize-select-card.selected::after {
            content: '✓';
            position: absolute;
            top: 12px;
            right: 12px;
            width: 32px;
            height: 32px;
            background: var(--good);
            color: var(--bg1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }

        .prize-select-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .selection-actions {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg2);
            border-top: 2px solid var(--bg4);
            padding: 20px;
            display: flex;
            justify-content: center;
            gap: 16px;
            z-index: 100;
        }

        .content-wrapper {
            padding-bottom: 120px;
        }

        .selected-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-top: 12px;
        }

        .selected-chip {
            background: var(--good);
            color: var(--bg1);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="selection-header">
        <div class="text-center">
            <a href="index.php" class="btn btn-secondary btn-sm mb-2">← Back to Main</a>
            <h1><?= h($employee['name']) ?></h1>
            <p class="text-muted">Select your 5 most wanted gifts</p>
        </div>

        <div class="selection-counter">
            <span class="counter-number" id="selection-count"><?= count($currentSelections) ?></span>
            <span class="counter-text">/ <?= MAX_SELECTIONS ?> selected</span>
        </div>

        <div class="selected-preview" id="selected-preview">
            <?php foreach ($currentSelections as $prizeId):
                $prize = getPrize($state, $prizeId);
                if ($prize):
            ?>
            <span class="selected-chip"><?= h($prize['name']) ?></span>
            <?php endif; endforeach; ?>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="container">
            <div class="search-container">
                <input type="text" id="prize-search" class="search-input"
                       placeholder="Search prizes..." autocomplete="off">
            </div>

            <div class="prize-selection-grid" id="prize-grid">
                <?php foreach ($availablePrizes as $prize): ?>
                <div class="prize-select-card <?= $prize['selected'] ? 'selected' : '' ?>"
                     data-id="<?= h($prize['id']) ?>"
                     data-name="<?= h($prize['name']) ?>"
                     onclick="toggleSelection('<?= h($prize['id']) ?>', '<?= h(addslashes($prize['name'])) ?>')">
                    <div class="prize-number">#<?= $prize['num'] ?></div>
                    <div class="prize-name" style="font-size: 20px; font-weight: 600; margin: 12px 0;">
                        <?= h($prize['name']) ?>
                    </div>
                    <div class="text-muted" style="font-size: 14px;">
                        <?= $prize['selector_count'] ?> <?= $prize['selector_count'] === 1 ? 'person' : 'people' ?> interested
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="selection-actions">
        <button class="btn btn-secondary btn-lg" onclick="clearSelections()">Clear All</button>
        <button class="btn btn-success btn-lg" id="save-btn" onclick="saveSelections()" disabled>
            Save Selections
        </button>
    </div>

    <script src="assets/app.js"></script>
    <script>
        const employeeId = '<?= h($employeeId) ?>';
        const maxSelections = <?= MAX_SELECTIONS ?>;
        let selectedPrizes = <?= json_encode($currentSelections) ?>;

        function updateUI() {
            const count = selectedPrizes.length;
            document.getElementById('selection-count').textContent = count;

            // Update save button state
            const saveBtn = document.getElementById('save-btn');
            saveBtn.disabled = count !== maxSelections;

            // Update preview chips
            const preview = document.getElementById('selected-preview');
            preview.innerHTML = '';
            selectedPrizes.forEach(prizeId => {
                const card = document.querySelector(`.prize-select-card[data-id="${prizeId}"]`);
                if (card) {
                    const chip = document.createElement('span');
                    chip.className = 'selected-chip';
                    chip.textContent = card.dataset.name;
                    preview.appendChild(chip);
                }
            });

            // Update card states
            document.querySelectorAll('.prize-select-card').forEach(card => {
                const prizeId = card.dataset.id;
                const isSelected = selectedPrizes.includes(prizeId);
                card.classList.toggle('selected', isSelected);

                // Disable unselected cards if max reached
                if (!isSelected && count >= maxSelections) {
                    card.classList.add('disabled');
                } else {
                    card.classList.remove('disabled');
                }
            });
        }

        function toggleSelection(prizeId, prizeName) {
            const index = selectedPrizes.indexOf(prizeId);

            if (index > -1) {
                // Remove selection
                selectedPrizes.splice(index, 1);
            } else if (selectedPrizes.length < maxSelections) {
                // Add selection
                selectedPrizes.push(prizeId);
            } else {
                RaffleApp.showAlert('You can only select ' + maxSelections + ' prizes', 'warning');
                return;
            }

            updateUI();
        }

        function clearSelections() {
            selectedPrizes = [];
            updateUI();
        }

        async function saveSelections() {
            if (selectedPrizes.length !== maxSelections) {
                RaffleApp.showAlert('Please select exactly ' + maxSelections + ' prizes', 'warning');
                return;
            }

            RaffleApp.showLoading('Saving your selections...');

            try {
                await RaffleApp.saveSelections(employeeId, selectedPrizes);
                RaffleApp.hideLoading();
                RaffleApp.showAlert('Selections saved successfully!', 'success');

                // Celebrate
                if (RaffleApp.confetti()) {
                    RaffleApp.confetti().fire(window.innerWidth / 2, window.innerHeight / 2, 50);
                }

                // Redirect after a moment
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);

            } catch (error) {
                RaffleApp.hideLoading();
                RaffleApp.showAlert('Failed to save: ' + error.message, 'danger');
            }
        }

        // Search functionality
        document.getElementById('prize-search').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.prize-select-card').forEach(card => {
                const name = card.dataset.name.toLowerCase();
                card.style.display = name.includes(query) ? '' : 'none';
            });
        });

        // Initial UI update
        updateUI();
    </script>
</body>
</html>
