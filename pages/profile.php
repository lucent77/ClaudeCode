<?php
/**
 * Profile Page
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: profile.php");
    exit;
}

Auth::require();

$user = Auth::user();
$userId = Auth::id();

// Get user stats
$stats = User::getStats($userId);

// Get level info
$levelInfo = Database::fetch(
    "SELECT * FROM levels WHERE level = ?",
    [$user['level']]
);

$nextLevelInfo = Database::fetch(
    "SELECT * FROM levels WHERE level = ?",
    [$user['level'] + 1]
);

// Get recent achievements
$recentAchievements = Database::fetchAll(
    "SELECT a.*, ua.earned_at
     FROM user_achievements ua
     JOIN achievements a ON ua.achievement_id = a.id
     WHERE ua.user_id = ?
     ORDER BY ua.earned_at DESC
     LIMIT 6",
    [$userId]
);

// Get active goal
$activeGoal = Goal::getActiveByUser($userId);

// Get recent activity
$recentLogs = Database::fetchAll(
    "SELECT log_date, execution_score, points_earned, completed_tasks
     FROM daily_logs
     WHERE user_id = ?
     ORDER BY log_date DESC
     LIMIT 7",
    [$userId]
);

// Calculate progress to next level
$progressToNextLevel = 0;
if ($nextLevelInfo && $levelInfo) {
    $currentProgress = $user['points'] - $levelInfo['min_points'];
    $levelRange = $nextLevelInfo['min_points'] - $levelInfo['min_points'];
    $progressToNextLevel = ($currentProgress / $levelRange) * 100;
}

$pageTitle = __('profile');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Profile Header -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl p-6 text-white mb-6">
        <div class="flex items-center space-x-6">
            <!-- Avatar -->
            <div class="relative">
                <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center text-4xl font-bold">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div class="absolute -bottom-2 -right-2 w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold"
                     style="background-color: <?= $levelInfo['color'] ?>">
                    <?= $user['level'] ?>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1">
                <h1 class="text-2xl font-bold"><?= e($user['name']) ?></h1>
                <p class="text-primary-100">@<?= e($user['username']) ?></p>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="flex items-center text-sm">
                        <i data-feather="calendar" class="w-4 h-4 mr-1"></i>
                        <?= localDate($user['created_at']) ?>
                    </span>
                    <?php if ($stats['streak_days'] > 0): ?>
                    <span class="flex items-center text-sm bg-white/20 px-2 py-1 rounded">
                        <i data-feather="flame" class="w-4 h-4 mr-1 text-orange-300"></i>
                        <?= $stats['streak_days'] ?> <?= __('streak_days') ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="hidden md:flex items-center space-x-6">
                <div class="text-center">
                    <p class="text-3xl font-bold"><?= number_format($stats['points']) ?></p>
                    <p class="text-sm text-primary-100"><?= __('points') ?></p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold"><?= $stats['badge_count'] ?></p>
                    <p class="text-sm text-primary-100"><?= __('badges') ?></p>
                </div>
            </div>
        </div>

        <!-- Level Progress -->
        <?php if ($nextLevelInfo): ?>
        <div class="mt-6">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="flex items-center">
                    <span class="w-4 h-4 rounded mr-1" style="background-color: <?= $levelInfo['color'] ?>"></span>
                    Lv.<?= $user['level'] ?> <?= getCurrentLanguage() === 'ko' ? $levelInfo['name_ko'] : $levelInfo['name_en'] ?>
                </span>
                <span>
                    Lv.<?= $user['level'] + 1 ?> <?= getCurrentLanguage() === 'ko' ? $nextLevelInfo['name_ko'] : $nextLevelInfo['name_en'] ?>
                </span>
            </div>
            <div class="h-3 bg-white/20 rounded-full overflow-hidden">
                <div class="h-full bg-white rounded-full transition-all" style="width: <?= $progressToNextLevel ?>%"></div>
            </div>
            <p class="text-sm text-primary-100 mt-1 text-center">
                <?= __('points_to_next_level', ['points' => number_format($nextLevelInfo['min_points'] - $user['points'])]) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm text-center">
                    <p class="text-2xl font-bold text-primary-600"><?= number_format($stats['total_tasks_completed']) ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('tasks') ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm text-center">
                    <p class="text-2xl font-bold text-green-600"><?= $stats['weekly_avg_score'] ?>%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('weekly_score') ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm text-center">
                    <p class="text-2xl font-bold text-orange-600"><?= $stats['streak_days'] ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('streak_days') ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm text-center">
                    <p class="text-2xl font-bold text-purple-600"><?= $stats['badge_count'] ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('badges') ?></p>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('this_week') ?></h2>
                </div>
                <div class="p-4">
                    <?php if (empty($recentLogs)): ?>
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4"><?= __('none') ?></p>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recentLogs as $log): ?>
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $log['execution_score'] >= 70 ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : ($log['execution_score'] >= 50 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600' : 'bg-red-100 dark:bg-red-900/30 text-red-600') ?>">
                                    <?= number_format($log['execution_score'], 0) ?>%
                                </div>
                                <div>
                                    <p class="font-medium"><?= localDate($log['log_date']) ?></p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= $log['completed_tasks'] ?> <?= __('tasks') ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-yellow-600">+<?= $log['points_earned'] ?></p>
                                <p class="text-xs text-gray-500"><?= __('points') ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Current Goal -->
            <?php if ($activeGoal): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold"><?= __('core_goal') ?></h2>
                    <a href="mandalart.php" class="text-sm text-primary-600 dark:text-primary-400 hover:underline"><?= __('view_all') ?></a>
                </div>
                <div class="p-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                            <i data-feather="target" class="w-7 h-7 text-primary-600 dark:text-primary-400"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold"><?= e($activeGoal['title']) ?></p>
                            <div class="flex items-center space-x-2 mt-1">
                                <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500 rounded-full" style="width: <?= $activeGoal['progress'] ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-500"><?= number_format($activeGoal['progress'], 1) ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Achievements -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold"><?= __('achievements') ?></h2>
                    <a href="achievements.php" class="text-sm text-primary-600 dark:text-primary-400 hover:underline"><?= __('view_all') ?></a>
                </div>
                <div class="p-4">
                    <?php if (empty($recentAchievements)): ?>
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4"><?= __('none') ?></p>
                    <?php else: ?>
                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach ($recentAchievements as $achievement): ?>
                        <div class="text-center" title="<?= e(getCurrentLanguage() === 'ko' ? $achievement['name_ko'] : $achievement['name_en']) ?>">
                            <div class="w-12 h-12 rounded-full mx-auto flex items-center justify-center mb-1"
                                 style="background-color: <?= $achievement['color'] ?>20">
                                <i data-feather="<?= $achievement['icon'] ?: 'award' ?>" class="w-6 h-6" style="color: <?= $achievement['color'] ?>"></i>
                            </div>
                            <p class="text-xs truncate"><?= e(getCurrentLanguage() === 'ko' ? $achievement['name_ko'] : $achievement['name_en']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Level Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('current_level') ?></h2>
                </div>
                <div class="p-4 text-center">
                    <div class="w-20 h-20 rounded-full mx-auto mb-3 flex items-center justify-center"
                         style="background: linear-gradient(135deg, <?= $levelInfo['color'] ?> 0%, <?= $levelInfo['color'] ?>aa 100%)">
                        <span class="text-3xl font-bold text-white"><?= $user['level'] ?></span>
                    </div>
                    <p class="text-xl font-bold"><?= getCurrentLanguage() === 'ko' ? $levelInfo['name_ko'] : $levelInfo['name_en'] ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= number_format($user['points']) ?> <?= __('points') ?></p>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4">
                    <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <i data-feather="settings" class="w-5 h-5 text-gray-500"></i>
                        <span><?= __('settings') ?></span>
                    </a>
                    <a href="tracker.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <i data-feather="bar-chart-2" class="w-5 h-5 text-gray-500"></i>
                        <span><?= __('nav_tracker') ?></span>
                    </a>
                    <a href="leaderboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <i data-feather="trending-up" class="w-5 h-5 text-gray-500"></i>
                        <span><?= __('leaderboard') ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
