<?php
/**
 * Operator Tasks Page
 * Shows tasks filtered by operator's assigned steps
 */

require_once __DIR__ . '/../includes/bootstrap.php';

auth()->requireLogin();

$pageTitle = 'My Tasks';
$pageDescription = 'Cases assigned to your workflow steps';

$userDept = auth()->userDepartment();
$assignedSteps = auth()->getAssignedSteps();

// Filters
$filters = [
    'step' => input('step'),
    'on_hold' => input('on_hold') !== null ? (bool) input('on_hold') : null,
    'due_date' => input('due_date'),
    'search' => input('search')
];

// Get tasks based on user's department
$tasks = [];
$toothTasks = [];

if ($userDept === 'ALL' || $userDept === DEPT_COCR) {
    $cocrTasks = workflow()->getOperatorTasks(DEPT_COCR, $assignedSteps, $filters);
    foreach ($cocrTasks as $task) {
        $task['department'] = DEPT_COCR;
        $tasks[] = $task;
    }
}

if ($userDept === 'ALL' || $userDept === DEPT_SOLIDEX) {
    // For SOLIDEX, get per-tooth tasks
    $toothTasks = workflow()->getSolidexToothTasks($assignedSteps, $filters);
}

if ($userDept === 'ALL' || $userDept === DEPT_3D_PRINT) {
    $printTasks = workflow()->getOperatorTasks(DEPT_3D_PRINT, $assignedSteps, $filters);
    foreach ($printTasks as $task) {
        $task['department'] = DEPT_3D_PRINT;
        $tasks[] = $task;
    }
}

// Sort all tasks by due date
usort($tasks, fn($a, $b) => strtotime($a['due_date']) - strtotime($b['due_date']));

// Get steps for filter dropdown
$availableSteps = [];
if ($userDept === 'ALL' || $userDept === DEPT_COCR) {
    foreach (workflow()->getSteps(DEPT_COCR) as $step) {
        $availableSteps[$step['step_code']] = $step['step_name'] . ' (COCR)';
    }
}
if ($userDept === 'ALL' || $userDept === DEPT_SOLIDEX) {
    foreach (workflow()->getSteps(DEPT_SOLIDEX) as $step) {
        $availableSteps[$step['step_code']] = $step['step_name'] . ' (SOLIDEX)';
    }
}
if ($userDept === 'ALL' || $userDept === DEPT_3D_PRINT) {
    foreach (workflow()->getSteps(DEPT_3D_PRINT) as $step) {
        $availableSteps[$step['step_code']] = $step['step_name'] . ' (3D PRINT)';
    }
}

ob_start();
?>

