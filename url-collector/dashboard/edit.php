<?php
/**
 * URL Collector - Edit Link Details
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config/config.php';

require_once APP_ROOT . '/lib/db.php';
require_once APP_ROOT . '/lib/security.php';
require_once APP_ROOT . '/lib/util.php';

// Initialize
Database::init($config['db']);
Security::init($config);
Util::init($config);
Security::startSession();

// Require login
Security::requireLogin('login.php');

// Get link ID
$id = Security::sanitizeInt($_GET['id'] ?? null, 1);

if (!$id) {
    Util::setFlash('error', 'Invalid link ID');
    Util::redirect('index.php');
}

// Fetch link
$link = Database::fetch('SELECT * FROM links WHERE id = ?', [$id]);

if (!$link) {
    Util::setFlash('error', 'Link not found');
    Util::redirect('index.php');
}

// CSRF token
$csrfToken = Security::generateCsrfToken();

// Parse keywords
$keywords = $link['keywords'] ? json_decode($link['keywords'], true) : [];

// Helper
$e = fn($s) => Security::escape((string)$s);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Link - URL Collector</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔗</text></svg>">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-40">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <h1 class="text-xl font-bold text-gray-900">Edit Link</h1>
                </div>
                <a href="<?= $e($link['url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                    Open Link
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Link Preview -->
        <div class="bg-white rounded-xl shadow-sm border mb-6 overflow-hidden">
            <?php if (!empty($link['image_url'])): ?>
            <div class="h-48 bg-gray-100">
                <img src="<?= $e($link['image_url']) ?>" alt=""
                     class="w-full h-full object-cover"
                     onerror="this.parentElement.style.display='none'">
            </div>
            <?php endif; ?>

            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">
                    <?= $e($link['title'] ?: 'Untitled') ?>
                </h2>
                <p class="text-sm text-gray-500 mb-4">
                    <span class="font-medium"><?= $e($link['site']) ?></span>
                    •
                    Added <?= Util::timeAgo($link['created_at']) ?>
                    <?php if ($link['analyzed_at']): ?>
                    • Analyzed <?= Util::timeAgo($link['analyzed_at']) ?>
                    <?php endif; ?>
                </p>

                <?php if (!empty($link['description'])): ?>
                <p class="text-gray-600 mb-4"><?= $e($link['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($link['summary'])): ?>
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">AI Summary</h3>
                    <p class="text-sm text-blue-800"><?= $e($link['summary']) ?></p>
                </div>
                <?php endif; ?>

                <!-- Current Status -->
                <div class="flex flex-wrap gap-2">
                    <span class="px-3 py-1 text-sm font-medium rounded-full <?= Util::getStatusColor($link['status']) ?>">
                        Status: <?= ucfirst($e($link['status'])) ?>
                    </span>
                    <span class="px-3 py-1 text-sm font-medium rounded-full <?= Util::getReviewStatusColor($link['review_status']) ?>">
                        Review: <?= ucfirst($e($link['review_status'])) ?>
                    </span>
                    <?php if ($link['category']): ?>
                    <span class="px-3 py-1 text-sm font-medium rounded-full <?= Util::getCategoryColor($link['category']) ?>">
                        <?= ucfirst($e($link['category'])) ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Keywords -->
                <?php if (!empty($keywords)): ?>
                <div class="mt-4">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Keywords</h4>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($keywords as $kw): ?>
                        <span class="px-2 py-1 text-sm bg-gray-100 text-gray-700 rounded-full"><?= $e($kw) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if ($link['status'] === 'error' && $link['error_message']): ?>
                <div class="mt-4 bg-red-50 border border-red-100 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-red-900 mb-1">Error</h4>
                    <p class="text-sm text-red-700"><?= $e($link['error_message']) ?></p>
                    <p class="text-xs text-red-500 mt-2">Retry count: <?= $link['retry_count'] ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Edit Details</h3>

            <form method="POST" action="update.php" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                <input type="hidden" name="redirect" value="edit.php?id=<?= $link['id'] ?>">

                <!-- Review Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Review Status</label>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach (['inbox' => '📥 Inbox', 'keep' => '⭐ Keep', 'archive' => '📦 Archive'] as $value => $label): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="review_status" value="<?= $value ?>"
                                   <?= $link['review_status'] === $value ? 'checked' : '' ?>
                                   class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <span class="text-sm text-gray-700"><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Interest Score -->
                <div>
                    <label for="interest_score" class="block text-sm font-medium text-gray-700 mb-2">
                        Interest Score (0-100)
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="range" id="score_slider" min="0" max="100"
                               value="<?= $link['interest_score'] ?? ($link['auto_score'] ?? 50) ?>"
                               class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                               oninput="document.getElementById('interest_score').value = this.value; updateScoreDisplay(this.value)">
                        <input type="number" name="interest_score" id="interest_score"
                               value="<?= $link['interest_score'] ?? '' ?>"
                               min="0" max="100" placeholder="Score"
                               class="w-20 px-3 py-2 border rounded-lg text-center focus:ring-2 focus:ring-blue-500"
                               oninput="document.getElementById('score_slider').value = this.value; updateScoreDisplay(this.value)">
                    </div>
                    <?php if ($link['auto_score'] !== null): ?>
                    <p class="text-xs text-gray-500 mt-1">AI suggested score: <?= $link['auto_score'] ?></p>
                    <?php endif; ?>
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" id="category"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select category...</option>
                        <?php foreach ($config['categories'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= $link['category'] === $cat ? 'selected' : '' ?>>
                            <?= ucfirst($cat) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                    <textarea name="note" id="note" rows="4"
                              placeholder="Add your notes here..."
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= $e($link['note'] ?? '') ?></textarea>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center pt-4 border-t">
                    <a href="index.php" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Metadata (Read Only) -->
        <div class="bg-white rounded-xl shadow-sm border p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Technical Details</h3>

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">ID</dt>
                    <dd class="font-mono text-gray-900"><?= $link['id'] ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">URL Hash</dt>
                    <dd class="font-mono text-gray-900 text-xs break-all"><?= $e($link['url_hash']) ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Source</dt>
                    <dd class="text-gray-900"><?= $e($link['source'] ?: 'Unknown') ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Created</dt>
                    <dd class="text-gray-900"><?= Util::formatDate($link['created_at'], 'Y-m-d H:i:s') ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Analyzed</dt>
                    <dd class="text-gray-900"><?= Util::formatDate($link['analyzed_at'], 'Y-m-d H:i:s') ?></dd>
                </div>
                <div>
                    <dt class="text-gray-500">Reviewed</dt>
                    <dd class="text-gray-900"><?= Util::formatDate($link['reviewed_at'], 'Y-m-d H:i:s') ?></dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="text-gray-500">Full URL</dt>
                    <dd class="font-mono text-xs text-gray-900 break-all"><?= $e($link['url']) ?></dd>
                </div>
            </dl>

            <!-- Retry Analysis Button -->
            <?php if ($link['status'] === 'error' || $link['status'] === 'done'): ?>
            <form method="POST" action="update.php" class="mt-6 pt-4 border-t">
                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                <input type="hidden" name="reset_analysis" value="1">
                <input type="hidden" name="redirect" value="edit.php?id=<?= $link['id'] ?>">
                <button type="submit"
                        class="px-4 py-2 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-sm font-medium hover:bg-yellow-100 transition"
                        onclick="return confirm('This will re-queue the URL for analysis. Continue?')">
                    🔄 Re-analyze
                </button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function updateScoreDisplay(value) {
            // Could add visual feedback here
        }
    </script>
</body>
</html>
