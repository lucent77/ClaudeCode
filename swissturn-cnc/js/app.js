/**
 * SwissTurn CNC Parser - Main Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();

    // Initialize code highlighting interactions
    initCodeHighlighting();

    // Initialize live parsing (if on upload page)
    initLiveParsing();
});

/**
 * Initialize tooltip functionality
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = e.target.dataset.tooltip;
    if (!text) return;

    const tooltip = document.createElement('div');
    tooltip.className = 'fixed bg-cnc-dark border border-cnc-accent rounded px-3 py-2 text-sm z-50 max-w-xs';
    tooltip.textContent = text;
    tooltip.id = 'tooltip';

    document.body.appendChild(tooltip);

    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + 'px';
    tooltip.style.top = (rect.bottom + 5) + 'px';
}

function hideTooltip() {
    const tooltip = document.getElementById('tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

/**
 * Initialize code line highlighting interactions
 */
function initCodeHighlighting() {
    const codeLines = document.querySelectorAll('.code-line');
    codeLines.forEach(line => {
        line.addEventListener('click', function() {
            // Remove previous selection
            document.querySelectorAll('.code-line.selected').forEach(el => {
                el.classList.remove('selected', 'bg-cnc-highlight/20');
            });

            // Select this line
            this.classList.add('selected', 'bg-cnc-highlight/20');

            // Show line details in modal or panel (future feature)
            const lineNum = this.dataset.line;
            console.log('Selected line:', lineNum);
        });
    });
}

/**
 * Initialize live parsing preview
 */
function initLiveParsing() {
    const codeTextarea = document.querySelector('textarea[name="source_code"]');
    const machineSelect = document.querySelector('select[name="machine_id"]');

    if (!codeTextarea || !machineSelect) return;

    let parseTimeout;

    codeTextarea.addEventListener('input', function() {
        clearTimeout(parseTimeout);
        parseTimeout = setTimeout(() => {
            if (this.value.length > 10 && machineSelect.value) {
                // Preview parsing could be implemented here
                console.log('Would preview parse:', this.value.substring(0, 100) + '...');
            }
        }, 500);
    });
}

/**
 * Parse CNC code via API
 */
async function parseCode(machineId, code) {
    try {
        const response = await fetch('/api/parse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                machine_id: machineId,
                code: code,
            }),
        });

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Parse error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Export annotated program
 */
function exportAnnotated(programId, format = 'text') {
    window.open(`/programs/${programId}/export?format=${format}`, '_blank');
}

/**
 * Copy code to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard');
    }).catch(err => {
        console.error('Copy failed:', err);
    });
}

/**
 * Show notification toast
 */
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Confirm deletion
 */
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}
