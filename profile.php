<?php
/**
 * Profile Page
 * 프로필 페이지
 */

$pageTitle = '프로필';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/DailyTracker.php';

Session::requireAuth();

$user = Session::getUser();
$tracker = new DailyTracker();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $user->updateProfile([
            'full_name' => $_POST['full_name'] ?? ''
        ]);
        Session::setFlash('success', '프로필이 업데이트되었습니다.');
        redirect('/profile.php');
    }

    if ($action === 'change_password') {
        $result = $user->changePassword(
            $_POST['current_password'] ?? '',
            $_POST['new_password'] ?? ''
        );
        if ($result['success']) {
            Session::setFlash('success', '비밀번호가 변경되었습니다.');
        } else {
            Session::setFlash('error', $result['error']);
        }
        redirect('/profile.php');
    }
}

// Get statistics
$levelInfo = $user->getLevelInfo();
$statistics = $user->getStatistics();
$achievements = $user->getAchievements();
$monthlyStats = $tracker->getMonthlyStats($user->getId());
$activityFeed = $user->getActivityFeed(10);
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Profile Header -->
    <div class="bg-gradient-to-r from-primary-600 to-purple-600 rounded-2xl p-8 text-white mb-8">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <!-- Avatar -->
            <div class="relative">
                <?php if ($user->getAvatarUrl()): ?>
                    <img src="<?= e($user->getAvatarUrl()) ?>" class="w-28 h-28 rounded-full object-cover border-4 border-white/20">
                <?php else: ?>
                    <div class="w-28 h-28 rounded-full bg-white/20 flex items-center justify-center text-4xl font-bold">
                        <?= mb_substr($user->getUsername(), 0, 1) ?>
                    </div>
                <?php endif; ?>
                <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-white rounded-full flex items-center justify-center text-xl">
                    <?= $levelInfo['current_level']['badge_icon'] ?? '🌱' ?>
                </div>
            </div>

            <!-- Info -->
            <div class="text-center md:text-left flex-1">
                <h1 class="text-2xl font-bold"><?= e($user->getFullName()) ?></h1>
                <p class="text-white/80">@<?= e($user->getUsername()) ?></p>
                <div class="mt-3 flex flex-wrap justify-center md:justify-start gap-4">
                    <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        레벨 <?= $user->getLevel() ?> <?= $levelInfo['current_level']['title'] ?? '' ?>
                    </span>
                    <span class="px-3 py-1 bg-white/20 rounded-full text-sm">
                        🔥 <?= $user->getStreakDays() ?>일 연속
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="flex gap-6 text-center">
                <div>
                    <p class="text-3xl font-bold"><?= number_format($user->getPoints()) ?></p>
                    <p class="text-sm text-white/80">총 포인트</p>
                </div>
                <div>
                    <p class="text-3xl font-bold"><?= count($achievements) ?></p>
                    <p class="text-sm text-white/80">배지</p>
                </div>
                <div>
                    <p class="text-3xl font-bold"><?= $statistics['total_completions'] ?? 0 ?></p>
                    <p class="text-sm text-white/80">완료 과제</p>
                </div>
            </div>
        </div>

        <!-- Level Progress -->
        <div class="mt-6 pt-6 border-t border-white/20">
            <div class="flex items-center justify-between text-sm mb-2">
                <span>레벨 <?= $user->getLevel() ?></span>
                <span><?= number_format($user->getPoints()) ?> / <?= number_format($levelInfo['next_level']['min_points'] ?? $user->getPoints()) ?> XP</span>
                <span>레벨 <?= $user->getLevel() + 1 ?></span>
            </div>
            <div class="h-3 bg-white/30 rounded-full overflow-hidden">
                <div class="h-full bg-white transition-all duration-500 rounded-full" style="width: <?= $levelInfo['progress'] ?>%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="md:col-span-2 space-y-6">
            <!-- Edit Profile -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">프로필 편집</h2>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">이름</label>
                        <input type="text" name="full_name" value="<?= e($user->getFullName()) ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">사용자명</label>
                        <input type="text" value="<?= e($user->getUsername()) ?>" disabled
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-500">
                        <p class="mt-1 text-xs text-gray-500">사용자명은 변경할 수 없습니다</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">이메일</label>
                        <input type="email" value="<?= e($user->getEmail()) ?>" disabled
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-500">
                    </div>

                    <button type="submit"
                            class="px-6 py-3 bg-primary-600 text-white font-medium rounded-xl hover:bg-primary-700 transition-colors">
                        변경 저장
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">비밀번호 변경</h2>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">현재 비밀번호</label>
                        <input type="password" name="current_password" required
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">새 비밀번호</label>
                        <input type="password" name="new_password" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    </div>

                    <button type="submit"
                            class="px-6 py-3 bg-gray-600 text-white font-medium rounded-xl hover:bg-gray-700 transition-colors">
                        비밀번호 변경
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Monthly Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">이번 달 요약</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">평균 점수</span>
                        <span class="font-semibold"><?= round($monthlyStats['avg_score'] ?? 0) ?>점</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">완료 과제</span>
                        <span class="font-semibold"><?= $monthlyStats['total_completed'] ?? 0 ?>개</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">획득 포인트</span>
                        <span class="font-semibold text-primary-600">+<?= $monthlyStats['total_points'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">완벽한 날</span>
                        <span class="font-semibold"><?= $monthlyStats['perfect_days'] ?? 0 ?>일</span>
                    </div>
                </div>
            </div>

            <!-- Recent Badges -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">획득 배지</h3>
                <?php if (!empty($achievements)): ?>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach (array_slice($achievements, 0, 8) as $achievement): ?>
                    <div class="aspect-square rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xl"
                         title="<?= e($achievement['name']) ?>">
                        <?= $achievement['icon'] ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($achievements) > 8): ?>
                <a href="/achievements.php" class="mt-3 block text-center text-sm text-primary-600 hover:text-primary-700">
                    +<?= count($achievements) - 8 ?>개 더 보기
                </a>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-sm text-gray-500 text-center py-4">아직 획득한 배지가 없어요</p>
                <?php endif; ?>
            </div>

            <!-- Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">최근 활동</h3>
                <?php if (!empty($activityFeed)): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($activityFeed, 0, 5) as $activity): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-xl"><?= $activity['icon'] ?? '📌' ?></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white truncate"><?= e($activity['title']) ?></p>
                            <p class="text-xs text-gray-500"><?= date('n/j', strtotime($activity['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-500 text-center py-4">아직 활동 기록이 없어요</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
