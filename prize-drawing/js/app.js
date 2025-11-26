/**
 * Offline Prize Drawing System
 * Main Application JavaScript
 */

class PrizeDrawingApp {
    constructor() {
        this.data = null;
        this.isSpinning = false;
        this.currentRotation = 0;
        this.init();
    }

    async init() {
        await this.loadData();
        this.renderRoulette();
        this.renderPrizeList();
        this.renderMilestones();
        this.updateStats();
        this.bindEvents();
    }

    // Data Management
    async loadData() {
        // First, try to load from localStorage
        const savedData = localStorage.getItem('prizeDrawingData');
        if (savedData) {
            this.data = JSON.parse(savedData);
            return;
        }

        // If no localStorage data, load from JSON file
        try {
            const response = await fetch('data/prizes.json');
            this.data = await response.json();
            this.saveData();
        } catch (error) {
            console.error('Error loading prize data:', error);
            // Create default data if file cannot be loaded
            this.data = this.getDefaultData();
            this.saveData();
        }
    }

    saveData() {
        localStorage.setItem('prizeDrawingData', JSON.stringify(this.data));
    }

    getDefaultData() {
        return {
            settings: { totalSpins: 0, milestoneEnabled: true },
            prizes: [
                { id: 1, name: "Grand Prize", probability: 5, stock: 1, color: "#FF6B6B", icon: "🎁", isSpecial: false },
                { id: 2, name: "Second Prize", probability: 15, stock: 5, color: "#4ECDC4", icon: "🎉", isSpecial: false },
                { id: 3, name: "Third Prize", probability: 30, stock: 10, color: "#45B7D1", icon: "⭐", isSpecial: false },
                { id: 4, name: "Try Again", probability: 50, stock: 9999, color: "#95A5A6", icon: "🔄", isSpecial: false }
            ],
            milestones: []
        };
    }

    // Get prizes with stock available
    getAvailablePrizes() {
        return this.data.prizes.filter(prize => prize.stock > 0);
    }

    // Render SVG Roulette Wheel
    renderRoulette() {
        const container = document.getElementById('roulette-wheel');
        if (!container) return;

        const prizes = this.getAvailablePrizes();
        if (prizes.length === 0) {
            container.innerHTML = '<p style="color: white; text-align: center;">No prizes available</p>';
            return;
        }

        const totalProbability = prizes.reduce((sum, p) => sum + p.probability, 0);
        const size = 500;
        const center = size / 2;
        const radius = size / 2 - 10;

        let svg = `<svg viewBox="0 0 ${size} ${size}" class="roulette-wheel" id="wheel-svg">`;

        // Add outer ring
        svg += `<circle cx="${center}" cy="${center}" r="${radius + 5}" fill="none" stroke="#333" stroke-width="10"/>`;

        let currentAngle = -90; // Start from top

        prizes.forEach((prize, index) => {
            const angle = (prize.probability / totalProbability) * 360;
            const startAngle = currentAngle;
            const endAngle = currentAngle + angle;

            // Calculate arc path
            const startRad = (startAngle * Math.PI) / 180;
            const endRad = (endAngle * Math.PI) / 180;

            const x1 = center + radius * Math.cos(startRad);
            const y1 = center + radius * Math.sin(startRad);
            const x2 = center + radius * Math.cos(endRad);
            const y2 = center + radius * Math.sin(endRad);

            const largeArcFlag = angle > 180 ? 1 : 0;

            // Draw segment
            svg += `<path d="M ${center} ${center} L ${x1} ${y1} A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2} Z"
                    fill="${prize.color}"
                    stroke="#fff"
                    stroke-width="2"
                    class="wheel-segment"
                    data-prize-id="${prize.id}"/>`;

            // Add text label
            const midAngle = startAngle + angle / 2;
            const midRad = (midAngle * Math.PI) / 180;
            const textRadius = radius * 0.65;
            const textX = center + textRadius * Math.cos(midRad);
            const textY = center + textRadius * Math.sin(midRad);

            // Add icon
            const iconRadius = radius * 0.45;
            const iconX = center + iconRadius * Math.cos(midRad);
            const iconY = center + iconRadius * Math.sin(midRad);

            svg += `<text x="${iconX}" y="${iconY}"
                    text-anchor="middle"
                    dominant-baseline="middle"
                    font-size="30"
                    transform="rotate(${midAngle + 90}, ${iconX}, ${iconY})">${prize.icon}</text>`;

            // Add prize name (only if segment is large enough)
            if (angle > 25) {
                const nameRadius = radius * 0.8;
                const nameX = center + nameRadius * Math.cos(midRad);
                const nameY = center + nameRadius * Math.sin(midRad);

                svg += `<text x="${nameX}" y="${nameY}"
                        text-anchor="middle"
                        dominant-baseline="middle"
                        font-size="12"
                        fill="#fff"
                        font-weight="bold"
                        transform="rotate(${midAngle + 90}, ${nameX}, ${nameY})">${this.truncateText(prize.name, 12)}</text>`;
            }

            currentAngle = endAngle;
        });

        // Add decorative circles
        svg += `<circle cx="${center}" cy="${center}" r="60" fill="url(#centerGradient)"/>`;
        svg += `<circle cx="${center}" cy="${center}" r="50" fill="#1a1a2e" stroke="#667eea" stroke-width="3"/>`;

        // Add gradient definition
        svg = svg.replace('<svg', `<svg><defs>
            <linearGradient id="centerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#667eea"/>
                <stop offset="100%" style="stop-color:#764ba2"/>
            </linearGradient>
        </defs`) + '</svg>';

        container.innerHTML = svg;
    }

