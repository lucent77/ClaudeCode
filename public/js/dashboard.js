/**
 * Creodent AoX Dashboard JavaScript
 * Handles all dashboard interactions and API calls
 */

// Global variables
let currentUser = null;
let allCases = [];
let filteredCases = [];

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
    initializeEventListeners();
    loadUserInfo();
    loadStatistics();
    loadCases();
});

/**
 * Check if user is authenticated
 */
function checkAuthentication() {
    const token = localStorage.getItem('token');

    if (!token) {
        window.location.href = '/login';
        return;
    }
}

/**
 * Get authentication headers
 */
function getAuthHeaders() {
    const token = localStorage.getItem('token');
    return {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    };
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // User menu toggle
    const userMenuButton = document.getElementById('userMenuButton');
    const userDropdown = document.getElementById('userDropdown');

    userMenuButton?.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        userDropdown?.classList.add('hidden');
    });

    // Logout button
    document.getElementById('logoutButton')?.addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });

    // Sync button
    document.getElementById('syncButton')?.addEventListener('click', function() {
        syncWithSlack();
    });

    // Search input
    document.getElementById('searchInput')?.addEventListener('input', function(e) {
        filterCases();
    });

    // Status filter
    document.getElementById('statusFilter')?.addEventListener('change', function() {
        filterCases();
    });

    // New case button
    document.getElementById('newCaseButton')?.addEventListener('click', function() {
        // TODO: Open new case modal
        alert('New case functionality coming soon!');
    });
}

/**
 * Load user information
 */
async function loadUserInfo() {
    try {
        const user = JSON.parse(localStorage.getItem('user'));

        if (user) {
            currentUser = user;
            document.getElementById('userName').textContent = user.name;
        }

    } catch (error) {
        console.error('Failed to load user info:', error);
    }
}

/**
 * Load statistics
 */
async function loadStatistics() {
    try {
        const response = await fetch('/api/cases/stats', {
            headers: getAuthHeaders()
        });

        if (!response.ok) {
            throw new Error('Failed to load statistics');
        }

        const data = await response.json();

        if (data.success && data.stats) {
            const stats = data.stats;

            document.getElementById('totalCases').textContent = stats.total_cases || 0;
            document.getElementById('inProgressCases').textContent = stats.by_status?.in_progress || 0;
            document.getElementById('upcomingSurgeries').textContent = stats.upcoming_surgeries || 0;
            document.getElementById('overdueCases').textContent = stats.overdue_cases || 0;
        }

    } catch (error) {
        console.error('Failed to load statistics:', error);
    }
}

/**
 * Load cases
 */
async function loadCases() {
    try {
        showLoadingState();

        const response = await fetch('/api/cases', {
            headers: getAuthHeaders()
        });

        if (!response.ok) {
            throw new Error('Failed to load cases');
        }

        const data = await response.json();

        if (data.success && data.cases) {
            allCases = data.cases;
            filteredCases = data.cases;
            renderCases();
        } else {
            showEmptyState();
        }

    } catch (error) {
        console.error('Failed to load cases:', error);
        showEmptyState();
    }
}

/**
 * Filter cases based on search and status
 */
function filterCases() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';

    filteredCases = allCases.filter(caseItem => {
        const matchesSearch = !searchTerm ||
            caseItem.patient_name.toLowerCase().includes(searchTerm) ||
            (caseItem.notes && caseItem.notes.toLowerCase().includes(searchTerm));

        const matchesStatus = !statusFilter || caseItem.status === statusFilter;

        return matchesSearch && matchesStatus;
    });

    renderCases();
}

/**
 * Render cases table
 */
