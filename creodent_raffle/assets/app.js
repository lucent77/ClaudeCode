/**
 * CREODENT HV Raffle 2025 - Application JavaScript
 */

// Global state
const RaffleApp = {
    maxSelections: 5,
    selectedPrizes: [],
    currentEmployee: null,
    spinDuration: 5000,
    highlightInterval: 80,
    isSpinning: false,
    audioEnabled: true
};

/**
 * Initialize the application
 */
document.addEventListener('DOMContentLoaded', function() {
    initSearch();
    initTabs();
    initPrizeSelection();
    initSpinAnimation();
    loadSavedSelections();
});

/**
 * Search functionality
 */
function initSearch() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        filterItems(query);
    });
}

function filterItems(query) {
    const items = document.querySelectorAll('[data-searchable]');

    items.forEach(item => {
        const searchText = item.dataset.searchable.toLowerCase();
        if (query === '' || searchText.includes(query)) {
            item.style.display = '';
            item.classList.add('fade-in');
        } else {
            item.style.display = 'none';
        }
    });
}

/**
 * Tab navigation
 */
function initTabs() {
    const tabs = document.querySelectorAll('.tab[data-tab]');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.dataset.tab;
            switchTab(targetId);
        });
    });
}

function switchTab(targetId) {
    // Update tab buttons
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`[data-tab="${targetId}"]`)?.classList.add('active');

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById(targetId)?.classList.remove('hidden');
}

/**
 * Prize selection (choose.php)
 */
function initPrizeSelection() {
    const prizeCards = document.querySelectorAll('.prize-card.selectable');

    prizeCards.forEach(card => {
        card.addEventListener('click', function() {
            togglePrizeSelection(this);
        });
    });
}

function togglePrizeSelection(card) {
    const prizeId = card.dataset.prizeId;
    const prizeName = card.dataset.prizeName;

    if (card.classList.contains('selected')) {
        // Deselect
        card.classList.remove('selected');
        RaffleApp.selectedPrizes = RaffleApp.selectedPrizes.filter(p => p.id !== prizeId);
    } else {
        // Check max selections
        if (RaffleApp.selectedPrizes.length >= RaffleApp.maxSelections) {
            showNotification('Maximum 5 selections allowed!', 'warning');
            shakeElement(card);
            return;
        }

        // Select
        card.classList.add('selected');
        RaffleApp.selectedPrizes.push({ id: prizeId, name: prizeName });
    }

    updateSelectionDisplay();
    updateSelectionCounter();
}

function updateSelectionDisplay() {
    const container = document.getElementById('selection-list');
    if (!container) return;

    if (RaffleApp.selectedPrizes.length === 0) {
        container.innerHTML = '<div class="no-selection">No prizes selected yet. Click on prizes below to select.</div>';
        return;
    }

    container.innerHTML = RaffleApp.selectedPrizes.map(prize => `
        <div class="selection-item" data-prize-id="${prize.id}">
            <span>${prize.name}</span>
            <span class="remove" onclick="removePrizeSelection('${prize.id}')">&times;</span>
        </div>
    `).join('');
}

function removePrizeSelection(prizeId) {
    RaffleApp.selectedPrizes = RaffleApp.selectedPrizes.filter(p => p.id !== prizeId);

    // Update card visual
    const card = document.querySelector(`.prize-card[data-prize-id="${prizeId}"]`);
    if (card) card.classList.remove('selected');

    updateSelectionDisplay();
    updateSelectionCounter();
}

function updateSelectionCounter() {
    const counter = document.getElementById('selection-counter');
    const countDisplay = document.getElementById('selection-count');

    if (counter && countDisplay) {
        countDisplay.textContent = RaffleApp.selectedPrizes.length;
        counter.classList.toggle('full', RaffleApp.selectedPrizes.length >= RaffleApp.maxSelections);
    }
}

