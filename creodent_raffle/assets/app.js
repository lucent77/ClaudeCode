/**
 * CREODENT HV Raffle 2025 - Main JavaScript Application
 */

// API Base URL
const API_BASE = 'api';

// Global state
let appState = null;
let isLoading = false;

// ============================================================================
// API Functions
// ============================================================================

async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    };

    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    const response = await fetch(`${API_BASE}/${endpoint}`, options);
    const result = await response.json();

    if (!response.ok) {
        throw new Error(result.error || 'API request failed');
    }

    return result;
}

async function loadState() {
    try {
        const result = await apiCall('state.php');
        appState = result;
        return result;
    } catch (error) {
        console.error('Failed to load state:', error);
        showAlert('Failed to load data: ' + error.message, 'danger');
        throw error;
    }
}

async function saveSelections(employeeId, prizeIds) {
    return apiCall('select.php', 'POST', { employee_id: employeeId, prize_ids: prizeIds });
}

async function executeSpin(prizeId) {
    return apiCall('spin.php', 'POST', { prize_id: prizeId });
}

async function undoLastSpin() {
    return apiCall('undo.php', 'POST');
}

async function resetAllData() {
    return apiCall('reset.php', 'POST', { confirm: true });
}

async function updateSettings(settings) {
    return apiCall('settings.php', 'POST', settings);
}

async function adminLogin(pin) {
    return apiCall('login.php', 'POST', { pin });
}

async function adminLogout() {
    return apiCall('logout.php', 'POST');
}

// ============================================================================
// UI Helper Functions
// ============================================================================

