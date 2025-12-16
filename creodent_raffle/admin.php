<?php
/**
 * CREODENT HV Raffle 2025 - Admin Page
 *
 * Settings management, statistics, and administrative actions
 */

require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';

// Handle login form submission
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pin'])) {
    if (authenticateAdmin($_POST['admin_pin'])) {
        header('Location: admin.php');
        exit;
    } else {
        $loginError = 'Invalid PIN';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    logoutAdmin();
    header('Location: index.php');
    exit;
}

// Check if logged in
$isLoggedIn = isAdmin();

// If not logged in, show login form
if (!$isLoggedIn):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CREODENT Raffle</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container-narrow" style="margin-top: 100px;">
        <div class="card">
            <h1 class="text-center mb-4">Admin Login</h1>

            <?php if ($loginError): ?>
            <div class="alert alert-danger"><?= h($loginError) ?></div>
            <?php endif; ?>

            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label class="form-label">Admin PIN</label>
                    <input type="password" name="admin_pin" class="form-input"
                           placeholder="Enter PIN" autofocus required>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>

            <div class="text-center mt-4">
                <a href="index.php" class="text-muted">← Back to Main</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
exit;
endif;

// Admin is logged in - load data
$state = loadState();
$stats = getStatistics($state);

// Get all jackpot prizes
$jackpotPrizes = array_filter($state['prizes'], fn($p) => $p['tier'] === 'jackpot');
$currentJackpotOrder = $state['settings']['jackpot_order'] ?? [];
$unlockSpins = $state['settings']['unlock_spins'] ?? DEFAULT_UNLOCK_SPINS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CREODENT Raffle</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/confetti.css">
</head>
<body>
    <div class="header">
        <h1>Admin Dashboard</h1>
        <p class="subtitle">CREODENT HV Raffle 2025</p>
    </div>

    <div class="container">
        <div class="flex-between mb-4">
            <a href="index.php" class="btn btn-secondary">← Back to Main</a>
            <a href="admin.php?logout=1" class="btn btn-outline">Logout</a>
        </div>

        <!-- Quick Stats -->
        <div class="stats-bar mb-4">
            <div class="stat-item">
                <div class="stat-value"><?= $stats['spin_count'] ?></div>
                <div class="stat-label">Total Spins</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['completed_employees'] ?>/<?= $stats['total_employees'] ?></div>
                <div class="stat-label">Winners</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['available_prizes'] ?></div>
                <div class="stat-label">Prizes Left</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $stats['employees_with_selections'] ?></div>
                <div class="stat-label">Made Selections</div>
            </div>
        </div>

        <div class="grid grid-2">
            <!-- Jackpot Settings -->
            <div class="card">
                <h2 class="card-title">Jackpot Settings</h2>
                <p class="text-muted mb-3">Configure the 3 jackpot prizes and their unlock thresholds</p>

                <form id="jackpot-form">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="form-group">
                        <label class="form-label">Jackpot <?= $i + 1 ?> (Unlocks at spin #<?= $unlockSpins[$i] ?? (($i + 1) * 25) ?>)</label>
                        <select name="jackpot_<?= $i ?>" class="form-select">
                            <option value="">-- Select Prize --</option>
                            <?php foreach ($jackpotPrizes as $jp): ?>
                            <option value="<?= h($jp['id']) ?>"
                                <?= ($currentJackpotOrder[$i] ?? '') === $jp['id'] ? 'selected' : '' ?>
                                <?= $jp['status'] === 'won' ? 'disabled' : '' ?>>
                                #<?= $jp['num'] ?> - <?= h($jp['name']) ?>
                                <?= $jp['status'] === 'won' ? '(WON)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endfor; ?>

                    <div class="form-group">
                        <label class="form-label">Unlock Thresholds (spin numbers)</label>
                        <div class="flex gap-2">
                            <input type="number" name="unlock_1" class="form-input" value="<?= $unlockSpins[0] ?? 25 ?>" min="1">
                            <input type="number" name="unlock_2" class="form-input" value="<?= $unlockSpins[1] ?? 50 ?>" min="1">
                            <input type="number" name="unlock_3" class="form-input" value="<?= $unlockSpins[2] ?? 75 ?>" min="1">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Jackpot Settings</button>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2 class="card-title">Quick Actions</h2>

                <div class="flex flex-col gap-3 mt-3">
                    <button class="btn btn-warning btn-lg" onclick="undoLastSpin()">
                        ↩️ Undo Last Spin
                    </button>

                    <a href="api/export.php" class="btn btn-success btn-lg">
                        📥 Export Results (CSV)
                    </a>

                    <button class="btn btn-danger btn-lg" onclick="confirmReset()">
                        🗑️ Reset All Data
                    </button>
                </div>

                <div class="mt-4">
                    <h3>Last Spin</h3>
                    <?php if (count($state['spins']) > 0):
                        $lastSpin = end($state['spins']);
                    ?>
                    <div class="alert alert-info mt-2">
                        <strong>#<?= $lastSpin['spin_number'] ?></strong>:
                        <?= h($lastSpin['employee_name']) ?> won <?= h($lastSpin['prize_name']) ?>
                        <?php if ($lastSpin['prize_tier'] === 'jackpot'): ?>
                        <span class="badge badge-jackpot">JACKPOT</span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mt-2">No spins yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card mt-4">
            <h2 class="card-title">All Raffle Results</h2>

            <?php if (count($state['spins']) > 0): ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Spin #</th>
                            <th>Winner</th>
                            <th>Prize</th>
                            <th>Eligible</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($state['spins']) as $spin): ?>
                        <tr>
                            <td>
                                <?= $spin['spin_number'] ?>
                                <?php if ($spin['prize_tier'] === 'jackpot'): ?>
                                <span class="badge badge-jackpot">JP</span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($spin['employee_name']) ?></td>
                            <td><?= h($spin['prize_name']) ?></td>
                            <td><?= $spin['eligible_count'] ?></td>
                            <td><?= h($spin['timestamp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted">No raffle spins have been executed yet.</p>
            <?php endif; ?>
        </div>

        <!-- Employee Selection Status -->
        <div class="card mt-4">
            <h2 class="card-title">Employee Selection Status</h2>
            <p class="text-muted mb-3"><?= $stats['employees_with_selections'] ?> of <?= $stats['total_employees'] ?> employees have made selections</p>

            <div style="max-height: 300px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Selections</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($state['employees'] as $emp): ?>
                        <tr>
                            <td><?= h($emp['name']) ?></td>
                            <td>
                                <?php if ($emp['status'] === 'done'): ?>
                                <span class="badge badge-success">Won</span>
                                <?php elseif (isset($state['employee_selections'][$emp['id']])): ?>
                                <span class="badge badge-warning">Selected</span>
                                <?php else: ?>
                                <span class="badge badge-danger">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($emp['status'] === 'done'): ?>
                                    Won: <?= h($emp['won_prize_name']) ?>
                                <?php elseif (isset($state['employee_selections'][$emp['id']])): ?>
                                    <?= count($state['employee_selections'][$emp['id']]) ?> prizes selected
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="assets/app.js"></script>
    <script>
        // Save jackpot settings
        document.getElementById('jackpot-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const jackpotOrder = [
                this.jackpot_0.value,
                this.jackpot_1.value,
                this.jackpot_2.value
            ].filter(Boolean);

            const unlockSpins = [
                parseInt(this.unlock_1.value),
                parseInt(this.unlock_2.value),
                parseInt(this.unlock_3.value)
            ];

            // Validate ascending order
            if (unlockSpins[0] >= unlockSpins[1] || unlockSpins[1] >= unlockSpins[2]) {
                RaffleApp.showAlert('Unlock thresholds must be in ascending order', 'danger');
                return;
            }

            RaffleApp.showLoading('Saving settings...');

            try {
                await RaffleApp.updateSettings({
                    jackpot_order: jackpotOrder,
                    unlock_spins: unlockSpins
                });
                RaffleApp.hideLoading();
                RaffleApp.showAlert('Settings saved successfully!', 'success');
            } catch (error) {
                RaffleApp.hideLoading();
                RaffleApp.showAlert('Failed to save: ' + error.message, 'danger');
            }
        });

        // Undo last spin
        async function undoLastSpin() {
            if (!confirm('Are you sure you want to undo the last spin?')) {
                return;
            }

            RaffleApp.showLoading('Undoing last spin...');

            try {
                const result = await RaffleApp.undoLastSpin();
                RaffleApp.hideLoading();
                RaffleApp.showAlert('Last spin undone: ' + result.undone_spin.employee_name + ' - ' + result.undone_spin.prize_name, 'success');
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                RaffleApp.hideLoading();
                RaffleApp.showAlert('Failed: ' + error.message, 'danger');
            }
        }

        // Reset all data
        async function confirmReset() {
            const confirmed = prompt('Type "RESET" to confirm data reset. This cannot be undone!');

            if (confirmed !== 'RESET') {
                RaffleApp.showAlert('Reset cancelled', 'warning');
                return;
            }

            RaffleApp.showLoading('Resetting all data...');

            try {
                await RaffleApp.resetAllData();
                RaffleApp.hideLoading();
                RaffleApp.showAlert('All data has been reset!', 'success');
                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                RaffleApp.hideLoading();
                RaffleApp.showAlert('Failed: ' + error.message, 'danger');
            }
        }
    </script>
</body>
</html>
