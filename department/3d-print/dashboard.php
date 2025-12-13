<?php
/**
 * 3D Print Department Dashboard
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

auth()->requireDepartment(DEPT_3D_PRINT);

$pageTitle = '3D Print Department';
$pageDescription = 'Manage 3D Print workflow and cases';

$department = DEPT_3D_PRINT;

// Get filters
$filters = [
    'step' => input('step'),
    'on_hold' => input('on_hold') !== null ? (bool) input('on_hold') : null,
    'due_date' => input('due_date'),
    'search' => input('search'),
    'completed' => input('show_completed') ? true : false
];

$page = max(1, (int) input('page', 1));
$perPage = ITEMS_PER_PAGE;

// Get step stats
$stepStats = workflow()->getStepStats($department);
$steps = workflow()->getSteps($department);

// Get cases
$cases = caseManager()->getDepartmentCases($department, $filters, $page, $perPage);
$noteTags = caseManager()->getNoteTags($department);

ob_start();
?>

<!-- Step Overview Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php foreach ($steps as $step): ?>
        <?php $stat = $stepStats[$step['step_code']] ?? ['count' => 0, 'on_hold' => 0]; ?>
        <a href="?step=<?= e($step['step_code']) ?>" class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow <?= $filters['step'] === $step['step_code'] ? 'ring-2 ring-violet-500' : '' ?>">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($step['step_code']) ?>">
                    <?= e($step['step_code']) ?>
                </span>
                <?php if ($stat['on_hold'] > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                        <?= $stat['on_hold'] ?> hold
                    </span>
                <?php endif; ?>
            </div>
            <p class="mt-2 text-2xl font-bold text-gray-900"><?= $stat['count'] ?></p>
            <p class="text-xs text-gray-500"><?= e($step['step_name']) ?></p>
        </a>
    <?php endforeach; ?>
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
                <input type="text" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>" class="form-input" placeholder="Case #, Lab, Patient...">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= url('/department/3d-print/dashboard.php') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Action Bar -->
<div id="bulkActionBar" class="hidden bg-violet-50 border border-violet-200 rounded-lg p-4 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center">
            <span class="text-sm font-medium text-violet-800">
                <span id="selectedCount">0</span> cases selected
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" id="nestingJobName" placeholder="Nesting job name"
                   class="form-input text-sm w-48 hidden">
            <button onclick="bulkCompleteStep()" class="btn btn-success text-sm">Complete Selected</button>
            <button onclick="showBulkHoldModal()" class="btn btn-warning text-sm">Put On Hold</button>
            <button onclick="clearSelection()" class="btn btn-secondary text-sm">Clear</button>
        </div>
    </div>
</div>

<!-- Cases Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Cases (<?= $cases['total'] ?>)
            </h3>
            <label class="flex items-center text-sm">
                <input type="checkbox" onchange="window.location.href='?show_completed=' + (this.checked ? '1' : '')" <?= $filters['completed'] ? 'checked' : '' ?>
                       class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded mr-2">
                Show Completed
            </label>
        </div>
    </div>

    <?php if (empty($cases['data'])): ?>
        <div class="px-4 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="mt-2 text-gray-500">No cases found matching your criteria.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()"
                                   class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded">
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab / Patient</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Print Type</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Step</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nesting Job</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($cases['data'] as $case): ?>
                        <tr class="hover:bg-gray-50 case-row" data-id="<?= $case['id'] ?>" data-step="<?= e($case['current_step_code']) ?>">
                            <td class="px-3 py-4">
                                <?php if (!$case['is_on_hold'] && !$case['is_completed']): ?>
                                    <input type="checkbox" class="case-checkbox h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded"
                                           onchange="updateBulkSelection()">
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <a href="<?= url('/cases/view.php?id=' . $case['case_id']) ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                    <?= e($case['case_number']) ?>
                                </a>
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= e($case['site']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?= e($case['lab_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= e($case['patient_name'] ?? '-') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($case['print_type'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= dueDateClass($case['due_date']) ?>">
                                    <?= formatDate($case['due_date']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if ($case['is_completed']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        COMPLETED
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($case['current_step_code']) ?>">
                                        <?= e($case['current_step_name'] ?? $case['current_step_code']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($case['nesting_job_name'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <?php if ($case['is_on_hold']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        On Hold
                                    </span>
                                <?php elseif ($case['is_completed']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Done
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <?php if (!$case['is_completed']): ?>
                                        <?php if ($case['is_on_hold']): ?>
                                            <button onclick="releaseHold(<?= $case['id'] ?>)" class="text-green-600 hover:text-green-900">Release</button>
                                        <?php else: ?>
                                            <button onclick="showCompleteModal(<?= $case['id'] ?>, '<?= e($case['current_step_code']) ?>')"
                                                    class="text-green-600 hover:text-green-900">Complete</button>
                                            <button onclick="showHoldModal(<?= $case['id'] ?>)" class="text-yellow-600 hover:text-yellow-900">Hold</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="<?= url('/cases/view.php?id=' . $case['case_id']) ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($cases['total_pages'] > 1): ?>
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= (($page - 1) * $perPage) + 1 ?></span>
                        to <span class="font-medium"><?= min($page * $perPage, $cases['total']) ?></span>
                        of <span class="font-medium"><?= $cases['total'] ?></span> results
                    </div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php if ($cases['page'] > 1): ?>
                            <a href="?<?= buildQueryString(['page' => $page - 1], ['step', 'on_hold', 'due_date', 'search']) ?>"
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($cases['page'] < $cases['total_pages']): ?>
                            <a href="?<?= buildQueryString(['page' => $page + 1], ['step', 'on_hold', 'due_date', 'search']) ?>"
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modals -->
<?php include APP_ROOT . '/views/components/workflow-modals.php'; ?>

<script>
    const department = '<?= $department ?>';
    const machineSteps = []; // 3D Print doesn't use machines like COCR/SOLIDEX
</script>
<?php include APP_ROOT . '/views/components/workflow-scripts.php'; ?>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
