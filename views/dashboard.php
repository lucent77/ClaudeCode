<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back, <?= htmlspecialchars($user['name']) ?>!</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Cases -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-indigo-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Total Cases
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900">
                                    <?= number_format($stats['total_cases']) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Cases -->
            <?php
            $newCases = 0;
            foreach ($stats['cases_by_status'] as $status) {
                if ($status['status'] === 'new') {
                    $newCases = (int) $status['count'];
                    break;
                }
            }
            ?>
            <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-green-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    New Cases
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900">
                                    <?= number_format($newCases) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <?php
            $inProgress = 0;
            foreach ($stats['cases_by_status'] as $status) {
                if ($status['status'] === 'in_progress') {
                    $inProgress = (int) $status['count'];
                    break;
                }
            }
            ?>
            <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-yellow-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    In Progress
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900">
                                    <?= number_format($inProgress) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue -->
            <div class="bg-white overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-red-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Overdue
                                </dt>
                                <dd class="text-3xl font-bold text-gray-900">
                                    <?= number_format($stats['overdue_cases']) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Cases -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Cases</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($stats['recent_cases'])): ?>
                        <p class="text-gray-500 text-center py-8">No cases yet</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($stats['recent_cases'] as $case): ?>
                                <a href="/cases/<?= $case['id'] ?>" class="block hover:bg-gray-50 p-4 rounded-lg border border-gray-200 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-900">
                                                <?= htmlspecialchars($case['external_case_no']) ?>
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">
                                                <?= htmlspecialchars($case['patient_name'] ?? 'N/A') ?> •
                                                <?= htmlspecialchars($case['lab_name'] ?? 'N/A') ?>
                                            </div>
                                            <?php if ($case['due_date']): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    Due: <?= date('M d, Y', strtotime($case['due_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php
                                            $statusColors = [
                                                'new' => 'bg-green-100 text-green-800',
                                                'in_progress' => 'bg-yellow-100 text-yellow-800',
                                                'done' => 'bg-blue-100 text-blue-800',
                                                'on_hold' => 'bg-gray-100 text-gray-800',
                                                'canceled' => 'bg-red-100 text-red-800'
                                            ];
                                            $statusColor = $statusColors[$case['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusColor ?>">
                                                <?= ucfirst(str_replace('_', ' ', $case['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="/cases" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                                View all cases →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Assigned Cases (for workers) or Status Breakdown -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <?php if ($stats['my_assigned_cases'] !== null): ?>
                            My Assigned Cases
                        <?php else: ?>
                            Cases by Status
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="p-6">
                    <?php if ($stats['my_assigned_cases'] !== null): ?>
                        <!-- Worker's assigned cases -->
                        <?php if (empty($stats['my_assigned_cases'])): ?>
                            <p class="text-gray-500 text-center py-8">No cases assigned to you</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($stats['my_assigned_cases'] as $item): ?>
                                    <a href="/cases/<?= $item['case_id'] ?>" class="block hover:bg-gray-50 p-4 rounded-lg border border-gray-200 transition">
                                        <div class="font-semibold text-gray-900">
                                            <?= htmlspecialchars($item['external_case_no']) ?>
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <?= htmlspecialchars($item['work_type']) ?> •
                                            <?= htmlspecialchars($item['patient_name'] ?? 'N/A') ?>
                                        </div>
                                        <?php if ($item['due_date']): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Due: <?= date('M d, Y', strtotime($item['due_date'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Status breakdown chart -->
                        <div class="space-y-4">
                            <?php foreach ($stats['cases_by_status'] as $status): ?>
                                <?php
                                $percentage = $stats['total_cases'] > 0
                                    ? round(($status['count'] / $stats['total_cases']) * 100, 1)
                                    : 0;
                                $barColors = [
                                    'new' => 'bg-green-500',
                                    'in_progress' => 'bg-yellow-500',
                                    'done' => 'bg-blue-500',
                                    'on_hold' => 'bg-gray-500',
                                    'canceled' => 'bg-red-500'
                                ];
                                $barColor = $barColors[$status['status']] ?? 'bg-gray-500';
                                ?>
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="font-medium text-gray-700">
                                            <?= ucfirst(str_replace('_', ' ', $status['status'])) ?>
                                        </span>
                                        <span class="text-gray-600">
                                            <?= number_format($status['count']) ?> (<?= $percentage ?>%)
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="<?= $barColor ?> h-2.5 rounded-full transition-all duration-300"
                                             style="width: <?= $percentage ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="/cases/create" class="flex flex-col items-center justify-center p-6 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition text-center">
                        <svg class="h-8 w-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="text-sm font-medium text-indigo-900">New Case</span>
                    </a>

                    <a href="/cases" class="flex flex-col items-center justify-center p-6 bg-blue-50 hover:bg-blue-100 rounded-lg transition text-center">
                        <svg class="h-8 w-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-900">Search Cases</span>
                    </a>

                    <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin'])): ?>
                        <a href="/import" class="flex flex-col items-center justify-center p-6 bg-green-50 hover:bg-green-100 rounded-lg transition text-center">
                            <svg class="h-8 w-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span class="text-sm font-medium text-green-900">Import Data</span>
                        </a>

                        <a href="/admin/users" class="flex flex-col items-center justify-center p-6 bg-purple-50 hover:bg-purple-100 rounded-lg transition text-center">
                            <svg class="h-8 w-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-purple-900">Manage Users</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
