<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prize Drawing - Offline Raffle System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1 class="title">🎰 PRIZE DRAWING</h1>
            <p class="subtitle">Spin the wheel and win amazing prizes!</p>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Roulette Wheel Container -->
            <div id="wheelContainer" class="wheel-container">
                <div class="wheel-wrapper">
                    <!-- Arrow Pointer -->
                    <div class="arrow-pointer">▼</div>

                    <!-- SVG Roulette Wheel -->
                    <svg id="wheel" class="wheel" viewBox="0 0 400 400">
                        <!-- Wheel will be generated dynamically by JavaScript -->
                    </svg>

                    <!-- Center Circle -->
                    <div class="center-circle">
                        <span class="center-text">SPIN</span>
                    </div>
                </div>
            </div>

            <!-- Spin Button -->
            <div class="button-container">
                <button id="spinButton" class="spin-button" onclick="startDraw()">
                    <span class="button-icon">🎰</span>
                    <span class="button-text">SPIN THE WHEEL!</span>
                </button>
            </div>

            <!-- Special Prize Progress (Optional) -->
            <div id="specialPrizeProgress" class="special-progress" style="display: none;">
                <div class="progress-text">
                    Special Prize Progress: <span id="progressCount">0</span> / <span id="progressTotal">100</span>
                </div>
                <div class="progress-bar-container">
                    <div id="progressBar" class="progress-bar"></div>
                </div>
            </div>

            <!-- Result Display -->
            <div id="resultContainer" class="result-container" style="display: none;">
                <div class="result-card">
                    <div id="resultIcon" class="result-icon">🎉</div>
                    <div id="resultTitle" class="result-title">Congratulations!</div>
                    <div id="resultPrize" class="result-prize">iPhone 15 Pro</div>
                    <button onclick="resetDraw()" class="reset-button">SPIN AGAIN</button>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <a href="admin/" class="admin-link">Admin Panel</a>
        </footer>
    </div>

    <script src="assets/js/raffle.js"></script>
    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPrizes();
            loadSpecialPrizeSettings();
        });
    </script>
</body>
</html>