function loadSavedSelections() {
    // Load from page data if available
    const savedData = document.getElementById('saved-selections');
    if (savedData && savedData.dataset.selections) {
        try {
            const selections = JSON.parse(savedData.dataset.selections);
            selections.forEach(prizeId => {
                const card = document.querySelector(`.prize-card[data-prize-id="${prizeId}"]`);
                if (card) {
                    card.classList.add('selected');
                    RaffleApp.selectedPrizes.push({
                        id: prizeId,
                        name: card.dataset.prizeName
                    });
                }
            });
            updateSelectionDisplay();
            updateSelectionCounter();
        } catch (e) {
            console.error('Error loading saved selections:', e);
        }
    }
}

function saveSelections() {
    const prizeIds = RaffleApp.selectedPrizes.map(p => p.id);
    const employeeId = document.getElementById('employee-id')?.value;

    if (!employeeId) {
        showNotification('Employee ID not found!', 'error');
        return;
    }

    if (prizeIds.length === 0) {
        showNotification('Please select at least one prize!', 'warning');
        return;
    }

    // Submit via form
    const form = document.getElementById('selection-form');
    const input = document.getElementById('selected-prizes-input');

    if (form && input) {
        input.value = JSON.stringify(prizeIds);
        form.submit();
    }
}

/**
 * Spin Animation (spin.php)
 */
function initSpinAnimation() {
    const spinBtn = document.getElementById('spin-btn');
    if (spinBtn) {
        spinBtn.addEventListener('click', startSpin);
    }
}