    truncateText(text, maxLength) {
        return text.length > maxLength ? text.substring(0, maxLength - 2) + '..' : text;
    }

    // Render Prize List
    renderPrizeList() {
        const container = document.getElementById('prize-list');
        if (!container) return;

        container.innerHTML = this.data.prizes.map(prize => `
            <div class="prize-item" style="--prize-color: ${prize.color}">
                <span class="prize-icon">${prize.icon}</span>
                <div class="prize-info">
                    <div class="prize-name">${prize.name}</div>
                    <div class="prize-stock ${prize.stock <= 0 ? 'out-of-stock' : ''}">
                        ${prize.stock <= 0 ? 'Out of Stock' : `Stock: ${prize.stock}`}
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Render Milestones
    renderMilestones() {
        const container = document.getElementById('milestone-list');
        if (!container || !this.data.milestones || this.data.milestones.length === 0) {
            const section = document.querySelector('.milestone-section');
            if (section) section.style.display = 'none';
            return;
        }

        const nextMilestone = this.getNextMilestone();

        container.innerHTML = this.data.milestones.map(milestone => {
            const isNext = nextMilestone && milestone.id === nextMilestone.id;
            const spinsRemaining = milestone.spinNumber - this.data.settings.totalSpins;

            return `
                <div class="milestone-item ${milestone.awarded ? 'awarded' : ''} ${isNext ? 'next' : ''}">
                    <div class="milestone-info">
                        <span class="milestone-icon">${milestone.icon}</span>
                        <span class="milestone-name">${milestone.name}</span>
                    </div>
                    <span class="milestone-spin">
                        ${milestone.awarded ? 'AWARDED' : `Spin #${milestone.spinNumber} ${isNext ? `(${spinsRemaining} left)` : ''}`}
                    </span>
                </div>
            `;
        }).join('');
    }

    getNextMilestone() {
        if (!this.data.settings.milestoneEnabled) return null;
        return this.data.milestones
            .filter(m => !m.awarded && m.stock > 0)
            .sort((a, b) => a.spinNumber - b.spinNumber)[0];
    }

    // Update Statistics Display
    updateStats() {
        const totalSpinsEl = document.getElementById('total-spins');
        const nextMilestoneEl = document.getElementById('next-milestone');

        if (totalSpinsEl) {
            totalSpinsEl.textContent = this.data.settings.totalSpins;
        }

        if (nextMilestoneEl) {
            const next = this.getNextMilestone();
            if (next) {
                const remaining = next.spinNumber - this.data.settings.totalSpins;
                nextMilestoneEl.textContent = remaining > 0 ? remaining : 'NOW!';
            } else {
                nextMilestoneEl.textContent = '-';
            }
        }
    }

    // Bind Event Handlers
    bindEvents() {
        const spinButton = document.getElementById('spin-button');
        if (spinButton) {
            spinButton.addEventListener('click', () => this.spin());
        }

        const closeModal = document.getElementById('close-modal');
        if (closeModal) {
            closeModal.addEventListener('click', () => this.closeResultModal());
        }

        // Close modal on overlay click
        const overlay = document.getElementById('result-modal');
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.closeResultModal();
            });
        }

        // Keyboard shortcut for spin (Space or Enter)
        document.addEventListener('keydown', (e) => {
            if ((e.code === 'Space' || e.code === 'Enter') && !this.isSpinning) {
                const modal = document.getElementById('result-modal');
                if (modal && modal.classList.contains('active')) {
                    this.closeResultModal();
                } else {
                    this.spin();
                }
                e.preventDefault();
            }
        });
    }

    // Perform Spin
    async spin() {
        if (this.isSpinning) return;

        const availablePrizes = this.getAvailablePrizes();
        if (availablePrizes.length === 0) {
            alert('No prizes available!');
            return;
        }

        this.isSpinning = true;
        const spinButton = document.getElementById('spin-button');
        if (spinButton) {
            spinButton.disabled = true;
            spinButton.classList.add('spinning');
            spinButton.textContent = 'SPINNING...';
        }

        // Increment spin count
        this.data.settings.totalSpins++;

        // Check for milestone
        const currentMilestone = this.checkMilestone();
        let selectedPrize;

        if (currentMilestone) {
            selectedPrize = {
                ...currentMilestone,
                isMilestone: true
            };
            currentMilestone.awarded = true;
            currentMilestone.stock--;
        } else {
            selectedPrize = this.selectPrize(availablePrizes);
            // Decrease stock
            const prizeInData = this.data.prizes.find(p => p.id === selectedPrize.id);
            if (prizeInData && prizeInData.stock > 0) {
                prizeInData.stock--;
            }
        }

        // Save data
        this.saveData();

        // Calculate rotation angle
        const rotationAngle = this.calculateRotationAngle(selectedPrize, availablePrizes);

        // Animate wheel
        await this.animateWheel(rotationAngle);

        // Show result
        this.showResult(selectedPrize);

        // Update UI
        this.renderRoulette();
        this.renderPrizeList();
        this.renderMilestones();
        this.updateStats();

        this.isSpinning = false;
        if (spinButton) {
            spinButton.disabled = false;
            spinButton.classList.remove('spinning');
            spinButton.textContent = 'SPIN';
        }
    }

    checkMilestone() {
        if (!this.data.settings.milestoneEnabled) return null;

        return this.data.milestones.find(
            m => m.spinNumber === this.data.settings.totalSpins && !m.awarded && m.stock > 0
        );
    }

    selectPrize(availablePrizes) {
        const totalProbability = availablePrizes.reduce((sum, p) => sum + p.probability, 0);
        let random = Math.random() * totalProbability;

        for (const prize of availablePrizes) {
            random -= prize.probability;
            if (random <= 0) {
                return prize;
            }
        }

        return availablePrizes[availablePrizes.length - 1];
    }

    calculateRotationAngle(selectedPrize, availablePrizes) {
        const totalProbability = availablePrizes.reduce((sum, p) => sum + p.probability, 0);

        let prizeIndex = -1;
        let angleOffset = 0;

        // For milestone prizes, select a random visible segment
        if (selectedPrize.isMilestone) {
            prizeIndex = Math.floor(Math.random() * availablePrizes.length);
            for (let i = 0; i < prizeIndex; i++) {
                angleOffset += (availablePrizes[i].probability / totalProbability) * 360;
            }
            const prizeAngle = (availablePrizes[prizeIndex].probability / totalProbability) * 360;
            angleOffset += prizeAngle / 2;
        } else {
            // Find the prize segment
            for (let i = 0; i < availablePrizes.length; i++) {
                if (availablePrizes[i].id === selectedPrize.id) {
                    prizeIndex = i;
                    break;
                }
                angleOffset += (availablePrizes[i].probability / totalProbability) * 360;
            }

            const prizeAngle = (availablePrizes[prizeIndex].probability / totalProbability) * 360;
            // Random position within the segment
            angleOffset += Math.random() * prizeAngle;
        }

        // Calculate final rotation (multiple full rotations + position)
        const fullRotations = 5 + Math.floor(Math.random() * 3); // 5-7 full rotations
        const finalAngle = fullRotations * 360 + (360 - angleOffset);

        return this.currentRotation + finalAngle;
    }

    animateWheel(targetRotation) {
        return new Promise(resolve => {
            const wheel = document.getElementById('wheel-svg');
            if (wheel) {
                wheel.style.transition = 'transform 5s cubic-bezier(0.17, 0.67, 0.12, 0.99)';
                wheel.style.transform = `rotate(${targetRotation}deg)`;
                this.currentRotation = targetRotation;
            }

            setTimeout(resolve, 5000);
        });
    }

    showResult(prize) {
        const modal = document.getElementById('result-modal');
        const icon = document.getElementById('result-icon');
        const title = document.getElementById('result-title');
        const prizeName = document.getElementById('result-prize');

        if (modal && icon && title && prizeName) {
            icon.textContent = prize.icon;

            if (prize.isMilestone) {
                title.textContent = '🎊 MILESTONE PRIZE! 🎊';
                prizeName.textContent = prize.name;
                prizeName.classList.add('milestone');
            } else {
                title.textContent = prize.name === 'Try Again' ? 'Better Luck Next Time!' : 'Congratulations!';
                prizeName.textContent = prize.name;
                prizeName.classList.remove('milestone');
            }

            modal.classList.add('active');

            // Show confetti for wins (not for "Try Again")
            if (prize.name !== 'Try Again') {
                this.showConfetti();
            }
        }
    }

    closeResultModal() {
        const modal = document.getElementById('result-modal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    showConfetti() {
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#FFD700'];

        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDelay = Math.random() * 2 + 's';
            confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
            document.body.appendChild(confetti);

            // Remove confetti after animation
            setTimeout(() => confetti.remove(), 5000);
        }
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.prizeApp = new PrizeDrawingApp();
});
