<?php
/**
 * Hold Management Page
 * View and manage all cases on hold across departments
 */

require_once __DIR__ . '/../includes/bootstrap.php';

auth()->requireRole(ROLE_SUPER_ADMIN, ROLE_DEPT_MANAGER);

$pageTitle = 'Hold Management';
$pageDescription = 'Review and manage cases on hold to prevent schedule delays';

$department = input('department');
$userDept = auth()->userDepartment();

// If not super admin, restrict to user's department
if (!auth()->isSuperAdmin() && $userDept !== 'ALL') {
    $department = $userDept;
}

// Get hold items
$holdItems = [];

if (empty($department) || $department === DEPT_COCR || $userDept === 'ALL') {
    if (auth()->hasAccessToDepartment(DEPT_COCR)) {
        $items = workflow()->getHoldItems(DEPT_COCR);
        foreach ($items as $item) {
            $item['department'] = DEPT_COCR;
            $holdItems[] = $item;
        }
    }
}

if (empty($department) || $department === DEPT_SOLIDEX || $userDept === 'ALL') {
    if (auth()->hasAccessToDepartment(DEPT_SOLIDEX)) {
        $items = workflow()->getHoldItems(DEPT_SOLIDEX);
        foreach ($items as $item) {
            $item['department'] = DEPT_SOLIDEX;
            $holdItems[] = $item;
        }
    }
}

if (empty($department) || $department === DEPT_3D_PRINT || $userDept === 'ALL') {
    if (auth()->hasAccessToDepartment(DEPT_3D_PRINT)) {
        $items = workflow()->getHoldItems(DEPT_3D_PRINT);
        foreach ($items as $item) {
            $item['department'] = DEPT_3D_PRINT;
            $holdItems[] = $item;
        }
    }
}

// Sort by days on hold (most critical first)
usort($holdItems, fn($a, $b) => ($b['days_on_hold'] ?? 0) - ($a['days_on_hold'] ?? 0));

ob_start();
?>

<!-- Summary Cards -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
    <?php
    $cocrCount = count(array_filter($holdItems, fn($i) => $i['department'] === DEPT_COCR));
    $solidexCount = count(array_filter($holdItems, fn($i) => $i['department'] === DEPT_SOLIDEX));
    $printCount = count(array_filter($holdItems, fn($i) => $i['department'] === DEPT_3D_PRINT));
    ?>
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="w-3 h-3 bg-blue-500 rounded-full inline-block"></span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">COCR On Hold</dt>
                        <dd class="text-2xl font-semibold text-gray-900"><?= $cocrCount ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full inline-block"></span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">SOLIDEX On Hold</dt>
                        <dd class="text-2xl font-semibold text-gray-900"><?= $solidexCount ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="w-3 h-3 bg-violet-500 rounded-full inline-block"></span>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">3D Print On Hold</dt>
                        <dd class="text-2xl font-semibold text-gray-900"><?= $printCount ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" class="flex items-center space-x-4">
            <div>
                <label for="department" class="form-label">Department</label>
                <select id="department" name="department" class="form-input" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php if (auth()->hasAccessToDepartment(DEPT_COCR)): ?>
                        <option value="<?= DEPT_COCR ?>" <?= $department === DEPT_COCR ? 'selected' : '' ?>>COCR</option>
                    <?php endif; ?>
                    <?php if (auth()->hasAccessToDepartment(DEPT_SOLIDEX)): ?>
                        <option value="<?= DEPT_SOLIDEX ?>" <?= $department === DEPT_SOLIDEX ? 'selected' : '' ?>>SOLIDEX</option>
                    <?php endif; ?>
                    <?php if (auth()->hasAccessToDepartment(DEPT_3D_PRINT)): ?>
                        <option value="<?= DEPT_3D_PRINT ?>" <?= $department === DEPT_3D_PRINT ? 'selected' : '' ?>>3D Print</option>
                    <?php endif; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Hold Items Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Cases On Hold (<?= count($holdItems) ?>)
        </h3>
    </div>

    <?php if (empty($holdItems)): ?>
        <div class="px-4 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="mt-2 text-gray-500">No cases on hold. Great job!</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab / Patient</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Step</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days On Hold</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Held By</th>
                        <th scope="col" class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($holdItems as $item): ?>
                        <tr class="hover:bg-gray-50 <?= ($item['days_on_hold'] ?? 0) >= 3 ? 'bg-red-50' : '' ?>">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= departmentBadgeClass($item['department']) ?>">
                                    <?= e(str_replace('_', ' ', $item['department'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <a href="<?= url('/cases/view.php?id=' . ($item['case_id'] ?? $item['id'])) ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                    <?= e($item['case_number']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-gray-900"><?= e($item['lab_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= e($item['patient_name'] ?? '-') ?></div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= dueDateClass($item['due_date']) ?>">
                                    <?= formatDate($item['due_date']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($item['current_step_code']) ?>">
                                    <?= e($item['current_step_name'] ?? $item['current_step_code']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium <?= ($item['days_on_hold'] ?? 0) >= 3 ? 'text-red-600' : 'text-gray-900' ?>">
                                    <?= $item['days_on_hold'] ?? 0 ?> day(s)
                                </span>
                                <div class="text-xs text-gray-500"><?= formatDate($item['hold_at'] ?? '') ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= e($item['hold_reason'] ?? '') ?>">
                                    <?= e(truncate($item['hold_reason'] ?? '', 40)) ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($item['hold_by_initials'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="releaseHold(<?= $item['id'] ?>, '<?= e($item['department']) ?>')"
                                        class="text-green-600 hover:text-green-900 font-medium">
                                    Release
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    async function releaseHold(entityId, department) {
        if (!confirm('Release this case from hold?')) {
            return;
        }

        try {
            const response = await fetchApi('<?= url('/api/workflow/release-hold.php') ?>', {
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
</script>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
