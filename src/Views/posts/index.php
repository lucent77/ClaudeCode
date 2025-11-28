<?php
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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Browse Feedback</h1>
            <p class="mt-1 text-gray-600"><?= number_format($totalPosts ?? 0) ?> items</p>
        </div>
        <a href="/submit" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            Submit Feedback
        </a>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Filters Sidebar -->
        <div class="lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-lg shadow p-4 sticky top-4">
                <!-- Sort -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Sort By</h3>
                    <div class="space-y-2">
                        <?php foreach (['trending' => 'Trending', 'new' => 'Newest', 'unresolved' => 'Unresolved'] as $key => $label): ?>
                        <a href="?sort=<?= $key ?><?= $currentCategory ? '&category=' . $currentCategory : '' ?><?= $currentStatus ? '&status=' . $currentStatus : '' ?>"
                           class="block px-3 py-2 rounded-md text-sm <?= ($currentSort ?? 'trending') === $key ? 'bg-primary-100 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                            <?= $label ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Category</h3>
                    <div class="space-y-2">
                        <a href="?sort=<?= $currentSort ?? 'trending' ?><?= $currentStatus ? '&status=' . $currentStatus : '' ?>"
                           class="block px-3 py-2 rounded-md text-sm <?= empty($currentCategory) ? 'bg-primary-100 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                            All Categories
                        </a>
                        <?php foreach ($categories as $key => $label): ?>
                        <a href="?sort=<?= $currentSort ?? 'trending' ?>&category=<?= $key ?><?= $currentStatus ? '&status=' . $currentStatus : '' ?>"
                           class="flex justify-between items-center px-3 py-2 rounded-md text-sm <?= ($currentCategory ?? '') === $key ? 'bg-primary-100 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                            <span><?= $label ?></span>
                            <span class="text-xs text-gray-400"><?= $categoryCounts[$key] ?? 0 ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-3">Status</h3>
                    <div class="space-y-2">
                        <a href="?sort=<?= $currentSort ?? 'trending' ?><?= $currentCategory ? '&category=' . $currentCategory : '' ?>"
                           class="block px-3 py-2 rounded-md text-sm <?= empty($currentStatus) ? 'bg-primary-100 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                            All Statuses
                        </a>
                        <?php foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved'] as $key => $label): ?>
                        <a href="?sort=<?= $currentSort ?? 'trending' ?><?= $currentCategory ? '&category=' . $currentCategory : '' ?>&status=<?= $key ?>"
                           class="flex justify-between items-center px-3 py-2 rounded-md text-sm <?= ($currentStatus ?? '') === $key ? 'bg-primary-100 text-primary-700 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                            <span><?= $label ?></span>
                            <span class="text-xs text-gray-400"><?= $statusCounts[$key] ?? 0 ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts List -->
        <div class="flex-1">
            <?php if (empty($posts)): ?>
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No feedback found</h3>
                <p class="mt-2 text-gray-500">Be the first to share your thoughts!</p>
                <a href="/submit" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                    Submit Feedback
                </a>
            </div>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($posts as $post): ?>
                <article class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                    <a href="/posts/<?= htmlspecialchars($post['public_id']) ?>" class="block p-6">
                        <div class="flex items-start">
                            <!-- Vote count -->
                            <div class="flex-shrink-0 flex flex-col items-center mr-4">
                                <button type="button"
                                        class="vote-btn p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-primary-600"
                                        data-post-id="<?= htmlspecialchars($post['public_id']) ?>"
                                        onclick="event.preventDefault(); vote('<?= htmlspecialchars($post['public_id']) ?>')">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                                <span class="text-lg font-semibold text-gray-700 vote-count"><?= number_format($post['vote_count'] ?? 0) ?></span>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        <?= htmlspecialchars($categories[$post['category']] ?? $post['category']) ?>
                                    </span>
                                    <?php if ($post['label']): ?>
                                    <?php
                                    $labelColors = [
                                        'urgent' => 'bg-red-100 text-red-800',
                                        'compliance' => 'bg-purple-100 text-purple-800',
                                        'safety' => 'bg-orange-100 text-orange-800',
                                        'culture' => 'bg-blue-100 text-blue-800',
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $labelColors[$post['label']] ?? 'bg-gray-100 text-gray-800' ?>">
                                        <?= htmlspecialchars(ucfirst($post['label'])) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <h2 class="text-lg font-medium text-gray-900">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h2>

                                <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                                    <?= htmlspecialchars(substr($post['body'], 0, 200)) ?><?= strlen($post['body']) > 200 ? '...' : '' ?>
                                </p>

                                <div class="mt-3 flex items-center gap-4 text-sm text-gray-500">
                                    <?php
                                    $statusColors = [
                                        'open' => 'bg-blue-100 text-blue-800',
                                        'in_progress' => 'bg-yellow-100 text-yellow-800',
                                        'resolved' => 'bg-green-100 text-green-800',
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $statusColors[$post['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $post['status']))) ?>
                                    </span>

                                    <span class="flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <?= number_format($post['comment_count'] ?? 0) ?>
                                    </span>

                                    <span><?= date('M j, Y', strtotime($post['created_at'])) ?></span>

                                    <?php if ($post['owner_name']): ?>
                                    <span class="text-gray-400">Assigned to <?= htmlspecialchars($post['owner_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if (($totalPages ?? 1) > 1): ?>
            <nav class="mt-8 flex justify-center">
                <ul class="flex items-center space-x-1">
                    <?php if ($currentPage > 1): ?>
                    <li>
                        <a href="?page=<?= $currentPage - 1 ?>&sort=<?= $currentSort ?><?= $currentCategory ? '&category=' . $currentCategory : '' ?><?= $currentStatus ? '&status=' . $currentStatus : '' ?>"
                           class="px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:bg-gray-100">
                            Previous
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <li>
                        <a href="?page=<?= $i ?>&sort=<?= $currentSort ?><?= $currentCategory ? '&category=' . $currentCategory : '' ?><?= $currentStatus ? '&status=' . $currentStatus : '' ?>"
                           class="px-3 py-2 rounded-md text-sm font-medium <?= $i === $currentPage ? 'bg-primary-600 text-white' : 'text-gray-500 hover:bg-gray-100' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                    <li>
                        <a href="?page=<?= $currentPage + 1 ?>&sort=<?= $currentSort ?><?= $currentCategory ? '&category=' . $currentCategory : '' ?><?= $currentStatus ? '&status=' . $currentStatus : '' ?>"
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
    </div>
</div>

<script>
async function vote(publicId) {
    try {
        const response = await fetch(`/api/posts/${publicId}/votes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            // Find and update vote count
            const article = document.querySelector(`[data-post-id="${publicId}"]`).closest('article');
            const countEl = article.querySelector('.vote-count');
            countEl.textContent = data.vote_count;

            // Update button state
            const btn = article.querySelector('.vote-btn');
            if (data.voted) {
                btn.classList.add('text-primary-600');
                btn.classList.remove('text-gray-400');
            } else {
                btn.classList.remove('text-primary-600');
                btn.classList.add('text-gray-400');
            }
        }
    } catch (error) {
        console.error('Vote failed:', error);
    }
}
</script>
