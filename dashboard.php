<?php
/**
 * Dashboard Page
 * 메인 대시보드
 */

$pageTitle = '대시보드';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Goal.php';
require_once __DIR__ . '/classes/DailyTracker.php';

Session::requireAuth();

$user = Session::getUser();
$goal = new Goal();
$tracker = new DailyTracker();

// Get user statistics
$levelInfo = $user->getLevelInfo();
$statistics = $user->getStatistics();
$achievements = $user->getAchievements();

// Get today's data
$today = date('Y-m-d');
$dailyScore = $tracker->getDailyScore($user->getId(), $today);
$taskLogs = $tracker->getTaskLogs($user->getId(), $today);
$completedToday = count(array_filter($taskLogs, fn($t) => $t['status'] === 'completed'));
$plannedToday = count($taskLogs);

// Get score history for chart
$scoreHistory = $tracker->getScoreHistory($user->getId(), 14);

// Get weekly and monthly stats
$weeklyStats = $tracker->getWeeklyStats($user->getId());
$monthlyStats = $tracker->getMonthlyStats($user->getId());

// Get completion by category
$categoryCompletion = $tracker->getCompletionByCategory($user->getId(), 30);

// Get struggling tasks for coaching
$strugglingTasks = $tracker->getStrugglingTasks($user->getId());

// Get core goals
$coreGoals = $goal->getUserCoreGoals($user->getId());

// Get activity feed
$activityFeed = $user->getActivityFeed(10);

