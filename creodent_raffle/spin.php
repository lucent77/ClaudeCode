<?php
/**
 * CREODENT HV Raffle 2025 - Prize Drawing Page
 *
 * Animated raffle drawing for prizes
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/storage.php';

// Get prize ID
$prizeId = getParam('prize_id');

if (!$prizeId || !isValidPrizeId($prizeId)) {
    redirect('index.php?tab=prizes');
}

$prize = getPrize($prizeId);
if (!$prize) {
    redirect('index.php?tab=prizes');
}

// Check if prize is available
if ($prize['status'] !== 'available') {
    redirect('index.php?tab=prizes');
}

// Check if jackpot is unlocked
$settings = getSettings();
$unlockedJackpots = getUnlockedJackpots();
if ($prize['tier'] === 'jackpot' && !in_array($prizeId, $unlockedJackpots)) {
    redirect('index.php?tab=jackpot');
}

// Get eligible employees
$eligibleFromSelectors = getEligibleEmployees($prizeId);
$allPending = getPendingEmployees();

// Determine candidate pool
if (!empty($eligibleFromSelectors)) {
    $candidates = $eligibleFromSelectors;
    $selectionType = 'selected';
} else {
    $candidates = array_values($allPending);
    $selectionType = 'random';
}

// Handle spin submission
$winner = null;
$spinResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && postParam('action') === 'spin') {
    $spinResult = performSpin($prizeId);

    if (isset($spinResult['success']) && $spinResult['success']) {
        $winner = $spinResult['winner'];

        // Check if a jackpot was just unlocked
        $newUnlocked = getUnlockedJackpots();
        $justUnlocked = array_diff($newUnlocked, $unlockedJackpots);
    }
}

$stats = getStatistics();
$isJackpot = $prize['tier'] === 'jackpot';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drawing: <?= h($prize['display_name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/confetti.css">
    <style>
        .drawing-container {
            text-align: center;
            padding: 3rem;
        }
        .prize-display {
            background: var(--bg-card);
            border: 2px solid <?= $isJackpot ? 'var(--jackpot)' : 'var(--accent)' ?>;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            <?= $isJackpot ? 'box-shadow: 0 0 30px var(--jackpot-glow);' : '' ?>
        }
        .prize-display h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            <?= $isJackpot ? 'color: var(--jackpot);' : 'color: var(--accent);' ?>
        }
        .candidates-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
            min-height: 200px;
            padding: 1.5rem;
            background: var(--bg-card);
            border-radius: 1rem;
        }
        .candidate {
            padding: 1rem 1.5rem;
            background: var(--bg3);
            border: 2px solid var(--border);
            border-radius: 0.75rem;
            font-size: 1.25rem;
            transition: all 0.1s ease;
        }
        .candidate.highlight {
            border-color: var(--warn);
            background: var(--warn-bg);
            transform: scale(1.05);
        }
        .candidate.winner {
            border-color: var(--good);
            background: var(--good-bg);
            transform: scale(1.3);
            font-weight: bold;
            box-shadow: 0 0 20px rgba(52, 211, 153, 0.5);
        }
        .winner-reveal {
            margin-top: 2rem;
            padding: 3rem;
            background: linear-gradient(135deg, var(--bg-card), var(--bg3));
            border-radius: 1.5rem;
            animation: fadeInUp 0.5s ease;
        }
        .winner-reveal h2 {
            font-size: 2rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        .winner-reveal .winner-name {
            font-size: 4rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--accent), var(--good), var(--warn));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer 2s infinite;
            background-size: 200% auto;
        }
        .winner-reveal.jackpot .winner-name {
            background: linear-gradient(90deg, var(--jackpot), var(--warn), var(--jackpot));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        @keyframes shimmer {
            0% { background-position: 0% center; }
            100% { background-position: 200% center; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1><?= $isJackpot ? 'JACKPOT DRAWING' : 'Prize Drawing' ?></h1>
            <p class="subtitle">Spin #<?= $stats['spin_count'] + 1 ?></p>
        </header>

        <!-- Back Link -->
        <div class="mb-3">
            <a href="index.php?tab=<?= $isJackpot ? 'jackpot' : 'prizes' ?>" class="btn btn-secondary">
                &larr; Back to <?= $isJackpot ? 'Jackpots' : 'Prizes' ?>
            </a>
        </div>

        <div class="drawing-container">
            <!-- Prize Display -->
            <div class="prize-display <?= $isJackpot ? 'jackpot-glow' : '' ?>">
                <?php if ($isJackpot): ?>
                <div class="badge badge-danger mb-2">JACKPOT</div>
                <?php endif; ?>
                <h2><?= h($prize['display_name']) ?></h2>
                <p class="text-muted"><?= h($prize['name']) ?></p>
            </div>

            <?php if ($winner): ?>
            <!-- Winner Display -->
            <div class="winner-reveal <?= $isJackpot ? 'jackpot' : '' ?>">
                <h2>AND THE WINNER IS...</h2>
                <div class="winner-name bounce-text"><?= h($winner['name']) ?></div>
                <p class="mt-3 text-muted">
                    <?php if ($spinResult['spin']['from_selectors']): ?>
                    Selected from <?= $spinResult['spin']['eligible_count'] ?> employees who chose this prize
                    <?php else: ?>
                    Randomly selected from <?= $spinResult['spin']['eligible_count'] ?> eligible employees
                    <?php endif; ?>
                </p>
            </div>

            <div class="action-buttons">
                <a href="index.php?tab=<?= $isJackpot ? 'jackpot' : 'prizes' ?>" class="btn btn-primary btn-large">
                    Continue to Next Prize
                </a>
            </div>

            <script>
                // Trigger celebration
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if ($isJackpot): ?>
                    createJackpotEffects();
                    <?php endif; ?>
                    createConfetti(<?= $isJackpot ? 200 : 100 ?>);
                });
            </script>

            <?php else: ?>
            <!-- Pre-Spin Display -->
            <div class="card mb-3">
                <div class="flex justify-between items-center">
                    <div>
                        <h3>Eligible Candidates</h3>
                        <p class="text-muted">
                            <?php if ($selectionType === 'selected'): ?>
                            These employees selected this prize
                            <?php else: ?>
                            No one selected this prize - drawing from all eligible employees
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="badge <?= $selectionType === 'selected' ? 'badge-success' : 'badge-warning' ?>">
                        <?= count($candidates) ?> candidates
                    </div>
                </div>
            </div>

            <?php if (empty($candidates)): ?>
            <div class="alert alert-danger">
                No eligible candidates for this prize. All employees have already won!
            </div>
            <a href="index.php?tab=prizes" class="btn btn-secondary">Back to Prizes</a>
            <?php else: ?>

            <!-- Candidates Grid -->
            <div class="candidates-grid" id="candidates-container">
                <?php foreach ($candidates as $candidate): ?>
                <div class="candidate" data-employee-id="<?= h($candidate['id']) ?>">
                    <?= h($candidate['name']) ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Spin Form -->
            <form method="POST" id="spin-form">
                <input type="hidden" name="action" value="spin">

                <button type="submit" class="btn <?= $isJackpot ? 'btn-danger' : 'btn-primary' ?> btn-large" id="spin-btn">
                    <?= $isJackpot ? 'DRAW JACKPOT WINNER' : 'DRAW WINNER' ?>
                </button>
            </form>

            <script>
                // Visual spin animation before form submission
                document.getElementById('spin-form').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const btn = document.getElementById('spin-btn');
                    btn.disabled = true;
                    btn.textContent = 'Drawing...';

                    const candidates = document.querySelectorAll('.candidate');
                    let currentIndex = 0;
                    let speed = 50;
                    let iterations = 0;
                    const totalIterations = candidates.length * 4 + Math.floor(Math.random() * candidates.length);

                    const animate = () => {
                        candidates.forEach(c => c.classList.remove('highlight'));
                        candidates[currentIndex].classList.add('highlight');

                        currentIndex = (currentIndex + 1) % candidates.length;
                        iterations++;

                        if (iterations >= totalIterations) {
                            // Animation complete, submit form
                            setTimeout(() => {
                                e.target.submit();
                            }, 500);
                            return;
                        }

                        // Slow down near the end
                        if (iterations > totalIterations * 0.7) {
                            speed = Math.min(speed + 15, 300);
                        }

                        setTimeout(animate, speed);
                    };

                    animate();
                });
            </script>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/app.js"></script>
</body>
</html>
