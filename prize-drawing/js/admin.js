/**
 * Offline Prize Drawing System
 * Admin Panel JavaScript
 */

class AdminPanel {
    constructor() {
        this.data = null;
        this.editingPrizeId = null;
        this.editingMilestoneId = null;
        this.init();
    }

    async init() {
        await this.loadData();
        this.bindEvents();
        this.renderPrizeTable();
        this.renderMilestoneTable();
        this.renderProbabilityBar();
        this.updateStats();
    }

    // Data Management
    async loadData() {
        const savedData = localStorage.getItem('prizeDrawingData');
        if (savedData) {
            this.data = JSON.parse(savedData);
            return;
        }

        try {
            const response = await fetch('data/prizes.json');
            this.data = await response.json();
            this.saveData();
        } catch (error) {
            console.error('Error loading data:', error);
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
            prizes: [],
            milestones: []
        };
    }

    // Event Binding
    bindEvents() {
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.switchTab(e.target.dataset.tab));
        });

        // Prize form
        const prizeForm = document.getElementById('prize-form');
        if (prizeForm) {
            prizeForm.addEventListener('submit', (e) => this.handlePrizeSubmit(e));
        }

        // Milestone form
        const milestoneForm = document.getElementById('milestone-form');
        if (milestoneForm) {
            milestoneForm.addEventListener('submit', (e) => this.handleMilestoneSubmit(e));
        }

        // Cancel buttons
        document.getElementById('cancel-prize')?.addEventListener('click', () => this.resetPrizeForm());
        document.getElementById('cancel-milestone')?.addEventListener('click', () => this.resetMilestoneForm());

        // Reset buttons
        document.getElementById('reset-data')?.addEventListener('click', () => this.showResetConfirm());
        document.getElementById('reset-spins')?.addEventListener('click', () => this.resetSpinsCount());
        document.getElementById('restore-stock')?.addEventListener('click', () => this.restoreAllStock());
        document.getElementById('reset-milestones')?.addEventListener('click', () => this.resetMilestones());

        // Confirm modal buttons
        document.getElementById('confirm-reset')?.addEventListener('click', () => this.confirmReset());
        document.getElementById('cancel-reset')?.addEventListener('click', () => this.hideResetConfirm());

        // Export/Import
        document.getElementById('export-data')?.addEventListener('click', () => this.exportData());
        document.getElementById('import-data')?.addEventListener('click', () => this.triggerImport());
        document.getElementById('import-file')?.addEventListener('change', (e) => this.importData(e));

        // Milestone toggle
        document.getElementById('milestone-enabled')?.addEventListener('change', (e) => {
            this.data.settings.milestoneEnabled = e.target.checked;
            this.saveData();
            this.showAlert('Milestone setting updated', 'success');
        });
    }

    // Tab Management
    switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === `${tabName}-tab`);
        });
    }

    // Prize Management
    renderPrizeTable() {
        const tbody = document.getElementById('prize-tbody');
        if (!tbody) return;

        if (this.data.prizes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No prizes added yet</td></tr>';
            return;
        }

        tbody.innerHTML = this.data.prizes.map(prize => `
            <tr>
                <td>
                    <span class="color-preview" style="background-color: ${prize.color}"></span>
                </td>
                <td>${prize.icon}</td>
                <td>${prize.name}</td>
                <td>${prize.probability}%</td>
                <td>${prize.stock}</td>
                <td>${prize.isSpecial ? 'Yes' : 'No'}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="adminPanel.editPrize(${prize.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="adminPanel.deletePrize(${prize.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    handlePrizeSubmit(e) {
        e.preventDefault();

        const formData = {
            name: document.getElementById('prize-name').value.trim(),
            probability: parseFloat(document.getElementById('prize-probability').value),
            stock: parseInt(document.getElementById('prize-stock').value),
            color: document.getElementById('prize-color').value,
            icon: document.getElementById('prize-icon').value || '🎁',
            isSpecial: document.getElementById('prize-special').checked
        };

        // Validate
        if (!formData.name) {
            this.showAlert('Please enter a prize name', 'danger');
            return;
        }

        if (formData.probability < 0 || formData.probability > 100) {
            this.showAlert('Probability must be between 0 and 100', 'danger');
            return;
        }

        if (formData.stock < 0) {
            this.showAlert('Stock cannot be negative', 'danger');
            return;
        }

        if (this.editingPrizeId) {
            // Update existing prize
            const index = this.data.prizes.findIndex(p => p.id === this.editingPrizeId);
            if (index !== -1) {
                this.data.prizes[index] = { ...this.data.prizes[index], ...formData };
            }
            this.showAlert('Prize updated successfully', 'success');
        } else {
            // Add new prize
            const newId = Math.max(0, ...this.data.prizes.map(p => p.id)) + 1;
            this.data.prizes.push({ id: newId, ...formData });
            this.showAlert('Prize added successfully', 'success');
        }

        this.saveData();
        this.renderPrizeTable();
        this.renderProbabilityBar();
        this.resetPrizeForm();
    }

    editPrize(id) {
        const prize = this.data.prizes.find(p => p.id === id);
        if (!prize) return;

        this.editingPrizeId = id;

        document.getElementById('prize-name').value = prize.name;
        document.getElementById('prize-probability').value = prize.probability;
        document.getElementById('prize-stock').value = prize.stock;
        document.getElementById('prize-color').value = prize.color;
        document.getElementById('prize-icon').value = prize.icon;
        document.getElementById('prize-special').checked = prize.isSpecial;

        document.getElementById('prize-form-title').textContent = 'Edit Prize';
        document.getElementById('prize-submit-btn').textContent = 'Update Prize';
        document.getElementById('cancel-prize').style.display = 'inline-block';

        // Scroll to form
        document.getElementById('prize-form').scrollIntoView({ behavior: 'smooth' });
    }

    deletePrize(id) {
        if (!confirm('Are you sure you want to delete this prize?')) return;

        this.data.prizes = this.data.prizes.filter(p => p.id !== id);
        this.saveData();
        this.renderPrizeTable();
        this.renderProbabilityBar();
        this.showAlert('Prize deleted successfully', 'success');
    }

    resetPrizeForm() {
        this.editingPrizeId = null;
        document.getElementById('prize-form').reset();
        document.getElementById('prize-form-title').textContent = 'Add New Prize';
        document.getElementById('prize-submit-btn').textContent = 'Add Prize';
        document.getElementById('cancel-prize').style.display = 'none';
    }

    // Milestone Management
    renderMilestoneTable() {
        const tbody = document.getElementById('milestone-tbody');
        if (!tbody) return;

        // Update milestone enabled checkbox
        const checkbox = document.getElementById('milestone-enabled');
        if (checkbox) {
            checkbox.checked = this.data.settings.milestoneEnabled;
        }

        if (!this.data.milestones || this.data.milestones.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No milestones added yet</td></tr>';
            return;
        }

        tbody.innerHTML = this.data.milestones.map(milestone => `
            <tr class="${milestone.awarded ? 'awarded' : ''}">
                <td>${milestone.icon}</td>
                <td>${milestone.name}</td>
                <td>${milestone.spinNumber}</td>
                <td>${milestone.stock}</td>
                <td>${milestone.awarded ? '✅ Yes' : '❌ No'}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="adminPanel.editMilestone(${milestone.id})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="adminPanel.deleteMilestone(${milestone.id})">Delete</button>
                </td>
            </tr>
        `).join('');
    }

    handleMilestoneSubmit(e) {
        e.preventDefault();

        const formData = {
            name: document.getElementById('milestone-name').value.trim(),
            spinNumber: parseInt(document.getElementById('milestone-spin').value),
            stock: parseInt(document.getElementById('milestone-stock').value),
            color: document.getElementById('milestone-color').value,
            icon: document.getElementById('milestone-icon').value || '🎁',
            awarded: false
        };

        // Validate
        if (!formData.name) {
            this.showAlert('Please enter a milestone name', 'danger');
            return;
        }

        if (formData.spinNumber < 1) {
            this.showAlert('Spin number must be at least 1', 'danger');
            return;
        }

        // Check for duplicate spin numbers
        const existingMilestone = this.data.milestones.find(
            m => m.spinNumber === formData.spinNumber && m.id !== this.editingMilestoneId
        );
        if (existingMilestone) {
            this.showAlert(`Spin #${formData.spinNumber} already has a milestone`, 'danger');
            return;
        }

        if (this.editingMilestoneId) {
            // Update existing milestone
            const index = this.data.milestones.findIndex(m => m.id === this.editingMilestoneId);
            if (index !== -1) {
                const currentAwarded = this.data.milestones[index].awarded;
                this.data.milestones[index] = { ...this.data.milestones[index], ...formData, awarded: currentAwarded };
            }
            this.showAlert('Milestone updated successfully', 'success');
        } else {
            // Add new milestone
            const newId = Math.max(100, ...this.data.milestones.map(m => m.id)) + 1;
            this.data.milestones.push({ id: newId, ...formData });
            this.showAlert('Milestone added successfully', 'success');
        }

        // Sort milestones by spin number
        this.data.milestones.sort((a, b) => a.spinNumber - b.spinNumber);

        this.saveData();
        this.renderMilestoneTable();
        this.resetMilestoneForm();
    }

    editMilestone(id) {
        const milestone = this.data.milestones.find(m => m.id === id);
        if (!milestone) return;

        this.editingMilestoneId = id;

        document.getElementById('milestone-name').value = milestone.name;
        document.getElementById('milestone-spin').value = milestone.spinNumber;
        document.getElementById('milestone-stock').value = milestone.stock;
        document.getElementById('milestone-color').value = milestone.color;
        document.getElementById('milestone-icon').value = milestone.icon;

        document.getElementById('milestone-form-title').textContent = 'Edit Milestone';
        document.getElementById('milestone-submit-btn').textContent = 'Update Milestone';
        document.getElementById('cancel-milestone').style.display = 'inline-block';

        document.getElementById('milestone-form').scrollIntoView({ behavior: 'smooth' });
    }

    deleteMilestone(id) {
        if (!confirm('Are you sure you want to delete this milestone?')) return;

        this.data.milestones = this.data.milestones.filter(m => m.id !== id);
        this.saveData();
        this.renderMilestoneTable();
        this.showAlert('Milestone deleted successfully', 'success');
    }

    resetMilestoneForm() {
        this.editingMilestoneId = null;
        document.getElementById('milestone-form').reset();
        document.getElementById('milestone-form-title').textContent = 'Add New Milestone';
        document.getElementById('milestone-submit-btn').textContent = 'Add Milestone';
        document.getElementById('cancel-milestone').style.display = 'none';
    }

    // Probability Visualization
    renderProbabilityBar() {
        const container = document.getElementById('probability-visual');
        const totalEl = document.getElementById('probability-total');
        if (!container) return;

        const total = this.data.prizes.reduce((sum, p) => sum + p.probability, 0);

        container.innerHTML = this.data.prizes.map(prize => {
            const width = (prize.probability / Math.max(total, 100)) * 100;
            return `
                <div class="probability-segment"
                     style="width: ${width}%; background-color: ${prize.color};"
                     title="${prize.name}: ${prize.probability}%">
                    ${prize.probability > 5 ? prize.probability + '%' : ''}
                </div>
            `;
        }).join('');

        if (totalEl) {
            totalEl.textContent = `Total: ${total}%`;
            totalEl.className = 'probability-total ' + (total === 100 ? 'valid' : 'invalid');
        }
    }

    // Statistics
    updateStats() {
        const totalSpinsEl = document.getElementById('admin-total-spins');
        const totalPrizesEl = document.getElementById('admin-total-prizes');
        const totalMilestonesEl = document.getElementById('admin-total-milestones');

        if (totalSpinsEl) totalSpinsEl.textContent = this.data.settings.totalSpins;
        if (totalPrizesEl) totalPrizesEl.textContent = this.data.prizes.length;
        if (totalMilestonesEl) totalMilestonesEl.textContent = this.data.milestones?.length || 0;
    }

    // Reset Functions
    showResetConfirm() {
        document.getElementById('reset-modal').classList.add('active');
    }

    hideResetConfirm() {
        document.getElementById('reset-modal').classList.remove('active');
    }

    async confirmReset() {
        localStorage.removeItem('prizeDrawingData');
        await this.loadData();
        this.renderPrizeTable();
        this.renderMilestoneTable();
        this.renderProbabilityBar();
        this.updateStats();
        this.hideResetConfirm();
        this.showAlert('All data has been reset to defaults', 'success');
    }

    resetSpinsCount() {
        if (!confirm('Reset the spin counter to 0?')) return;
        this.data.settings.totalSpins = 0;
        this.saveData();
        this.updateStats();
        this.showAlert('Spin counter reset to 0', 'success');
    }

    restoreAllStock() {
        if (!confirm('Restore stock for all prizes to their original values? This will reload from the default JSON file.')) return;

        fetch('data/prizes.json')
            .then(response => response.json())
            .then(defaultData => {
                defaultData.prizes.forEach(defaultPrize => {
                    const currentPrize = this.data.prizes.find(p => p.id === defaultPrize.id);
                    if (currentPrize) {
                        currentPrize.stock = defaultPrize.stock;
                    }
                });
                this.saveData();
                this.renderPrizeTable();
                this.showAlert('Stock restored for all prizes', 'success');
            })
            .catch(() => {
                this.showAlert('Could not load default data', 'danger');
            });
    }

    resetMilestones() {
        if (!confirm('Reset all milestones to not awarded?')) return;

        this.data.milestones.forEach(m => {
            m.awarded = false;
        });
        this.saveData();
        this.renderMilestoneTable();
        this.showAlert('All milestones reset', 'success');
    }

    // Export/Import
    exportData() {
        const dataStr = JSON.stringify(this.data, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `prize-drawing-data-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);
        this.showAlert('Data exported successfully', 'success');
    }

    triggerImport() {
        document.getElementById('import-file').click();
    }

    importData(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const importedData = JSON.parse(event.target.result);

                // Validate imported data structure
                if (!importedData.prizes || !importedData.settings) {
                    throw new Error('Invalid data structure');
                }

                this.data = importedData;
                this.saveData();
                this.renderPrizeTable();
                this.renderMilestoneTable();
                this.renderProbabilityBar();
                this.updateStats();
                this.showAlert('Data imported successfully', 'success');
            } catch (error) {
                this.showAlert('Error importing data: Invalid file format', 'danger');
            }
        };
        reader.readAsText(file);
        e.target.value = ''; // Reset file input
    }

    // Alert Display
    showAlert(message, type = 'success') {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <span>${type === 'success' ? '✓' : type === 'danger' ? '✗' : '⚠'}</span>
            <span>${message}</span>
        `;

        alertContainer.appendChild(alert);

        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    }
}

// Initialize admin panel
document.addEventListener('DOMContentLoaded', () => {
    window.adminPanel = new AdminPanel();
});
