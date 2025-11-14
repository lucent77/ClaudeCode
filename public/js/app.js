// API Configuration
const API_BASE_URL = '/api';
let authToken = localStorage.getItem('auth_token');
let currentUser = null;

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    if (authToken) {
        validateToken();
    } else {
        showLogin();
    }
});

// Authentication
function showLogin() {
    document.getElementById('loginPage').classList.remove('hidden');
    document.getElementById('dashboard').classList.add('hidden');
}

function showDashboard() {
    document.getElementById('loginPage').classList.add('hidden');
    document.getElementById('dashboard').classList.remove('hidden');
    loadStatistics();
    loadCases();
}

document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;

    try {
        const response = await fetch(`${API_BASE_URL}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            authToken = data.access_token;
            currentUser = data.user;
            localStorage.setItem('auth_token', authToken);
            document.getElementById('userName').textContent = currentUser.name;
            showDashboard();
        } else {
            document.getElementById('loginError').textContent = data.error || 'Login failed';
            document.getElementById('loginError').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Login error:', error);
        document.getElementById('loginError').textContent = 'Network error';
        document.getElementById('loginError').classList.remove('hidden');
    }
});

async function validateToken() {
    try {
        const response = await apiRequest('/auth/me');
        if (response.ok) {
            currentUser = await response.json();
            document.getElementById('userName').textContent = currentUser.name;
            showDashboard();
        } else {
            logout();
        }
    } catch (error) {
        logout();
    }
}

function logout() {
    localStorage.removeItem('auth_token');
    authToken = null;
    currentUser = null;
    showLogin();
}

// API Helper
async function apiRequest(endpoint, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers
    };

    if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }

    return fetch(`${API_BASE_URL}${endpoint}`, {
        ...options,
        headers
    });
}

// Load Statistics
async function loadStatistics() {
    try {
        const response = await apiRequest('/cases/statistics');
        const stats = await response.json();

        document.getElementById('statTotal').textContent = stats.total || 0;
        document.getElementById('statOpen').textContent = stats.open || 0;
        document.getElementById('statInProgress').textContent = stats.in_progress || 0;
        document.getElementById('statCompleted').textContent = stats.completed || 0;
        document.getElementById('statOverdue').textContent = stats.overdue || 0;
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Load Cases
async function loadCases(filters = {}) {
    const tbody = document.getElementById('casesTableBody');
    const spinner = document.getElementById('loadingSpinner');
    const noCases = document.getElementById('noCases');

    tbody.innerHTML = '';
    spinner.classList.remove('hidden');
    noCases.classList.add('hidden');

    try {
        const params = new URLSearchParams(filters);
        const response = await apiRequest(`/cases?${params}`);
        const data = await response.json();

        spinner.classList.add('hidden');

        if (data.data && data.data.length > 0) {
            data.data.forEach(caseItem => {
                tbody.appendChild(createCaseRow(caseItem));
            });
        } else {
            noCases.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error loading cases:', error);
        spinner.classList.add('hidden');
    }
}

// Create Case Row
function createCaseRow(caseItem) {
    const tr = document.createElement('tr');
    tr.className = 'hover:bg-gray-50 cursor-pointer';
    tr.onclick = () => showCaseDetail(caseItem.id);

    const statusColors = {
        'open': 'bg-blue-100 text-blue-800',
        'in_progress': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    };

    const photos = caseItem.attachments?.filter(a => a.type === 'photo') || [];
    const stlFiles = caseItem.attachments?.filter(a => a.type === 'stl') || [];
    const cbctFiles = caseItem.attachments?.filter(a => a.type === 'cbct') || [];

    tr.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-gray-900">${caseItem.patient_name}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${caseItem.assignee_name || '-'}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${formatDate(caseItem.surgery_date)}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${formatDate(caseItem.due_date)}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColors[caseItem.status]}">
                ${caseItem.status.replace('_', ' ')}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex -space-x-2">
                ${photos.slice(0, 3).map(p => `
                    <img src="${p.preview_url || p.local_path}" class="w-8 h-8 rounded-full border-2 border-white object-cover" />
                `).join('')}
                ${photos.length > 3 ? `<div class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-xs">+${photos.length - 3}</div>` : ''}
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex gap-2">
                ${stlFiles.length > 0 ? `<span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded"><i class="fas fa-cube"></i> ${stlFiles.length}</span>` : ''}
                ${cbctFiles.length > 0 ? `<span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded"><i class="fas fa-x-ray"></i> ${cbctFiles.length}</span>` : ''}
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm">
            <button onclick="event.stopPropagation(); showCaseDetail(${caseItem.id})" class="text-blue-600 hover:text-blue-900">
                <i class="fas fa-eye"></i> View
            </button>
        </td>
    `;

    return tr;
}

// Show Case Detail
async function showCaseDetail(caseId) {
    const modal = document.getElementById('caseDetailModal');
    const modalContent = document.getElementById('modalContent');

    modal.classList.add('show');
    modalContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></div>';

    try {
        const response = await apiRequest(`/cases/${caseId}`);
        const caseData = await response.json();

        modalContent.innerHTML = `
            <div class="space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Patient Name</label>
                        <div class="text-lg font-semibold">${caseData.patient_name}</div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Assignee</label>
                        <div class="text-lg">${caseData.assignee_name || '-'}</div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Surgery Date</label>
                        <div class="text-lg">${formatDate(caseData.surgery_date)}</div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Due Date</label>
                        <div class="text-lg">${formatDate(caseData.due_date)}</div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <div class="text-lg">${caseData.status.replace('_', ' ')}</div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Priority</label>
                        <div class="text-lg">${caseData.priority}</div>
                    </div>
                </div>

                <!-- Notes -->
                ${caseData.notes ? `
                    <div>
                        <label class="text-sm font-medium text-gray-500">Notes</label>
                        <div class="mt-2 p-4 bg-gray-50 rounded">${caseData.notes}</div>
                    </div>
                ` : ''}

                <!-- Attachments -->
                <div>
                    <label class="text-sm font-medium text-gray-500 mb-2 block">Attachments</label>
                    ${renderAttachments(caseData.attachments)}
                </div>

                <!-- Activity Log -->
                <div>
                    <label class="text-sm font-medium text-gray-500 mb-2 block">Recent Activity</label>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        ${caseData.activity_logs?.slice(0, 10).map(log => `
                            <div class="flex items-start gap-2 text-sm">
                                <span class="text-gray-400">${formatDateTime(log.created_at)}</span>
                                <span class="text-gray-700">${log.description}</span>
                            </div>
                        `).join('') || '<p class="text-gray-500">No activity yet</p>'}
                    </div>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error loading case details:', error);
        modalContent.innerHTML = '<div class="text-red-500">Error loading case details</div>';
    }
}

