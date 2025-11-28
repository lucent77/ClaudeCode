<?php
/**
 * Achievements Page
 * 업적/배지 페이지
 */

$pageTitle = '업적';
require_once __DIR__ . '/includes/header.php';

Session::requireAuth();

$user = Session::getUser();
$db = Database::getInstance();

// Get all achievements
$allAchievements = $db->fetchAll("SELECT * FROM achievements WHERE is_active = 1 ORDER BY category, rarity DESC");

// Get user's earned achievements
$earnedAchievements = $user->getAchievements();
$earnedIds = array_column($earnedAchievements, 'id');

// Group by category
$categories = [
    'streak' => ['name' => '연속 달성', 'icon' => '🔥'],
    'completion' => ['name' => '완료', 'icon' => '✅'],
    'milestone' => ['name' => '마일스톤', 'icon' => '🎯'],
    'special' => ['name' => '특별', 'icon' => '⭐'],
    'social' => ['name' => '소셜', 'icon' => '👥']
];

$achievementsByCategory = [];
foreach ($allAchievements as $achievement) {
    $cat = $achievement['category'];
    if (!isset($achievementsByCategory[$cat])) {
        $achievementsByCategory[$cat] = [];
    }
    $achievementsByCategory[$cat][] = $achievement;
}

// Rarity colors
$rarityColors = [
    'common' => 'from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600',
    'uncommon' => 'from-green-100 to-emerald-200 dark:from-green-900 dark:to-emerald-800',
    'rare' => 'from-blue-100 to-indigo-200 dark:from-blue-900 dark:to-indigo-800',
    'epic' => 'from-purple-100 to-violet-200 dark:from-purple-900 dark:to-violet-800',
    'legendary' => 'from-amber-100 to-orange-200 dark:from-amber-900 dark:to-orange-800'
];

$rarityLabels = [
    'common' => '일반',
    'uncommon' => '고급',
    'rare' => '희귀',
    'epic' => '영웅',
    'legendary' => '전설'
];
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">업적 & 배지</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">목표를 달성하며 특별한 배지를 수집하세요</p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-primary-600"><?= count($earnedAchievements) ?></p>
            <p class="text-sm text-gray-500 mt-1">획득한 배지</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= count($allAchievements) ?></p>
            <p class="text-sm text-gray-500 mt-1">전체 배지</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-green-600"><?= round(count($earnedAchievements) / max(1, count($allAchievements)) * 100) ?>%</p>
            <p class="text-sm text-gray-500 mt-1">달성률</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 text-center shadow-sm">
            <p class="text-3xl font-bold text-yellow-600">
                <?= array_sum(array_column($earnedAchievements, 'points_reward')) ?>
            </p>
            <p class="text-sm text-gray-500 mt-1">보너스 포인트</p>
        </div>
    </div>

    <!-- Recent Achievements -->
    <?php
    $recentEarned = array_filter($earnedAchievements, function($a) {
        return strtotime($a['earned_at']) > strtotime('-7 days');
    });
    if (!empty($recentEarned)):
    ?>
    <div class="mb-8 p-6 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-2xl border border-yellow-200 dark:border-yellow-800">
        <h3 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-4">🎉 최근 획득한 배지</h3>
        <div class="flex flex-wrap gap-4">
            <?php foreach ($recentEarned as $achievement): ?>
            <div class="flex items-center gap-3 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <span class="text-2xl"><?= $achievement['icon'] ?></span>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white"><?= e($achievement['name']) ?></p>
                    <p class="text-xs text-gray-500"><?= date('n/j', strtotime($achievement['earned_at'])) ?> 획득</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Achievement Categories -->
    <?php foreach ($achievementsByCategory as $category => $achievements): ?>
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xl"><?= $categories[$category]['icon'] ?? '🏆' ?></span>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= $categories[$category]['name'] ?? $category ?></h2>
            <span class="text-sm text-gray-500">
                (<?= count(array_filter($achievements, fn($a) => in_array($a['id'], $earnedIds))) ?>/<?= count($achievements) ?>)
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($achievements as $achievement):
                $isEarned = in_array($achievement['id'], $earnedIds);
                $earnedInfo = $isEarned ? array_filter($earnedAchievements, fn($a) => $a['id'] == $achievement['id']) : [];
                $earnedInfo = !empty($earnedInfo) ? reset($earnedInfo) : null;
            ?>
            <div class="relative bg-gradient-to-br <?= $rarityColors[$achievement['rarity']] ?> rounded-2xl p-5 <?= $isEarned ? '' : 'opacity-50 grayscale' ?> transition-all hover:scale-105 cursor-pointer group">
                <!-- Rarity Badge -->
                <span class="absolute top-2 right-2 text-xs font-medium px-2 py-0.5 rounded-full bg-white/50 dark:bg-black/30 text-gray-700 dark:text-gray-300">
                    <?= $rarityLabels[$achievement['rarity']] ?>
                </span>

                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-white/50 dark:bg-black/20 rounded-xl flex items-center justify-center text-3xl <?= $isEarned ? 'badge-shine' : '' ?>">
                        <?= $achievement['icon'] ?>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white"><?= e($achievement['name']) ?></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= e($achievement['description']) ?></p>

                        <?php if ($isEarned && $earnedInfo): ?>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                            ✓ <?= date('Y.n.j', strtotime($earnedInfo['earned_at'])) ?> 획득
                        </p>
                        <?php else: ?>
                        <p class="text-xs text-gray-500 mt-2">+<?= $achievement['points_reward'] ?> 포인트</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$isEarned): ?>
                <div class="absolute inset-0 bg-black/10 dark:bg-black/30 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="text-2xl">🔒</span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Motivation -->
    <div class="text-center py-8">
        <p class="text-gray-500 dark:text-gray-400">
            꾸준히 목표를 달성하며 더 많은 배지를 수집해보세요! 🏆
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
