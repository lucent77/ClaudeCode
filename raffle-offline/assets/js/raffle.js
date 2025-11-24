/**
 * Offline Prize Drawing System - JavaScript
 * Handles wheel rendering, spinning animation, and API interactions
 */

// Global variables
let prizes = [];
let isSpinning = false;
let specialSettings = null;

/**
 * Load prizes from API
 */
async function loadPrizes() {
    try {
        const response = await fetch('api/get_prizes.php');
        const data = await response.json();

        if (data.success) {
            prizes = data.prizes;
            renderWheel();

            if (!data.is_valid) {
                console.warn('Warning: Total probability does not equal 100%');
            }
        } else {
            showError('Failed to load prizes: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error loading prizes:', error);
        showError('Failed to load prizes. Please check your connection.');
    }
}

/**
 * Load special prize settings
 */
async function loadSpecialPrizeSettings() {
    try {
        const response = await fetch('api/get_special_prize_settings.php');
        const data = await response.json();

        if (data.success) {
            specialSettings = data.settings;
            updateSpecialPrizeProgress();
        }
    } catch (error) {
        console.error('Error loading special prize settings:', error);
    }
}

/**
 * Update special prize progress display
 */
function updateSpecialPrizeProgress() {
    if (!specialSettings || specialSettings.is_active != 1) {
        document.getElementById('specialPrizeProgress').style.display = 'none';
        return;
    }

    document.getElementById('specialPrizeProgress').style.display = 'block';
    document.getElementById('progressCount').textContent = specialSettings.current_count;
    document.getElementById('progressTotal').textContent = specialSettings.milestone_count;

    const percentage = (specialSettings.current_count / specialSettings.milestone_count) * 100;
    document.getElementById('progressBar').style.width = percentage + '%';
}

/**
 * Render the SVG wheel
 */
function renderWheel() {
    if (prizes.length === 0) {
        console.warn('No prizes to render');
        return;
    }

    const wheel = document.getElementById('wheel');
    wheel.innerHTML = '';

    const centerX = 200;
    const centerY = 200;
    const radius = 190;
    const totalProbability = prizes.reduce((sum, p) => sum + parseFloat(p.probability), 0);
    let currentAngle = 0;

    // Create wheel segments
    prizes.forEach((prize, index) => {
        const sliceAngle = (parseFloat(prize.probability) / totalProbability) * 360;
        const endAngle = currentAngle + sliceAngle;

        // Create slice path
        const slice = createSlice(centerX, centerY, radius, currentAngle, endAngle, prize.color);
        wheel.appendChild(slice);

        // Add text label
        const textAngle = currentAngle + (sliceAngle / 2);
        const text = createText(centerX, centerY, radius * 0.65, textAngle, prize.name);
        wheel.appendChild(text);

        currentAngle = endAngle;
    });

    // Add outer circle border
    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', centerX);
    circle.setAttribute('cy', centerY);
    circle.setAttribute('r', radius);
    circle.setAttribute('fill', 'none');
    circle.setAttribute('stroke', '#fff');
    circle.setAttribute('stroke-width', '8');
    wheel.appendChild(circle);
}

/**
 * Create SVG slice path
 */
function createSlice(cx, cy, r, startAngle, endAngle, color) {
    const start = polarToCartesian(cx, cy, r, endAngle);
    const end = polarToCartesian(cx, cy, r, startAngle);
    const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';

    const d = [
        'M', cx, cy,
        'L', start.x, start.y,
        'A', r, r, 0, largeArcFlag, 0, end.x, end.y,
        'Z'
    ].join(' ');

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', d);
    path.setAttribute('fill', color);
    path.setAttribute('stroke', '#fff');
    path.setAttribute('stroke-width', '2');

    return path;
}

/**
 * Create SVG text element
 */
function createText(cx, cy, r, angle, text) {
    const pos = polarToCartesian(cx, cy, r, angle);

    const textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    textElement.setAttribute('x', pos.x);
    textElement.setAttribute('y', pos.y);
    textElement.setAttribute('fill', '#fff');
    textElement.setAttribute('font-size', '14');
    textElement.setAttribute('font-weight', 'bold');
    textElement.setAttribute('text-anchor', 'middle');
    textElement.setAttribute('dominant-baseline', 'middle');
    textElement.setAttribute('transform', `rotate(${angle}, ${pos.x}, ${pos.y})`);

    // Truncate long text
    const maxLength = 20;
    const displayText = text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    textElement.textContent = displayText;

    return textElement;
}

/**
 * Convert polar coordinates to cartesian
 */
function polarToCartesian(cx, cy, r, angle) {
    const angleInRadians = (angle - 90) * Math.PI / 180.0;
    return {
        x: cx + (r * Math.cos(angleInRadians)),
        y: cy + (r * Math.sin(angleInRadians))
    };
}

/**
 * Start drawing
 */
async function startDraw() {
    if (isSpinning) {
        return;
    }

    if (prizes.length === 0) {
        showError('No prizes available');
        return;
    }

    isSpinning = true;
    const spinButton = document.getElementById('spinButton');
    spinButton.disabled = true;

    try {
        // Call draw API
        const response = await fetch('api/draw.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Update special prize progress
            if (data.special_prize_progress) {
                specialSettings = {
                    ...specialSettings,
                    current_count: data.special_prize_progress.current_count
                };
                updateSpecialPrizeProgress();
            }

            // Spin the wheel
            await spinWheel(data.prize);

            // Show result
            showResult(data.prize);

            // Reload prizes to update stock
            await loadPrizes();
        } else {
            showError(data.error || 'Drawing failed');
            isSpinning = false;
            spinButton.disabled = false;
        }
    } catch (error) {
        console.error('Error during draw:', error);
        showError('Drawing failed. Please try again.');
        isSpinning = false;
        spinButton.disabled = false;
    }
}

/**
 * Spin the wheel animation
 */
function spinWheel(prize) {
    return new Promise((resolve) => {
        const wheel = document.getElementById('wheel');

        // Find the angle for the winning prize
        let targetAngle = 0;
        let currentAngle = 0;
        const totalProbability = prizes.reduce((sum, p) => sum + parseFloat(p.probability), 0);

        for (let i = 0; i < prizes.length; i++) {
            const sliceAngle = (parseFloat(prizes[i].probability) / totalProbability) * 360;

            if (prizes[i].id === prize.id) {
                // Target the middle of the slice
                targetAngle = currentAngle + (sliceAngle / 2);
                break;
            }

            currentAngle += sliceAngle;
        }

        // Calculate final rotation (multiple spins + target angle)
        const spins = 5; // Number of full rotations
        const randomOffset = (Math.random() - 0.5) * 20; // Random offset ±10 degrees
        const finalRotation = (360 * spins) + (360 - targetAngle) + randomOffset;

        // Apply rotation
        wheel.style.transform = `rotate(${finalRotation}deg)`;

        // Wait for animation to complete
        setTimeout(() => {
            resolve();
        }, 4000); // Match the CSS transition duration
    });
}

/**
 * Show result modal
 */
function showResult(prize) {
    const resultContainer = document.getElementById('resultContainer');
    const resultIcon = document.getElementById('resultIcon');
    const resultTitle = document.getElementById('resultTitle');
    const resultPrize = document.getElementById('resultPrize');

    // Determine if it's a winning prize or not
    const isWinner = !prize.name.toLowerCase().includes('no prize') &&
                     !prize.name.toLowerCase().includes('꽝') &&
                     !prize.name.toLowerCase().includes('lose');

    if (isWinner) {
        resultIcon.textContent = prize.is_special ? '🌟' : '🎉';
        resultTitle.textContent = prize.is_special ? 'SPECIAL PRIZE!' : 'Congratulations!';
    } else {
        resultIcon.textContent = '😢';
        resultTitle.textContent = 'Try Again!';
    }

    resultPrize.textContent = prize.name;
    resultPrize.style.color = prize.color;

    resultContainer.style.display = 'flex';
}

/**
 * Reset and hide result
 */
function resetDraw() {
    const resultContainer = document.getElementById('resultContainer');
    resultContainer.style.display = 'none';

    const wheel = document.getElementById('wheel');
    wheel.style.transform = 'rotate(0deg)';
    wheel.style.transition = 'none';

    setTimeout(() => {
        wheel.style.transition = 'transform 4s cubic-bezier(0.17, 0.67, 0.12, 0.99)';
    }, 50);

    isSpinning = false;
    const spinButton = document.getElementById('spinButton');
    spinButton.disabled = false;
}

/**
 * Show error message
 */
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;

    const container = document.querySelector('.main-content');
    container.insertBefore(errorDiv, container.firstChild);

    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}
