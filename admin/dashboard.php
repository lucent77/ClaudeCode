<?php
/**
 * Super Admin Dashboard
 */

require_once __DIR__ . '/../includes/bootstrap.php';

auth()->requireRole(ROLE_SUPER_ADMIN);

$pageTitle = 'Admin Dashboard';
$pageDescription = 'Overview of all departments and system status';

// Get statistics
$stats = caseManager()->getDashboardStats();
$cocrStats = workflow()->getStepStats(DEPT_COCR);
$solidexStats = workflow()->getStepStats(DEPT_SOLIDEX);
$printStats = workflow()->getStepStats(DEPT_3D_PRINT);

// Get recent cases
$recentCases = caseManager()->getCases(['status' => STATUS_ACTIVE], 1, 10);

// Start output buffering for content
ob_start();
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <!-- Total Active Cases -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Cases</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900"><?= $stats['total_active'] ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <a href="<?= url('/cases/index.php?status=ACTIVE') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-500">View all</a>
        </div>
    </div>

    <!-- Due Today -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Due Today</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold text-gray-900"><?= $stats['due_today'] ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <a href="<?= url('/cases/index.php?due_date=' . date('Y-m-d')) ?>" class="text-sm font-medium text-blue-600 hover:text-blue-500">View all</a>
        </div>
    </div>

    <!-- Overdue -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold <?= $stats['overdue'] > 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $stats['overdue'] ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <a href="<?= url('/cases/index.php?status=overdue') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-500">View all</a>
        </div>
    </div>

    <!-- On Hold -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">On Hold</dt>
                        <dd class="flex items-baseline">
                            <div class="text-2xl font-semibold <?= $stats['on_hold'] > 0 ? 'text-yellow-600' : 'text-gray-900' ?>"><?= $stats['on_hold'] ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <a href="<?= url('/hold/index.php') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-500">View all</a>
        </div>
    </div>
</div>

<!-- Department Overview -->
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-8">
    <!-- COCR Department -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                    COCR Department
                </h3>
                <a href="<?= url('/department/cocr/dashboard.php') ?>" class="text-sm text-blue-600 hover:text-blue-500">View</a>
            </div>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-3">
                <?php foreach (workflow()->getSteps(DEPT_COCR) as $step): ?>
                    <?php $stepData = $cocrStats[$step['step_code']] ?? ['count' => 0, 'on_hold' => 0]; ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($step['step_code']) ?>">
                                <?= e($step['step_code']) ?>
                            </span>
                            <span class="ml-2 text-sm text-gray-600"><?= e($step['step_name']) ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-900"><?= $stepData['count'] ?></span>
                            <?php if ($stepData['on_hold'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <?= $stepData['on_hold'] ?> hold
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- SOLIDEX Department -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
                    <span class="w-3 h-3 bg-emerald-500 rounded-full mr-2"></span>
                    SOLIDEX Department
                </h3>
                <a href="<?= url('/department/solidex/dashboard.php') ?>" class="text-sm text-blue-600 hover:text-blue-500">View</a>
            </div>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-3">
                <?php foreach (workflow()->getSteps(DEPT_SOLIDEX) as $step): ?>
                    <?php $stepData = $solidexStats[$step['step_code']] ?? ['count' => 0, 'on_hold' => 0]; ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($step['step_code']) ?>">
                                <?= e($step['step_code']) ?>
                            </span>
                            <span class="ml-2 text-sm text-gray-600"><?= e($step['step_name']) ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-900"><?= $stepData['count'] ?></span>
                            <?php if ($stepData['on_hold'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <?= $stepData['on_hold'] ?> hold
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 3D PRINT Department -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
                    <span class="w-3 h-3 bg-violet-500 rounded-full mr-2"></span>
                    3D Print Department
                </h3>
                <a href="<?= url('/department/3d-print/dashboard.php') ?>" class="text-sm text-blue-600 hover:text-blue-500">View</a>
            </div>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-3">
                <?php foreach (workflow()->getSteps(DEPT_3D_PRINT) as $step): ?>
                    <?php $stepData = $printStats[$step['step_code']] ?? ['count' => 0, 'on_hold' => 0]; ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= stepBadgeClass($step['step_code']) ?>">
                                <?= e($step['step_code']) ?>
                            </span>
                            <span class="ml-2 text-sm text-gray-600"><?= e($step['step_name']) ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-900"><?= $stepData['count'] ?></span>
                            <?php if ($stepData['on_hold'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <?= $stepData['on_hold'] ?> hold
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Cases -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Active Cases</h3>
            <a href="<?= url('/cases/index.php') ?>" class="text-sm text-blue-600 hover:text-blue-500">View all cases</a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case #</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departments</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($recentCases['data'])): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="mt-2">No active cases found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentCases['data'] as $case): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="<?= url('/cases/view.php?id=' . $case['id']) ?>" class="text-blue-600 hover:text-blue-500 font-medium">
                                    <?= e($case['case_number']) ?>
                                </a>
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    <?= e($case['site']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= e($case['lab_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= e($case['patient_name'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= dueDateClass($case['due_date']) ?>">
                                    <?= formatDate($case['due_date']) ?>
                                    <?php $days = daysUntilDue($case['due_date']); ?>
                                    <?php if ($days < 0): ?>
                                        <span class="ml-1">(<?= abs($days) ?>d overdue)</span>
                                    <?php elseif ($days === 0): ?>
                                        <span class="ml-1">(today)</span>
                                    <?php elseif ($days === 1): ?>
                                        <span class="ml-1">(tomorrow)</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex space-x-1">
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= statusBadgeClass($case['status']) ?>">
                                    <?= e($case['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= url('/cases/view.php?id=' . $case['id']) ?>" class="text-blue-600 hover:text-blue-900">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
