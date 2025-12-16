<?php
/**
 * CREODENT HV Raffle 2025 - Main Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/utils.php';

// Load state
Storage::acquireLock();
$state = Storage::loadState();
Storage::releaseLock();

// Get statistics
$stats = Storage::getStats($state);

// Get available prizes (sorted by number)
$availablePrizes = array_values(Storage::getAvailablePrizes($state, true));
usort($availablePrizes, fn($a, $b) => $a['num'] - $b['num']);

// Get pending employees (sorted by name)
$pendingEmployees = array_values(Storage::getPendingEmployees($state));
usort($pendingEmployees, fn($a, $b) => strcasecmp($a['name'], $b['name']));

// Get all employees for selection page
$allEmployees = $state['employees'];
usort($allEmployees, fn($a, $b) => strcasecmp($a['name'], $b['name']));

// Current tab
$currentTab = getParam('tab', 'employees');

// Check for newly unlocked jackpots
$jackpotAlert = null;
foreach ($stats['jackpot_status'] as $jackpot) {
    if ($jackpot['is_unlocked'] && !$jackpot['is_won'] && $jackpot['spins_remaining'] === 0) {
        $jackpotAlert = $jackpot;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CREODENT HV Raffle 2025</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="brand">
                <span class="brand-icon">🎁</span>
            </div>
            <h1>CREODENT HV Raffle 2025</h1>
            <p class="subtitle">Year-End Party Prize Drawing</p>
        </header>

        <!-- Jackpot Alert -->
        <?php if ($jackpotAlert): ?>
        <div class="alert-banner jackpot-unlocked">
            <span class="alert-icon">🎰</span>
            <div>
                <strong>JACKPOT UNLOCKED!</strong><br>
                Stage <?= $jackpotAlert['stage'] ?> jackpot "<?= h($jackpotAlert['name']) ?>" is now available!
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['spin_count'] ?></div>
                <div class="stat-label">Spins Complete</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['available_prizes'] ?></div>
                <div class="stat-label">Prizes Remaining</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['pending_employees'] ?></div>
                <div class="stat-label">Employees Waiting</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['employees_with_selections'] ?></div>
                <div class="stat-label">Made Selections</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">Progress</h4>
                <span><?= $stats['progress_percent'] ?>% Complete</span>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: <?= $stats['progress_percent'] ?>%"></div>
            </div>
        </div>

        <!-- Jackpot Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">🎰 Jackpot Status</h4>
            </div>
            <div class="jackpot-status">
                <?php foreach ($stats['jackpot_status'] as $jackpot): ?>
                <div class="jackpot-item <?= $jackpot['is_won'] ? 'won' : ($jackpot['is_unlocked'] ? 'unlocked' : 'locked') ?>">
                    <div class="jackpot-stage">
                        <?php if ($jackpot['is_won']): ?>✓
                        <?php elseif ($jackpot['is_unlocked']): ?>🔓
                        <?php else: ?>🔒<?php endif; ?>
                    </div>
                    <div class="jackpot-info">
                        <div class="jackpot-name"><?= h($jackpot['name']) ?></div>
                        <div class="jackpot-unlock">
                            <?php if ($jackpot['is_won']): ?>
                                <span class="text-good">Already Won!</span>
                            <?php elseif ($jackpot['is_unlocked']): ?>
                                <span class="text-jackpot">UNLOCKED - Ready to spin!</span>
                            <?php else: ?>
                                Unlocks at spin #<?= $jackpot['unlock_at'] ?> (<?= $jackpot['spins_remaining'] ?> spins remaining)
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <nav class="tabs">
            <a href="?tab=employees" class="tab <?= $currentTab === 'employees' ? 'active' : '' ?>" data-tab="employees">
                <span class="tab-icon">👤</span>
                <span>Employee Selection</span>
            </a>
            <a href="?tab=prizes" class="tab <?= $currentTab === 'prizes' ? 'active' : '' ?>" data-tab="prizes">
                <span class="tab-icon">🎁</span>
                <span>Available Prizes</span>
            </a>
            <a href="?tab=history" class="tab <?= $currentTab === 'history' ? 'active' : '' ?>" data-tab="history">
                <span class="tab-icon">📋</span>
                <span>Spin History</span>
            </a>
            <a href="admin.php" class="tab">
                <span class="tab-icon">⚙️</span>
                <span>Admin</span>
            </a>
        </nav>

        <!-- Tab Content: Employees -->
        <div id="employees" class="tab-content <?= $currentTab !== 'employees' ? 'hidden' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Select Your Name to Choose Prizes</h3>
                </div>

                <!-- Search -->
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="search-input" class="search-input"
                           placeholder="Search employees..." autocomplete="off">
                </div>

                <!-- Employee Grid -->
                <div class="grid grid-4">
                    <?php foreach ($allEmployees as $emp): ?>
                    <a href="choose.php?id=<?= h($emp['id']) ?>"
                       class="employee-card <?= $emp['status'] === 'done' ? 'done' : '' ?>"
                       data-searchable="<?= h($emp['name'] . ' ' . $emp['id']) ?>">
                        <div class="employee-name"><?= h($emp['name']) ?></div>
                        <div class="employee-id"><?= h($emp['id']) ?></div>
                        <?php if ($emp['status'] === 'done'): ?>
                            <div class="badge badge-good mt-2">Won: <?= h($emp['won_prize_name']) ?></div>
                        <?php elseif (isset($state['employee_selections'][$emp['id']])): ?>
                            <div class="badge badge-pending mt-2">
                                <?= count($state['employee_selections'][$emp['id']]) ?> selected
                            </div>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Tab Content: Prizes -->
        <div id="prizes" class="tab-content <?= $currentTab !== 'prizes' ? 'hidden' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Available Prizes - Click to Start Raffle</h3>
                </div>

                <!-- Search -->
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" id="prize-search"
                           placeholder="Search prizes..." autocomplete="off"
                           oninput="filterItems(this.value.toLowerCase())">
                </div>

                <!-- Prize Grid -->
                <div class="grid grid-4">
                    <?php foreach ($availablePrizes as $prize):
                        $selectorCount = isset($state['prize_selections'][$prize['id']])
                            ? count(array_filter($state['prize_selections'][$prize['id']], function($empId) use ($state) {
                                $emp = Storage::getEmployee($state, $empId);
                                return $emp && $emp['status'] === 'pending';
                            }))
                            : 0;
                    ?>
                    <a href="spin.php?prize_id=<?= h($prize['id']) ?>"
                       class="prize-card <?= $prize['tier'] === 'jackpot' ? 'jackpot' : '' ?>"
                       data-searchable="<?= h($prize['name'] . ' ' . $prize['display_name'] . ' ' . $prize['id']) ?>">
                        <span class="prize-num">#<?= $prize['num'] ?></span>
                        <div class="prize-name"><?= h($prize['display_name']) ?></div>
                        <?php if ($prize['tier'] === 'jackpot'): ?>
                            <div class="prize-tier">🎰 JACKPOT</div>
                        <?php endif; ?>
                        <div class="prize-selectors">
                            <?= $selectorCount ?> employee<?= $selectorCount !== 1 ? 's' : '' ?> selected this
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($availablePrizes)): ?>
                <div class="text-center text-muted" style="padding: 48px;">
                    <p style="font-size: 24px;">🎉 All prizes have been won!</p>
                    <p>The raffle is complete.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Content: History -->
        <div id="history" class="tab-content <?= $currentTab !== 'history' ? 'hidden' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Spin History</h3>
                    <a href="admin.php?action=export" class="btn btn-sm">📥 Export CSV</a>
                </div>

                <?php if (empty($state['spins'])): ?>
                <div class="text-center text-muted" style="padding: 48px;">
                    <p>No spins yet. Start the raffle from the "Available Prizes" tab!</p>
                </div>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Prize</th>
                            <th>Winner</th>
                            <th>Type</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($state['spins']) as $spin): ?>
                        <tr>
                            <td><?= $spin['spin_number'] ?></td>
                            <td><?= h($spin['prize_name']) ?></td>
                            <td><?= h($spin['winner_name']) ?></td>
                            <td>
                                <?php if ($spin['is_jackpot']): ?>
                                    <span class="badge badge-jackpot">JACKPOT</span>
                                <?php else: ?>
                                    <span class="badge badge-available">Regular</span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($spin['timestamp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/app.js"></script>
</body>
</html>