function showLoading(message = 'Loading...') {
    isLoading = true;
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="spinner"></div>
            <div class="loading-text">${message}</div>
        `;
        document.body.appendChild(overlay);
    } else {
        overlay.querySelector('.loading-text').textContent = message;
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    isLoading = false;
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alertContainer.appendChild(alert);

    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9000; max-width: 400px;';
    document.body.appendChild(container);
    return container;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// Confetti and Celebration Effects
// ============================================================================

class ConfettiCannon {
    constructor() {
        this.canvas = document.createElement('canvas');
        this.canvas.id = 'confetti-canvas';
        document.body.appendChild(this.canvas);
        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        this.animating = false;
        this.resize();
        window.addEventListener('resize', () => this.resize());
    }

    resize() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    createParticle(x, y) {
        const colors = ['#7dd3fc', '#34d399', '#fbbf24', '#fb7185', '#f472b6', '#a78bfa'];
        return {
            x,
            y,
            vx: (Math.random() - 0.5) * 20,
            vy: Math.random() * -20 - 10,
            color: colors[Math.floor(Math.random() * colors.length)],
            size: Math.random() * 10 + 5,
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * 10,
            gravity: 0.5,
            friction: 0.99,
            opacity: 1
        };
    }

    fire(x, y, count = 100) {
        for (let i = 0; i < count; i++) {
            this.particles.push(this.createParticle(x, y));
        }
        if (!this.animating) {
            this.animating = true;
            this.animate();
        }
    }

    burst() {
        // Fire from multiple points
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;
        this.fire(centerX, centerY, 150);
        setTimeout(() => this.fire(centerX - 200, centerY, 50), 100);
        setTimeout(() => this.fire(centerX + 200, centerY, 50), 100);
    }

    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

        this.particles = this.particles.filter(p => {
            p.vy += p.gravity;
            p.vx *= p.friction;
            p.vy *= p.friction;
            p.x += p.vx;
            p.y += p.vy;
            p.rotation += p.rotationSpeed;
            p.opacity -= 0.01;

            if (p.opacity <= 0) return false;

            this.ctx.save();
            this.ctx.translate(p.x, p.y);
            this.ctx.rotate((p.rotation * Math.PI) / 180);
            this.ctx.globalAlpha = p.opacity;
            this.ctx.fillStyle = p.color;
            this.ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
            this.ctx.restore();

            return true;
        });

        if (this.particles.length > 0) {
            requestAnimationFrame(() => this.animate());
        } else {
            this.animating = false;
        }
    }
}

// Initialize confetti cannon
let confetti;
document.addEventListener('DOMContentLoaded', () => {
    confetti = new ConfettiCannon();
});

function celebrateWinner(isJackpot = false) {
    if (!confetti) {
        confetti = new ConfettiCannon();
    }

    // Confetti burst
    confetti.burst();

    // Additional celebration for jackpot
    if (isJackpot) {
        document.body.classList.add('jackpot-celebration');
        setTimeout(() => document.body.classList.remove('jackpot-celebration'), 1500);

        // Extra confetti bursts
        setTimeout(() => confetti.burst(), 500);
        setTimeout(() => confetti.burst(), 1000);
    }

    // Add floating text
    createFloatingText('WINNER!');
}

function createFloatingText(text) {
    const el = document.createElement('div');
    el.className = 'floating-text';
    el.textContent = text;
    el.style.left = (window.innerWidth / 2 - 100) + 'px';
    el.style.top = (window.innerHeight / 2) + 'px';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2000);
}

function createStarBurst(x, y) {
    const colors = ['#fbbf24', '#7dd3fc', '#fb7185', '#34d399'];
    for (let i = 0; i < 8; i++) {
        const star = document.createElement('div');
        star.className = 'star-burst';
        star.style.left = x + 'px';
        star.style.top = y + 'px';
        star.style.background = colors[i % colors.length];
        star.style.transform = `rotate(${i * 45}deg)`;
        document.body.appendChild(star);
        setTimeout(() => star.remove(), 1500);
    }
}

// ============================================================================
// Roulette Spinner
// ============================================================================

class RouletteSpinner {
    constructor(container, candidates) {
        this.container = container;
        this.candidates = candidates;
        this.currentIndex = 0;
        this.spinning = false;
        this.spinInterval = null;
    }

    render() {
        this.container.innerHTML = `
            <div class="roulette-display">
                <div class="roulette-frame glow-effect">
                    <div class="roulette-name" id="roulette-name"></div>
                </div>
            </div>
            <div class="candidate-list" id="candidate-list">
                ${this.candidates.map(c => `
                    <div class="candidate-chip" data-id="${c.id}">${escapeHtml(c.name)}</div>
                `).join('')}
            </div>
        `;

        this.nameElement = document.getElementById('roulette-name');
        this.updateDisplay();
    }

    updateDisplay() {
        if (this.candidates.length > 0) {
            this.nameElement.textContent = this.candidates[this.currentIndex].name;
        }
    }

    async spin(duration = 5000) {
        return new Promise((resolve) => {
            this.spinning = true;
            let speed = 50; // Start fast
            const slowdownStart = duration * 0.6;
            const startTime = Date.now();

            const tick = () => {
                const elapsed = Date.now() - startTime;
                this.currentIndex = (this.currentIndex + 1) % this.candidates.length;
                this.updateDisplay();

                // Add visual effect
                this.nameElement.classList.add('shake');
                setTimeout(() => this.nameElement.classList.remove('shake'), 50);

                if (elapsed < slowdownStart) {
                    // Fast phase
                    speed = 50;
                } else {
                    // Slowdown phase
                    const progress = (elapsed - slowdownStart) / (duration - slowdownStart);
                    speed = 50 + progress * 450; // Slow from 50ms to 500ms
                }

                if (elapsed < duration) {
                    setTimeout(tick, speed);
                } else {
                    this.spinning = false;
                    resolve(this.candidates[this.currentIndex]);
                }
            };

            tick();
        });
    }

    setWinner(winnerId) {
        // Find and highlight the winner
        const winnerIndex = this.candidates.findIndex(c => c.id === winnerId);
        if (winnerIndex !== -1) {
            this.currentIndex = winnerIndex;
            this.updateDisplay();
        }

        // Highlight in candidate list
        const chips = this.container.querySelectorAll('.candidate-chip');
        chips.forEach(chip => {
            if (chip.dataset.id === winnerId) {
                chip.classList.add('winner');
            }
        });

        // Add celebration effects
        this.nameElement.classList.add('bounce');
    }
}

// ============================================================================
// Tab Navigation
// ============================================================================

function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    const panels = document.querySelectorAll('.tab-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tab;

            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Show target panel
            panels.forEach(panel => {
                panel.classList.toggle('hidden', panel.id !== targetId);
            });

            // Save to URL hash
            window.location.hash = targetId;
        });
    });

    // Load from hash on page load
    const hash = window.location.hash.slice(1);
    if (hash) {
        const targetTab = document.querySelector(`.tab[data-tab="${hash}"]`);
        if (targetTab) {
            targetTab.click();
        }
    }
}

// ============================================================================
// Search Functionality
// ============================================================================

function initSearch(inputId, containerSelector, itemSelector, searchField) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener('input', () => {
        const query = input.value.toLowerCase().trim();
        const container = document.querySelector(containerSelector);
        const items = container.querySelectorAll(itemSelector);

        items.forEach(item => {
            const text = item.dataset[searchField] || item.textContent;
            const matches = text.toLowerCase().includes(query);
            item.style.display = matches ? '' : 'none';
        });
    });
}

// ============================================================================
// Modal Functions
// ============================================================================

function showModal(content) {
    let overlay = document.getElementById('modal-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'modal-overlay';
        overlay.className = 'modal-overlay';
        overlay.innerHTML = '<div class="modal"></div>';
        document.body.appendChild(overlay);

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                hideModal();
            }
        });
    }

    overlay.querySelector('.modal').innerHTML = content;
    overlay.classList.add('active');
}

function hideModal() {
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

// ============================================================================
// Sound Effects (Optional - requires audio files)
// ============================================================================

const sounds = {
    spin: null,
    winner: null,
    jackpot: null
};

function playSound(name) {
    if (sounds[name]) {
        sounds[name].currentTime = 0;
        sounds[name].play().catch(() => {});
    }
}

// ============================================================================
// Utility Functions
// ============================================================================

function formatNumber(num) {
    return num.toLocaleString();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================================================
// Initialize on Page Load
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
});

// Export functions for use in pages
window.RaffleApp = {
    apiCall,
    loadState,
    saveSelections,
    executeSpin,
    undoLastSpin,
    resetAllData,
    updateSettings,
    adminLogin,
    adminLogout,
    showLoading,
    hideLoading,
    showAlert,
    showModal,
    hideModal,
    celebrateWinner,
    createStarBurst,
    RouletteSpinner,
    escapeHtml,
    formatNumber,
    debounce,
    initSearch,
    confetti: () => confetti
};