// Recent achievements (last 7 days)
$recentAchievements = array_filter($achievements, function($a) {
    return strtotime($a['earned_at']) > strtotime('-7 days');
});
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            안녕하세요, <?= e($user->getFullName()) ?>님! 👋
        </h1>
        <p class="mt-1 text-gray-600 dark:text-gray-400">
            <?php
            $hour = date('G');
            if ($hour < 12) echo '좋은 아침이에요. 오늘도 목표를 향해 나아가세요!';
            elseif ($hour < 18) echo '좋은 오후에요. 계획한 일들을 잘 진행하고 계신가요?';
            else echo '좋은 저녁이에요. 오늘 하루 수고 많으셨어요!';
            ?>
        </p>
    </div>

    <!-- Level & Streak Bar -->
    <div class="bg-gradient-to-r from-primary-600 to-purple-600 rounded-2xl p-6 mb-8 text-white">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-3xl">
                    <?= $levelInfo['current_level']['badge_icon'] ?? '🌱' ?>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold">레벨 <?= $user->getLevel() ?></span>
                        <span class="px-2 py-0.5 bg-white/20 rounded-full text-sm"><?= $levelInfo['current_level']['title'] ?? '' ?></span>
                    </div>
                    <div class="mt-2 flex items-center gap-3">
                        <div class="w-48 h-2 bg-white/30 rounded-full overflow-hidden">
                            <div class="h-full bg-white transition-all duration-500" style="width: <?= $levelInfo['progress'] ?>%"></div>
                        </div>
                        <span class="text-sm text-white/80"><?= number_format($user->getPoints()) ?> / <?= number_format($levelInfo['next_level']['min_points'] ?? $user->getPoints()) ?></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <!-- Streak -->
                <div class="text-center">
                    <div class="flex items-center gap-1">
                        <span class="text-3xl <?= $user->getStreakDays() >= 7 ? 'flame-animate' : '' ?>">🔥</span>
                        <span class="text-3xl font-bold"><?= $user->getStreakDays() ?></span>
                    </div>
                    <p class="text-sm text-white/80">연속일</p>
                </div>

                <!-- Total Points -->
                <div class="text-center">
                    <div class="text-3xl font-bold"><?= number_format($user->getPoints()) ?></div>
                    <p class="text-sm text-white/80">총 포인트</p>
                </div>

                <!-- Badges -->
                <div class="text-center">
                    <div class="text-3xl font-bold"><?= count($achievements) ?></div>
                    <p class="text-sm text-white/80">배지</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Today's Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">오늘의 요약</h2>
                    <a href="/daily.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        상세보기 →
                    </a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Execution Score -->
                    <div class="text-center p-4 bg-gradient-to-br from-primary-50 to-purple-50 dark:from-primary-900/20 dark:to-purple-900/20 rounded-xl">
                        <div class="relative w-20 h-20 mx-auto">
                            <svg class="w-20 h-20 transform -rotate-90">
                                <circle cx="40" cy="40" r="34" stroke="#e5e7eb" stroke-width="6" fill="none"/>
                                <circle cx="40" cy="40" r="34" stroke="url(#scoreGradient)" stroke-width="6" fill="none"
                                        stroke-dasharray="213.63"
                                        stroke-dashoffset="<?= 213.63 - (213.63 * ($dailyScore['execution_score'] ?? 0) / 100) ?>"
                                        stroke-linecap="round"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xl font-bold text-gray-900 dark:text-white"><?= $dailyScore['execution_score'] ?? 0 ?></span>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">실행 점수</p>
                    </div>

                    <!-- Tasks Completed -->
                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?= $completedToday ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">완료</p>
                        <p class="text-xs text-gray-500 mt-1">/ <?= $plannedToday ?> 계획</p>
                    </div>

                    <!-- Points Earned -->
                    <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">+<?= $dailyScore['points_earned'] ?? 0 ?></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">포인트</p>
                        <p class="text-xs text-gray-500 mt-1">오늘 획득</p>
                    </div>

                    <!-- Mood -->
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <p class="text-3xl">
                            <?php
                            $score = $dailyScore['execution_score'] ?? 0;
                            echo $score >= 80 ? '😊' : ($score >= 50 ? '😐' : ($score > 0 ? '😔' : '🤔'));
                            ?>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <?= $score >= 80 ? '훌륭해요!' : ($score >= 50 ? '괜찮아요' : ($score > 0 ? '더 노력해봐요' : '시작해봐요')) ?>
                        </p>
                    </div>
                </div>

                <!-- Quick Tasks -->
                <?php if (!empty($taskLogs)): ?>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">오늘의 할 일</h3>
                    <div class="space-y-2">
                        <?php foreach (array_slice($taskLogs, 0, 5) as $log): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs
                                        <?= $log['status'] === 'completed' ? 'bg-green-500 text-white' : 'border-2 border-gray-300' ?>">
                                <?= $log['status'] === 'completed' ? '✓' : '' ?>
                            </span>
                            <span class="flex-1 text-sm <?= $log['status'] === 'completed' ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-white' ?>">
                                <?= e($log['title']) ?>
                            </span>
                            <span class="w-2 h-2 rounded-full" style="background-color: <?= e($log['color']) ?>"></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($taskLogs) > 5): ?>
                    <a href="/daily.php" class="mt-3 block text-center text-sm text-primary-600 hover:text-primary-700">
                        +<?= count($taskLogs) - 5 ?>개 더 보기
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
                    <p class="text-gray-500 dark:text-gray-400 mb-3">오늘 계획된 할 일이 없어요</p>
                    <a href="/daily.php" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        할 일 추가하기
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Score Trend Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">실행 점수 추이</h2>
                <div class="h-64">
                    <canvas id="scoreTrendChart"></canvas>
                </div>
            </div>

            <!-- Goal Progress -->
            <?php if (!empty($coreGoals)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">목표 진행 현황</h2>
                    <a href="/mandalart.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        모두 보기 →
                    </a>
                </div>

                <div class="space-y-4">
                    <?php foreach ($coreGoals as $cg): ?>
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-primary-300 dark:hover:border-primary-600 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-gray-900 dark:text-white"><?= e($cg['title']) ?></h3>
                            <span class="text-sm font-semibold text-primary-600"><?= $cg['progress_percent'] ?>%</span>
                        </div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-primary-500 to-purple-500 transition-all duration-500"
                                 style="width: <?= $cg['progress_percent'] ?>%"></div>
                        </div>
                        <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                            <span><?= $cg['sub_goals_count'] ?>개 세부 목표</span>
                            <span><?= $cg['tasks_count'] ?>개 실행 과제</span>
                            <?php if ($cg['target_date']): ?>
                            <span>D-<?= max(0, ceil((strtotime($cg['target_date']) - time()) / 86400)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Category Performance -->
            <?php if (!empty($categoryCompletion)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">영역별 실행률 (30일)</h2>
                <div class="space-y-4">
                    <?php foreach ($categoryCompletion as $cat): ?>
                    <div class="flex items-center gap-4">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= e($cat['color']) ?>"></span>
                        <span class="w-32 text-sm text-gray-700 dark:text-gray-300 truncate"><?= e($cat['category']) ?></span>
                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 style="width: <?= $cat['completion_rate'] ?>%; background-color: <?= e($cat['color']) ?>"></div>
                        </div>
                        <span class="w-12 text-sm text-right font-medium <?= $cat['completion_rate'] >= 70 ? 'text-green-600' : ($cat['completion_rate'] >= 40 ? 'text-yellow-600' : 'text-red-600') ?>">
                            <?= $cat['completion_rate'] ?>%
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Weekly Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">이번 주 통계</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= round($weeklyStats['avg_score'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500">평균 점수</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $weeklyStats['total_completed'] ?? 0 ?></p>
                        <p class="text-xs text-gray-500">완료 과제</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-2xl font-bold text-primary-600">+<?= $weeklyStats['total_points'] ?? 0 ?></p>
                        <p class="text-xs text-gray-500">획득 포인트</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $weeklyStats['best_score'] ?? 0 ?></p>
                        <p class="text-xs text-gray-500">최고 점수</p>
                    </div>
                </div>
            </div>

            <!-- Coaching Messages -->
            <?php if (!empty($strugglingTasks)): ?>
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">💡</span>
                    <div>
                        <h3 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">개선 제안</h3>
                        <?php $task = $strugglingTasks[0]; ?>
                        <p class="text-sm text-amber-800 dark:text-amber-200 mb-3">
                            <strong>"<?= e($task['title']) ?>"</strong>의 실행률이 <?= $task['completion_rate'] ?>%로 낮습니다.
                            <?php if ($task['frequency'] === 'daily'): ?>
                            목표 빈도를 주 3회로 낮춰보는 것은 어떨까요?
                            <?php else: ?>
                            더 작은 단계로 나눠보는 것은 어떨까요?
                            <?php endif; ?>
                        </p>
                        <button onclick="adjustFrequency(<?= $task['id'] ?>)"
                                class="text-sm font-medium text-amber-700 dark:text-amber-300 hover:text-amber-800 dark:hover:text-amber-200">
                            빈도 조정하기 →
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Achievements -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">배지 컬렉션</h3>
                    <a href="/achievements.php" class="text-sm text-primary-600 hover:text-primary-700">모두 보기</a>
                </div>

                <?php if (!empty($achievements)): ?>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach (array_slice($achievements, 0, 8) as $achievement): ?>
                    <div class="aspect-square rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center text-2xl badge-shine relative group cursor-pointer"
                         title="<?= e($achievement['name']) ?>">
                        <?= $achievement['icon'] ?>
                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                            <?= e($achievement['name']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($recentAchievements)): ?>
                <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <p class="text-sm text-green-700 dark:text-green-400">
                        🎉 이번 주에 <?= count($recentAchievements) ?>개의 새 배지를 획득했어요!
                    </p>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                    아직 획득한 배지가 없어요.<br>목표를 달성하며 배지를 모아보세요!
                </p>
                <?php endif; ?>
            </div>

            <!-- Activity Feed -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">활동 기록</h3>

                <?php if (!empty($activityFeed)): ?>
                <div class="space-y-4">
                    <?php foreach (array_slice($activityFeed, 0, 5) as $activity): ?>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-sm">
                            <?= $activity['icon'] ?? '📌' ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($activity['title']) ?></p>
                            <p class="text-xs text-gray-500"><?= date('n/j H:i', strtotime($activity['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                    아직 활동 기록이 없어요
                </p>
                <?php endif; ?>
            </div>

            <!-- Quick Links -->
            <div class="bg-gradient-to-br from-primary-500 to-purple-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-4">빠른 메뉴</h3>
                <div class="space-y-2">
                    <a href="/mandalart.php?new=1" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors">
                        <span class="text-xl">🎯</span>
                        <span class="text-sm">새 목표 설정하기</span>
                    </a>
                    <a href="/daily.php" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors">
                        <span class="text-xl">✅</span>
                        <span class="text-sm">오늘 할 일 관리</span>
                    </a>
                    <a href="/review.php" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors">
                        <span class="text-xl">📝</span>
                        <span class="text-sm">주간 회고 작성</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SVG Gradients -->
<svg class="hidden">
    <defs>
        <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#6366f1"/>
            <stop offset="100%" style="stop-color:#a855f7"/>
        </linearGradient>
    </defs>
</svg>

<script>
// Score Trend Chart
const scoreData = <?= json_encode(array_reverse($scoreHistory)) ?>;

const ctx = document.getElementById('scoreTrendChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: scoreData.map(d => {
            const date = new Date(d.score_date);
            return `${date.getMonth() + 1}/${date.getDate()}`;
        }),
        datasets: [{
            label: '실행 점수',
            data: scoreData.map(d => d.execution_score),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#1f2937',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 12,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return `실행 점수: ${context.parsed.y}점`;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#9ca3af'
                }
            },
            y: {
                min: 0,
                max: 100,
                grid: {
                    color: '#f3f4f6'
                },
                ticks: {
                    color: '#9ca3af',
                    callback: function(value) {
                        return value + '점';
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// Adjust task frequency
function adjustFrequency(taskId) {
    // Implement frequency adjustment modal
    showToast('빈도 조정 기능 준비 중입니다', 'info');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