<!-- Filters -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="step" class="form-label">Step</label>
                <select id="step" name="step" class="form-input">
                    <option value="">All Steps</option>
                    <?php foreach ($availableSteps as $code => $name): ?>
                        <option value="<?= e($code) ?>" <?= $filters['step'] === $code ? 'selected' : '' ?>>
                            <?= e($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="on_hold" class="form-label">Status</label>
                <select id="on_hold" name="on_hold" class="form-input">
                    <option value="">All</option>
                    <option value="0" <?= $filters['on_hold'] === false ? 'selected' : '' ?>>Active Only</option>
                    <option value="1" <?= $filters['on_hold'] === true ? 'selected' : '' ?>>On Hold Only</option>
                </select>
            </div>
            <div>
                <label for="due_date" class="form-label">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="<?= e($filters['due_date'] ?? '') ?>" class="form-input">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= url('/operator/tasks.php') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Action Bar (hidden by default) -->
<div id="bulkActionBar" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6" x-data="{ showMachineInput: false }">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <span class="text-sm font-medium text-blue-800">
                <span id="selectedCount">0</span> items selected
            </span>
        </div>
        <div class="flex items-center space-x-3">
            <input type="text" id="bulkMachineInput" placeholder="Machine name (if required)"
                   class="form-input text-sm w-48 hidden">
            <button onclick="bulkCompleteStep()" class="btn btn-success text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Complete Selected
            </button>
            <button onclick="clearSelection()" class="btn btn-secondary text-sm">Clear</button>
        </div>
    </div>
</div>

<!-- Tasks Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Tasks (<?= count($tasks) + count($toothTasks) ?>)
        </h3>
    </div>

    <?php if (empty($tasks) && empty($toothTasks)): ?>
        <div class="px-4 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="mt-2 text-gray-500">No tasks assigned to you at the moment.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case / Tooth</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Step</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($tasks as $task): ?>
                        <tr class="hover:bg-gray-50 task-row" data-id="<?= $task['id'] ?>" data-type="case"
                            data-department="<?= e($task['department']) ?>" data-step="<?= e($task['current_step_code']) ?>">
                            <td class="px-3 py-4">
                                <?php if (!$task['is_on_hold']): ?>
                                    <input type="checkbox" class="task-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                           onchange="updateBulkSelection()">
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="w-2 h-2 rounded-full mr-2" style="background-color: <?= departmentColor($task['department']) ?>"></span>
                                    <div>
                                        <a href="<?= url('/cases/view.php?id=' . $task['case_id']) ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                            <?= e($task['case_number']) ?>
                                        </a>
                                        <span class="ml-1 text-xs text-gray-500"><?= e($task['site']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= e($task['lab_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($task['patient_name'] ?? '-') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= dueDateClass($task['due_date']) ?>">
                                    <?= formatDate($task['due_date']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($task['current_step_code']) ?>">
                                    <?= e($task['current_step_name'] ?? $task['current_step_code']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if ($task['is_on_hold']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        On Hold
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                <?php if (!$task['is_on_hold']): ?>
                                    <button onclick="showCompleteModal(<?= $task['id'] ?>, 'case', '<?= e($task['department']) ?>', '<?= e($task['current_step_code']) ?>')"
                                            class="text-green-600 hover:text-green-900 font-medium">
                                        Complete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- SOLIDEX Tooth Tasks -->
                    <?php foreach ($toothTasks as $task): ?>
                        <tr class="hover:bg-gray-50 task-row" data-id="<?= $task['id'] ?>" data-type="tooth"
                            data-department="SOLIDEX" data-step="<?= e($task['current_step_code']) ?>">
                            <td class="px-3 py-4">
                                <?php if (!$task['is_on_hold']): ?>
                                    <input type="checkbox" class="task-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                           onchange="updateBulkSelection()">
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="w-2 h-2 rounded-full mr-2 bg-emerald-500"></span>
                                    <div>
                                        <a href="<?= url('/cases/view.php?id=' . $task['case_id']) ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                            <?= e($task['case_number']) ?>
                                        </a>
                                        <span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-xs font-medium">
                                            Tooth #<?= e($task['tooth_number']) ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= e($task['lab_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= e($task['patient_name'] ?? '-') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= dueDateClass($task['due_date']) ?>">
                                    <?= formatDate($task['due_date']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($task['current_step_code']) ?>">
                                    <?= e($task['current_step_name'] ?? $task['current_step_code']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if ($task['is_on_hold']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        On Hold
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                <?php if (!$task['is_on_hold']): ?>
                                    <button onclick="showCompleteModal(<?= $task['id'] ?>, 'tooth', 'SOLIDEX', '<?= e($task['current_step_code']) ?>')"
                                            class="text-green-600 hover:text-green-900 font-medium">
                                        Complete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Complete Step Modal -->
<div id="completeModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideCompleteModal()"></div>

        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Complete Step
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Mark this step as complete and advance to the next step.
                        </p>
                    </div>
                </div>
            </div>

            <form id="completeForm" class="mt-5 space-y-4">
                <input type="hidden" id="entityId" name="entity_id">
                <input type="hidden" id="entityType" name="entity_type">
                <input type="hidden" id="department" name="department">
                <input type="hidden" id="stepCode" name="step_code">

                <div id="machineInputGroup" class="hidden">
                    <label for="machineName" class="form-label">Machine Name <span class="text-red-500">*</span></label>
                    <input type="text" id="machineName" name="machine_name" class="form-input" placeholder="Enter machine name">
                </div>

                <div>
                    <label for="notes" class="form-label">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="2" class="form-input" placeholder="Any notes about this step..."></textarea>
                </div>

                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm">
                        Complete Step
                    </button>
                    <button type="button" onclick="hideCompleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Steps that require machine input
    const machineSteps = ['CNC', 'OVENS'];

    function showCompleteModal(id, type, department, stepCode) {
        document.getElementById('entityId').value = id;
        document.getElementById('entityType').value = type;
        document.getElementById('department').value = department;
        document.getElementById('stepCode').value = stepCode;

        // Show/hide machine input
        const machineGroup = document.getElementById('machineInputGroup');
        if (machineSteps.includes(stepCode)) {
            machineGroup.classList.remove('hidden');
            document.getElementById('machineName').required = true;
        } else {
            machineGroup.classList.add('hidden');
            document.getElementById('machineName').required = false;
        }

        document.getElementById('completeModal').classList.remove('hidden');
    }

    function hideCompleteModal() {
        document.getElementById('completeModal').classList.add('hidden');
        document.getElementById('completeForm').reset();
    }

    // Handle form submission
    document.getElementById('completeForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = {
            entity_id: document.getElementById('entityId').value,
            entity_type: document.getElementById('entityType').value,
            department: document.getElementById('department').value,
            step_code: document.getElementById('stepCode').value,
            machine_name: document.getElementById('machineName').value,
            notes: document.getElementById('notes').value
        };

        try {
            const response = await fetchApi('<?= url('/api/workflow/complete-step.php') ?>', {
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

    // Bulk selection
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        document.querySelectorAll('.task-checkbox').forEach(cb => {
            cb.checked = selectAll.checked;
        });
        updateBulkSelection();
    }

    function updateBulkSelection() {
        const checked = document.querySelectorAll('.task-checkbox:checked');
        const bulkBar = document.getElementById('bulkActionBar');
        const countSpan = document.getElementById('selectedCount');

        if (checked.length > 0) {
            bulkBar.classList.remove('hidden');
            countSpan.textContent = checked.length;

            // Check if any selected items require machine input
            let needsMachine = false;
            checked.forEach(cb => {
                const row = cb.closest('.task-row');
                if (machineSteps.includes(row.dataset.step)) {
                    needsMachine = true;
                }
            });

            if (needsMachine) {
                document.getElementById('bulkMachineInput').classList.remove('hidden');
            } else {
                document.getElementById('bulkMachineInput').classList.add('hidden');
            }
        } else {
            bulkBar.classList.add('hidden');
        }
    }

    function clearSelection() {
        document.getElementById('selectAll').checked = false;
        document.querySelectorAll('.task-checkbox').forEach(cb => cb.checked = false);
        updateBulkSelection();
    }

    async function bulkCompleteStep() {
        const checked = document.querySelectorAll('.task-checkbox:checked');
        const items = [];
        const machineName = document.getElementById('bulkMachineInput').value;

        checked.forEach(cb => {
            const row = cb.closest('.task-row');
            items.push({
                id: parseInt(row.dataset.id),
                type: row.dataset.type,
                department: row.dataset.department,
                step: row.dataset.step
            });
        });

        // Check for machine requirement
        const needsMachine = items.some(item => machineSteps.includes(item.step));
        if (needsMachine && !machineName) {
            showToast('Please enter a machine name for CNC/OVENS steps', 'warning');
            return;
        }

        if (!confirm(`Complete ${items.length} item(s)?`)) {
            return;
        }

        try {
            const response = await fetchApi('<?= url('/api/workflow/bulk-complete.php') ?>', {
                method: 'POST',
                body: JSON.stringify({
                    items: items,
                    machine_name: machineName
                })
            });

            if (response.success) {
                showToast(`Successfully completed ${response.data.success.length} item(s)`, 'success');
                if (response.data.failed.length > 0) {
                    showToast(`${response.data.failed.length} item(s) failed`, 'warning');
                }
                setTimeout(() => location.reload(), 500);
            }
        } catch (error) {
            showToast(error.message || 'An error occurred', 'error');
        }
    }
</script>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
