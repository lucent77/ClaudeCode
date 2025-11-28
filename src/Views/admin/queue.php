<?php
$statusLabels = [
    'queued' => ['label' => 'In Queue', 'color' => 'bg-yellow-100 text-yellow-800'],
    'open' => ['label' => 'Open', 'color' => 'bg-blue-100 text-blue-800'],
    'in_progress' => ['label' => 'In Progress', 'color' => 'bg-orange-100 text-orange-800'],
    'resolved' => ['label' => 'Resolved', 'color' => 'bg-green-100 text-green-800'],
    'rejected' => ['label' => 'Rejected', 'color' => 'bg-red-100 text-red-800'],
    'duplicate' => ['label' => 'Duplicate', 'color' => 'bg-gray-100 text-gray-800'],
];

$categories = [
    'process' => 'Process',
    'tools' => 'Tools & Facilities',
    'communication' => 'Communication',
    'leadership' => 'Leadership',
    'benefits' => 'Benefits & Policy',
    'other' => 'Other',
];
?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Moderation Queue</h1>
            <p class="mt-1 text-gray-600"><?= number_format($totalPosts ?? 0) ?> items</p>
        </div>
        <a href="/admin" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Dashboard</a>
    </div>

    <!-- Status Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8 overflow-x-auto">
            <?php foreach ($statusLabels as $key => $status): ?>
            <a href="/admin/queue?status=<?= $key ?>"
               class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?= ($currentStatus ?? 'queued') === $key ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                <?= $status['label'] ?>
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs <?= ($currentStatus ?? 'queued') === $key ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-600' ?>">
                    <?= $statusCounts[$key] ?? 0 ?>
                </span>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php if (empty($posts)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No items in this queue</h3>
        <p class="mt-2 text-gray-500">All caught up! Check other tabs for more items.</p>
    </div>
    <?php else: ?>

    <!-- Posts List -->
    <div class="space-y-4">
        <?php foreach ($posts as $post): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                <?= htmlspecialchars($categories[$post['category']] ?? $post['category']) ?>
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusLabels[$post['status']]['color'] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= $statusLabels[$post['status']]['label'] ?? $post['status'] ?>
                            </span>
                            <?php if ($post['flag_count'] > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <?= $post['flag_count'] ?> flag(s)
                            </span>
                            <?php endif; ?>
                            <?php if ($post['label']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <?= htmlspecialchars(ucfirst($post['label'])) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Title & Content -->
                        <h3 class="text-lg font-medium text-gray-900">
                            <a href="/admin/posts/<?= $post['id'] ?>" class="hover:text-primary-600">
                                <?= htmlspecialchars($post['title']) ?>
                            </a>
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                            <?= htmlspecialchars(substr($post['body'], 0, 300)) ?>...
                        </p>

                        <!-- Flags -->
                        <?php if (!empty($flags[$post['id']])): ?>
                        <div class="mt-3 p-3 bg-red-50 rounded-md">
                            <p class="text-sm font-medium text-red-800 mb-1">Content Flags:</p>
                            <ul class="text-sm text-red-700 space-y-1">
                                <?php foreach ($flags[$post['id']] as $flag): ?>
                                <li>
                                    <span class="font-medium"><?= htmlspecialchars($flag['rule_code']) ?>:</span>
                                    <code class="bg-red-100 px-1 rounded"><?= htmlspecialchars($flag['matched_snippet']) ?></code>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Meta -->
                        <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                            <span><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></span>
                            <span><?= $post['vote_count'] ?> votes</span>
                            <span><?= $post['comment_count'] ?> comments</span>
                            <?php if ($post['owner_name']): ?>
                            <span>Assigned to: <?= htmlspecialchars($post['owner_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($post['department_hint']): ?>
                            <span>Dept: <?= htmlspecialchars($post['department_hint']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="ml-4 flex-shrink-0">
                        <a href="/admin/posts/<?= $post['id'] ?>"
                           class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            View Details
                        </a>
                    </div>
                </div>

                <!-- Action Buttons (for queued items) -->
                <?php if ($post['status'] === 'queued'): ?>
                <div class="mt-4 pt-4 border-t flex flex-wrap gap-2">
                    <form action="/admin/posts/<?= $post['id'] ?>/moderate" method="POST" class="inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            Approve
                        </button>
                    </form>
                    <form action="/admin/posts/<?= $post['id'] ?>/moderate" method="POST" class="inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            Reject
                        </button>
                    </form>
                    <form action="/admin/posts/<?= $post['id'] ?>/moderate" method="POST" class="inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>">
                        <input type="hidden" name="action" value="ask_revision">
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Ask Revision
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if (($totalPages ?? 1) > 1): ?>
    <nav class="mt-8 flex justify-center">
        <ul class="flex items-center space-x-1">
            <?php if ($currentPage > 1): ?>
            <li>
                <a href="?status=<?= $currentStatus ?>&page=<?= $currentPage - 1 ?>"
                   class="px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:bg-gray-100">
                    Previous
                </a>
            </li>
            <?php endif; ?>

            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
            <li>
                <a href="?status=<?= $currentStatus ?>&page=<?= $i ?>"
                   class="px-3 py-2 rounded-md text-sm font-medium <?= $i === $currentPage ? 'bg-primary-600 text-white' : 'text-gray-500 hover:bg-gray-100' ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
            <li>
                <a href="?status=<?= $currentStatus ?>&page=<?= $currentPage + 1 ?>"
                   class="px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:bg-gray-100">
                    Next
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
</div>