function renderAttachments(attachments) {
    if (!attachments || attachments.length === 0) {
        return '<p class="text-gray-500">No attachments</p>';
    }

    const photos = attachments.filter(a => a.type === 'photo');
    const files = attachments.filter(a => a.type !== 'photo');

    let html = '';

    if (photos.length > 0) {
        html += `
            <div class="mb-4">
                <h4 class="text-sm font-medium mb-2">Photos (${photos.length})</h4>
                <div class="grid grid-cols-4 gap-2">
                    ${photos.map(photo => `
                        <img src="${photo.preview_url || photo.local_path}"
                             class="w-full h-32 object-cover rounded cursor-pointer hover:opacity-75"
                             onclick="window.open('${photo.file_url || photo.local_path}', '_blank')" />
                    `).join('')}
                </div>
            </div>
        `;
    }

    if (files.length > 0) {
        html += `
            <div>
                <h4 class="text-sm font-medium mb-2">Files (${files.length})</h4>
                <div class="space-y-2">
                    ${files.map(file => {
                        const icon = file.type === 'stl' ? 'fa-cube' : file.type === 'cbct' ? 'fa-x-ray' : 'fa-file';
                        return `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div class="flex items-center gap-3">
                                    <i class="fas ${icon} text-gray-400"></i>
                                    <div>
                                        <div class="text-sm font-medium">${file.filename || 'Unknown'}</div>
                                        <div class="text-xs text-gray-500">${file.type.toUpperCase()}</div>
                                    </div>
                                </div>
                                <a href="/api/cases/${file.case_id}/attachments/${file.id}/download"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    return html;
}

function closeCaseModal() {
    document.getElementById('caseDetailModal').classList.remove('show');
}

// Sync Cases
async function syncCases() {
    if (!confirm('Start sync from Slack Canvas?')) {
        return;
    }

    try {
        const response = await apiRequest('/sync/trigger', { method: 'POST' });
        const result = await response.json();

        if (response.ok) {
            alert(`Sync completed!\nCases synced: ${result.data.cases_synced}\nAttachments synced: ${result.data.attachments_synced}`);
            loadStatistics();
            loadCases();
        } else {
            alert('Sync failed: ' + (result.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Sync error:', error);
        alert('Sync failed: Network error');
    }
}

// Filters
function applyFilters() {
    const filters = {
        search: document.getElementById('filterSearch').value,
        status: document.getElementById('filterStatus').value,
        sort_by: document.getElementById('filterSort').value,
    };

    loadCases(filters);
}

// Admin Panel
function showAdminPanel() {
    alert('Admin panel coming soon!');
}

// Utility Functions
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
