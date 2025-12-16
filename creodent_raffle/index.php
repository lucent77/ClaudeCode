<?php
/**
 * CREODENT HV Raffle 2025 - Main Page
 *
 * Tab-based navigation with:
 * - Employee Selection (Portrait monitor optimized)
 * - Available Prizes (TV display optimized)
 * - Statistics Dashboard
 */

require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';

// Load current state
$state = loadState();
$stats = getStatistics($state);
$selectionCounts = countPrizeSelections($state);
$jackpotStatus = getJackpotUnlockStatus($state);

// Get available prizes sorted by selector count
$availablePrizes = getAvailablePrizes($state, false); // Exclude jackpots for regular list
$availablePrizes = sortPrizesBySelectors($availablePrizes, $selectionCounts);

// Get jackpots for separate display
$jackpots = [];
foreach ($state['settings']['jackpot_order'] ?? [] as $index => $jackpotId) {
    $prize = getPrize($state, $jackpotId);
    if ($prize) {
        $prize['tier_number'] = $index + 1;
        $prize['unlocked'] = $jackpotStatus[$index]['unlocked'] ?? false;
        $prize['threshold'] = $jackpotStatus[$index]['threshold'] ?? 0;
        $jackpots[] = $prize;
    }
}

$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CREODENT HV Raffle 2025</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/confetti.css">
</head>
<body>
    <div class="header">
        <h1>CREODENT HV Raffle 2025</h1>
        <p class="subtitle">Year-End Party Gift Raffle</p>
    </div>

    <div class="container">
        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab active" data-tab="employees">
                <span class="tab-icon">👥</span>
                <span>Employee Selection</span>
            </button>
            <button class="tab" data-tab="prizes">
                <span class="tab-icon">🎁</span>
                <span>Available Prizes</span>
            </button>
            <button class="tab" data-tab="stats">
                <span class="tab-icon">📊</span>
                <span>Statistics</span>
            </button>
            <?php if ($isAdmin): ?>
            <a href="admin.php" class="tab">
                <span class="tab-icon">⚙️</span>
                <span>Admin</span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value"><?= $stats['employees_with_selections'] ?>/<?= $stats['total_employees'] ?></div>
                <div class="stat-label">Selections Made</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['spin_count'] ?></div>
                <div class="stat-label">Spins Completed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['available_prizes'] ?></div>
                <div class="stat-label">Prizes Remaining</div>
            </div>
        </div>

        <!-- Jackpot Status -->
        <?php if (count($jackpots) > 0): ?>
        <div class="jackpot-status">
            <?php foreach ($jackpots as $jackpot): ?>
            <div class="jackpot-tier <?= $jackpot['unlocked'] ? 'unlocked' : '' ?> <?= $jackpot['status'] === 'won' ? 'won' : '' ?>">
                <div class="jackpot-tier-number">Jackpot #<?= $jackpot['tier_number'] ?></div>
                <div class="jackpot-tier-icon">
                    <?php if ($jackpot['status'] === 'won'): ?>
                        ✓
                    <?php elseif ($jackpot['unlocked']): ?>
                        🔓
                    <?php else: ?>
                        🔒
                    <?php endif; ?>
                </div>
                <div class="jackpot-tier-threshold">
                    <?php if ($jackpot['status'] === 'won'): ?>
                        Won!
                    <?php elseif ($jackpot['unlocked']): ?>
                        UNLOCKED!
                    <?php else: ?>
                        Unlocks at spin #<?= $jackpot['threshold'] ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tab Panels -->

        <!-- Employee Selection Panel -->
        <div id="employees" class="tab-panel">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Select Your Name</h2>
                    <span class="badge badge-success"><?= $stats['employees_with_selections'] ?> selected</span>
                </div>

                <div class="search-container">
                    <input type="text" id="employee-search" class="search-input"
                           placeholder="Search by name..." autocomplete="off">
                </div>

                <div class="employee-grid" id="employee-grid">
                    <?php foreach ($state['employees'] as $emp): ?>
                        <?php
                        $hasSelections = isset($state['employee_selections'][$emp['id']]);
                        $statusClass = $emp['status'] === 'done' ? 'done' : ($hasSelections ? 'selected' : '');
                        $statusText = $emp['status'] === 'done' ? 'Won: ' . h($emp['won_prize_name']) :
                                     ($hasSelections ? 'Selections made' : 'Click to select');
                        ?>
                        <div class="employee-card <?= $statusClass ?>"
                             data-name="<?= h($emp['name']) ?>"
                             data-id="<?= h($emp['id']) ?>"
                             onclick="selectEmployee('<?= h($emp['id']) ?>', '<?= h($emp['status']) ?>')">
                            <div class="employee-name"><?= h($emp['name']) ?></div>
                            <div class="employee-status"><?= $statusText ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Available Prizes Panel -->
        <div id="prizes" class="tab-panel hidden">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Available Prizes</h2>
                    <span class="badge badge-success"><?= count($availablePrizes) ?> available</span>
                </div>

                <div class="search-container">
                    <input type="text" id="prize-search" class="search-input"
                           placeholder="Search prizes..." autocomplete="off">
                </div>

                <!-- Jackpot Prizes (if unlocked) -->
                <?php
                $unlockedJackpots = array_filter($jackpots, fn($j) => $j['unlocked'] && $j['status'] === 'available');
                if (count($unlockedJackpots) > 0):
                ?>
                <h3 class="text-jackpot mb-2">🎰 Unlocked Jackpots</h3>
                <div class="prize-grid mb-4">
                    <?php foreach ($unlockedJackpots as $jackpot): ?>
                    <div class="prize-card jackpot"
                         data-name="<?= h($jackpot['name']) ?>"
                         onclick="selectPrize('<?= h($jackpot['id']) ?>')">
                        <div class="prize-number">#<?= $jackpot['num'] ?> - Jackpot Tier <?= $jackpot['tier_number'] ?></div>
                        <div class="prize-name"><?= h($jackpot['name']) ?></div>
                        <div class="prize-selectors">
                            <span>All employees eligible</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Regular Prizes -->
                <h3 class="mb-2">Regular Prizes (sorted by popularity)</h3>
                <div class="prize-grid" id="prize-grid">
                    <?php foreach ($availablePrizes as $prize): ?>
                        <?php $selectorCount = $selectionCounts[$prize['id']] ?? 0; ?>
                        <div class="prize-card"
                             data-name="<?= h($prize['name']) ?>"
                             onclick="selectPrize('<?= h($prize['id']) ?>')">
                            <div class="prize-number">#<?= $prize['num'] ?></div>
                            <div class="prize-name"><?= h($prize['name']) ?></div>
                            <div class="prize-selectors">
                                <span><?= $selectorCount ?> <?= $selectorCount === 1 ? 'person' : 'people' ?> selected</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Statistics Panel -->
        <div id="stats" class="tab-panel hidden">
            <div class="grid grid-2">
                <div class="card">
                    <h3 class="card-title">Employee Status</h3>
                    <div class="progress-bar mt-2">
                        <div class="progress-fill" style="width: <?= ($stats['completed_employees'] / max(1, $stats['total_employees'])) * 100 ?>%"></div>
                    </div>
                    <p class="text-muted mt-1">
                        <?= $stats['completed_employees'] ?> of <?= $stats['total_employees'] ?> employees have won prizes
                    </p>

                    <div class="mt-3">
                        <h4>Recent Winners</h4>
                        <?php
                        $recentSpins = array_slice(array_reverse($state['spins']), 0, 5);
                        if (count($recentSpins) > 0):
                        ?>
                        <table class="table mt-2">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Winner</th>
                                    <th>Prize</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSpins as $spin): ?>
                                <tr>
                                    <td><?= $spin['spin_number'] ?></td>
                                    <td><?= h($spin['employee_name']) ?></td>
                                    <td>
                                        <?= h($spin['prize_name']) ?>
                                        <?php if ($spin['prize_tier'] === 'jackpot'): ?>
                                            <span class="badge badge-jackpot">JACKPOT</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted mt-2">No spins yet</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Prize Status</h3>
                    <div class="progress-bar mt-2">
                        <div class="progress-fill" style="width: <?= ($stats['won_prizes'] / max(1, $stats['total_prizes'])) * 100 ?>%"></div>
                    </div>
                    <p class="text-muted mt-1">
                        <?= $stats['won_prizes'] ?> of <?= $stats['total_prizes'] ?> prizes have been won
                    </p>

                    <div class="mt-3">
                        <h4>Selection Statistics</h4>
                        <p class="text-muted mt-2">
                            <?= $stats['employees_with_selections'] ?> employees have made their 5 selections
                        </p>
                    </div>

                    <div class="mt-3">
                        <h4>Jackpot Progress</h4>
                        <?php foreach ($jackpotStatus as $index => $status): ?>
                        <div class="flex-between mt-2">
                            <span>Jackpot <?= $index + 1 ?> (Spin #<?= $status['threshold'] ?>)</span>
                            <?php if ($status['won']): ?>
                                <span class="badge badge-success">WON</span>
                            <?php elseif ($status['unlocked']): ?>
                                <span class="badge badge-warning">UNLOCKED</span>
                            <?php else: ?>
                                <span class="badge badge-danger">LOCKED</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/app.js"></script>
    <script>
        // Employee selection handler
        function selectEmployee(employeeId, status) {
            if (status === 'done') {
                RaffleApp.showAlert('This employee has already won a prize', 'warning');
                return;
            }
            window.location.href = 'choose.php?id=' + employeeId;
        }

        // Prize selection handler
        function selectPrize(prizeId) {
            <?php if (!$isAdmin): ?>
            RaffleApp.showAlert('Admin access required to run raffle', 'warning');
            <?php else: ?>
            window.location.href = 'spin.php?prize=' + prizeId;
            <?php endif; ?>
        }

        // Initialize search
        document.addEventListener('DOMContentLoaded', function() {
            // Employee search
            const empSearch = document.getElementById('employee-search');
            if (empSearch) {
                empSearch.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    document.querySelectorAll('.employee-card').forEach(card => {
                        const name = card.dataset.name.toLowerCase();
                        card.style.display = name.includes(query) ? '' : 'none';
                    });
                });
            }

            // Prize search
            const prizeSearch = document.getElementById('prize-search');
            if (prizeSearch) {
                prizeSearch.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    document.querySelectorAll('.prize-card').forEach(card => {
                        const name = card.dataset.name.toLowerCase();
                        card.style.display = name.includes(query) ? '' : 'none';
                    });
                });
            }
        });
    </script>
</body>
</html>
