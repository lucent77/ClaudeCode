<?php
/**
 * CREODENT HV Raffle 2025 - Spin/Raffle Page
 * TV Optimized raffle animation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/utils.php';

// Get prize ID
$prizeId = getParam('prize_id');

if (!$prizeId || !isValidPrizeId($prizeId)) {
    redirect('index.php?tab=prizes&error=invalid_prize');
}

// Load state
Storage::acquireLock();
$state = Storage::loadState();

// Get prize
$prize = Storage::getPrize($state, $prizeId);

if (!$prize) {
    Storage::releaseLock();
    redirect('index.php?tab=prizes&error=prize_not_found');
}

if ($prize['status'] !== 'available') {
    Storage::releaseLock();
    redirect('index.php?tab=prizes&error=prize_already_won');
}

// Check if jackpot is unlocked
$isJackpot = $prize['tier'] === 'jackpot';
if ($isJackpot) {
    $jackpotOrder = $state['settings']['jackpot_order'] ?? [];
    $unlockSpins = $state['settings']['unlock_spins'] ?? DEFAULT_UNLOCK_SPINS;
    $jackpotIndex = array_search($prizeId, $jackpotOrder);

    if ($jackpotIndex !== false && isset($unlockSpins[$jackpotIndex])) {
        if ($state['spin_count'] < $unlockSpins[$jackpotIndex]) {
            Storage::releaseLock();
            redirect('index.php?tab=prizes&error=jackpot_locked');
        }
    }
}

// Handle confirm win
$message = null;
$winnerConfirmed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && postParam('action') === 'confirm_win') {
    $winnerId = postParam('employee_id');

    if ($winnerId && isValidEmployeeId($winnerId)) {
        $result = Storage::performSpin($state, $prizeId, $winnerId);

        if ($result) {
            Storage::saveState($state);
            Storage::releaseLock();
            redirect('index.php?tab=history&success=win_confirmed&winner=' . urlencode($result['winner_name']));
        } else {
            $message = 'Error confirming win. Please try again.';
        }
    }
}

// Get candidates (employees who selected this prize)
$candidates = Storage::getPrizeSelectors($state, $prizeId);

// If no selectors, get all pending employees
if (empty($candidates)) {
    $candidates = array_values(Storage::getPendingEmployees($state));
}

// Shuffle candidates
shuffle($candidates);

// Pre-select winner (server-side randomization for fairness)
$winner = null;
if (!empty($candidates)) {
    $winner = $candidates[array_rand($candidates)];
}

Storage::releaseLock();

// Get selector count for display
$selectorCount = isset($state['prize_selections'][$prizeId])
    ? count(array_filter($state['prize_selections'][$prizeId], function($empId) use ($state) {
        $emp = Storage::getEmployee($state, $empId);
        return $emp && $emp['status'] === 'pending';
    }))
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spin for <?= h($prize['display_name']) ?> | CREODENT HV Raffle 2025</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Spin Page Specific Styles */
        .spin-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .prize-showcase {
            text-align: center;
            padding: 48px;
            background: linear-gradient(135deg, var(--bg2), var(--bg3));
            border-radius: var(--radius);
            margin-bottom: 32px;
            border: 2px solid var(--border);
        }

        .prize-showcase.jackpot {
            border-color: var(--jackpot);
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.2), var(--bg3));
            animation: jackpot-glow 2s ease infinite;
        }

        @keyframes jackpot-glow {
            0%, 100% { box-shadow: 0 0 20px var(--jackpot-glow); }
            50% { box-shadow: 0 0 60px var(--jackpot-glow); }
        }

        .prize-number {
            font-size: 24px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .prize-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .prize-title.jackpot {
            background: linear-gradient(90deg, var(--jackpot), var(--gold), var(--jackpot));
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 2s linear infinite;
        }

        .prize-meta {
            font-size: 20px;
            color: var(--text-muted);
        }

        .candidate-section {
            flex: 1;
            padding: 32px 0;
        }

        .candidate-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .candidate-header h3 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .candidate-pool {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: center;
            padding: 32px;
            background: var(--card-bg);
            border-radius: var(--radius);
            min-height: 200px;
            align-items: center;
        }

        .candidate {
            padding: 20px 32px;
            font-size: 22px;
            font-weight: 600;
            background: var(--bg3);
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            transition: all 0.1s ease;
        }

        .spin-controls {
            text-align: center;
            padding: 32px;
        }

        .spin-btn {
            padding: 28px 64px;
            font-size: 32px;
            background: linear-gradient(135deg, var(--good), #10b981);
            border: none;
            border-radius: var(--radius);
            color: var(--bg1);
            cursor: pointer;
            transition: all 0.3s ease;
            animation: pulse-btn 2s infinite;
        }

        .spin-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 40px var(--good-glow);
        }

        .spin-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            animation: none;
        }

        .spin-btn.jackpot {
            background: linear-gradient(135deg, var(--jackpot), #e11d48);
            animation: pulse-jackpot 1.5s infinite;
        }

        @keyframes pulse-btn {
            0%, 100% { box-shadow: 0 0 0 0 var(--good-glow); }
            50% { box-shadow: 0 0 30px 15px var(--good-glow); }
        }

        .no-candidates {
            text-align: center;
            padding: 64px;
            color: var(--text-muted);
        }

        .no-candidates p {
            font-size: 24px;
            margin-bottom: 24px;
        }

        /* Hidden inputs for JS */
        .hidden-data {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container spin-page">
        <!-- Back Button -->
        <div style="margin-bottom: 24px;">
            <a href="index.php?tab=prizes" class="btn">← Back to Prizes</a>
        </div>

        <!-- Prize Showcase -->
        <div class="prize-showcase <?= $isJackpot ? 'jackpot' : '' ?>">
            <div class="prize-number">Prize #<?= $prize['num'] ?></div>
            <h1 class="prize-title <?= $isJackpot ? 'jackpot' : '' ?>">
                <?php if ($isJackpot): ?>🎰 <?php endif; ?>
                <?= h($prize['display_name']) ?>
                <?php if ($isJackpot): ?> 🎰<?php endif; ?>
            </h1>
            <?php if ($isJackpot): ?>
                <div class="badge badge-jackpot" style="font-size: 18px; padding: 10px 20px;">JACKPOT PRIZE</div>
            <?php endif; ?>
            <div class="prize-meta mt-2">
                <?php if ($selectorCount > 0): ?>
                    <?= $selectorCount ?> employee<?= $selectorCount !== 1 ? 's' : '' ?> selected this prize
                <?php else: ?>
                    No pre-selections - All pending employees eligible
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="error-message"><?= h($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($candidates)): ?>
        <!-- Candidate Section -->
        <div class="candidate-section">
            <div class="candidate-header">
                <h3>🎯 Eligible Candidates</h3>
                <p class="text-muted"><?= count($candidates) ?> employee<?= count($candidates) !== 1 ? 's' : '' ?> in the draw</p>
            </div>

            <div id="spin-container">
                <div class="candidate-pool">
                    <?php foreach ($candidates as $candidate): ?>
                    <div class="candidate" data-employee-id="<?= h($candidate['id']) ?>">
                        <?= h($candidate['name']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="spin-controls">
                    <button id="spin-btn" class="spin-btn <?= $isJackpot ? 'jackpot' : '' ?>">
                        <?php if ($isJackpot): ?>
                            🎰 SPIN THE JACKPOT! 🎰
                        <?php else: ?>
                            🎯 SPIN TO WIN!
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden data for JavaScript -->
        <div class="hidden-data">
            <input type="hidden" id="prize-id" value="<?= h($prizeId) ?>">
            <input type="hidden" id="prize-name" value="<?= h($prize['display_name']) ?>">
            <input type="hidden" id="winner-id" value="<?= $winner ? h($winner['id']) : '' ?>">
            <input type="hidden" id="is-jackpot" value="<?= $isJackpot ? '1' : '0' ?>">
        </div>

        <?php else: ?>
        <!-- No Candidates -->
        <div class="no-candidates">
            <p>😕 No eligible candidates for this prize.</p>
            <p>All employees have already won prizes.</p>
            <a href="index.php?tab=prizes" class="btn btn-primary btn-lg">← Back to Prizes</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="assets/app.js"></script>
</body>
</html>
