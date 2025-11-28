<?php
$statusLabels = [
    'queued' => ['label' => 'In Queue', 'color' => 'bg-yellow-500'],
    'open' => ['label' => 'Open', 'color' => 'bg-blue-500'],
    'in_progress' => ['label' => 'In Progress', 'color' => 'bg-orange-500'],
    'resolved' => ['label' => 'Resolved', 'color' => 'bg-green-500'],
    'rejected' => ['label' => 'Rejected', 'color' => 'bg-red-500'],
    'duplicate' => ['label' => 'Duplicate', 'color' => 'bg-gray-500'],
];
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="mt-1 text-gray-600">Overview of feedback and moderation status</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">In Queue</div>
            <div class="mt-2 text-3xl font-bold text-yellow-600"><?= number_format($statusCounts['queued'] ?? 0) ?></div>
            <a href="/admin/queue" class="mt-2 text-sm text-primary-600 hover:underline">Review &rarr;</a>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Open</div>
            <div class="mt-2 text-3xl font-bold text-blue-600"><?= number_format($statusCounts['open'] ?? 0) ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">In Progress</div>
            <div class="mt-2 text-3xl font-bold text-orange-600"><?= number_format($statusCounts['in_progress'] ?? 0) ?></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm font-medium text-gray-500">Resolved</div>
            <div class="mt-2 text-3xl font-bold text-green-600"><?= number_format($statusCounts['resolved'] ?? 0) ?></div>
        </div>
    </div>

    <!-- SLA Warning -->
    <?php if (($slaBreaches ?? 0) > 0): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">SLA Breach Warning</h3>
                <p class="mt-1 text-sm text-red-700">
                    <?= number_format($slaBreaches) ?> item(s) have not received a response within 5 business days.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Aging Buckets -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Aging (Open & In Progress)</h2>
            <div class="space-y-4">
                <?php
                $agingColors = [
                    '0-7' => 'bg-green-500',
                    '8-14' => 'bg-yellow-500',
                    '15-30' => 'bg-orange-500',
                    '31+' => 'bg-red-500',
                ];
                $total = array_sum($agingBuckets ?? []) ?: 1;
                ?>
                <?php foreach ($agingBuckets ?? [] as $bucket => $count): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700"><?= $bucket ?> days</span>
                        <span class="text-gray-500"><?= $count ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="<?= $agingColors[$bucket] ?? 'bg-gray-500' ?> h-2 rounded-full" style="width: <?= ($count / $total) * 100 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">By Category</h2>
            <div class="space-y-3">
                <?php
                $categoryLabels = [
                    'process' => 'Process',
                    'tools' => 'Tools & Facilities',
                    'communication' => 'Communication',
                    'leadership' => 'Leadership',
                    'benefits' => 'Benefits & Policy',
                    'other' => 'Other',
                ];
                $catTotal = array_sum($categoryCounts ?? []) ?: 1;
                $catColors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500', 'bg-pink-500', 'bg-gray-500'];
                $i = 0;
                ?>
                <?php foreach ($categoryLabels as $key => $label): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full <?= $catColors[$i++ % count($catColors)] ?> mr-2"></div>
                        <span class="text-sm text-gray-700"><?= $label ?></span>
                    </div>
                    <span class="text-sm font-medium text-gray-900"><?= $categoryCounts[$key] ?? 0 ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Owner Workload -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Owner Workload</h2>
            <?php if (empty($ownerWorkload)): ?>
            <p class="text-sm text-gray-500">No assigned items</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($ownerWorkload as $owner): ?>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700"><?= htmlspecialchars($owner['name']) ?></span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $owner['count'] > 10 ? 'bg-red-100 text-red-800' : ($owner['count'] > 5 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                        <?= $owner['count'] ?> active
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h2>
            <?php if (empty($recentActivity)): ?>
            <p class="text-sm text-gray-500">No recent activity</p>
            <?php else: ?>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                <?php foreach ($recentActivity as $activity): ?>
                <div class="flex items-start text-sm">
                    <span class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-primary-500"></span>
                    <div class="ml-3">
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($activity['moderator_name']) ?></span>
                        <span class="text-gray-600"><?= htmlspecialchars($activity['action']) ?></span>
                        <a href="/admin/posts/<?= $activity['post_id'] ?>" class="text-primary-600 hover:underline truncate block">
                            <?= htmlspecialchars(substr($activity['post_title'], 0, 40)) ?>...
                        </a>
                        <span class="text-gray-400 text-xs"><?= date('M j, g:i A', strtotime($activity['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="/admin/queue" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition">
            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <span class="mt-2 block text-sm font-medium text-gray-900">Moderation Queue</span>
        </a>
        <a href="/admin/queue?status=open" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition">
            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
            <span class="mt-2 block text-sm font-medium text-gray-900">Open Items</span>
        </a>
        <a href="/admin/export.csv" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition">
            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="mt-2 block text-sm font-medium text-gray-900">Export CSV</span>
        </a>
        <a href="/admin/users" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition">
            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span class="mt-2 block text-sm font-medium text-gray-900">Manage Users</span>
        </a>
    </div>
</div>
