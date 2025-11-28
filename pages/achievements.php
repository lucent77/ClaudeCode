<?php
/**
 * Achievements Page
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: achievements.php");
    exit;
}

Auth::require();

$userId = Auth::id();

// Get all achievements
$allAchievements = Achievement::getAll();

// Get user's earned achievements
$earnedAchievements = Achievement::getByUser($userId);
$earnedIds = array_column($earnedAchievements, 'id');

// Group achievements by category
$achievementsByCategory = [];
foreach ($allAchievements as $achievement) {
    $achievementsByCategory[$achievement['category']][] = $achievement;
}

// Stats
$totalAchievements = count($allAchievements);
$earnedCount = count($earnedAchievements);
$totalPoints = array_sum(array_column($earnedAchievements, 'points_reward'));

$pageTitle = __('achievements');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold"><?= __('achievements') ?></h1>
        <p class="text-gray-500 dark:text-gray-400"><?= __('earned_badges') ?>: <?= $earnedCount ?> / <?= $totalAchievements ?></p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm text-center">
            <p class="text-3xl font-bold text-primary-600"><?= $earnedCount ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('earned_badges') ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm text-center">
            <p class="text-3xl font-bold text-yellow-600">+<?= $totalPoints ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('points') ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm text-center">
            <p class="text-3xl font-bold text-green-600"><?= round(($earnedCount / $totalAchievements) * 100) ?>%</p>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('completed') ?></p>
        </div>
    </div>

    <!-- Recently Earned -->
    <?php if (!empty($earnedAchievements)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-6">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold"><?= __('recent_achievements') ?></h2>
        </div>
        <div class="p-4">
            <div class="flex space-x-4 overflow-x-auto pb-2">
                <?php foreach (array_slice($earnedAchievements, 0, 5) as $achievement): ?>
                <div class="flex-shrink-0 text-center">
                    <div class="w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-2"
                         style="background-color: <?= $achievement['color'] ?>20">
                        <i data-feather="<?= $achievement['icon'] ?: 'award' ?>" class="w-8 h-8" style="color: <?= $achievement['color'] ?>"></i>
                    </div>
                    <p class="text-xs font-medium truncate w-20"><?= e(getCurrentLanguage() === 'ko' ? $achievement['name_ko'] : $achievement['name_en']) ?></p>
                    <p class="text-xs text-gray-500"><?= formatDate($achievement['earned_at'], 'm/d') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Achievements by Category -->
    <?php
    $categoryNames = [
        'streak' => ['ko' => '연속 달성', 'en' => 'Streak', 'icon' => 'flame', 'color' => 'orange'],
        'completion' => ['ko' => '완료', 'en' => 'Completion', 'icon' => 'check-circle', 'color' => 'green'],
        'milestone' => ['ko' => '마일스톤', 'en' => 'Milestone', 'icon' => 'flag', 'color' => 'blue'],
        'social' => ['ko' => '소셜', 'en' => 'Social', 'icon' => 'users', 'color' => 'pink'],
        'special' => ['ko' => '특별', 'en' => 'Special', 'icon' => 'star', 'color' => 'purple'],
    ];
    ?>

    <?php foreach ($achievementsByCategory as $category => $achievements): ?>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-6">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-<?= $categoryNames[$category]['color'] ?>-100 dark:bg-<?= $categoryNames[$category]['color'] ?>-900/30 flex items-center justify-center">
                <i data-feather="<?= $categoryNames[$category]['icon'] ?>" class="w-5 h-5 text-<?= $categoryNames[$category]['color'] ?>-600 dark:text-<?= $categoryNames[$category]['color'] ?>-400"></i>
            </div>
            <h2 class="font-semibold"><?= getCurrentLanguage() === 'ko' ? $categoryNames[$category]['ko'] : $categoryNames[$category]['en'] ?></h2>
        </div>
        <div class="p-4 grid md:grid-cols-2 gap-4">
            <?php foreach ($achievements as $achievement):
                $isEarned = in_array($achievement['id'], $earnedIds);
                $rarityColors = [
                    'common' => 'gray',
                    'uncommon' => 'green',
                    'rare' => 'blue',
                    'epic' => 'purple',
                    'legendary' => 'yellow'
                ];
            ?>
            <div class="flex items-start space-x-4 p-3 rounded-lg <?= $isEarned ? 'bg-gray-50 dark:bg-gray-700/50' : 'opacity-50' ?>">
                <div class="w-14 h-14 rounded-full flex-shrink-0 flex items-center justify-center <?= $isEarned ? '' : 'grayscale' ?>"
                     style="background-color: <?= $achievement['color'] ?>20">
                    <i data-feather="<?= $achievement['icon'] ?: 'award' ?>" class="w-7 h-7" style="color: <?= $isEarned ? $achievement['color'] : '#9CA3AF' ?>"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2">
                        <p class="font-semibold"><?= e(getCurrentLanguage() === 'ko' ? $achievement['name_ko'] : $achievement['name_en']) ?></p>
                        <span class="px-2 py-0.5 text-xs rounded bg-<?= $rarityColors[$achievement['rarity']] ?>-100 dark:bg-<?= $rarityColors[$achievement['rarity']] ?>-900/30 text-<?= $rarityColors[$achievement['rarity']] ?>-600 dark:text-<?= $rarityColors[$achievement['rarity']] ?>-400">
                            <?= __('rarity_' . $achievement['rarity']) ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= e(getCurrentLanguage() === 'ko' ? $achievement['description_ko'] : $achievement['description_en']) ?></p>
                    <div class="flex items-center space-x-3 mt-2 text-xs">
                        <span class="text-yellow-600">+<?= $achievement['points_reward'] ?> <?= __('points') ?></span>
                        <?php if ($isEarned): ?>
                        <span class="text-green-600 flex items-center">
                            <i data-feather="check" class="w-3 h-3 mr-1"></i>
                            <?= __('completed') ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-400">
                            <i data-feather="lock" class="w-3 h-3 inline mr-1"></i>
                            <?= __('locked_badges') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
