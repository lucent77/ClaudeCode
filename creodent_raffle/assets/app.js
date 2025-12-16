/**
 * CREODENT HV Raffle 2025 - Main JavaScript
 */

// Global state
const App = {
    maxSelections: 5,
    selectedPrizes: [],
    spinInterval: null,
    isSpinning: false
};

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize application
 */
function initializeApp() {
    // Initialize search functionality
    initSearch();

    // Initialize selection system if on choose page
    initSelectionSystem();

    // Initialize spin system if on spin page
    initSpinSystem();

    // Initialize admin functions if on admin page
    initAdminFunctions();

    // Initialize modals
    initModals();
}

/**
 * Search functionality
 */
function initSearch() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const items = document.querySelectorAll('[data-searchable]');

        items.forEach(item => {
            const text = item.dataset.searchable.toLowerCase();
            if (text.includes(query) || query === '') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
}

/**
 * Prize selection system
 */
function initSelectionSystem() {
    const selectionContainer = document.getElementById('prize-selection');
    if (!selectionContainer) return;

    // Load existing selections
    const existingSelections = document.querySelectorAll('.prize-item.selected');
    existingSelections.forEach(item => {
        App.selectedPrizes.push(item.dataset.prizeId);
    });
    updateSelectionCounter();

    // Handle click on prize items
    selectionContainer.addEventListener('click', function(e) {
        const prizeItem = e.target.closest('.prize-item');
        if (!prizeItem || prizeItem.classList.contains('disabled')) return;

        const prizeId = prizeItem.dataset.prizeId;
        toggleSelection(prizeItem, prizeId);
    });
}

/**
 * Toggle prize selection
 */
function toggleSelection(element, prizeId) {
    const index = App.selectedPrizes.indexOf(prizeId);

    if (index > -1) {
        // Remove selection
        App.selectedPrizes.splice(index, 1);
        element.classList.remove('selected');
    } else {
        // Add selection (check limit)
        if (App.selectedPrizes.length >= App.maxSelections) {
            showAlert('You can only select up to ' + App.maxSelections + ' prizes!', 'warning');
            return;
        }
        App.selectedPrizes.push(prizeId);
        element.classList.add('selected');
    }

    updateSelectionCounter();
}

/**
 * Update selection counter display
 */
function updateSelectionCounter() {
    const counter = document.getElementById('selection-counter');
    if (!counter) return;

    counter.textContent = App.selectedPrizes.length + ' / ' + App.maxSelections;

    // Update submit button state
    const submitBtn = document.getElementById('submit-selections');
    if (submitBtn) {
        submitBtn.disabled = App.selectedPrizes.length === 0;
    }
}

/**
 * Save selections
 */
function saveSelections(employeeId) {
    if (App.selectedPrizes.length === 0) {
        showAlert('Please select at least one prize!', 'warning');
        return;
    }

    const form = document.getElementById('selection-form');
    if (form) {
        // Update hidden input with selections
        const input = document.getElementById('selected-prizes');
        if (input) {
            input.value = App.selectedPrizes.join(',');
        }
        form.submit();
    }
}

/**
 * Spin system
 */
function initSpinSystem() {
    const spinContainer = document.getElementById('spin-container');
    if (!spinContainer) return;

    // Initialize spin button
    const spinBtn = document.getElementById('spin-btn');
    if (spinBtn) {
        spinBtn.addEventListener('click', startSpin);
    }
}

/**
 * Start spin animation
 */
function startSpin() {
    if (App.isSpinning) return;

    const candidates = document.querySelectorAll('.candidate');
    if (candidates.length === 0) return;

    App.isSpinning = true;
    const spinBtn = document.getElementById('spin-btn');
    if (spinBtn) {
        spinBtn.disabled = true;
        spinBtn.textContent = 'Spinning...';
    }

    let currentIndex = 0;
    let speed = 50;
    let iterations = 0;
    const maxIterations = candidates.length * 3 + Math.floor(Math.random() * candidates.length);

    // Clear previous highlights
    candidates.forEach(c => c.classList.remove('highlight', 'winner'));

    App.spinInterval = setInterval(() => {
        // Remove previous highlight
        candidates.forEach(c => c.classList.remove('highlight'));

        // Add highlight to current
        candidates[currentIndex].classList.add('highlight');

        // Move to next
        currentIndex = (currentIndex + 1) % candidates.length;
        iterations++;

        // Slow down near the end
        if (iterations > maxIterations * 0.7) {
            speed += 20;
            clearInterval(App.spinInterval);
            App.spinInterval = setInterval(arguments.callee, speed);
        }

        // Stop at the end
        if (iterations >= maxIterations) {
            clearInterval(App.spinInterval);
            App.spinInterval = null;
            finishSpin();
        }
    }, speed);
}

/**
 * Finish spin and submit form
 */
function finishSpin() {
    App.isSpinning = false;

    // Submit the form to get the actual winner from server
    const form = document.getElementById('spin-form');
    if (form) {
        form.submit();
    }
}

/**
 * Show winner with effects
 */
function showWinner(winnerName, prizeName, isJackpot = false) {
    // Create confetti
    createConfetti(100);

    // Show winner modal
    const modal = document.getElementById('winner-modal');
    if (modal) {
        const nameEl = modal.querySelector('.winner-name');
        const prizeEl = modal.querySelector('.prize-name');

        if (nameEl) nameEl.textContent = winnerName;
        if (prizeEl) prizeEl.textContent = prizeName;

        modal.classList.add('active');

        if (isJackpot) {
            modal.classList.add('jackpot');
            createJackpotEffects();
        }
    }
}

/**
 * Create confetti effect
 */
function createConfetti(count = 50) {
    const container = document.createElement('div');
    container.className = 'confetti-container';
    document.body.appendChild(container);

    const colors = ['color-1', 'color-2', 'color-3', 'color-4', 'color-5', 'color-6', 'color-7', 'color-8'];
    const shapes = ['square', 'circle', 'ribbon'];

    for (let i = 0; i < count; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti ' + colors[Math.floor(Math.random() * colors.length)] + ' ' + shapes[Math.floor(Math.random() * shapes.length)];
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
        confetti.style.animationDelay = Math.random() * 2 + 's';
        container.appendChild(confetti);
    }

    // Remove after animation
    setTimeout(() => {
        container.remove();
    }, 6000);
}

/**
 * Create jackpot special effects
 */
function createJackpotEffects() {
    // Add shimmer overlay
    const shimmer = document.createElement('div');
    shimmer.className = 'jackpot-shimmer';
    document.body.appendChild(shimmer);

    // Add spotlight
    const spotlight = document.createElement('div');
    spotlight.className = 'spotlight';
    document.body.appendChild(spotlight);

    // Create more confetti for jackpot
    createConfetti(200);

    // Remove effects after delay
    setTimeout(() => {
        shimmer.remove();
        spotlight.remove();
    }, 5000);
}

/**
 * Show jackpot unlock notification
 */
function showJackpotUnlock(jackpotNumber) {
    const overlay = document.createElement('div');
    overlay.className = 'jackpot-unlock';
    overlay.innerHTML = `
        <div class="jackpot-unlock-content">
            <h2>JACKPOT ${jackpotNumber} UNLOCKED!</h2>
            <p>A special prize is now available for drawing!</p>
            <button class="btn btn-primary btn-large" onclick="this.closest('.jackpot-unlock').remove()">
                Continue
            </button>
        </div>
    `;
    document.body.appendChild(overlay);

    createConfetti(150);
}

/**
 * Admin functions
 */
function initAdminFunctions() {
    // Undo button
    const undoBtn = document.getElementById('undo-btn');
    if (undoBtn) {
        undoBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to undo the last spin?')) {
                document.getElementById('undo-form').submit();
            }
        });
    }

    // Reset button
    const resetBtn = document.getElementById('reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (confirm('WARNING: This will reset ALL data. Are you sure?')) {
                if (confirm('This action cannot be undone. Type "RESET" to confirm.')) {
                    document.getElementById('reset-form').submit();
                }
            }
        });
    }

    // Export button
    const exportBtn = document.getElementById('export-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            window.location.href = 'admin.php?action=export';
        });
    }

    // Jackpot order drag and drop
    initJackpotSorting();
}

