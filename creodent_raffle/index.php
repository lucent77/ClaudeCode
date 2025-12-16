<?php
/**
 * CREODENT HV Raffle 2025 - Main Page
 *
 * Tab-based navigation for employees and prizes
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/storage.php';

// Get current tab
$tab = getParam('tab', 'employees');
$validTabs = ['employees', 'prizes', 'jackpot', 'history'];
if (!in_array($tab, $validTabs)) {
    $tab = 'employees';
}

// Load data
$employees = getEmployees();
$prizes = getPrizes();
$stats = getStatistics();
$settings = getSettings();
$unlockedJackpots = getUnlockedJackpots();

// Sort employees by name
uasort($employees, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

// Sort prizes by number
uasort($prizes, function($a, $b) {
    return $a['num'] - $b['num'];
});

// Get search query
$search = getParam('search', '');
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
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>CREODENT HV Raffle 2025</h1>
            <p class="subtitle">Year-End Party Gift Drawing System</p>
        </header>

        <!-- Statistics Bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value accent"><?= $stats['spin_count'] ?></div>
                <div class="stat-label">Spins Completed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value good"><?= $stats['employees_won'] ?></div>
                <div class="stat-label">Winners</div>
            </div>
            <div class="stat-item">
                <div class="stat-value warn"><?= $stats['prizes_available'] ?></div>
                <div class="stat-label">Prizes Left</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['employees_with_selections'] ?>/<?= $stats['total_employees'] ?></div>
                <div class="stat-label">Selections Made</div>
            </div>
            <div class="stat-item">
                <div class="stat-value bad"><?= count($unlockedJackpots) ?>/<?= $stats['jackpot_total'] ?></div>
                <div class="stat-label">Jackpots Unlocked</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card mb-3">
            <div class="flex justify-between items-center mb-2">
                <span>Overall Progress</span>
                <span><?= $stats['prizes_won'] ?> / <?= $stats['total_prizes'] ?> prizes drawn</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($stats['total_prizes'] > 0 ? ($stats['prizes_won'] / $stats['total_prizes'] * 100) : 0) ?>%"></div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="tabs">
            <a href="?tab=employees" class="tab <?= $tab === 'employees' ? 'active' : '' ?>">
                <span class="tab-icon">&#128100;</span>
                Employee Selection
            </a>
            <a href="?tab=prizes" class="tab <?= $tab === 'prizes' ? 'active' : '' ?>">
                <span class="tab-icon">&#127873;</span>
                Available Prizes
            </a>
            <a href="?tab=jackpot" class="tab <?= $tab === 'jackpot' ? 'active' : '' ?>">
                <span class="tab-icon">&#11088;</span>
                Jackpot Prizes
            </a>
            <a href="?tab=history" class="tab <?= $tab === 'history' ? 'active' : '' ?>">
                <span class="tab-icon">&#128203;</span>
                History
            </a>
            <a href="admin.php" class="tab">
                <span class="tab-icon">&#9881;</span>
                Admin
            </a>
        </nav>

        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="search-input" class="search-input"
                   placeholder="Search <?= $tab === 'employees' ? 'employees' : 'prizes' ?>..."
                   value="<?= h($search) ?>">
        </div>

        <!-- Tab Content -->
        <?php if ($tab === 'employees'): ?>
        <!-- Employees Tab -->
        <div class="grid grid-5">
            <?php foreach ($employees as $emp): ?>
            <a href="choose.php?id=<?= h($emp['id']) ?>"
               class="card employee-card <?= $emp['won_prize_id'] ? 'won' : ($emp['status'] === 'done' ? 'done' : 'pending') ?>"
               data-searchable="<?= h($emp['name']) ?>">
                <div class="name"><?= h($emp['name']) ?></div>
                <?php if ($emp['won_prize_id']): ?>
                <div class="status badge badge-success">Won: <?= h($emp['won_prize_name']) ?></div>
                <?php elseif ($emp['status'] === 'done'): ?>
                <div class="status badge badge-info">Selections Made</div>
                <?php else: ?>
                <div class="status badge badge-warning">Not Selected</div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'prizes'): ?>
        <!-- Prizes Tab -->
        <div class="grid grid-4">
            <?php foreach ($prizes as $prize):
                if ($prize['tier'] === 'jackpot') continue; // Skip jackpots in regular list
                $selectors = getPrizeSelections($prize['id']);
                $selectorCount = count($selectors);
            ?>
            <a href="<?= $prize['status'] === 'available' ? 'spin.php?prize_id=' . h($prize['id']) : '#' ?>"
               class="card prize-card <?= $prize['status'] === 'won' ? 'won' : '' ?>"
               data-searchable="<?= h($prize['name'] . ' ' . $prize['display_name']) ?>">
                <div class="prize-num"><?= $prize['num'] ?></div>
                <div class="prize-name"><?= h($prize['display_name']) ?></div>
                <div class="prize-selectors">
                    <?php if ($prize['status'] === 'won'): ?>
                        Won by: <?= h(getEmployee($prize['won_by_employee_id'])['name'] ?? 'Unknown') ?>
                    <?php else: ?>
                        <?= $selectorCount ?> employee<?= $selectorCount !== 1 ? 's' : '' ?> selected this
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'jackpot'): ?>
        <!-- Jackpot Tab -->
        <div class="alert alert-info mb-3">
            <strong>Jackpot System:</strong> Special prizes unlock after reaching spin milestones.
            <?php
            $unlockSpins = $settings['unlock_spins'];
            echo "Unlock at: " . implode(', ', $unlockSpins) . " spins.";
            ?>
            Current spins: <?= $stats['spin_count'] ?>
        </div>

        <div class="grid grid-3">
            <?php
            $jackpotOrder = $settings['jackpot_order'];
            foreach ($jackpotOrder as $index => $prizeId):
                $prize = $prizes[$prizeId] ?? null;
                if (!$prize) continue;
                $isUnlocked = in_array($prizeId, $unlockedJackpots);
                $unlockAt = $unlockSpins[$index] ?? 'N/A';
                $selectors = getPrizeSelections($prize['id']);
            ?>
            <div class="card prize-card jackpot <?= $prize['status'] === 'won' ? 'won' : '' ?> <?= !$isUnlocked ? 'locked' : '' ?>"
                 data-searchable="<?= h($prize['name']) ?>">
                <div class="prize-num"><?= $index + 1 ?></div>
                <div class="prize-name"><?= h($prize['display_name']) ?></div>

                <?php if (!$isUnlocked): ?>
                <div class="prize-selectors">
                    <span class="badge badge-danger">Locked - Unlocks at <?= $unlockAt ?> spins</span>
                </div>
                <?php elseif ($prize['status'] === 'won'): ?>
                <div class="prize-selectors">
                    Won by: <?= h(getEmployee($prize['won_by_employee_id'])['name'] ?? 'Unknown') ?>
                </div>
                <?php else: ?>
                <div class="prize-selectors mb-2">
                    <?= count($selectors) ?> employee(s) selected this
                </div>
                <a href="spin.php?prize_id=<?= h($prize['id']) ?>" class="btn btn-danger">
                    Draw Jackpot Winner
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'history'): ?>
        <!-- History Tab -->
        <?php $spins = getSpinsHistory(); ?>
        <?php if (empty($spins)): ?>
        <div class="empty-state">
            <h3>No spins yet</h3>
            <p>Start drawing prizes to see the history here.</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Prize</th>
                        <th>Winner</th>
                        <th>Selection Type</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spins as $spin): ?>
                    <tr>
                        <td><?= $spin['id'] ?></td>
                        <td><?= h($spin['prize_name']) ?></td>
                        <td><?= h($spin['winner_name']) ?></td>
                        <td>
                            <?php if ($spin['from_selectors']): ?>
                            <span class="badge badge-success">Selected (<?= $spin['eligible_count'] ?> candidates)</span>
                            <?php else: ?>
                            <span class="badge badge-warning">Random (<?= $spin['eligible_count'] ?> eligible)</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('H:i:s', $spin['timestamp']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>

    <script src="assets/app.js"></script>
</body>
</html>