function startSpin() {
    if (RaffleApp.isSpinning) return;

    const candidates = document.querySelectorAll('.candidate');
    if (candidates.length === 0) {
        showNotification('No candidates available!', 'error');
        return;
    }

    RaffleApp.isSpinning = true;
    const spinBtn = document.getElementById('spin-btn');
    if (spinBtn) spinBtn.disabled = true;

    // Get pre-selected winner from server (hidden field)
    const winnerInput = document.getElementById('winner-id');
    const winnerId = winnerInput ? winnerInput.value : null;

    // Animation
    let currentIndex = 0;
    let speed = RaffleApp.highlightInterval;
    let iterations = 0;
    const totalIterations = Math.floor(RaffleApp.spinDuration / RaffleApp.highlightInterval);

    // Play sound effect
    playSound('spin');

    const interval = setInterval(() => {
        // Remove previous highlight
        candidates.forEach(c => c.classList.remove('highlight'));

        // Add highlight to current
        candidates[currentIndex].classList.add('highlight');
        candidates[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Move to next
        currentIndex = (currentIndex + 1) % candidates.length;
        iterations++;

        // Slow down towards the end
        if (iterations > totalIterations * 0.7) {
            speed += 20;
        }
        if (iterations > totalIterations * 0.85) {
            speed += 40;
        }

        // End animation
        if (iterations >= totalIterations) {
            clearInterval(interval);

            // Find and highlight winner
            candidates.forEach(c => c.classList.remove('highlight'));

            const winnerCandidate = winnerId
                ? document.querySelector(`.candidate[data-employee-id="${winnerId}"]`)
                : candidates[Math.floor(Math.random() * candidates.length)];

            if (winnerCandidate) {
                winnerCandidate.classList.add('winner');
                winnerCandidate.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Show winner
                setTimeout(() => {
                    showWinner(winnerCandidate.dataset.employeeId, winnerCandidate.textContent.trim());
                }, 500);
            }
        }
    }, speed);
}

function showWinner(employeeId, employeeName) {
    playSound('win');
    createConfetti();

    const prizeId = document.getElementById('prize-id')?.value;
    const prizeName = document.getElementById('prize-name')?.value || 'Unknown Prize';
    const isJackpot = document.getElementById('is-jackpot')?.value === '1';

    // Create winner display
    const container = document.getElementById('spin-container');
    if (container) {
        container.innerHTML = `
            <div class="winner-display ${isJackpot ? 'winner-jackpot' : ''}">
                <div class="winner-crown">${isJackpot ? '🎰👑🎰' : '🎉👑🎉'}</div>
                <div class="winner-name">${employeeName}</div>
                <div class="winner-prize">wins ${prizeName}!</div>
                <div class="btn-group mt-4">
                    <button class="btn btn-success btn-lg" onclick="confirmWin('${employeeId}', '${prizeId}')">
                        ✓ Confirm Winner
                    </button>
                    <button class="btn btn-danger btn-lg" onclick="cancelWin()">
                        ✕ Cancel & Respin
                    </button>
                </div>
            </div>
        `;
    }

    if (isJackpot) {
        createJackpotEffect();
    }
}

function confirmWin(employeeId, prizeId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'spin.php';

    const inputs = [
        { name: 'action', value: 'confirm_win' },
        { name: 'employee_id', value: employeeId },
        { name: 'prize_id', value: prizeId }
    ];

    inputs.forEach(input => {
        const el = document.createElement('input');
        el.type = 'hidden';
        el.name = input.name;
        el.value = input.value;
        form.appendChild(el);
    });

    document.body.appendChild(form);
    form.submit();
}

function cancelWin() {
    location.reload();
}

/**
 * Confetti Effect
 */
function createConfetti() {
    const container = document.createElement('div');
    container.className = 'confetti-container';
    document.body.appendChild(container);

    const colors = ['#7dd3fc', '#34d399', '#fbbf24', '#fb7185', '#ffd700', '#a855f7'];

    for (let i = 0; i < 150; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.animationDelay = Math.random() * 2 + 's';
        confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';

        // Random shapes
        if (Math.random() > 0.5) {
            confetti.style.borderRadius = '50%';
        } else if (Math.random() > 0.5) {
            confetti.style.width = '8px';
            confetti.style.height = '16px';
        }

        container.appendChild(confetti);
    }

    // Remove after animation
    setTimeout(() => container.remove(), 5000);
}

/**
 * Jackpot Special Effect
 */
function createJackpotEffect() {
    // Add extra celebration elements
    document.body.classList.add('jackpot-celebration');

    // Create golden sparkles
    const sparkleContainer = document.createElement('div');
    sparkleContainer.className = 'sparkle-container';
    sparkleContainer.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:999;';

    for (let i = 0; i < 50; i++) {
        const sparkle = document.createElement('div');
        sparkle.innerHTML = '✨';
        sparkle.style.cssText = `
            position:absolute;
            left:${Math.random()*100}%;
            top:${Math.random()*100}%;
            font-size:${Math.random()*30+20}px;
            animation: sparkle ${Math.random()*2+1}s ease-in-out infinite;
            animation-delay: ${Math.random()}s;
        `;
        sparkleContainer.appendChild(sparkle);
    }

    document.body.appendChild(sparkleContainer);

    // Add CSS for sparkle animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0); }
            50% { opacity: 1; transform: scale(1); }
        }
        .jackpot-celebration {
            animation: jackpot-bg 1s ease infinite;
        }
        @keyframes jackpot-bg {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.1); }
        }
    `;
    document.head.appendChild(style);

    setTimeout(() => {
        sparkleContainer.remove();
        document.body.classList.remove('jackpot-celebration');
    }, 8000);
}

/**
 * Sound Effects
 */
function playSound(type) {
    if (!RaffleApp.audioEnabled) return;

    // Using Web Audio API for simple sounds
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        if (type === 'spin') {
            oscillator.frequency.value = 440;
            oscillator.type = 'sine';
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        } else if (type === 'win') {
            // Victory fanfare
            oscillator.frequency.value = 523.25; // C5
            oscillator.type = 'square';
            gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        }

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    } catch (e) {
        console.log('Audio not available');
    }
}

/**
 * Notifications
 */
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 20px 28px;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    `;

    const colors = {
        info: { bg: 'rgba(125, 211, 252, 0.2)', border: '#7dd3fc', color: '#7dd3fc' },
        success: { bg: 'rgba(52, 211, 153, 0.2)', border: '#34d399', color: '#34d399' },
        warning: { bg: 'rgba(251, 191, 36, 0.2)', border: '#fbbf24', color: '#fbbf24' },
        error: { bg: 'rgba(251, 113, 133, 0.2)', border: '#fb7185', color: '#fb7185' }
    };

    const c = colors[type] || colors.info;
    notification.style.background = c.bg;
    notification.style.border = `2px solid ${c.border}`;
    notification.style.color = c.color;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add notification animations
const notifStyle = document.createElement('style');
notifStyle.textContent = `
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
`;
document.head.appendChild(notifStyle);

