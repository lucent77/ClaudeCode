/**
 * Main JavaScript Application
 * Handles real-time updates and interactive features
 */

// Real-time update settings
const POLLING_INTERVAL = 5000; // 5 seconds
let lastUpdateTimestamp = null;
let pollingTimer = null;

/**
 * Initialize real-time updates
 */
function initRealTimeUpdates(departmentId = null) {
    // Set initial timestamp
    lastUpdateTimestamp = new Date().toISOString().slice(0, 19).replace('T', ' ');

    // Start polling
    startPolling(departmentId);
}

/**
 * Start polling for updates
 */
function startPolling(departmentId = null) {
    pollingTimer = setInterval(() => {
        checkForUpdates(departmentId);
    }, POLLING_INTERVAL);
}

/**
 * Stop polling
 */
function stopPolling() {
    if (pollingTimer) {
        clearInterval(pollingTimer);
        pollingTimer = null;
    }
}

/**
 * Check for task updates
 */
function checkForUpdates(departmentId = null) {
    let url = `/public/api/tasks.php?since=${encodeURIComponent(lastUpdateTimestamp)}`;

    if (departmentId) {
        url += `&department_id=${departmentId}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tasks.length > 0) {
                console.log(`Found ${data.tasks.length} updated tasks`);

                // Update tasks in the UI
                data.tasks.forEach(task => {
                    updateTaskInUI(task);
                });

                // Update timestamp
                lastUpdateTimestamp = data.timestamp;
            }
        })
        .catch(error => {
            console.error('Error checking for updates:', error);
        });
}

/**
 * Update task in the UI
 */
function updateTaskInUI(task) {
    const row = document.querySelector(`tr[data-task-id="${task.id}"]`);

    if (row) {
        // Task exists, update it
        updateTaskRow(row, task);

        // Flash the row to indicate update
        flashRow(row);
    } else {
        // New task, reload the page to show it
        // (Alternative: dynamically insert the new row)
        location.reload();
    }
}

/**
 * Update task row with new data
 */
function updateTaskRow(row, task) {
    // Update status select
    const statusSelect = row.querySelector('.status-select');
    if (statusSelect) {
        statusSelect.value = task.status_id;
        statusSelect.className = 'status-select rounded px-2 py-1 text-xs font-semibold ' + getStatusClass(task.status_id);
    }

    // Update assigned user
    const assignedCell = row.querySelector('td:nth-child(8)');
    if (assignedCell) {
        assignedCell.textContent = task.assigned_to_name || 'Unassigned';
    }
}

/**
 * Flash row to indicate update
 */
function flashRow(row) {
    row.classList.add('bg-blue-100');
    setTimeout(() => {
        row.classList.remove('bg-blue-100');
    }, 2000);
}

/**
 * Get status CSS class
 */
function getStatusClass(statusId) {
    const classes = {
        '1': 'bg-yellow-100 text-yellow-800',
        '2': 'bg-blue-100 text-blue-800',
        '3': 'bg-orange-100 text-orange-800',
        '4': 'bg-green-100 text-green-800',
        '5': 'bg-red-100 text-red-800'
    };
    return classes[statusId] || 'bg-gray-100 text-gray-800';
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 rounded-lg p-4 shadow-lg ${
        type === 'success' ? 'bg-green-100 text-green-800' :
        type === 'error' ? 'bg-red-100 text-red-800' :
        type === 'warning' ? 'bg-yellow-100 text-yellow-800' :
        'bg-blue-100 text-blue-800'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remove after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
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
 * Format date for display
 */
function formatDate(dateString) {
    if (!dateString) return '-';

    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
    });
}

/**
 * Initialize page
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Task Management System initialized');

    // Initialize real-time updates if on a page with tasks
    const tasksTable = document.getElementById('tasksTable');
    if (tasksTable) {
        // Get department ID from page if available
        const departmentIdMeta = document.querySelector('meta[name="department-id"]');
        const departmentId = departmentIdMeta ? departmentIdMeta.content : null;

        initRealTimeUpdates(departmentId);
    }

    // Stop polling when page is hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            const departmentIdMeta = document.querySelector('meta[name="department-id"]');
            const departmentId = departmentIdMeta ? departmentIdMeta.content : null;
            startPolling(departmentId);
        }
    });
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    stopPolling();
});
