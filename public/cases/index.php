<?php
/**
 * Cases List Page
 */

require_once __DIR__ . '/../../includes/bootstrap.php';

auth()->requireLogin();

$pageTitle = 'All Cases';
$pageDescription = 'View and manage all cases in the system';

// Get filters
$filters = [
    'status' => input('status'),
    'department' => input('department'),
    'site' => input('site'),
    'due_date_from' => input('due_date_from'),
    'due_date_to' => input('due_date_to'),
    'search' => input('search')
];

// Special filter for overdue
if ($filters['status'] === 'overdue') {
    $filters['status'] = STATUS_ACTIVE;
    $filters['due_date_to'] = date('Y-m-d', strtotime('-1 day'));
}

$page = max(1, (int) input('page', 1));
$perPage = ITEMS_PER_PAGE;

// Get cases
$cases = caseManager()->getCases($filters, $page, $perPage);

ob_start();
?>

<!-- Filters -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-6">
            <div>
                <label for="search" class="form-label">Search</label>
                <input type="text" id="search" name="search" value="<?= e($filters['search'] ?? '') ?>"
                       class="form-input" placeholder="Case #, Lab, Patient...">
            </div>
            <div>
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-input">
                    <option value="">All Statuses</option>
                    <option value="<?= STATUS_ACTIVE ?>" <?= $filters['status'] === STATUS_ACTIVE ? 'selected' : '' ?>>Active</option>
                    <option value="overdue" <?= input('status') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    <option value="<?= STATUS_COMPLETED ?>" <?= $filters['status'] === STATUS_COMPLETED ? 'selected' : '' ?>>Completed</option>
                    <option value="<?= STATUS_CANCELLED ?>" <?= $filters['status'] === STATUS_CANCELLED ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div>
                <label for="department" class="form-label">Department</label>
                <select id="department" name="department" class="form-input">
                    <option value="">All Departments</option>
                    <option value="<?= DEPT_COCR ?>" <?= $filters['department'] === DEPT_COCR ? 'selected' : '' ?>>COCR</option>
                    <option value="<?= DEPT_SOLIDEX ?>" <?= $filters['department'] === DEPT_SOLIDEX ? 'selected' : '' ?>>SOLIDEX</option>
                    <option value="<?= DEPT_3D_PRINT ?>" <?= $filters['department'] === DEPT_3D_PRINT ? 'selected' : '' ?>>3D Print</option>
                </select>
            </div>
            <div>
                <label for="site" class="form-label">Site</label>
                <select id="site" name="site" class="form-input">
                    <option value="">All Sites</option>
                    <option value="NYC" <?= $filters['site'] === 'NYC' ? 'selected' : '' ?>>NYC</option>
                    <option value="HV" <?= $filters['site'] === 'HV' ? 'selected' : '' ?>>HV</option>
                </select>
            </div>
            <div>
                <label for="due_date_from" class="form-label">Due From</label>
                <input type="date" id="due_date_from" name="due_date_from" value="<?= e($filters['due_date_from'] ?? '') ?>" class="form-input">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?= url('/cases/index.php') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Cases Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Cases (<?= $cases['total'] ?>)
            </h3>
            <?php if (auth()->isDepartmentManager()): ?>
                <a href="<?= url('/cases/create.php') ?>" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Case
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($cases['data'])): ?>
        <div class="px-4 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="mt-2 text-gray-500">No cases found matching your criteria.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab / Patient</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departments</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($cases['data'] as $case): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <a href="<?= url('/cases/view.php?id=' . $case['id']) ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                    <?= e($case['case_number']) ?>
                                </a>
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= e($case['site']) ?>
                                </span>
                                <?php if ($case['combo']): ?>
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                        COMBO
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?= e($case['lab_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= e($case['patient_name'] ?? '-') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= formatDate($case['created_timestamp']) ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= dueDateClass($case['due_date']) ?>">
                                    <?= formatDate($case['due_date']) ?>
                                    <?php $days = daysUntilDue($case['due_date']); ?>
                                    <?php if ($days < 0 && $case['status'] === STATUS_ACTIVE): ?>
                                        <span class="ml-1">(<?= abs($days) ?>d late)</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    <?php if ($case['has_cocr']): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">COCR</span>
                                    <?php endif; ?>
                                    <?php if ($case['has_solidex']): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">SOLIDEX</span>
                                    <?php endif; ?>
                                    <?php if ($case['has_3d_print']): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-800">3D PRINT</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= statusBadgeClass($case['status']) ?>">
                                    <?= e($case['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= url('/cases/view.php?id=' . $case['id']) ?>" class="text-blue-600 hover:text-blue-900">View</a>
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
                        <?php if ($page > 1): ?>
                            <a href="?<?= buildQueryString(['page' => $page - 1], ['search', 'status', 'department', 'site', 'due_date_from', 'due_date_to']) ?>"
                               class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $cases['total_pages']): ?>
                            <a href="?<?= buildQueryString(['page' => $page + 1], ['search', 'status', 'department', 'site', 'due_date_from', 'due_date_to']) ?>"
                               class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
