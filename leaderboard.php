<?php
/**
 * Leaderboard Page
 * 리더보드 페이지
 */

$pageTitle = '리더보드';
require_once __DIR__ . '/includes/header.php';

Session::requireAuth();

$user = Session::getUser();
$db = Database::getInstance();

// Get leaderboard data
$weeklyLeaders = $db->fetchAll(
    "SELECT * FROM weekly_leaderboard ORDER BY weekly_points DESC LIMIT 50"
);

// Get all-time leaderboard
$allTimeLeaders = $db->fetchAll(
    "SELECT id, username, full_name, avatar_url, level, points, streak_days
     FROM users
     ORDER BY points DESC
     LIMIT 50"
);

// Get user's rank
$userWeeklyRank = 0;
$userAllTimeRank = 0;

foreach ($weeklyLeaders as $idx => $leader) {
    if ($leader['id'] == $user->getId()) {
        $userWeeklyRank = $idx + 1;
        break;
    }
}

foreach ($allTimeLeaders as $idx => $leader) {
    if ($leader['id'] == $user->getId()) {
        $userAllTimeRank = $idx + 1;
        break;
    }
}

$activeTab = $_GET['tab'] ?? 'weekly';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">리더보드</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">함께 성장하는 목표 달성 커뮤니티</p>
    </div>

    <!-- User's Rank Card -->
    <div class="bg-gradient-to-r from-primary-600 to-purple-600 rounded-2xl p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <?php if ($user->getAvatarUrl()): ?>
                    <img src="<?= e($user->getAvatarUrl()) ?>" class="w-16 h-16 rounded-full object-cover border-4 border-white/20">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold">
                        <?= mb_substr($user->getUsername(), 0, 1) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="text-xl font-bold"><?= e($user->getFullName()) ?></h2>
                    <p class="text-white/80">레벨 <?= $user->getLevel() ?></p>
                </div>
            </div>

            <div class="flex gap-8">
                <div class="text-center">
                    <p class="text-3xl font-bold">#<?= $userWeeklyRank ?: '-' ?></p>
                    <p class="text-sm text-white/80">주간 순위</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold">#<?= $userAllTimeRank ?: '-' ?></p>
                    <p class="text-sm text-white/80">전체 순위</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold"><?= number_format($user->getPoints()) ?></p>
                    <p class="text-sm text-white/80">총 포인트</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-2 mb-6">
        <a href="?tab=weekly"
           class="px-6 py-3 rounded-xl font-medium transition-all <?= $activeTab === 'weekly' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
            주간 순위
        </a>
        <a href="?tab=alltime"
           class="px-6 py-3 rounded-xl font-medium transition-all <?= $activeTab === 'alltime' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
            전체 순위
        </a>
    </div>

    <!-- Leaderboard Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden">
        <?php
        $leaders = $activeTab === 'weekly' ? $weeklyLeaders : $allTimeLeaders;
        $pointsKey = $activeTab === 'weekly' ? 'weekly_points' : 'points';

        if (!empty($leaders)):
        ?>

        <!-- Top 3 -->
        <div class="p-6 bg-gradient-to-b from-gray-50 to-white dark:from-gray-700 dark:to-gray-800">
            <div class="flex items-end justify-center gap-4">
                <?php if (isset($leaders[1])): ?>
                <!-- 2nd Place -->
                <div class="text-center">
                    <div class="w-20 h-20 mx-auto mb-2 relative">
                        <?php if ($leaders[1]['avatar_url']): ?>
                            <img src="<?= e($leaders[1]['avatar_url']) ?>" class="w-full h-full rounded-full object-cover border-4 border-gray-300">
                        <?php else: ?>
                            <div class="w-full h-full rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-2xl font-bold">
                                <?= mb_substr($leaders[1]['username'], 0, 1) ?>
                            </div>
                        <?php endif; ?>
                        <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white font-bold text-sm">2</div>
                    </div>
                    <p class="font-semibold text-gray-900 dark:text-white"><?= e($leaders[1]['username']) ?></p>
                    <p class="text-sm text-gray-500"><?= number_format($leaders[1][$pointsKey]) ?> pt</p>
                </div>
                <?php endif; ?>

                <?php if (isset($leaders[0])): ?>
                <!-- 1st Place -->
                <div class="text-center -mt-4">
                    <div class="text-4xl mb-2">👑</div>
                    <div class="w-24 h-24 mx-auto mb-2 relative">
                        <?php if ($leaders[0]['avatar_url']): ?>
                            <img src="<?= e($leaders[0]['avatar_url']) ?>" class="w-full h-full rounded-full object-cover border-4 border-yellow-400 shadow-lg">
                        <?php else: ?>
                            <div class="w-full h-full rounded-full bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center text-3xl font-bold text-yellow-600">
                                <?= mb_substr($leaders[0]['username'], 0, 1) ?>
                            </div>
                        <?php endif; ?>
                        <div class="absolute -bottom-1 -right-1 w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold">1</div>
                    </div>
                    <p class="font-bold text-lg text-gray-900 dark:text-white"><?= e($leaders[0]['username']) ?></p>
                    <p class="text-primary-600 font-semibold"><?= number_format($leaders[0][$pointsKey]) ?> pt</p>
                </div>
                <?php endif; ?>

                <?php if (isset($leaders[2])): ?>
                <!-- 3rd Place -->
                <div class="text-center">
                    <div class="w-20 h-20 mx-auto mb-2 relative">
                        <?php if ($leaders[2]['avatar_url']): ?>
                            <img src="<?= e($leaders[2]['avatar_url']) ?>" class="w-full h-full rounded-full object-cover border-4 border-orange-300">
                        <?php else: ?>
                            <div class="w-full h-full rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center text-2xl font-bold text-orange-600">
                                <?= mb_substr($leaders[2]['username'], 0, 1) ?>
                            </div>
                        <?php endif; ?>
                        <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-orange-400 rounded-full flex items-center justify-center text-white font-bold text-sm">3</div>
                    </div>
                    <p class="font-semibold text-gray-900 dark:text-white"><?= e($leaders[2]['username']) ?></p>
                    <p class="text-sm text-gray-500"><?= number_format($leaders[2][$pointsKey]) ?> pt</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rest of the list -->
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach (array_slice($leaders, 3) as $idx => $leader): ?>
            <div class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors <?= $leader['id'] == $user->getId() ? 'bg-primary-50 dark:bg-primary-900/20' : '' ?>">
                <span class="w-8 text-center text-lg font-bold text-gray-400"><?= $idx + 4 ?></span>

                <?php if ($leader['avatar_url']): ?>
                    <img src="<?= e($leader['avatar_url']) ?>" class="w-10 h-10 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center font-medium">
                        <?= mb_substr($leader['username'], 0, 1) ?>
                    </div>
                <?php endif; ?>

                <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= e($leader['username']) ?>
                        <?= $leader['id'] == $user->getId() ? '<span class="text-xs text-primary-600">(나)</span>' : '' ?>
                    </p>
                    <p class="text-sm text-gray-500">레벨 <?= $leader['level'] ?></p>
                </div>

                <div class="flex items-center gap-4">
                    <?php if ($leader['streak_days'] > 0): ?>
                    <div class="flex items-center gap-1 text-orange-500">
                        <span>🔥</span>
                        <span class="text-sm font-medium"><?= $leader['streak_days'] ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="text-right">
                        <p class="font-semibold text-gray-900 dark:text-white"><?= number_format($leader[$pointsKey]) ?></p>
                        <p class="text-xs text-gray-500">포인트</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="p-12 text-center">
            <p class="text-gray-500 dark:text-gray-400">아직 순위 데이터가 없습니다</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Motivational Message -->
    <div class="mt-8 p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-200 dark:border-green-800">
        <div class="flex items-center gap-4">
            <span class="text-4xl">🌟</span>
            <div>
                <h3 class="font-semibold text-green-900 dark:text-green-100">함께 성장해요!</h3>
                <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                    순위보다 중요한 것은 어제의 나보다 성장하는 것입니다.
                    꾸준한 실천이 결국 최고의 결과를 만들어냅니다.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