/**
 * Utility Functions
 */
function shakeElement(element) {
    element.style.animation = 'shake 0.5s ease';
    setTimeout(() => element.style.animation = '', 500);
}

// Add shake animation
const shakeStyle = document.createElement('style');
shakeStyle.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;
document.head.appendChild(shakeStyle);

/**
 * Admin Functions
 */
function undoLastSpin() {
    if (!confirm('Are you sure you want to undo the last spin? This action cannot be undone.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin.php?action=undo';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'confirm';
    input.value = '1';
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}

function resetAllData() {
    if (!confirm('WARNING: This will reset ALL data including selections and spin history. Are you absolutely sure?')) {
        return;
    }

    if (!confirm('This is your FINAL warning. All data will be permanently deleted. Continue?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'admin.php?action=reset';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'confirm';
    input.value = '1';
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
}

function exportCSV() {
    window.location.href = 'admin.php?action=export';
}

/**
 * Jackpot Selection (Admin)
 */
function toggleJackpotSelection(prizeId) {
    const input = document.getElementById('jackpot-order');
    if (!input) return;

    let order = input.value ? input.value.split(',') : [];
    const maxJackpots = 3;

    const index = order.indexOf(prizeId);
    if (index > -1) {
        // Remove from selection
        order.splice(index, 1);
        document.querySelector(`[data-prize-id="${prizeId}"]`)?.classList.remove('jackpot-selected');
    } else {
        if (order.length >= maxJackpots) {
            showNotification(`Maximum ${maxJackpots} jackpot prizes allowed!`, 'warning');
            return;
        }
        // Add to selection
        order.push(prizeId);
        document.querySelector(`[data-prize-id="${prizeId}"]`)?.classList.add('jackpot-selected');
    }

    input.value = order.join(',');
    updateJackpotOrderDisplay(order);
}

function updateJackpotOrderDisplay(order) {
    const display = document.getElementById('jackpot-order-display');
    if (!display) return;

    if (order.length === 0) {
        display.innerHTML = '<div class="no-selection">No jackpot prizes selected</div>';
        return;
    }

    display.innerHTML = order.map((id, index) => {
        const prize = document.querySelector(`[data-prize-id="${id}"]`);
        const name = prize ? prize.dataset.prizeName : id;
        return `<div class="jackpot-order-item">Stage ${index + 1}: ${name}</div>`;
    }).join('');
}

/**
 * Slot Machine Animation (Alternative Spin)
 */
function startSlotMachineSpin() {
    const slots = document.querySelectorAll('.slot');
    if (slots.length === 0) return;

    RaffleApp.isSpinning = true;

    slots.forEach((slot, index) => {
        const items = slot.querySelectorAll('.slot-item');
        let currentItem = 0;

        const spin = setInterval(() => {
            items.forEach(item => item.classList.remove('active'));
            items[currentItem].classList.add('active');
            currentItem = (currentItem + 1) % items.length;
        }, 50 + index * 30);

        // Stop at different times for slot machine effect
        setTimeout(() => {
            clearInterval(spin);
        }, 2000 + index * 800);
    });
}

/**
 * Real-time Updates (Optional - for multi-device sync)
 */
function pollForUpdates() {
    setInterval(async () => {
        try {
            const response = await fetch('api.php?action=get_stats');
            const data = await response.json();

            if (data.spin_count !== RaffleApp.lastSpinCount) {
                RaffleApp.lastSpinCount = data.spin_count;
                updateStatsDisplay(data);
            }
        } catch (e) {
            console.log('Polling error:', e);
        }
    }, 5000);
}

function updateStatsDisplay(data) {
    const spinCount = document.getElementById('stat-spin-count');
    const wonPrizes = document.getElementById('stat-won-prizes');
    const progressBar = document.querySelector('.progress-bar');

    if (spinCount) spinCount.textContent = data.spin_count;
    if (wonPrizes) wonPrizes.textContent = data.won_prizes;
    if (progressBar) progressBar.style.width = data.progress_percent + '%';
}

// Initialize polling if enabled
if (document.querySelector('[data-enable-polling]')) {
    pollForUpdates();
}
