<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Navigation -->
    <div class="flex items-center justify-between mb-6">
        <a href="/digests" class="text-sm text-gray-500 hover:text-gray-700">
            &larr; All digests
        </a>
        <div class="flex items-center space-x-4">
            <?php if ($prevDigest): ?>
            <a href="/digests/<?= $prevDigest['id'] ?>" class="text-sm text-gray-500 hover:text-gray-700">
                &larr; Previous
            </a>
            <?php endif; ?>
            <?php if ($nextDigest): ?>
            <a href="/digests/<?= $nextDigest['id'] ?>" class="text-sm text-gray-500 hover:text-gray-700">
                Next &rarr;
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header -->
    <div class="bg-primary-600 rounded-lg p-8 text-white mb-8">
        <h1 class="text-3xl font-bold">
            Week of <?= date('F j', strtotime($digest['week_start'])) ?> - <?= date('F j, Y', strtotime($digest['week_end'])) ?>
        </h1>
        <p class="mt-2 text-primary-100">
            Published <?= date('F j, Y', strtotime($digest['created_at'])) ?>
        </p>
    </div>

    <!-- Content -->
    <div class="bg-white rounded-lg shadow p-8">
        <div class="prose prose-primary max-w-none">
            <?php
            // Simple markdown to HTML conversion
            $content = $digest['summary'];

            // Headers
            $content = preg_replace('/^### (.+)$/m', '<h3 class="text-lg font-semibold text-gray-900 mt-6 mb-2">$1</h3>', $content);
            $content = preg_replace('/^## (.+)$/m', '<h2 class="text-xl font-bold text-gray-900 mt-8 mb-4">$1</h2>', $content);
            $content = preg_replace('/^# (.+)$/m', '<h1 class="text-2xl font-bold text-gray-900 mb-6">$1</h1>', $content);

            // Bold and italic
            $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
            $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);

            // Links
            $content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" class="text-primary-600 hover:underline">$1</a>', $content);

            // List items
            $content = preg_replace('/^- (.+)$/m', '<li class="ml-4">$1</li>', $content);

            // Paragraphs
            $content = preg_replace('/\n\n/', '</p><p class="mt-4">', $content);

            // Wrap in paragraph
            $content = '<p>' . $content . '</p>';

            // Clean up empty paragraphs
            $content = preg_replace('/<p>\s*<\/p>/', '', $content);

            echo $content;
            ?>
        </div>
    </div>

    <!-- Share -->
    <div class="mt-8 bg-gray-50 rounded-lg p-6">
        <h3 class="text-sm font-medium text-gray-900 mb-3">Share this update</h3>
        <div class="flex items-center space-x-4">
            <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied!')"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                Copy Link
            </button>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="mt-8 text-center">
        <p class="text-gray-600 mb-4">Have feedback to share?</p>
        <a href="/submit" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700">
            Submit Anonymous Feedback
        </a>
    </div>
</div>
