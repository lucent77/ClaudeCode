<?php
/**
 * URL Collector - Dashboard Main Page
 *
 * Lists all URLs with filtering, sorting, and quick actions
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

// CSRF token
$csrfToken = Security::generateCsrfToken();

// Get filter parameters
$tab = $_GET['tab'] ?? 'inbox';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'default';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Valid tabs
$validTabs = ['all', 'inbox', 'keep', 'archive', 'error', 'pending'];
if (!in_array($tab, $validTabs)) {
    $tab = 'inbox';
}

// Build WHERE clause
$where = ['1=1'];
$params = [];

// Tab filters
switch ($tab) {
    case 'inbox':
        $where[] = 'review_status = ?';
        $params[] = 'inbox';
        break;
    case 'keep':
        $where[] = 'review_status = ?';
        $params[] = 'keep';
        break;
    case 'archive':
        $where[] = 'review_status = ?';
        $params[] = 'archive';
        break;
    case 'error':
        $where[] = 'status = ?';
        $params[] = 'error';
        break;
    case 'pending':
        $where[] = 'status = ?';
        $params[] = 'pending';
        break;
}

// Category filter
if (!empty($category) && in_array($category, $config['categories'])) {
    $where[] = 'category = ?';
    $params[] = $category;
}

// Status filter
if (!empty($status) && in_array($status, ['pending', 'done', 'error'])) {
    $where[] = 'status = ?';
    $params[] = $status;
}

// Search filter
if (!empty($search)) {
    $where[] = '(title LIKE ? OR summary LIKE ? OR url LIKE ? OR note LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = implode(' AND ', $where);

// Count total
$total = Database::count('links', $whereClause, $params);
$pagination = Util::paginate($total, $page, $perPage);

// Sort order
$orderBy = match ($sort) {
    'score_desc' => 'COALESCE(interest_score, -1) DESC, COALESCE(auto_score, -1) DESC, created_at DESC',
    'score_asc' => 'COALESCE(interest_score, 999) ASC, COALESCE(auto_score, 999) ASC, created_at DESC',
    'oldest' => 'created_at ASC',
    'analyzed' => 'analyzed_at DESC',
    'reviewed' => 'reviewed_at DESC',
    default => match ($tab) {
        'inbox' => 'COALESCE(interest_score, -1) DESC, COALESCE(auto_score, -1) DESC, created_at DESC',
        'keep' => 'COALESCE(interest_score, -1) DESC, reviewed_at DESC',
        'archive' => 'reviewed_at DESC, created_at DESC',
        default => 'created_at DESC',
    },
};

// Fetch links
$links = Database::fetchAll(
    "SELECT * FROM links WHERE {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $pagination['offset']])
);

// Get counts for tabs
$tabCounts = [
    'all' => Database::count('links'),
    'inbox' => Database::count('links', 'review_status = ?', ['inbox']),
    'keep' => Database::count('links', 'review_status = ?', ['keep']),
    'archive' => Database::count('links', 'review_status = ?', ['archive']),
    'error' => Database::count('links', 'status = ?', ['error']),
    'pending' => Database::count('links', 'status = ?', ['pending']),
];

// Flash message
$flash = Util::getFlash();

// Helper function
$e = fn($s) => Security::escape((string)$s);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - URL Collector</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔗</text></svg>">
    <style>
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🔗</span>
                    <h1 class="text-xl font-bold text-gray-900">URL Collector</h1>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500"><?= $e($_SESSION['login_time'] ? 'Logged in' : '') ?></span>
                    <a href="logout.php" class="text-sm text-red-600 hover:text-red-800 font-medium">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($flash): ?>
        <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
            <?= $e($flash['message']) ?>
        </div>
        <?php endif; ?>

        <!-- Stats & Tabs -->
        <div class="bg-white rounded-xl shadow-sm border mb-6">
            <div class="p-4 border-b">
                <div class="flex flex-wrap gap-2">
                    <?php
                    $tabConfig = [
                        'inbox' => ['label' => 'Inbox', 'icon' => '📥', 'color' => 'blue'],
                        'keep' => ['label' => 'Keep', 'icon' => '⭐', 'color' => 'green'],
                        'archive' => ['label' => 'Archive', 'icon' => '📦', 'color' => 'gray'],
                        'pending' => ['label' => 'Pending', 'icon' => '⏳', 'color' => 'yellow'],
                        'error' => ['label' => 'Errors', 'icon' => '❌', 'color' => 'red'],
                        'all' => ['label' => 'All', 'icon' => '📋', 'color' => 'slate'],
                    ];
                    foreach ($tabConfig as $key => $cfg):
                        $isActive = $tab === $key;
                        $count = $tabCounts[$key];
                        $activeClass = $isActive ? "bg-{$cfg['color']}-100 text-{$cfg['color']}-800 border-{$cfg['color']}-300" : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100';
                    ?>
                    <a href="?tab=<?= $key ?>"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border text-sm font-medium transition <?= $activeClass ?>">
                        <span><?= $cfg['icon'] ?></span>
                        <span><?= $cfg['label'] ?></span>
                        <span class="px-2 py-0.5 rounded-full text-xs <?= $isActive ? 'bg-white/50' : 'bg-gray-200' ?>">
                            <?= $count ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filters -->
            <div class="p-4 bg-gray-50/50">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="tab" value="<?= $e($tab) ?>">

                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                        <input type="text" name="search" value="<?= $e($search) ?>"
                               placeholder="Title, URL, summary..."
                               class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                        <select name="category" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            <?php foreach ($config['categories'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Default</option>
                            <option value="score_desc" <?= $sort === 'score_desc' ? 'selected' : '' ?>>Score (High)</option>
                            <option value="score_asc" <?= $sort === 'score_asc' ? 'selected' : '' ?>>Score (Low)</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="analyzed" <?= $sort === 'analyzed' ? 'selected' : '' ?>>Recently Analyzed</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                        Filter
                    </button>

                    <?php if (!empty($search) || !empty($category) || $sort !== 'default'): ?>
                    <a href="?tab=<?= $e($tab) ?>" class="px-4 py-2 text-gray-600 hover:text-gray-900 text-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Links List -->
        <div class="space-y-4">
            <?php if (empty($links)): ?>
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                <div class="text-4xl mb-4">📭</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No links found</h3>
                <p class="text-gray-500">Try adjusting your filters or add some URLs.</p>
            </div>
            <?php else: ?>
            <?php foreach ($links as $link): ?>
            <div class="bg-white rounded-xl shadow-sm border hover:shadow-md transition-shadow">
                <div class="p-4">
                    <div class="flex gap-4">
                        <!-- Thumbnail/Favicon -->
                        <div class="flex-shrink-0">
                            <?php if (!empty($link['image_url'])): ?>
                            <img src="<?= $e($link['image_url']) ?>" alt=""
                                 class="w-20 h-20 object-cover rounded-lg bg-gray-100"
                                 onerror="this.onerror=null; this.src='<?= Util::getFaviconUrl($link['site'] ?? '') ?>'">
                            <?php else: ?>
                            <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                <img src="<?= Util::getFaviconUrl($link['site'] ?? '') ?>" alt="" class="w-8 h-8">
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <a href="<?= $e($link['url']) ?>" target="_blank" rel="noopener noreferrer"
                                       class="text-lg font-semibold text-gray-900 hover:text-blue-600 line-clamp-2">
                                        <?= $e($link['title'] ?: $link['url']) ?>
                                    </a>
                                    <div class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                                        <span><?= $e($link['site']) ?></span>
                                        <span>•</span>
                                        <span><?= Util::timeAgo($link['created_at']) ?></span>
                                    </div>
                                </div>

                                <!-- Score Display -->
                                <div class="flex-shrink-0 text-right">
                                    <?php if ($link['interest_score'] !== null): ?>
                                    <div class="text-2xl font-bold <?= Util::getScoreColor($link['interest_score']) ?>">
                                        <?= $link['interest_score'] ?>
                                    </div>
                                    <div class="text-xs text-gray-400">Score</div>
                                    <?php elseif ($link['auto_score'] !== null): ?>
                                    <div class="text-xl font-medium text-gray-400">
                                        <?= $link['auto_score'] ?>
                                    </div>
                                    <div class="text-xs text-gray-400">Auto</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($link['summary'])): ?>
                            <p class="mt-2 text-sm text-gray-600 line-clamp-2"><?= $e($link['summary']) ?></p>
                            <?php endif; ?>

                            <!-- Tags & Badges -->
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <?php if ($link['category']): ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= Util::getCategoryColor($link['category']) ?>">
                                    <?= ucfirst($e($link['category'])) ?>
                                </span>
                                <?php endif; ?>

                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= Util::getStatusColor($link['status']) ?>">
                                    <?= ucfirst($e($link['status'])) ?>
                                </span>

                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= Util::getReviewStatusColor($link['review_status']) ?>">
                                    <?= ucfirst($e($link['review_status'])) ?>
                                </span>

                                <?php
                                $keywords = $link['keywords'] ? json_decode($link['keywords'], true) : [];
                                if (!empty($keywords)):
                                    foreach (array_slice($keywords, 0, 3) as $kw):
                                ?>
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full"><?= $e($kw) ?></span>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex items-center justify-between mt-4 pt-4 border-t">
                        <div class="flex items-center gap-2">
                            <?php if ($link['review_status'] !== 'keep'): ?>
                            <form method="POST" action="update.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                <input type="hidden" name="review_status" value="keep">
                                <input type="hidden" name="redirect" value="<?= $e($_SERVER['REQUEST_URI']) ?>">
                                <button type="submit" class="px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-sm font-medium hover:bg-green-100 transition flex items-center gap-1">
                                    <span>⭐</span> Keep
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if ($link['review_status'] !== 'archive'): ?>
                            <form method="POST" action="update.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                <input type="hidden" name="review_status" value="archive">
                                <input type="hidden" name="redirect" value="<?= $e($_SERVER['REQUEST_URI']) ?>">
                                <button type="submit" class="px-3 py-1.5 bg-gray-50 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition flex items-center gap-1">
                                    <span>📦</span> Archive
                                </button>
                            </form>
                            <?php endif; ?>

                            <!-- Quick Score Update -->
                            <form method="POST" action="update.php" class="inline flex items-center gap-1">
                                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                <input type="hidden" name="redirect" value="<?= $e($_SERVER['REQUEST_URI']) ?>">
                                <input type="number" name="interest_score"
                                       value="<?= $link['interest_score'] ?? '' ?>"
                                       min="0" max="100" placeholder="Score"
                                       class="w-16 px-2 py-1.5 border rounded-lg text-sm text-center focus:ring-2 focus:ring-blue-500">
                                <button type="submit" class="px-2 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm hover:bg-blue-100 transition">
                                    Set
                                </button>
                            </form>
                        </div>

                        <a href="edit.php?id=<?= $link['id'] ?>"
                           class="px-3 py-1.5 text-gray-500 hover:text-gray-700 text-sm font-medium hover:bg-gray-50 rounded-lg transition">
                            Edit Details →
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="flex items-center gap-1">
                <?php if ($pagination['has_prev']): ?>
                <a href="<?= Util::buildUrl('', array_merge($_GET, ['page' => $pagination['prev_page']])) ?>"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-50 transition">
                    Previous
                </a>
                <?php endif; ?>

                <span class="px-4 py-2 text-sm text-gray-500">
                    Page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?>
                </span>

                <?php if ($pagination['has_next']): ?>
                <a href="<?= Util::buildUrl('', array_merge($_GET, ['page' => $pagination['next_page']])) ?>"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-50 transition">
                    Next
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

        <!-- Footer Stats -->
        <div class="mt-8 text-center text-sm text-gray-500">
            Showing <?= count($links) ?> of <?= $total ?> links
        </div>
    </main>
</body>
</html>
