<script>
// Workflow management scripts
const apiBaseUrl = '<?= url('/api/workflow') ?>';

// Complete step modal
function showCompleteModal(entityId, stepCode) {
    document.getElementById('completeEntityId').value = entityId;
    document.getElementById('completeStepCode').value = stepCode;

    const machineGroup = document.getElementById('machineInputGroup');
    const machineInput = document.getElementById('machineName');

    if (machineSteps.includes(stepCode)) {
        machineGroup.classList.remove('hidden');
        machineInput.required = true;
    } else {
        machineGroup.classList.add('hidden');
        machineInput.required = false;
        machineInput.value = '';
    }

    document.getElementById('completeModal').classList.remove('hidden');
}

function hideCompleteModal() {
    document.getElementById('completeModal').classList.add('hidden');
    document.getElementById('completeForm').reset();
}

// Hold modal
function showHoldModal(entityId) {
    document.getElementById('holdEntityId').value = entityId;
    document.getElementById('holdModal').classList.remove('hidden');
}

function hideHoldModal() {
    document.getElementById('holdModal').classList.add('hidden');
    document.getElementById('holdForm').reset();
}

// Bulk hold modal
function showBulkHoldModal() {
    document.getElementById('bulkHoldModal').classList.remove('hidden');
}

function hideBulkHoldModal() {
    document.getElementById('bulkHoldModal').classList.add('hidden');
    document.getElementById('bulkHoldForm').reset();
}

// Note tags modal
function showNoteTagsModal(entityId, currentTags) {
    document.getElementById('noteTagsEntityId').value = entityId;
    // Populate tags - would need to fetch available tags
    document.getElementById('noteTagsModal').classList.remove('hidden');
}

function hideNoteTagsModal() {
    document.getElementById('noteTagsModal').classList.add('hidden');
}

// Complete step form submission
document.getElementById('completeForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        entity_id: document.getElementById('completeEntityId').value,
        department: department,
        step_code: document.getElementById('completeStepCode').value,
        machine_name: document.getElementById('machineName').value,
        notes: document.getElementById('completeNotes').value
    };

    try {
        const response = await fetchApi(apiBaseUrl + '/complete-step.php', {
            method: 'POST',
            body: JSON.stringify(formData)
        });

        if (response.success) {
            showToast('Step completed successfully!', 'success');
            hideCompleteModal();
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(response.message || 'Failed to complete step', 'error');
        }
    } catch (error) {
        showToast(error.message || 'An error occurred', 'error');
    }
});

// Hold form submission
document.getElementById('holdForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = {
        entity_id: document.getElementById('holdEntityId').value,
        department: department,
        reason: document.getElementById('holdReason').value
    };

    try {
        const response = await fetchApi(apiBaseUrl + '/set-hold.php', {
            method: 'POST',
            body: JSON.stringify(formData)
        });

        if (response.success) {
            showToast('Case placed on hold', 'success');
            hideHoldModal();
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(response.message || 'Failed to set hold', 'error');
        }
    } catch (error) {
        showToast(error.message || 'An error occurred', 'error');
    }
});

// Release hold
async function releaseHold(entityId) {
    if (!confirm('Release this case from hold?')) {
        return;
    }

    try {
        const response = await fetchApi(apiBaseUrl + '/release-hold.php', {
            method: 'POST',
            body: JSON.stringify({
                entity_id: entityId,
                department: department
            })
        });

        if (response.success) {
            showToast('Hold released successfully', 'success');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(response.message || 'Failed to release hold', 'error');
        }
    } catch (error) {
        showToast(error.message || 'An error occurred', 'error');
    }
}

// Bulk selection functions
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    document.querySelectorAll('.case-checkbox').forEach(cb => {
        cb.checked = selectAll.checked;
    });
    updateBulkSelection();
}

function updateBulkSelection() {
    const checked = document.querySelectorAll('.case-checkbox:checked');
    const bulkBar = document.getElementById('bulkActionBar');
    const countSpan = document.getElementById('selectedCount');

    if (checked.length > 0) {
        bulkBar.classList.remove('hidden');
        countSpan.textContent = checked.length;

        // Check if any selected items require machine input
        let needsMachine = false;
        checked.forEach(cb => {
            const row = cb.closest('.case-row');
            if (machineSteps.includes(row.dataset.step)) {
                needsMachine = true;
            }
        });

        const machineInput = document.getElementById('bulkMachineInput');
        if (needsMachine) {
            machineInput.classList.remove('hidden');
        } else {
            machineInput.classList.add('hidden');
        }
    } else {
        bulkBar.classList.add('hidden');
    }
}

function clearSelection() {
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.case-checkbox').forEach(cb => cb.checked = false);
    updateBulkSelection();
}

// Bulk complete
async function bulkCompleteStep() {
    const checked = document.querySelectorAll('.case-checkbox:checked');
    const items = [];
    const machineName = document.getElementById('bulkMachineInput')?.value || '';

    checked.forEach(cb => {
        const row = cb.closest('.case-row');
        items.push({
            id: parseInt(row.dataset.id),
            step: row.dataset.step
        });
    });

    // Check for machine requirement
    const needsMachine = items.some(item => machineSteps.includes(item.step));
    if (needsMachine && !machineName) {
        showToast('Please enter a machine name for CNC/OVENS steps', 'warning');
        return;
    }

    if (!confirm(`Complete ${items.length} case(s)?`)) {
        return;
    }

    try {
        const response = await fetchApi(apiBaseUrl + '/bulk-complete.php', {
            method: 'POST',
            body: JSON.stringify({
                department: department,
                items: items,
                machine_name: machineName
            })
        });

        if (response.success) {
            const successCount = response.data?.success?.length || 0;
            const failCount = response.data?.failed?.length || 0;

            if (successCount > 0) {
                showToast(`Successfully completed ${successCount} case(s)`, 'success');
            }
            if (failCount > 0) {
                showToast(`${failCount} case(s) failed to complete`, 'warning');
            }
            setTimeout(() => location.reload(), 500);
        }
    } catch (error) {
        showToast(error.message || 'An error occurred', 'error');
    }
}

// Bulk hold form submission
document.getElementById('bulkHoldForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const checked = document.querySelectorAll('.case-checkbox:checked');
    const entityIds = [];

    checked.forEach(cb => {
        const row = cb.closest('.case-row');
        entityIds.push(parseInt(row.dataset.id));
    });

    const formData = {
        entity_ids: entityIds,
        department: department,
        reason: document.getElementById('bulkHoldReason').value
    };

    try {
        const response = await fetchApi(apiBaseUrl + '/bulk-hold.php', {
            method: 'POST',
            body: JSON.stringify(formData)
        });

        if (response.success) {
            showToast('Cases placed on hold', 'success');
            hideBulkHoldModal();
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(response.message || 'Failed to set hold', 'error');
        }
    } catch (error) {
        showToast(error.message || 'An error occurred', 'error');
    }
});
</script>
