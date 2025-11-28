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

<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <a href="/admin/queue" class="text-sm text-gray-500 hover:text-gray-700">
            &larr; Back to queue
        </a>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Post Details -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800">
                            <?= htmlspecialchars($categories[$post['category']] ?? $post['category']) ?>
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusLabels[$post['status']]['color'] ?? 'bg-gray-100 text-gray-800' ?>">
                            <?= $statusLabels[$post['status']]['label'] ?? $post['status'] ?>
                        </span>
                        <?php if ($post['label']): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                            <?= htmlspecialchars(ucfirst($post['label'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($post['title']) ?></h1>

                    <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <span>ID: <?= htmlspecialchars($post['public_id']) ?></span>
                        <span>Posted: <?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></span>
                        <span><?= $voteCount ?> votes</span>
                        <?php if ($post['department_hint']): ?>
                        <span>Dept: <?= htmlspecialchars($post['department_hint']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-6 prose max-w-none">
                        <?= nl2br(htmlspecialchars($post['body'])) ?>
                    </div>

                    <!-- Attachments -->
                    <?php if (!empty($attachments)): ?>
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">Attachments</h3>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach ($attachments as $attachment): ?>
                            <a href="<?= htmlspecialchars($attachment['path']) ?>" target="_blank"
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

                    <!-- Content Flags -->
                    <?php if (!empty($flags)): ?>
                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-sm font-medium text-red-800 mb-3">Content Flags</h3>
                        <div class="bg-red-50 rounded-md p-4">
                            <ul class="text-sm text-red-700 space-y-2">
                                <?php foreach ($flags as $flag): ?>
                                <li>
                                    <span class="font-medium"><?= htmlspecialchars($flag['rule_code']) ?>:</span>
                                    <code class="bg-red-100 px-1 rounded"><?= htmlspecialchars($flag['matched_snippet']) ?></code>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Moderation Actions (for queued posts) -->
                <?php if ($post['status'] === 'queued'): ?>
                <div class="bg-gray-50 px-6 py-4 flex flex-wrap gap-2">
                    <form action="/admin/posts/<?= $post['id'] ?>/moderate" method="POST" class="inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            Approve
                        </button>
                    </form>
                    <form action="/admin/posts/<?= $post['id'] ?>/moderate" method="POST" class="inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            Reject
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Comments -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-medium text-gray-900">Comments (<?= count($comments) ?>)</h2>
                </div>
                <div class="divide-y">
                    <?php if (empty($comments)): ?>
                    <div class="p-6 text-gray-500 text-sm">No comments yet.</div>
                    <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="p-6 <?= $comment['is_official'] ? 'bg-green-50' : '' ?>">
                        <div class="flex items-center gap-2 mb-2">
                            <?php if ($comment['is_official']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                Official
                            </span>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-900">
                                <?= $comment['user_name'] ? htmlspecialchars($comment['user_name']) : 'Anonymous' ?>
                            </span>
                            <?php if ($comment['user_role']): ?>
                            <span class="text-xs text-gray-500">(<?= htmlspecialchars($comment['user_role']) ?>)</span>
                            <?php endif; ?>
                            <span class="text-sm text-gray-500"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></span>
                        </div>
                        <p class="text-gray-700"><?= nl2br(htmlspecialchars($comment['body'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Add Comment -->
                <div class="px-6 py-4 border-t bg-gray-50">
                    <form action="/admin/posts/<?= $post['id'] ?>/comment" method="POST">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <textarea name="body" rows="3" required
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                  placeholder="Add a comment..."></textarea>
                        <div class="mt-3 flex items-center justify-between">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_official" value="1" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-600">Mark as Official Response</span>
                            </label>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                Post Comment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Audit Log -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-medium text-gray-900">Moderation History</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($auditLog)): ?>
                    <p class="text-gray-500 text-sm">No moderation actions yet.</p>
                    <?php else: ?>
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <?php foreach ($auditLog as $i => $log): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($i < count($auditLog) - 1): ?>
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                    <?php endif; ?>
                                    <div class="relative flex space-x-3">
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">
                                                <span class="font-medium"><?= htmlspecialchars($log['moderator_name']) ?></span>
                                                performed <span class="font-medium"><?= htmlspecialchars($log['action']) ?></span>
                                            </p>
                                            <?php if ($log['notes']): ?>
                                            <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($log['notes']) ?></p>
                                            <?php endif; ?>
                                            <?php if ($log['previous_status'] && $log['new_status']): ?>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Status: <?= $log['previous_status'] ?> &rarr; <?= $log['new_status'] ?>
                                            </p>
                                            <?php endif; ?>
                                            <p class="mt-1 text-xs text-gray-400"><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Update -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Update Status</h3>
                <form action="/admin/posts/<?= $post['id'] ?>/status" method="POST">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <select name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="open" <?= $post['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="in_progress" <?= $post['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="resolved" <?= $post['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="duplicate" <?= $post['status'] === 'duplicate' ? 'selected' : '' ?>>Duplicate</option>
                    </select>
                    <button type="submit" class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        Update Status
                    </button>
                </form>
            </div>

            <!-- Assignment -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Assignment</h3>
                <form action="/admin/posts/<?= $post['id'] ?>/assign" method="POST">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 mb-1">Owner</label>
                        <select name="owner_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($post['owner_id'] ?? 0) == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?> (<?= $user['role'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" value="<?= $post['due_date'] ?? '' ?>"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 mb-1">Label</label>
                        <select name="label" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <option value="">None</option>
                            <option value="urgent" <?= ($post['label'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            <option value="compliance" <?= ($post['label'] ?? '') === 'compliance' ? 'selected' : '' ?>>Compliance</option>
                            <option value="safety" <?= ($post['label'] ?? '') === 'safety' ? 'selected' : '' ?>>Safety</option>
                            <option value="culture" <?= ($post['label'] ?? '') === 'culture' ? 'selected' : '' ?>>Culture</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        Update Assignment
                    </button>
                </form>
            </div>

            <!-- Quick Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Quick Info</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created</dt>
                        <dd class="text-gray-900"><?= date('M j, Y', strtotime($post['created_at'])) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="text-gray-900"><?= date('M j, Y', strtotime($post['updated_at'])) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Votes</dt>
                        <dd class="text-gray-900"><?= number_format($voteCount) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Comments</dt>
                        <dd class="text-gray-900"><?= count($comments) ?></dd>
                    </div>
                    <?php if ($post['due_date']): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Due Date</dt>
                        <dd class="text-gray-900 <?= strtotime($post['due_date']) < time() ? 'text-red-600' : '' ?>">
                            <?= date('M j, Y', strtotime($post['due_date'])) ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Public Link -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Public Link</h3>
                <?php if (in_array($post['status'], ['open', 'in_progress', 'resolved'])): ?>
                <a href="/posts/<?= htmlspecialchars($post['public_id']) ?>" target="_blank"
                   class="text-primary-600 hover:underline text-sm break-all">
                    /posts/<?= htmlspecialchars($post['public_id']) ?>
                </a>
                <?php else: ?>
                <p class="text-sm text-gray-500">Not publicly visible (<?= $post['status'] ?>)</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
