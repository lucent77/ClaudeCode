/**
 * Admin Panel JavaScript
 * Handles prize management and special prize settings
 */

let allPrizes = [];

/**
 * Load all prizes
 */
async function loadPrizes() {
    try {
        const response = await fetch('../api/get_all_prizes.php');
        const data = await response.json();

        if (data.success) {
            allPrizes = data.prizes;
            renderPrizeTable(data.prizes);
            updateProbabilitySummary(data.total_probability, data.is_valid);
            populateSpecialPrizeSelect(data.prizes);
        } else {
            showAlert('error', 'Failed to load prizes: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error loading prizes:', error);
        showAlert('error', 'Failed to load prizes. Please check your connection.');
    }
}

/**
 * Render prize table
 */
function renderPrizeTable(prizes) {
    const container = document.getElementById('prizeListContainer');

    if (prizes.length === 0) {
        container.innerHTML = '<p style="text-align: center; padding: 40px; color: #999;">No prizes found. Add your first prize!</p>';
        return;
    }

    let html = `
        <table class="prize-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Probability</th>
                    <th>Color</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    prizes.forEach(prize => {
        const stockDisplay = prize.stock == -1 ? 'Unlimited' : prize.stock;
        const statusClass = prize.is_active == 1 ? 'status-active' : 'status-inactive';
        const statusText = prize.is_active == 1 ? 'Active' : 'Inactive';

        html += `
            <tr>
                <td>${prize.id}</td>
                <td>${escapeHtml(prize.name)}</td>
                <td>${prize.probability}%</td>
                <td><span class="color-box" style="background-color: ${prize.color}"></span> ${prize.color}</td>
                <td>${stockDisplay}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <button class="btn btn-primary btn-small" onclick="openEditPrizeModal(${prize.id})">Edit</button>
                    <button class="btn btn-danger btn-small" onclick="deletePrize(${prize.id}, '${escapeHtml(prize.name)}')">Delete</button>
                </td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    container.innerHTML = html;
}

/**
 * Update probability summary
 */
function updateProbabilitySummary(total, isValid) {
    document.getElementById('totalProbability').textContent = total.toFixed(2);

    const statusElement = document.getElementById('probabilityStatus');
    if (isValid) {
        statusElement.className = 'status probability-valid';
        statusElement.textContent = '✓ Valid (Total = 100%)';
    } else {
        statusElement.className = 'status probability-invalid';
        statusElement.textContent = '✗ Invalid (Total must equal 100%)';
    }
}

/**
 * Populate special prize select dropdown
 */
function populateSpecialPrizeSelect(prizes) {
    const select = document.getElementById('specialPrizeId');
    select.innerHTML = '<option value="">Select a prize...</option>';

    prizes.forEach(prize => {
        const option = document.createElement('option');
        option.value = prize.id;
        option.textContent = `${prize.name} (${prize.probability}%)`;
        select.appendChild(option);
    });
}

/**
 * Open add prize modal
 */
function openAddPrizeModal() {
    document.getElementById('modalTitle').textContent = 'Add New Prize';
    document.getElementById('prizeForm').reset();
    document.getElementById('prizeId').value = '';
    document.getElementById('prizeColor').value = '#' + Math.floor(Math.random()*16777215).toString(16);
    document.getElementById('prizeModal').classList.add('active');
}

/**
 * Open edit prize modal
 */
function openEditPrizeModal(prizeId) {
    const prize = allPrizes.find(p => p.id === prizeId);
    if (!prize) {
        showAlert('error', 'Prize not found');
        return;
    }

    document.getElementById('modalTitle').textContent = 'Edit Prize';
    document.getElementById('prizeId').value = prize.id;
    document.getElementById('prizeName').value = prize.name;
    document.getElementById('prizeProbability').value = prize.probability;
    document.getElementById('prizeColor').value = prize.color;
    document.getElementById('prizeStock').value = prize.stock;
    document.getElementById('prizeActive').value = prize.is_active;
    document.getElementById('prizeModal').classList.add('active');
}

/**
 * Close prize modal
 */
function closePrizeModal() {
    document.getElementById('prizeModal').classList.remove('active');
}

/**
 * Save prize (add or update)
 */
async function savePrize(event) {
    event.preventDefault();

    const prizeId = document.getElementById('prizeId').value;
    const isEdit = prizeId !== '';

    const prizeData = {
        name: document.getElementById('prizeName').value.trim(),
        probability: parseFloat(document.getElementById('prizeProbability').value),
        color: document.getElementById('prizeColor').value,
        stock: parseInt(document.getElementById('prizeStock').value),
        is_active: parseInt(document.getElementById('prizeActive').value)
    };

    if (isEdit) {
        prizeData.id = parseInt(prizeId);
    }

    try {
        const method = isEdit ? 'PUT' : 'POST';
        const response = await fetch('../api/update_prize.php', {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(prizeData)
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', isEdit ? 'Prize updated successfully!' : 'Prize added successfully!');
            closePrizeModal();
            loadPrizes();
        } else {
            showAlert('error', 'Failed to save prize: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving prize:', error);
        showAlert('error', 'Failed to save prize. Please try again.');
    }
}

/**
 * Delete prize
 */
async function deletePrize(prizeId, prizeName) {
    if (!confirm(`Are you sure you want to delete "${prizeName}"?`)) {
        return;
    }

    try {
        const response = await fetch('../api/update_prize.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: prizeId })
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', 'Prize deleted successfully!');
            loadPrizes();
        } else {
            showAlert('error', 'Failed to delete prize: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting prize:', error);
        showAlert('error', 'Failed to delete prize. Please try again.');
    }
}

/**
 * Load special prize settings
 */
async function loadSpecialPrizeSettings() {
    try {
        const response = await fetch('../api/get_special_prize_settings.php');
        const data = await response.json();

        if (data.success) {
            const settings = data.settings;
            document.getElementById('specialPrizeActive').checked = settings.is_active == 1;
            document.getElementById('specialPrizeId').value = settings.special_prize_id || '';
            document.getElementById('milestoneCount').value = settings.milestone_count;
            document.getElementById('currentCount').value = settings.current_count;
        }
    } catch (error) {
        console.error('Error loading special prize settings:', error);
    }
}

/**
 * Save special prize settings
 */
async function saveSpecialPrizeSettings(event) {
    event.preventDefault();

    const settingsData = {
        is_active: document.getElementById('specialPrizeActive').checked ? 1 : 0,
        special_prize_id: parseInt(document.getElementById('specialPrizeId').value) || null,
        milestone_count: parseInt(document.getElementById('milestoneCount').value),
        current_count: parseInt(document.getElementById('currentCount').value)
    };

    try {
        const response = await fetch('../api/update_special_prize_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(settingsData)
        });

        const data = await response.json();

        if (data.success) {
            showAlert('success', 'Special prize settings saved successfully!');
        } else {
            showAlert('error', 'Failed to save settings: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showAlert('error', 'Failed to save settings. Please try again.');
    }
}

/**
 * Show alert message
 */
function showAlert(type, message) {
    const alertId = type === 'success' ? 'alertSuccess' : (type === 'warning' ? 'alertWarning' : 'alertError');
    const alertElement = document.getElementById(alertId);

    alertElement.textContent = message;
    alertElement.classList.add('show');

    setTimeout(() => {
        alertElement.classList.remove('show');
    }, 5000);
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
