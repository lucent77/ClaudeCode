<?php
/**
 * CREODENT HV Raffle 2025 - Raffle Spin Page
 *
 * TV-optimized page for running the raffle with dramatic animations
 */

require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';

// Require admin authentication
requireAdmin();

// Get prize ID from URL
$prizeId = $_GET['prize'] ?? '';

if (!isValidPrizeId($prizeId)) {
    header('Location: index.php');
    exit;
}

// Load state
$state = loadState();
$prize = getPrize($state, $prizeId);

if (!$prize || $prize['status'] !== 'available') {
    header('Location: index.php');
    exit;
}

// Get eligible employees
$selectors = getSelectorsForPrize($state, $prizeId);
$pendingEmployees = getPendingEmployees($state);

// If no selectors, all pending employees are eligible
$eligibleEmployees = count($selectors) > 0 ? $selectors : $pendingEmployees;

// Check if this is a jackpot prize
$isJackpot = $prize['tier'] === 'jackpot';
$jackpotTier = 0;
if ($isJackpot) {
    $jackpotOrder = $state['settings']['jackpot_order'] ?? [];
    $jackpotTier = array_search($prizeId, $jackpotOrder);
    if ($jackpotTier !== false) {
        $jackpotTier += 1;
    }
}

$stats = getStatistics($state);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raffle - <?= h($prize['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/confetti.css">
    <style>
        body {
            overflow: hidden;
        }

        .spin-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }

        .prize-display {
            margin-bottom: 40px;
        }

        .prize-badge {
            display: inline-block;
            padding: 12px 32px;
            background: <?= $isJackpot ? 'var(--jackpot)' : 'var(--accent)' ?>;
            color: <?= $isJackpot ? 'white' : 'var(--bg1)' ?>;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 16px;
            <?php if ($isJackpot): ?>
            animation: jackpot-pulse 2s infinite;
            <?php endif; ?>
        }

        .prize-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 8px;
            <?php if ($isJackpot): ?>
            background: linear-gradient(135deg, var(--jackpot) 0%, #ec4899 50%, var(--warn) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            <?php endif; ?>
        }

        .eligible-count {
            font-size: 24px;
            color: var(--text-muted);
        }

        .spinner-area {
            width: 100%;
            max-width: 800px;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .name-display {
            font-size: 72px;
            font-weight: 900;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 40px 0;
            padding: 20px 60px;
            background: var(--bg3);
            border: 4px solid var(--bg4);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .name-display.spinning {
            border-color: var(--accent);
            box-shadow: 0 0 40px var(--accent-glow);
        }

        .name-display.winner {
            border-color: var(--good);
            box-shadow: 0 0 60px var(--good-glow);
            animation: winner-reveal 0.5s ease;
        }

        @keyframes winner-reveal {
            0% { transform: scale(0.9); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .candidates-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            max-width: 1000px;
            margin: 30px auto;
        }

        .candidate-chip {
            padding: 10px 20px;
            background: var(--bg4);
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .candidate-chip.active {
            background: var(--accent);
            color: var(--bg1);
            transform: scale(1.1);
        }

        .candidate-chip.winner {
            background: var(--good);
            color: var(--bg1);
            transform: scale(1.2);
            animation: chip-pop 0.5s ease;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            margin-top: 40px;
        }

        .spin-stats {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg3);
            padding: 16px 24px;
            border-radius: 12px;
            text-align: right;
        }

        .back-link {
            position: fixed;
            top: 20px;
            left: 20px;
        }

        /* Winner Display Mode */
        .winner-mode .prize-display {
            margin-bottom: 20px;
        }

        .winner-mode .name-display {
            font-size: 96px;
            background: linear-gradient(135deg, var(--good) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            border: none;
            box-shadow: none;
        }

        .winner-label {
            font-size: 32px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        /* Jackpot special styling */
        .jackpot-mode .spin-container {
            background: radial-gradient(circle at center, rgba(244, 114, 182, 0.1) 0%, transparent 70%);
        }

        .jackpot-mode .name-display.winner {
            border-color: var(--jackpot);
            box-shadow: 0 0 80px var(--jackpot-glow);
        }
    </style>
</head>
<body class="<?= $isJackpot ? 'jackpot-mode' : '' ?>">
    <a href="index.php#prizes" class="back-link btn btn-secondary">← Back to Prizes</a>

    <div class="spin-stats">
        <div class="text-muted">Spin Count</div>
        <div style="font-size: 32px; font-weight: 700; color: var(--accent);"><?= $stats['spin_count'] ?></div>
    </div>

    <div class="spin-container" id="spin-container">
        <div class="prize-display">
            <div class="prize-badge">
                <?php if ($isJackpot): ?>
                    JACKPOT TIER <?= $jackpotTier ?>
                <?php else: ?>
                    Prize #<?= $prize['num'] ?>
                <?php endif; ?>
            </div>
            <h1 class="prize-title"><?= h($prize['name']) ?></h1>
            <p class="eligible-count">
                <?= count($eligibleEmployees) ?> eligible
                <?= count($eligibleEmployees) === 1 ? 'employee' : 'employees' ?>
                <?php if (count($selectors) > 0): ?>
                    (selected this prize)
                <?php else: ?>
                    (random pool)
                <?php endif; ?>
            </p>
        </div>

        <div class="spinner-area">
            <div id="winner-label" class="winner-label hidden">WINNER!</div>
            <div class="name-display" id="name-display">
                <?php if (count($eligibleEmployees) > 0): ?>
                    Press SPIN to start
                <?php else: ?>
                    No eligible employees
                <?php endif; ?>
            </div>
        </div>

        <div class="candidates-preview" id="candidates-preview">
            <?php foreach ($eligibleEmployees as $emp): ?>
            <div class="candidate-chip" data-id="<?= h($emp['id']) ?>">
                <?= h($emp['name']) ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="action-buttons" id="action-buttons">
            <?php if (count($eligibleEmployees) > 0): ?>
            <button class="btn <?= $isJackpot ? 'btn-jackpot' : 'btn-primary' ?> btn-lg" id="spin-btn" onclick="startSpin()">
                <?php if ($isJackpot): ?>
                    🎰 SPIN JACKPOT!
                <?php else: ?>
                    🎲 SPIN!
                <?php endif; ?>
            </button>
            <?php endif; ?>
            <a href="index.php#prizes" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </div>

    <script src="assets/app.js"></script>
    <script>
        const prizeId = '<?= h($prizeId) ?>';
        const isJackpot = <?= $isJackpot ? 'true' : 'false' ?>;
        const candidates = <?= json_encode(array_map(fn($e) => ['id' => $e['id'], 'name' => $e['name']], $eligibleEmployees)) ?>;

        let isSpinning = false;
        let spinInterval = null;
        let currentIndex = 0;

        const nameDisplay = document.getElementById('name-display');
        const spinBtn = document.getElementById('spin-btn');
        const winnerLabel = document.getElementById('winner-label');
        const chips = document.querySelectorAll('.candidate-chip');

        function startSpin() {
            if (isSpinning || candidates.length === 0) return;

            isSpinning = true;
            spinBtn.disabled = true;
            spinBtn.textContent = 'Spinning...';
            nameDisplay.classList.add('spinning');

            // Start the visual spin animation
            let speed = 50;
            const spinDuration = 5000;
            const slowdownStart = spinDuration * 0.6;
            const startTime = Date.now();

            function tick() {
                const elapsed = Date.now() - startTime;

                // Update display
                currentIndex = (currentIndex + 1) % candidates.length;
                nameDisplay.textContent = candidates[currentIndex].name;

                // Update chips
                chips.forEach((chip, i) => {
                    chip.classList.toggle('active', i === currentIndex);
                });

                // Calculate speed
                if (elapsed < slowdownStart) {
                    speed = 50;
                } else {
                    const progress = (elapsed - slowdownStart) / (spinDuration - slowdownStart);
                    speed = 50 + progress * 450;
                }

                if (elapsed < spinDuration) {
                    setTimeout(tick, speed);
                } else {
                    // Animation done, now make actual API call
                    executeRaffle();
                }
            }

            tick();
        }

        async function executeRaffle() {
            try {
                const result = await RaffleApp.executeSpin(prizeId);

                // Show the actual winner
                showWinner(result.winner);

            } catch (error) {
                isSpinning = false;
                nameDisplay.classList.remove('spinning');
                nameDisplay.textContent = 'Error: ' + error.message;
                spinBtn.disabled = false;
                spinBtn.textContent = isJackpot ? '🎰 SPIN JACKPOT!' : '🎲 SPIN!';
                RaffleApp.showAlert(error.message, 'danger');
            }
        }

        function showWinner(winner) {
            nameDisplay.classList.remove('spinning');
            nameDisplay.classList.add('winner');
            nameDisplay.textContent = winner.name;

            winnerLabel.classList.remove('hidden');
            document.getElementById('spin-container').classList.add('winner-mode');

            // Highlight winner chip
            chips.forEach(chip => {
                chip.classList.remove('active');
                if (chip.dataset.id === winner.id) {
                    chip.classList.add('winner');
                }
            });

            // Celebration effects
            RaffleApp.celebrateWinner(isJackpot);

            // Update buttons
            document.getElementById('action-buttons').innerHTML = `
                <a href="index.php#prizes" class="btn btn-success btn-lg">Next Prize</a>
                <button class="btn btn-secondary btn-lg" onclick="location.reload()">View Again</button>
            `;
        }
    </script>
</body>
</html>
