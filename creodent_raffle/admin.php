<?php
/**
 * CREODENT HV Raffle 2025 - Admin Page
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

$action = getParam('action', 'dashboard');
$message = null;
$messageType = null;

// Handle login
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pin = postParam('pin');
        if (authenticateAdmin($pin)) {
            redirect('admin.php');
        } else {
            $message = 'Invalid PIN. Please try again.';
            $messageType = 'error';
        }
    }

    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login | CREODENT HV Raffle 2025</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="container">
            <header class="header">
                <h1>🔐 Admin Access</h1>
            </header>
            <?= renderAdminLoginForm($message) ?>
            <div class="text-center mt-4">
                <a href="index.php" class="btn">← Back to Raffle</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle logout
if ($action === 'logout') {
    logoutAdmin();
    redirect('index.php');
}

// Require authentication for all other actions
requireAdmin();

// Load state
Storage::acquireLock();
$state = Storage::loadState();

// Handle actions
switch ($action) {
    case 'export':
        // Export CSV
        $csv = Storage::exportCSV($state);
        Storage::releaseLock();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="raffle_results_' . date('Y-m-d_His') . '.csv"');
        echo $csv;
        exit;

    case 'undo':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && postParam('confirm') === '1') {
            $undone = Storage::undoLastSpin($state);
            if ($undone) {
                Storage::saveState($state);
                $message = 'Last spin undone: ' . $undone['winner_name'] . ' - ' . $undone['prize_name'];
                $messageType = 'success';
            } else {
                $message = 'No spins to undo.';
                $messageType = 'error';
            }
        }
        break;

    case 'reset':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && postParam('confirm') === '1') {
            Storage::resetState();
            $state = Storage::loadState();
            $message = 'All data has been reset!';
            $messageType = 'success';
        }
        break;

    case 'save_settings':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Update jackpot order
            $jackpotOrder = postParam('jackpot_order');
            if ($jackpotOrder) {
                $order = array_filter(explode(',', $jackpotOrder));
                $state['settings']['jackpot_order'] = $order;
            }

            // Update unlock spins
            $unlockSpins = [];
            for ($i = 1; $i <= 3; $i++) {
                $spinNum = postParam('unlock_spin_' . $i);
                if (is_numeric($spinNum)) {
                    $unlockSpins[] = (int)$spinNum;
                }
            }
            if (!empty($unlockSpins)) {
                sort($unlockSpins);
                $state['settings']['unlock_spins'] = $unlockSpins;
            }

            Storage::saveState($state);
            $message = 'Settings saved successfully!';
            $messageType = 'success';
        }
        break;
}

// Get statistics
$stats = Storage::getStats($state);

// Get jackpot prizes for configuration
$jackpotPrizes = array_filter($state['prizes'], fn($p) => $p['tier'] === 'jackpot');
$currentJackpotOrder = $state['settings']['jackpot_order'] ?? [];
$unlockSpins = $state['settings']['unlock_spins'] ?? DEFAULT_UNLOCK_SPINS;

Storage::releaseLock();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CREODENT HV Raffle 2025</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .action-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 24px;
            text-align: center;
            border: 2px solid var(--border);
            transition: var(--transition);
        }

        .action-card:hover {
            border-color: var(--accent);
        }

        .action-card h4 {
            margin-bottom: 12px;
        }

        .action-card p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .settings-form {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 28px;
            border: 1px solid var(--border);
        }

        .settings-section {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .settings-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .jackpot-selector {
            display: grid;
            gap: 12px;
        }

        .jackpot-option {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: var(--bg3);
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
        }

        .jackpot-option:hover {
            border-color: var(--jackpot);
        }

        .jackpot-option.selected {
            border-color: var(--jackpot);
            background: rgba(244, 63, 94, 0.1);
        }

        .jackpot-order-badge {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--jackpot);
            color: white;
            border-radius: 50%;
            font-weight: 700;
        }

        .unlock-inputs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .unlock-input-group {
            text-align: center;
        }

        .unlock-input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .unlock-input-group input {
            width: 100%;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
        }

        .danger-zone {
            background: rgba(251, 113, 133, 0.1);
            border: 2px solid var(--bad);
            border-radius: var(--radius);
            padding: 24px;
            margin-top: 32px;
        }

        .danger-zone h4 {
            color: var(--bad);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>⚙️ Admin Dashboard</h1>
            <p class="subtitle">Manage raffle settings and data</p>
        </header>

        <!-- Navigation -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
            <a href="index.php" class="btn">← Back to Raffle</a>
            <a href="admin.php?action=logout" class="btn btn-danger">🚪 Logout</a>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'success-message' : 'error-message' ?>">
            <?= h($message) ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['spin_count'] ?></div>
                <div class="stat-label">Total Spins</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['won_prizes'] ?></div>
                <div class="stat-label">Prizes Won</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['pending_employees'] ?></div>
                <div class="stat-label">Employees Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['employees_with_selections'] ?></div>
                <div class="stat-label">Made Selections</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h3 class="mb-2">Quick Actions</h3>
        <div class="admin-actions">
            <div class="action-card">
                <h4>📥 Export Results</h4>
                <p>Download all spin results as CSV file</p>
                <a href="admin.php?action=export" class="btn btn-primary">Download CSV</a>
            </div>
            <div class="action-card">
                <h4>↩️ Undo Last Spin</h4>
                <p>Reverse the most recent raffle result</p>
                <button class="btn btn-danger" onclick="undoLastSpin()">Undo Spin</button>
            </div>
            <div class="action-card">
                <h4>🔄 Refresh Data</h4>
                <p>Reload the current state from storage</p>
                <a href="admin.php" class="btn">Refresh</a>
            </div>
        </div>

        <!-- Jackpot Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">🎰 Jackpot Configuration</h3>
            </div>

            <form method="POST" action="admin.php?action=save_settings" class="settings-form">
                <div class="settings-section">
                    <h4>Jackpot Prize Order</h4>
                    <p class="text-muted mb-2">Click prizes to set the order they will be unlocked (1st, 2nd, 3rd)</p>

                    <input type="hidden" id="jackpot-order" name="jackpot_order"
                           value="<?= h(implode(',', $currentJackpotOrder)) ?>">

                    <div class="jackpot-selector">
                        <?php foreach ($jackpotPrizes as $prize):
                            $orderIndex = array_search($prize['id'], $currentJackpotOrder);
                            $isSelected = $orderIndex !== false;
                        ?>
                        <div class="jackpot-option <?= $isSelected ? 'selected' : '' ?>"
                             data-prize-id="<?= h($prize['id']) ?>"
                             data-prize-name="<?= h($prize['display_name']) ?>"
                             onclick="toggleJackpotSelection('<?= h($prize['id']) ?>')">
                            <?php if ($isSelected): ?>
                            <span class="jackpot-order-badge"><?= $orderIndex + 1 ?></span>
                            <?php else: ?>
                            <span class="jackpot-order-badge" style="background: var(--bg4);">-</span>
                            <?php endif; ?>
                            <div>
                                <strong>#<?= $prize['num'] ?></strong> - <?= h($prize['display_name']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="jackpot-order-display" class="mt-2 text-muted" style="font-size: 14px;">
                        <?php if (empty($currentJackpotOrder)): ?>
                        <div class="no-selection">No jackpot prizes selected</div>
                        <?php else: ?>
                            <?php foreach ($currentJackpotOrder as $index => $jackpotId):
                                $prize = Storage::getPrize($state, $jackpotId);
                            ?>
                            <div class="jackpot-order-item">Stage <?= $index + 1 ?>: <?= $prize ? h($prize['display_name']) : $jackpotId ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="settings-section">
                    <h4>Unlock Spin Numbers</h4>
                    <p class="text-muted mb-2">Set which spin number unlocks each jackpot stage</p>

                    <div class="unlock-inputs">
                        <div class="unlock-input-group">
                            <label>Stage 1 Unlock</label>
                            <input type="number" name="unlock_spin_1" class="form-control"
                                   value="<?= $unlockSpins[0] ?? 25 ?>" min="1" max="100">
                        </div>
                        <div class="unlock-input-group">
                            <label>Stage 2 Unlock</label>
                            <input type="number" name="unlock_spin_2" class="form-control"
                                   value="<?= $unlockSpins[1] ?? 50 ?>" min="1" max="100">
                        </div>
                        <div class="unlock-input-group">
                            <label>Stage 3 Unlock</label>
                            <input type="number" name="unlock_spin_3" class="form-control"
                                   value="<?= $unlockSpins[2] ?? 75 ?>" min="1" max="100">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg btn-block">
                    💾 Save Settings
                </button>
            </form>
        </div>

        <!-- Recent Spins -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">📋 Recent Spins</h3>
            </div>

            <?php
            $recentSpins = array_slice(array_reverse($state['spins']), 0, 10);
            if (empty($recentSpins)):
            ?>
            <p class="text-muted text-center" style="padding: 32px;">No spins yet.</p>
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
                    <?php foreach ($recentSpins as $spin): ?>
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

        <!-- Danger Zone -->
        <div class="danger-zone">
            <h4>⚠️ Danger Zone</h4>
            <p>These actions cannot be undone. Use with caution!</p>
            <div class="btn-group mt-2">
                <button class="btn btn-danger" onclick="resetAllData()">
                    🗑️ Reset All Data
                </button>
            </div>
        </div>
    </div>

    <script src="assets/app.js"></script>
</body>
</html>
