<?php
/**
 * CREODENT HV Raffle 2025 - Admin Panel
 *
 * Settings, statistics, and management functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/storage.php';

$action = getParam('action', 'dashboard');

// Handle login
if ($action === 'login') {
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pin = postParam('pin', '');

        if (authenticateAdmin($pin)) {
            redirect('admin.php');
        } else {
            $error = 'Invalid PIN. Please try again.';
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - CREODENT HV Raffle 2025</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="container">
            <header class="header">
                <h1>Admin Access</h1>
                <p class="subtitle">Enter PIN to continue</p>
            </header>

            <div class="card" style="max-width: 400px; margin: 2rem auto;">
                <?php if ($error): ?>
                <div class="alert alert-danger mb-3"><?= h($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Admin PIN</label>
                        <input type="password" name="pin" class="form-input" autofocus required
                               placeholder="Enter PIN" maxlength="10">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Login
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-secondary">Back to Main</a>
                </div>
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

// Require authentication for other actions
requireAdmin();

// Handle AJAX stats request
if ($action === 'stats' && getParam('ajax')) {
    jsonResponse(getStatistics());
}

// Handle export
if ($action === 'export') {
    $csv = exportResultsCSV();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="raffle_results_' . date('Y-m-d_His') . '.csv"');
    echo $csv;
    exit;
}

// Handle undo
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = postParam('action');

    if ($postAction === 'undo') {
        $result = undoLastSpin();
        if (isset($result['success'])) {
            $message = 'Last spin undone successfully. Prize: ' . $result['undone_spin']['prize_name'];
            $messageType = 'success';
        } else {
            $message = 'Failed to undo: ' . ($result['error'] ?? 'Unknown error');
            $messageType = 'danger';
        }
    }

    if ($postAction === 'reset' && postParam('confirm_reset') === 'RESET') {
        if (resetData()) {
            $message = 'All data has been reset to initial state.';
            $messageType = 'success';
        } else {
            $message = 'Failed to reset data.';
            $messageType = 'danger';
        }
    }

    if ($postAction === 'save_settings') {
        $unlockSpins = array_map('intval', explode(',', postParam('unlock_spins', '25,50,75')));
        $jackpotOrder = array_filter(explode(',', postParam('jackpot_order', '')));
        $hideJackpot = postParam('hide_jackpot') === '1';

        $result = updateSettings([
            'unlock_spins' => $unlockSpins,
            'jackpot_order' => $jackpotOrder,
            'hide_jackpot_from_picks' => $hideJackpot
        ]);

        if ($result) {
            $message = 'Settings saved successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to save settings.';
            $messageType = 'danger';
        }
    }
}

// Load data
$stats = getStatistics();
$settings = getSettings();
$spins = getSpinsHistory();
$prizes = getPrizes();
$employees = getEmployees();

// Get jackpot prizes
$jackpotPrizes = [];
foreach ($prizes as $prize) {
    if ($prize['tier'] === 'jackpot') {
        $jackpotPrizes[$prize['id']] = $prize;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - CREODENT HV Raffle 2025</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .jackpot-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            cursor: grab;
            margin-bottom: 0.5rem;
        }
        .jackpot-item:active {
            cursor: grabbing;
        }
        .jackpot-item.dragging {
            opacity: 0.5;
        }
        .jackpot-item .drag-handle {
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1>Admin Panel</h1>
            <p class="subtitle">Manage raffle settings and monitor progress</p>
        </header>

        <!-- Navigation -->
        <div class="flex justify-between items-center mb-3">
            <a href="index.php" class="btn btn-secondary">&larr; Back to Main</a>
            <a href="admin.php?action=logout" class="btn btn-danger">Logout</a>
        </div>

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> mb-3"><?= h($message) ?></div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="admin-section">
            <h2>Statistics</h2>
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-value accent" data-stat="spin_count"><?= $stats['spin_count'] ?></div>
                    <div class="stat-label">Total Spins</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value good" data-stat="employees_won"><?= $stats['employees_won'] ?></div>
                    <div class="stat-label">Winners</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value warn" data-stat="prizes_available"><?= $stats['prizes_available'] ?></div>
                    <div class="stat-label">Prizes Left</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" data-stat="employees_with_selections"><?= $stats['employees_with_selections'] ?></div>
                    <div class="stat-label">Made Selections</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value bad" data-stat="jackpot_won"><?= $stats['jackpot_won'] ?>/<?= $stats['jackpot_total'] ?></div>
                    <div class="stat-label">Jackpots Won</div>
                </div>
            </div>
        </div>

        <div class="admin-grid">
            <!-- Quick Actions -->
            <div class="card">
                <h3>Quick Actions</h3>

                <div class="mt-3">
                    <form method="POST" id="undo-form" style="display: inline;">
                        <input type="hidden" name="action" value="undo">
                        <button type="button" id="undo-btn" class="btn btn-warning"
                                <?= empty($spins) ? 'disabled' : '' ?>>
                            Undo Last Spin
                        </button>
                    </form>
                </div>

                <div class="mt-2">
                    <button type="button" id="export-btn" class="btn btn-secondary">
                        Export Results (CSV)
                    </button>
                </div>

                <div class="mt-2">
                    <button type="button" class="btn btn-primary" onclick="refreshStats()">
                        Refresh Statistics
                    </button>
                </div>
            </div>

            <!-- Jackpot Settings -->
            <div class="card">
                <h3>Jackpot Settings</h3>

                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">

                    <div class="form-group">
                        <label class="form-label">Unlock Spins (comma-separated)</label>
                        <input type="text" name="unlock_spins" class="form-input"
                               value="<?= h(implode(',', $settings['unlock_spins'])) ?>">
                        <small class="text-muted">Jackpots unlock at these spin counts</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jackpot Order (drag to reorder)</label>
                        <div id="jackpot-sortable">
                            <?php
                            $orderedJackpots = $settings['jackpot_order'];
                            foreach ($orderedJackpots as $prizeId):
                                $jp = $jackpotPrizes[$prizeId] ?? null;
                                if (!$jp) continue;
                            ?>
                            <div class="jackpot-item" data-prize-id="<?= h($prizeId) ?>">
                                <span class="drag-handle">&#9776;</span>
                                <span><?= h($jp['display_name']) ?></span>
                                <span class="badge <?= $jp['status'] === 'won' ? 'badge-success' : 'badge-info' ?>">
                                    <?= $jp['status'] === 'won' ? 'Won' : 'Available' ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="jackpot_order" id="jackpot-order-input"
                               value="<?= h(implode(',', $settings['jackpot_order'])) ?>">
                    </div>

                    <div class="form-group">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="hide_jackpot" value="1"
                                   <?= $settings['hide_jackpot_from_picks'] ? 'checked' : '' ?>>
                            Hide jackpot prizes from employee selection
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>

        <!-- Recent Spins -->
        <div class="admin-section mt-4">
            <h2>Recent Spins</h2>

            <?php if (empty($spins)): ?>
            <div class="card">
                <p class="text-muted">No spins yet.</p>
            </div>
            <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Prize</th>
                            <th>Winner</th>
                            <th>Type</th>
                            <th>Candidates</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($spins, 0, 20) as $spin): ?>
                        <tr>
                            <td><?= $spin['id'] ?></td>
                            <td><?= h($spin['prize_name']) ?></td>
                            <td><?= h($spin['winner_name']) ?></td>
                            <td>
                                <span class="badge <?= $spin['from_selectors'] ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $spin['from_selectors'] ? 'Selected' : 'Random' ?>
                                </span>
                            </td>
                            <td><?= $spin['eligible_count'] ?></td>
                            <td><?= date('H:i:s', $spin['timestamp']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Danger Zone -->
        <div class="admin-section mt-4">
            <h2 style="color: var(--bad);">Danger Zone</h2>
            <div class="card" style="border-color: var(--bad);">
                <p class="mb-2"><strong>Reset All Data</strong></p>
                <p class="text-muted mb-3">This will reset all selections and spin results to the initial state. This action cannot be undone.</p>

                <form method="POST" id="reset-form">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="confirm_reset" value="">

                    <button type="button" id="reset-btn" class="btn btn-danger">
                        Reset All Data
                    </button>
                </form>
            </div>
        </div>

        <!-- Employee Selection Status -->
        <div class="admin-section mt-4">
            <h2>Employee Selection Status</h2>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Selection Status</th>
                            <th>Won Prize</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        uasort($employees, function($a, $b) {
                            return strcasecmp($a['name'], $b['name']);
                        });
                        foreach ($employees as $emp):
                            $selections = getEmployeeSelections($emp['id']);
                        ?>
                        <tr>
                            <td><?= h($emp['id']) ?></td>
                            <td><?= h($emp['name']) ?></td>
                            <td>
                                <?php if (count($selections) > 0): ?>
                                <span class="badge badge-success"><?= count($selections) ?> selections</span>
                                <?php else: ?>
                                <span class="badge badge-warning">No selections</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($emp['won_prize_id']): ?>
                                <span class="badge badge-info"><?= h($emp['won_prize_name']) ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
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
        // Reset confirmation
        document.getElementById('reset-btn').addEventListener('click', function() {
            if (confirm('WARNING: This will reset ALL data including selections and winners.\n\nAre you sure you want to continue?')) {
                const confirmation = prompt('Type "RESET" to confirm:');
                if (confirmation === 'RESET') {
                    document.querySelector('input[name="confirm_reset"]').value = 'RESET';
                    document.getElementById('reset-form').submit();
                }
            }
        });

        // Undo confirmation
        document.getElementById('undo-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to undo the last spin?')) {
                document.getElementById('undo-form').submit();
            }
        });

        // Export
        document.getElementById('export-btn').addEventListener('click', function() {
            window.location.href = 'admin.php?action=export';
        });

        // Initialize jackpot sorting
        initJackpotSorting();
    </script>
</body>
</html>
