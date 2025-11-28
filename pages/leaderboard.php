<?php
/**
 * Leaderboard Page
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: leaderboard.php");
    exit;
}

Auth::require();

$userId = Auth::id();

// Get leaderboards
$weeklyLeaderboard = Leaderboard::getWeekly(10);
$monthlyLeaderboard = Leaderboard::getMonthly(10);
$allTimeLeaderboard = Leaderboard::getAllTime(10);

// Get user ranks
$userWeeklyRank = Leaderboard::getUserRank($userId, 'weekly');
$userAllTimeRank = Leaderboard::getUserRank($userId, 'all_time');

$currentTab = $_GET['tab'] ?? 'weekly';

$pageTitle = __('leaderboard');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold"><?= __('leaderboard') ?></h1>
        <p class="text-gray-500 dark:text-gray-400"><?= __('your_rank') ?>: #<?= $currentTab === 'weekly' ? $userWeeklyRank : $userAllTimeRank ?></p>
    </div>

    <!-- User Rank Card -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-xl p-6 text-white mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-primary-100 text-sm"><?= __('your_rank') ?></p>
                <p class="text-4xl font-bold mt-1">#<?= $currentTab === 'weekly' ? $userWeeklyRank : $userAllTimeRank ?></p>
            </div>
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                <i data-feather="trending-up" class="w-10 h-10"></i>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex space-x-2 mb-6">
        <a href="?tab=weekly" class="px-4 py-2 rounded-lg font-medium transition-colors <?= $currentTab === 'weekly' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <?= __('weekly_ranking') ?>
        </a>
        <a href="?tab=monthly" class="px-4 py-2 rounded-lg font-medium transition-colors <?= $currentTab === 'monthly' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <?= __('monthly_ranking') ?>
        </a>
        <a href="?tab=all_time" class="px-4 py-2 rounded-lg font-medium transition-colors <?= $currentTab === 'all_time' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
            <?= __('all_time_ranking') ?>
        </a>
    </div>

    <!-- Leaderboard -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold"><?= __('top_10') ?></h2>
        </div>

        <?php
        $leaderboard = match($currentTab) {
            'monthly' => $monthlyLeaderboard,
            'all_time' => $allTimeLeaderboard,
            default => $weeklyLeaderboard
        };
        $pointsKey = match($currentTab) {
            'monthly' => 'monthly_points',
            'all_time' => 'points',
            default => 'weekly_points'
        };
        ?>

        <?php if (empty($leaderboard)): ?>
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <i data-feather="users" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
            <p><?= __('none') ?></p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach ($leaderboard as $idx => $user):
                $rank = $idx + 1;
                $isCurrentUser = $user['id'] === $userId;
                $rankColors = [
                    1 => 'from-yellow-400 to-yellow-600',
                    2 => 'from-gray-300 to-gray-500',
                    3 => 'from-orange-400 to-orange-600'
                ];
            ?>
            <div class="p-4 <?= $isCurrentUser ? 'bg-primary-50 dark:bg-primary-900/20' : '' ?> hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-center space-x-4">
                    <!-- Rank -->
                    <div class="w-10 h-10 flex-shrink-0">
                        <?php if ($rank <= 3): ?>
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $rankColors[$rank] ?> flex items-center justify-center text-white font-bold">
                            <?= $rank ?>
                        </div>
                        <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center font-bold text-gray-600 dark:text-gray-300">
                            <?= $rank ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Avatar -->
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-2">
                            <p class="font-semibold truncate <?= $isCurrentUser ? 'text-primary-600 dark:text-primary-400' : '' ?>">
                                <?= e($user['name']) ?>
                                <?php if ($isCurrentUser): ?>
                                <span class="text-xs font-normal text-gray-500">(<?= __('profile') ?>)</span>
                                <?php endif; ?>
                            </p>
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-xs rounded">Lv.<?= $user['level'] ?></span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">@<?= e($user['username']) ?></p>
                    </div>

                    <!-- Points -->
                    <div class="text-right flex-shrink-0">
                        <p class="text-xl font-bold text-primary-600"><?= number_format($user[$pointsKey] ?? 0) ?></p>
                        <p class="text-xs text-gray-500"><?= __('points') ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Motivation Message -->
    <div class="mt-6 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-6 text-center">
        <div class="text-4xl mb-3">
            <?php if ($userWeeklyRank <= 3): ?>
            🏆
            <?php elseif ($userWeeklyRank <= 10): ?>
            🌟
            <?php else: ?>
            💪
            <?php endif; ?>
        </div>
        <p class="text-lg font-semibold text-purple-800 dark:text-purple-200">
            <?php if ($userWeeklyRank <= 3): ?>
            <?= __('score_excellent') ?>
            <?php elseif ($userWeeklyRank <= 10): ?>
            <?= __('score_good') ?>
            <?php else: ?>
            <?= __('keep_going') ?>
            <?php endif; ?>
        </p>
        <p class="text-sm text-purple-600 dark:text-purple-400 mt-1">
            <?= __('habit_4_desc') ?>
        </p>
    </div>
</div>

<script>
    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
