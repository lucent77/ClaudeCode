<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900">Weekly Updates</h1>
        <p class="mt-2 text-lg text-gray-600">
            See what we heard and what we did
        </p>
    </div>

    <?php if (empty($digests)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No digests yet</h3>
        <p class="mt-2 text-gray-500">Weekly summaries will appear here once they're generated.</p>
    </div>
    <?php else: ?>

    <div class="space-y-6">
        <?php foreach ($digests as $digest): ?>
        <a href="/digests/<?= $digest['id'] ?>" class="block bg-white rounded-lg shadow hover:shadow-md transition p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        Week of <?= date('F j', strtotime($digest['week_start'])) ?> - <?= date('F j, Y', strtotime($digest['week_end'])) ?>
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Published <?= date('M j, Y', strtotime($digest['created_at'])) ?>
                    </p>
                </div>
                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
            <div class="mt-4 text-gray-600 line-clamp-3">
                <?php
                // Extract first paragraph from markdown
                $summary = $digest['summary'];
                $lines = explode("\n", $summary);
                $preview = '';
                foreach ($lines as $line) {
                    if (strpos($line, '#') === false && trim($line) !== '' && strpos($line, '-') !== 0) {
                        $preview = trim($line);
                        break;
                    }
                }
                echo htmlspecialchars($preview ?: 'View the full summary...');
                ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>
