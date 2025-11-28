<?php
$categories = [
    'process' => 'Process',
    'tools' => 'Tools & Facilities',
    'communication' => 'Communication',
    'leadership' => 'Leadership',
    'benefits' => 'Benefits & Policy',
    'other' => 'Other',
];

$statusColors = [
    'open' => 'bg-blue-100 text-blue-800',
    'in_progress' => 'bg-yellow-100 text-yellow-800',
    'resolved' => 'bg-green-100 text-green-800',
];

$labelColors = [
    'urgent' => 'bg-red-100 text-red-800',
    'compliance' => 'bg-purple-100 text-purple-800',
    'safety' => 'bg-orange-100 text-orange-800',
    'culture' => 'bg-blue-100 text-blue-800',
];
?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <a href="/posts" class="text-sm text-gray-500 hover:text-gray-700">
            &larr; Back to all feedback
        </a>
    </nav>

    <!-- Main Post -->
    <article class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6 sm:p-8">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                            <?= htmlspecialchars($categories[$post['category']] ?? $post['category']) ?>
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusColors[$post['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $post['status']))) ?>
                        </span>
                        <?php if ($post['label']): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $labelColors[$post['label']] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= htmlspecialchars(ucfirst($post['label'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                        <?= htmlspecialchars($post['title']) ?>
                    </h1>
                </div>

                <!-- Vote Button -->
                <div class="ml-4 flex flex-col items-center">
                    <button type="button" id="vote-btn"
                            class="p-2 rounded-lg border-2 <?= $hasVoted ? 'border-primary-500 bg-primary-50 text-primary-600' : 'border-gray-200 text-gray-400 hover:border-primary-300 hover:text-primary-500' ?> transition"
                            onclick="vote()">
                        <svg class="h-8 w-8" fill="<?= $hasVoted ? 'currentColor' : 'none' ?>" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                    <span id="vote-count" class="mt-1 text-xl font-bold text-gray-700"><?= number_format($voteCount ?? 0) ?></span>
                    <span class="text-xs text-gray-500">votes</span>
                </div>
            </div>

            <!-- Meta info -->
            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <span>Posted <?= date('F j, Y', strtotime($post['created_at'])) ?></span>
                <?php if ($post['department_hint']): ?>
                <span>Related to: <?= htmlspecialchars($post['department_hint']) ?></span>
                <?php endif; ?>
                <?php if ($post['owner_name']): ?>
                <span>Assigned to: <?= htmlspecialchars($post['owner_name']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="mt-6 prose prose-primary max-w-none">
                <?= nl2br(htmlspecialchars($post['body'])) ?>
            </div>

            <!-- Attachments -->
            <?php if (!empty($attachments)): ?>
            <div class="mt-6 border-t pt-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Attachments</h3>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($attachments as $attachment): ?>
                    <a href="<?= htmlspecialchars($attachment['path']) ?>"
                       target="_blank"
                       class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <?= htmlspecialchars($attachment['filename']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </article>

    <!-- Comments Section -->
    <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            Comments (<?= count($comments ?? []) ?>)
        </h2>

        <!-- Official responses first -->
        <?php
        $officialComments = array_filter($comments ?? [], fn($c) => $c['is_official']);
        $regularComments = array_filter($comments ?? [], fn($c) => !$c['is_official']);
        ?>

        <?php if (!empty($officialComments)): ?>
        <div class="space-y-4 mb-6">
            <?php foreach ($officialComments as $comment): ?>
            <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 sm:p-6">
                <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Official Response
                    </span>
                    <?php if ($comment['user_name']): ?>
                    <span class="ml-2 text-sm text-gray-600"><?= htmlspecialchars($comment['user_name']) ?></span>
                    <?php endif; ?>
                    <span class="ml-2 text-sm text-gray-500"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
                </div>
                <div class="prose prose-sm max-w-none">
                    <?= nl2br(htmlspecialchars($comment['body'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Regular comments -->
        <?php if (!empty($regularComments)): ?>
        <div class="space-y-4 mb-6">
            <?php foreach ($regularComments as $comment): ?>
            <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                <div class="flex items-center mb-2 text-sm text-gray-500">
                    <?php if ($comment['user_name']): ?>
                    <span class="font-medium text-gray-700"><?= htmlspecialchars($comment['user_name']) ?></span>
                    <span class="mx-1">&bull;</span>
                    <?php else: ?>
                    <span class="font-medium text-gray-500">Anonymous</span>
                    <span class="mx-1">&bull;</span>
                    <?php endif; ?>
                    <span><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
                </div>
                <div class="prose prose-sm max-w-none">
                    <?= nl2br(htmlspecialchars($comment['body'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
        <p class="text-gray-500 mb-6">No comments yet. Be the first to share your thoughts!</p>
        <?php endif; ?>

        <!-- Add comment form -->
        <div class="bg-white rounded-lg shadow p-4 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add a Comment</h3>
            <p class="text-sm text-gray-500 mb-4">Your comment will be anonymous.</p>
            <form id="comment-form" onsubmit="submitComment(event)">
                <textarea id="comment-body" name="body" rows="4" required minlength="10" maxlength="2000"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                          placeholder="Share your thoughts constructively..."></textarea>
                <div class="mt-3 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Post Comment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Related Posts -->
    <?php if (!empty($relatedPosts)): ?>
    <div class="mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Related Feedback</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <?php foreach ($relatedPosts as $related): ?>
            <a href="/posts/<?= htmlspecialchars($related['public_id']) ?>"
               class="bg-white rounded-lg shadow p-4 hover:shadow-md transition">
                <h3 class="font-medium text-gray-900 truncate"><?= htmlspecialchars($related['title']) ?></h3>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $statusColors[$related['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $related['status']))) ?>
                    </span>
                    <span class="text-gray-500"><?= number_format($related['vote_count'] ?? 0) ?> votes</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const publicId = '<?= htmlspecialchars($post['public_id']) ?>';

async function vote() {
    try {
        const response = await fetch(`/api/posts/${publicId}/votes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('vote-count').textContent = data.vote_count;
            const btn = document.getElementById('vote-btn');
            if (data.voted) {
                btn.classList.add('border-primary-500', 'bg-primary-50', 'text-primary-600');
                btn.classList.remove('border-gray-200', 'text-gray-400');
                btn.querySelector('svg').setAttribute('fill', 'currentColor');
            } else {
                btn.classList.remove('border-primary-500', 'bg-primary-50', 'text-primary-600');
                btn.classList.add('border-gray-200', 'text-gray-400');
                btn.querySelector('svg').setAttribute('fill', 'none');
            }
        }
    } catch (error) {
        console.error('Vote failed:', error);
    }
}

async function submitComment(event) {
    event.preventDefault();
    const body = document.getElementById('comment-body').value;

    try {
        const response = await fetch(`/api/posts/${publicId}/comments`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `body=${encodeURIComponent(body)}`,
        });

        const data = await response.json();

        if (data.success) {
            // Reload to show new comment
            window.location.reload();
        } else {
            alert(data.error || 'Failed to post comment');
        }
    } catch (error) {
        console.error('Comment failed:', error);
        alert('Failed to post comment. Please try again.');
    }
}
</script>
