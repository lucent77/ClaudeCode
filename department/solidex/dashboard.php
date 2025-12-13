<?php
/**
 * SOLIDEX Department Dashboard
 * Special handling for per-tooth workflow tracking
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

auth()->requireDepartment(DEPT_SOLIDEX);

$pageTitle = 'SOLIDEX Department';
$pageDescription = 'Per-tooth workflow tracking and management';

$department = DEPT_SOLIDEX;

// Get filters
$filters = [
    'step' => input('step'),
    'on_hold' => input('on_hold') !== null ? (bool) input('on_hold') : null,
    'due_date' => input('due_date'),
    'search' => input('search')
];

$page = max(1, (int) input('page', 1));
$perPage = ITEMS_PER_PAGE;

// Get step stats (for teeth tasks)
$stepStats = workflow()->getStepStats($department);
$steps = workflow()->getSteps($department);

// Get cases overview
$cases = caseManager()->getDepartmentCases($department, ['completed' => false], $page, $perPage);

// Get tooth tasks with filters
$toothTasks = workflow()->getSolidexToothTasks(null, $filters);
$noteTags = caseManager()->getNoteTags($department);

ob_start();
?>

<!-- Step Overview Cards -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <?php foreach ($steps as $step): ?>
        <?php $stat = $stepStats[$step['step_code']] ?? ['count' => 0, 'on_hold' => 0]; ?>
        <a href="?step=<?= e($step['step_code']) ?>" class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow <?= $filters['step'] === $step['step_code'] ? 'ring-2 ring-emerald-500' : '' ?>">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($step['step_code']) ?>">
                    <?= e($step['step_code']) ?>
                </span>
                <?php if ($stat['on_hold'] > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                        <?= $stat['on_hold'] ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900"><?= $stat['count'] ?></p>
            <p class="text-xs text-gray-500 truncate"><?= e($step['step_name']) ?></p>
        </a>
    <?php endforeach; ?>
</div>

<!-- Cases Progress Overview -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Active Cases - Teeth Progress</h3>
    </div>
    <div class="p-4">
        <?php if (empty($cases['data'])): ?>
            <p class="text-gray-500 text-center py-4">No active SOLIDEX cases</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach (array_slice($cases['data'], 0, 10) as $case): ?>
                    <?php
                    // Calculate completion percentage
                    $total = (int) ($case['teeth_count'] ?? 0);
                    $completed = (int) ($case['teeth_completed_count'] ?? 0);
                    $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                    ?>
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 w-32">
                            <a href="<?= url('/cases/view.php?id=' . $case['case_id']) ?>" class="text-blue-600 hover:text-blue-500 font-medium text-sm">
                                <?= e($case['case_number']) ?>
                            </a>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600"><?= e($case['lab_name']) ?></span>
                                <span class="text-sm font-medium text-gray-900"><?= $completed ?>/<?= $total ?> teeth</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: <?= $percent ?>%"></div>
                            </div>
                        </div>
                        <div class="flex-shrink-0 w-24 text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= dueDateClass($case['due_date']) ?>">
                                <?= formatDate($case['due_date']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-5">
            <div>
                <label for="step" class="form-label">Step</label>
                <select id="step" name="step" class="form-input">
                    <option value="">All Steps</option>
                    <?php foreach ($steps as $step): ?>
                        <option value="<?= e($step['step_code']) ?>" <?= $filters['step'] === $step['step_code'] ? 'selected' : '' ?>>
                            <?= e($step['step_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="on_hold" class="form-label">Hold Status</label>
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
            <div>
                <label for="search" class="form-label">Search</label>
                <input type="text" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" class="form-input" placeholder="Case #, Lab...">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= url('/department/solidex/dashboard.php') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Action Bar -->
<div id="bulkActionBar" class="hidden bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center">
            <span class="text-sm font-medium text-emerald-800">
                <span id="selectedCount">0</span> teeth tasks selected
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" id="bulkMachineInput" placeholder="Machine name (if required)"
                   class="form-input text-sm w-40 hidden">
            <button onclick="bulkCompleteStep()" class="btn btn-success text-sm">Complete Selected</button>
            <button onclick="showBulkHoldModal()" class="btn btn-warning text-sm">Put On Hold</button>
            <button onclick="clearSelection()" class="btn btn-secondary text-sm">Clear</button>
        </div>
    </div>
</div>

<!-- Tooth Tasks Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Tooth Tasks (<?= count($toothTasks) ?>)
        </h3>
    </div>

    <?php if (empty($toothTasks)): ?>
        <div class="px-4 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="mt-2 text-gray-500">No tooth tasks match your criteria.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                                   class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case / Tooth</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab / Patient</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Info</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Step</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($toothTasks as $task): ?>
                        <tr class="hover:bg-gray-50 case-row" data-id="<?= $task['id'] ?>" data-step="<?= e($task['current_step_code']) ?>" data-type="tooth">
                            <td class="px-3 py-4">
                                <?php if (!$task['is_on_hold']): ?>
                                    <input type="checkbox" class="case-checkbox h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
                                           onchange="updateBulkSelection()">
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <a href="<?= url('/cases/view.php?id=' . $task['case_id']) ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                        <?= e($task['case_number']) ?>
                                    </a>
                                    <span class="ml-2 px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-xs font-bold">
                                        #<?= e($task['tooth_number']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?= e($task['lab_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= e($task['patient_name'] ?? '-') ?></div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">
                                <?= e($task['job_type'] ?? '-') ?>
                                <?php if (!empty($task['implant_system'])): ?>
                                    <br><span class="text-xs text-gray-400"><?= e($task['implant_system']) ?></span>
                                <?php endif; ?>
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800" title="<?= e($task['hold_reason'] ?? '') ?>">
                                        On Hold
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <?php if ($task['is_on_hold']): ?>
                                        <button onclick="releaseHold(<?= $task['id'] ?>)" class="text-green-600 hover:text-green-900">Release</button>
                                    <?php else: ?>
                                        <button onclick="showCompleteModal(<?= $task['id'] ?>, '<?= e($task['current_step_code']) ?>')"
                                                class="text-green-600 hover:text-green-900">Complete</button>
                                        <button onclick="showHoldModal(<?= $task['id'] ?>)" class="text-yellow-600 hover:text-yellow-900">Hold</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modals -->
<?php include APP_ROOT . '/views/components/workflow-modals.php'; ?>

<script>
    const department = '<?= $department ?>';
    const machineSteps = ['CNC'];
    const entityType = 'TOOTH'; // SOLIDEX uses per-tooth tracking

    // Override the complete step function for SOLIDEX
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

    // Override complete form to use TOOTH entity type
    document.getElementById('completeForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = {
            entity_id: document.getElementById('completeEntityId').value,
            entity_type: 'TOOTH',
            department: department,
            step_code: document.getElementById('completeStepCode').value,
            machine_name: document.getElementById('machineName').value,
            notes: document.getElementById('completeNotes').value
        };

        try {
            const response = await fetchApi('<?= url('/api/workflow/complete-step.php') ?>', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (response.success) {
                showToast('Tooth task completed!', 'success');
                hideCompleteModal();
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(response.message || 'Failed to complete', 'error');
            }
        } catch (error) {
            showToast(error.message || 'An error occurred', 'error');
        }
    });
</script>
<?php include APP_ROOT . '/views/components/workflow-scripts.php'; ?>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
