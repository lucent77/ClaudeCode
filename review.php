<?php
/**
 * Review Page
 * 주간/월간 회고 페이지
 */

$pageTitle = '회고';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/DailyTracker.php';

Session::requireAuth();

$user = Session::getUser();
$db = Database::getInstance();
$tracker = new DailyTracker();

$activeTab = $_GET['tab'] ?? 'weekly';

// Get current week info
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));

// Get current month info
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

// Get weekly stats
$weeklyStats = $tracker->getWeeklyStats($user->getId(), $weekStart);

// Get monthly stats
$monthlyStats = $tracker->getMonthlyStats($user->getId(), date('Y-m'));

// Get existing review
$weeklyReview = $db->fetch(
    "SELECT * FROM weekly_reviews WHERE user_id = ? AND week_start = ?",
    [$user->getId(), $weekStart]
);

$monthlyReview = $db->fetch(
    "SELECT * FROM monthly_reviews WHERE user_id = ? AND review_month = ?",
    [$user->getId(), $monthStart]
);

// Get daily scores for the week
$weeklyScores = $db->fetchAll(
    "SELECT * FROM daily_scores WHERE user_id = ? AND score_date BETWEEN ? AND ? ORDER BY score_date",
    [$user->getId(), $weekStart, $weekEnd]
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['review_type'] ?? 'weekly';

    if ($type === 'weekly') {
        $data = [
            'accomplishments' => $_POST['accomplishments'] ?? '',
            'challenges' => $_POST['challenges'] ?? '',
            'lessons_learned' => $_POST['lessons_learned'] ?? '',
            'next_week_focus' => $_POST['next_week_focus'] ?? '',
            'overall_rating' => intval($_POST['overall_rating'] ?? 3)
        ];

        if ($weeklyReview) {
            $db->update('weekly_reviews', $data, 'id = ?', [$weeklyReview['id']]);
        } else {
            $data['user_id'] = $user->getId();
            $data['week_start'] = $weekStart;
            $data['week_end'] = $weekEnd;
            $data['avg_execution_score'] = $weeklyStats['avg_score'] ?? 0;
            $data['total_points_earned'] = $weeklyStats['total_points'] ?? 0;
            $db->insert('weekly_reviews', $data);

            // Check for first review achievement
            $user->checkAchievements();
        }

        Session::setFlash('success', '주간 회고가 저장되었습니다.');
    } else {
        $data = [
            'goal_progress' => $_POST['goal_progress'] ?? '',
            'key_achievements' => $_POST['key_achievements'] ?? '',
            'areas_for_improvement' => $_POST['areas_for_improvement'] ?? '',
            'next_month_priorities' => $_POST['next_month_priorities'] ?? '',
            'overall_satisfaction' => intval($_POST['overall_satisfaction'] ?? 3)
        ];

        if ($monthlyReview) {
            $db->update('monthly_reviews', $data, 'id = ?', [$monthlyReview['id']]);
        } else {
            $data['user_id'] = $user->getId();
            $data['review_month'] = $monthStart;
            $db->insert('monthly_reviews', $data);
        }

        Session::setFlash('success', '월간 회고가 저장되었습니다.');
    }

    redirect('/review.php?tab=' . $type);
}
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">회고</h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">지난 활동을 돌아보고 더 나은 다음을 준비하세요</p>
    </div>

    <!-- Tab Navigation -->
    <div class="flex gap-2 mb-6">
        <a href="?tab=weekly"
           class="px-6 py-3 rounded-xl font-medium transition-all <?= $activeTab === 'weekly' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
            주간 회고
        </a>
        <a href="?tab=monthly"
           class="px-6 py-3 rounded-xl font-medium transition-all <?= $activeTab === 'monthly' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' ?>">
            월간 회고
        </a>
    </div>

    <?php if ($activeTab === 'weekly'): ?>
    <!-- Weekly Review -->
    <div class="space-y-6">
        <!-- Week Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    이번 주 요약 (<?= date('n/j', strtotime($weekStart)) ?> - <?= date('n/j', strtotime($weekEnd)) ?>)
                </h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= round($weeklyStats['avg_score'] ?? 0) ?></p>
                    <p class="text-sm text-gray-500">평균 점수</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-green-600"><?= $weeklyStats['total_completed'] ?? 0 ?></p>
                    <p class="text-sm text-gray-500">완료 과제</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-primary-600">+<?= $weeklyStats['total_points'] ?? 0 ?></p>
                    <p class="text-sm text-gray-500">획득 포인트</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-yellow-600"><?= $weeklyStats['best_score'] ?? 0 ?></p>
                    <p class="text-sm text-gray-500">최고 점수</p>
                </div>
            </div>

            <!-- Daily Breakdown -->
            <div class="grid grid-cols-7 gap-2">
                <?php
                $days = ['월', '화', '수', '목', '금', '토', '일'];
                $scoresByDate = [];
                foreach ($weeklyScores as $score) {
                    $scoresByDate[$score['score_date']] = $score;
                }

                for ($i = 0; $i < 7; $i++):
                    $date = date('Y-m-d', strtotime($weekStart . " +$i days"));
                    $dayScore = $scoresByDate[$date] ?? null;
                    $score = $dayScore['execution_score'] ?? 0;
                ?>
                <div class="text-center p-3 rounded-xl <?= $score >= 80 ? 'bg-green-100 dark:bg-green-900/30' : ($score >= 50 ? 'bg-yellow-100 dark:bg-yellow-900/30' : ($score > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700')) ?>">
                    <p class="text-xs text-gray-500 mb-1"><?= $days[$i] ?></p>
                    <p class="text-lg font-bold <?= $score >= 80 ? 'text-green-600' : ($score >= 50 ? 'text-yellow-600' : ($score > 0 ? 'text-red-600' : 'text-gray-400')) ?>">
                        <?= $score ?: '-' ?>
                    </p>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Review Form -->
        <form method="POST" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
            <input type="hidden" name="review_type" value="weekly">

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">주간 회고 작성</h2>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        이번 주 성취한 것들 ✨
                    </label>
                    <textarea name="accomplishments" rows="3"
                              placeholder="이번 주에 이룬 성과나 완료한 일들을 적어보세요"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($weeklyReview['accomplishments'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        어려웠던 점 / 도전 🏔️
                    </label>
                    <textarea name="challenges" rows="3"
                              placeholder="이번 주에 힘들었던 점이나 장애물은 무엇이었나요?"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($weeklyReview['challenges'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        배운 점 / 깨달음 💡
                    </label>
                    <textarea name="lessons_learned" rows="3"
                              placeholder="이번 주를 통해 배우거나 깨달은 점이 있다면 적어보세요"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($weeklyReview['lessons_learned'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        다음 주 집중할 것 🎯
                    </label>
                    <textarea name="next_week_focus" rows="3"
                              placeholder="다음 주에 특히 집중하고 싶은 목표나 계획을 적어보세요"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($weeklyReview['next_week_focus'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        이번 주 전체 만족도
                    </label>
                    <div class="flex gap-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="flex-1">
                            <input type="radio" name="overall_rating" value="<?= $i ?>" class="hidden peer"
                                   <?= ($weeklyReview['overall_rating'] ?? 3) == $i ? 'checked' : '' ?>>
                            <div class="py-3 text-center border border-gray-300 dark:border-gray-600 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/30 cursor-pointer transition-all text-2xl">
                                <?= ['😢', '😔', '😐', '😊', '🤩'][$i-1] ?>
                            </div>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                    회고 저장하기
                </button>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- Monthly Review -->
    <div class="space-y-6">
        <!-- Month Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <?= date('Y년 n월') ?> 요약
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= round($monthlyStats['avg_score'] ?? 0) ?></p>
                    <p class="text-sm text-gray-500">평균 점수</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-green-600"><?= $monthlyStats['perfect_days'] ?? 0 ?></p>
                    <p class="text-sm text-gray-500">완벽한 날</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-primary-600">+<?= $monthlyStats['total_points'] ?? 0 ?></p>
                    <p class="text-sm text-gray-500">총 포인트</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <p class="text-2xl font-bold text-yellow-600"><?= $monthlyStats['great_days'] ?? 0 ?></p>
                    <p class="text-sm text-gray-500">80점 이상</p>
                </div>
            </div>
        </div>

        <!-- Monthly Review Form -->
        <form method="POST" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
            <input type="hidden" name="review_type" value="monthly">

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">월간 회고 작성</h2>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        목표 진행 상황 📊
                    </label>
                    <textarea name="goal_progress" rows="3"
                              placeholder="이번 달 핵심 목표들의 진행 상황을 정리해보세요"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($monthlyReview['goal_progress'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        주요 성취 🏆
                    </label>
                    <textarea name="key_achievements" rows="3"
                              placeholder="이번 달 가장 의미있는 성취는 무엇인가요?"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($monthlyReview['key_achievements'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        개선이 필요한 영역 📈
                    </label>
                    <textarea name="areas_for_improvement" rows="3"
                              placeholder="더 나아질 수 있는 부분은 무엇인가요?"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($monthlyReview['areas_for_improvement'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        다음 달 우선순위 🎯
                    </label>
                    <textarea name="next_month_priorities" rows="3"
                              placeholder="다음 달 가장 중요하게 집중할 것들은?"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"><?= e($monthlyReview['next_month_priorities'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        이번 달 전체 만족도
                    </label>
                    <div class="flex gap-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label class="flex-1">
                            <input type="radio" name="overall_satisfaction" value="<?= $i ?>" class="hidden peer"
                                   <?= ($monthlyReview['overall_satisfaction'] ?? 3) == $i ? 'checked' : '' ?>>
                            <div class="py-3 text-center border border-gray-300 dark:border-gray-600 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 dark:peer-checked:bg-primary-900/30 cursor-pointer transition-all text-2xl">
                                <?= ['😢', '😔', '😐', '😊', '🤩'][$i-1] ?>
                            </div>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                    회고 저장하기
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