/**
 * Initialize jackpot sorting
 */
function initJackpotSorting() {
    const sortable = document.getElementById('jackpot-sortable');
    if (!sortable) return;

    let draggedItem = null;

    sortable.querySelectorAll('.jackpot-item').forEach(item => {
        item.draggable = true;

        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            this.classList.add('dragging');
        });

        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            draggedItem = null;
            updateJackpotOrder();
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(sortable, e.clientY);
            if (afterElement == null) {
                sortable.appendChild(draggedItem);
            } else {
                sortable.insertBefore(draggedItem, afterElement);
            }
        });
    });
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.jackpot-item:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

function updateJackpotOrder() {
    const items = document.querySelectorAll('#jackpot-sortable .jackpot-item');
    const order = Array.from(items).map(item => item.dataset.prizeId);

    const input = document.getElementById('jackpot-order-input');
    if (input) {
        input.value = order.join(',');
    }
}

/**
 * Modal functionality
 */
function initModals() {
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Close modal on close button click
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('active');
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
}

/**
 * Open modal by ID
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

/**
 * Close modal by ID
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();

    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type + ' fade-in-up';
    alert.textContent = message;

    alertContainer.appendChild(alert);

    // Remove after 5 seconds
    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alert-container';
    container.style.cssText = 'position: fixed; top: 2rem; right: 2rem; z-index: 10000; display: flex; flex-direction: column; gap: 0.5rem;';
    document.body.appendChild(container);
    return container;
}

/**
 * PIN input handling
 */
function initPinInput() {
    const inputs = document.querySelectorAll('.pin-input input');
    if (inputs.length === 0) return;

    inputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            if (this.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const digits = paste.replace(/\D/g, '').split('');

            digits.forEach((digit, i) => {
                if (inputs[index + i]) {
                    inputs[index + i].value = digit;
                }
            });

            const nextIndex = Math.min(index + digits.length, inputs.length - 1);
            inputs[nextIndex].focus();
        });
    });
}

/**
 * Animated counter
 */
function animateCounter(element, targetValue, duration = 1000) {
    const start = parseInt(element.textContent) || 0;
    const increment = (targetValue - start) / (duration / 16);
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
            element.textContent = targetValue;
            clearInterval(timer);
        } else {
            element.textContent = Math.round(current);
        }
    }, 16);
}

/**
 * Refresh statistics
 */
function refreshStats() {
    fetch('admin.php?action=stats&ajax=1')
        .then(response => response.json())
        .then(data => {
            // Update stat values
            Object.keys(data).forEach(key => {
                const el = document.querySelector('[data-stat="' + key + '"]');
                if (el) {
                    animateCounter(el, data[key]);
                }
            });
        })
        .catch(err => console.error('Failed to refresh stats:', err));
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Debounce function
 */
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

/**
 * Scroll to element
 */
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