function renderCases() {
    const tbody = document.getElementById('casesTableBody');

    if (!filteredCases || filteredCases.length === 0) {
        showEmptyState();
        return;
    }

    hideLoadingState();
    hideEmptyState();
    document.getElementById('casesTableContainer').classList.remove('hidden');

    tbody.innerHTML = filteredCases.map(caseItem => `
        <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="viewCase(${caseItem.id})">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-indigo-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(caseItem.patient_name)}</div>
                        <div class="text-sm text-gray-500">${caseItem.slack_case_id || 'No ID'}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${escapeHtml(caseItem.assignee_name_full || caseItem.assignee_name || 'Unassigned')}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${formatDate(caseItem.surgery_date)}</div>
                ${caseItem.surgery_time ? `<div class="text-sm text-gray-500">${escapeHtml(caseItem.surgery_time)}</div>` : ''}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${getStatusBadge(caseItem.status)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${getPriorityBadge(caseItem.priority)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="event.stopPropagation(); viewCase(${caseItem.id})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                    <i class="fas fa-eye"></i> View
                </button>
                <button onclick="event.stopPropagation(); editCase(${caseItem.id})" class="text-green-600 hover:text-green-900 mr-3">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button onclick="event.stopPropagation(); deleteCase(${caseItem.id})" class="text-red-600 hover:text-red-900">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const badges = {
        'open': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"><i class="fas fa-folder-open mr-1"></i> Open</span>',
        'in_progress': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"><i class="fas fa-spinner mr-1"></i> In Progress</span>',
        'completed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Completed</span>',
        'cancelled': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800"><i class="fas fa-times-circle mr-1"></i> Cancelled</span>'
    };

    return badges[status] || badges['open'];
}

/**
 * Get priority badge HTML
 */
function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Low</span>',
        'normal': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Normal</span>',
        'high': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">High</span>',
        'critical': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Critical</span>'
    };

    return badges[priority] || badges['normal'];
}

/**
 * Format date
 */
function formatDate(dateString) {
    if (!dateString) return 'Not set';

    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show loading state
 */
function showLoadingState() {
    document.getElementById('loadingState')?.classList.remove('hidden');
    document.getElementById('emptyState')?.classList.add('hidden');
    document.getElementById('casesTableContainer')?.classList.add('hidden');
}

/**
 * Hide loading state
 */
function hideLoadingState() {
    document.getElementById('loadingState')?.classList.add('hidden');
}

/**
 * Show empty state
 */
function showEmptyState() {
    document.getElementById('emptyState')?.classList.remove('hidden');
    document.getElementById('loadingState')?.classList.add('hidden');
    document.getElementById('casesTableContainer')?.classList.add('hidden');
}

/**
 * Hide empty state
 */
function hideEmptyState() {
    document.getElementById('emptyState')?.classList.add('hidden');
}

/**
 * View case details
 */
function viewCase(caseId) {
    window.location.href = `/cases/${caseId}`;
}

/**
 * Edit case
 */
function editCase(caseId) {
    // TODO: Open edit modal
    alert(`Edit case ${caseId} - Coming soon!`);
}

/**
 * Delete case
 */
async function deleteCase(caseId) {
    if (!confirm('Are you sure you want to delete this case?')) {
        return;
    }

    try {
        const response = await fetch(`/api/cases/${caseId}`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });

        if (!response.ok) {
            throw new Error('Failed to delete case');
        }

        const data = await response.json();

        if (data.success) {
            alert('Case deleted successfully');
            loadCases();
            loadStatistics();
        } else {
            alert('Failed to delete case: ' + (data.error || 'Unknown error'));
        }

    } catch (error) {
        console.error('Failed to delete case:', error);
        alert('Failed to delete case. Please try again.');
    }
}

/**
 * Sync with Slack
 */
async function syncWithSlack() {
    const syncButton = document.getElementById('syncButton');
    const originalText = syncButton.innerHTML;

    try {
        syncButton.disabled = true;
        syncButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Syncing...';

        const response = await fetch('/api/sync', {
            method: 'POST',
            headers: getAuthHeaders()
        });

        const data = await response.json();

        if (data.success) {
            alert(`Sync completed!\n\nCases synced: ${data.cases_synced}\nAttachments synced: ${data.attachments_synced}`);
            loadCases();
            loadStatistics();
        } else {
            alert('Sync failed: ' + (data.error || 'Unknown error'));
        }

    } catch (error) {
        console.error('Sync failed:', error);
        alert('Sync failed. Please try again.');

    } finally {
        syncButton.disabled = false;
        syncButton.innerHTML = originalText;
    }
}

/**
 * Logout
 */
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/login';
}
