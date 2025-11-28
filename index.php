<?php
/**
 * Dashboard Page
 * LifeMandalart - Self Management Web Service
 */

require_once 'config/config.php';
require_once INCLUDES_PATH . '/auth.php';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: index.php");
    exit;
}

// Require authentication
Auth::require();

$user = Auth::user();
$userId = Auth::id();

// Get user statistics
$stats = User::getStats($userId);

// Get active goal
$activeGoal = Goal::getActiveByUser($userId);

// Get today's tasks
$today = date('Y-m-d');
$todaysTasks = [];
$todaysLog = DailyLog::getOrCreate($userId, $today);

// Get tasks from daily plans
$plannedTasks = Database::fetchAll(
    "SELECT dp.*, t.title, t.priority, t.task_type, s.color as subgoal_color, s.title as subgoal_title,
            CASE WHEN tl.id IS NOT NULL THEN 1 ELSE 0 END as is_completed
     FROM daily_plans dp
     JOIN tasks t ON dp.task_id = t.id
     JOIN subgoals s ON t.subgoal_id = s.id
     LEFT JOIN task_logs tl ON t.id = tl.task_id AND tl.log_date = ? AND tl.user_id = ?
     WHERE dp.user_id = ? AND dp.plan_date = ?
     ORDER BY dp.is_priority DESC, dp.sort_order",
    [$today, $userId, $userId, $today]
);

// Get weekly summary
$weeklySummary = DailyLog::getWeeklySummary($userId);

// Get recent achievements
$recentAchievements = Database::fetchAll(
    "SELECT a.*, ua.earned_at
     FROM user_achievements ua
     JOIN achievements a ON ua.achievement_id = a.id
     WHERE ua.user_id = ?
     ORDER BY ua.earned_at DESC
     LIMIT 3",
    [$userId]
);

// Get suggestions
$suggestions = Suggestion::generate($userId);

// Check for new achievements
$newAchievements = Achievement::checkAndAward($userId);

// Get level info
$levelInfo = Database::fetch(
    "SELECT * FROM levels WHERE level = ?",
    [$user['level']]
);

$nextLevelInfo = Database::fetch(
    "SELECT * FROM levels WHERE level = ?",
    [$user['level'] + 1]
);

$pageTitle = __('nav_dashboard');
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Welcome Banner -->
<div class="mb-6">
    <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold"><?= __('welcome_back', ['name' => e($user['name'])]) ?></h1>
                <p class="mt-1 text-primary-100"><?= localDate($today) ?></p>
            </div>
            <div class="hidden md:flex items-center space-x-4">
                <?php if ($stats['streak_days'] > 0): ?>
                <div class="bg-white/20 rounded-xl px-4 py-2 backdrop-blur-sm">
                    <div class="flex items-center space-x-2">
                        <i data-feather="flame" class="w-5 h-5 text-orange-300"></i>
                        <span class="font-bold"><?= $stats['streak_days'] ?></span>
                        <span class="text-sm text-primary-100"><?= __('streak_days') ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <a href="pages/daily.php" class="bg-white text-primary-600 px-4 py-2 rounded-xl font-semibold hover:bg-primary-50 transition-colors">
                    <?= __('nav_daily') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <!-- Execution Score -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('execution_score') ?></p>
                <p class="text-2xl font-bold mt-1"><?= number_format($todaysLog['execution_score'], 0) ?>%</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <i data-feather="target" class="w-6 h-6 text-blue-600 dark:text-blue-400"></i>
            </div>
        </div>
        <div class="mt-3 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
            <div class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: <?= min(100, $todaysLog['execution_score']) ?>%"></div>
        </div>
    </div>

    <!-- Total Points -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('total_points') ?></p>
                <p class="text-2xl font-bold mt-1"><?= number_format($stats['points']) ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                <i data-feather="zap" class="w-6 h-6 text-yellow-600 dark:text-yellow-400"></i>
            </div>
        </div>
        <?php if ($nextLevelInfo): ?>
        <p class="mt-2 text-xs text-gray-500"><?= __('points_to_next_level', ['points' => number_format($nextLevelInfo['min_points'] - $stats['points'])]) ?></p>
        <?php endif; ?>
    </div>

    <!-- Current Level -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('current_level') ?></p>
                <p class="text-2xl font-bold mt-1">Lv.<?= $stats['level'] ?></p>
            </div>
            <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background-color: <?= $levelInfo['color'] ?>20">
                <span class="text-lg" style="color: <?= $levelInfo['color'] ?>"><?= getCurrentLanguage() === 'ko' ? $levelInfo['name_ko'] : $levelInfo['name_en'] ?></span>
            </div>
        </div>
    </div>

    <!-- Badges -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('badges') ?></p>
                <p class="text-2xl font-bold mt-1"><?= $stats['badge_count'] ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                <i data-feather="award" class="w-6 h-6 text-purple-600 dark:text-purple-400"></i>
            </div>
        </div>
        <a href="pages/achievements.php" class="mt-2 text-xs text-primary-600 dark:text-primary-400 hover:underline inline-block"><?= __('view_all') ?></a>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid lg:grid-cols-3 gap-6">
    <!-- Today's Tasks -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Tasks Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold text-lg"><?= __('tasks_today') ?></h2>
                <a href="pages/daily.php" class="text-sm text-primary-600 dark:text-primary-400 hover:underline"><?= __('view_all') ?></a>
            </div>
            <div class="p-4">
                <?php if (empty($plannedTasks)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-feather="check-square" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 mb-4"><?= __('no_tasks_today') ?></p>
                    <a href="pages/daily.php" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <i data-feather="plus" class="w-4 h-4 mr-2"></i>
                        <?= __('add_tasks') ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($plannedTasks as $task): ?>
                    <div class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors <?= $task['is_completed'] ? 'opacity-60' : '' ?>">
                        <button onclick="toggleTaskComplete(<?= $task['task_id'] ?>)" class="flex-shrink-0">
                            <?php if ($task['is_completed']): ?>
                            <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                <i data-feather="check" class="w-4 h-4 text-white"></i>
                            </div>
                            <?php else: ?>
                            <div class="w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600 hover:border-primary-500"></div>
                            <?php endif; ?>
                        </button>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium <?= $task['is_completed'] ? 'line-through text-gray-500' : '' ?>"><?= e($task['title']) ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= e($task['subgoal_title']) ?></p>
                        </div>
                        <?php if ($task['is_priority']): ?>
                        <span class="flex-shrink-0 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs rounded-full">
                            <i data-feather="star" class="w-3 h-3 inline"></i>
                        </span>
                        <?php endif; ?>
                        <span class="flex-shrink-0 w-3 h-3 rounded-full" style="background-color: <?= $task['subgoal_color'] ?>"></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Weekly Progress Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-lg"><?= __('weekly_progress') ?></h2>
            </div>
            <div class="p-4">
                <canvas id="weeklyChart" height="200"></canvas>
            </div>
            <div class="px-4 pb-4 grid grid-cols-3 gap-4 text-center border-t border-gray-100 dark:border-gray-700 pt-4">
                <div>
                    <p class="text-2xl font-bold text-primary-600"><?= $weeklySummary['total_points'] ?></p>
                    <p class="text-xs text-gray-500"><?= __('points') ?></p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600"><?= $weeklySummary['avg_score'] ?>%</p>
                    <p class="text-xs text-gray-500"><?= __('average_score') ?></p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-orange-600"><?= $weeklySummary['active_days'] ?>/7</p>
                    <p class="text-xs text-gray-500"><?= __('days') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Mandalart Preview -->
        <?php if ($activeGoal): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold"><?= __('nav_mandalart') ?></h2>
                <a href="pages/mandalart.php" class="text-sm text-primary-600 dark:text-primary-400 hover:underline"><?= __('view_all') ?></a>
            </div>
            <div class="p-4">
                <div class="text-center mb-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('core_goal') ?></p>
                    <p class="font-semibold text-lg"><?= e($activeGoal['title']) ?></p>
                </div>
                <!-- Mini Mandalart Grid -->
                <div class="mandalart-grid gap-1">
                    <?php
                    $subgoals = SubGoal::getAllByGoal($activeGoal['id']);
                    $positions = [1, 2, 3, 4, 0, 5, 6, 7, 8]; // Center is position 0 (core goal)
                    foreach ($positions as $idx => $pos):
                        if ($pos === 0): ?>
                        <div class="mandalart-cell bg-primary-500 text-white rounded text-xs font-medium" title="<?= e($activeGoal['title']) ?>">
                            <?= mb_substr($activeGoal['title'], 0, 4) ?>...
                        </div>
                        <?php else:
                            $subgoal = array_filter($subgoals, fn($s) => $s['position'] === $pos);
                            $subgoal = reset($subgoal);
                        ?>
                        <div class="mandalart-cell bg-gray-100 dark:bg-gray-700 rounded text-xs <?= $subgoal ? '' : 'text-gray-400' ?>"
                             style="<?= $subgoal ? 'background-color: ' . $subgoal['color'] . '20; color: ' . $subgoal['color'] : '' ?>"
                             title="<?= $subgoal ? e($subgoal['title']) : '' ?>">
                            <?= $subgoal ? mb_substr($subgoal['title'], 0, 4) : '-' ?>
                        </div>
                        <?php endif;
                    endforeach; ?>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-500"><?= __('level_progress') ?></span>
                        <span class="font-medium"><?= number_format($activeGoal['progress'], 1) ?>%</span>
                    </div>
                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500 rounded-full transition-all duration-500" style="width: <?= $activeGoal['progress'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 text-center">
            <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-feather="target" class="w-8 h-8 text-primary-600 dark:text-primary-400"></i>
            </div>
            <h3 class="font-semibold mb-2"><?= __('create_mandalart') ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"><?= __('mandalart_tip') ?></p>
            <a href="pages/mandalart.php" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <i data-feather="plus" class="w-4 h-4 mr-2"></i>
                <?= __('start') ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- Recent Achievements -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold"><?= __('recent_achievements') ?></h2>
                <a href="pages/achievements.php" class="text-sm text-primary-600 dark:text-primary-400 hover:underline"><?= __('view_all') ?></a>
            </div>
            <div class="p-4">
                <?php if (empty($recentAchievements)): ?>
                <p class="text-center text-gray-500 dark:text-gray-400 py-4"><?= __('none') ?></p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($recentAchievements as $achievement): ?>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: <?= $achievement['color'] ?>20">
                            <i data-feather="<?= $achievement['icon'] ?: 'award' ?>" class="w-5 h-5" style="color: <?= $achievement['color'] ?>"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate"><?= e(getCurrentLanguage() === 'ko' ? $achievement['name_ko'] : $achievement['name_en']) ?></p>
                            <p class="text-xs text-gray-500"><?= formatDate($achievement['earned_at'], 'Y-m-d') ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Suggestions -->
        <?php if (!empty($suggestions)): ?>
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl shadow-sm border border-amber-200 dark:border-amber-800">
            <div class="p-4 border-b border-amber-200 dark:border-amber-800">
                <h2 class="font-semibold text-amber-800 dark:text-amber-200 flex items-center">
                    <i data-feather="lightbulb" class="w-5 h-5 mr-2"></i>
                    <?= __('suggestion') ?>
                </h2>
            </div>
            <div class="p-4 space-y-3">
                <?php foreach ($suggestions as $suggestion): ?>
                    <?php if ($suggestion['type'] === 'reduce_frequency'): ?>
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p><?= __('suggestion_reduce_frequency', ['task' => e($suggestion['task_title']), 'count' => $suggestion['suggested_frequency']]) ?></p>
                        <button onclick="acceptSuggestion('reduce_frequency', <?= $suggestion['task_id'] ?>, <?= $suggestion['suggested_frequency'] ?>)"
                                class="mt-2 px-3 py-1 bg-amber-600 text-white rounded text-xs hover:bg-amber-700">
                            <?= __('confirm') ?>
                        </button>
                    </div>
                    <?php elseif ($suggestion['type'] === 'rest'): ?>
                    <p class="text-sm text-amber-800 dark:text-amber-200"><?= __($suggestion['message']) ?></p>
                    <?php elseif ($suggestion['type'] === 'celebrate'): ?>
                    <p class="text-sm text-amber-800 dark:text-amber-200"><?= __('suggestion_celebrate', ['count' => $suggestion['streak_days']]) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 7 Habits Quick Tips -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold"><?= __('seven_habits') ?></h2>
            </div>
            <div class="p-4">
                <?php
                $habits = [
                    ['key' => 'habit_3', 'icon' => 'list', 'color' => 'blue'],
                    ['key' => 'habit_2', 'icon' => 'eye', 'color' => 'green'],
                    ['key' => 'habit_7', 'icon' => 'refresh-cw', 'color' => 'purple'],
                ];
                $randomHabit = $habits[array_rand($habits)];
                ?>
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 rounded-full bg-<?= $randomHabit['color'] ?>-100 dark:bg-<?= $randomHabit['color'] ?>-900/30 flex items-center justify-center flex-shrink-0">
                        <i data-feather="<?= $randomHabit['icon'] ?>" class="w-5 h-5 text-<?= $randomHabit['color'] ?>-600 dark:text-<?= $randomHabit['color'] ?>-400"></i>
                    </div>
                    <div>
                        <p class="font-medium text-sm"><?= __($randomHabit['key']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= __($randomHabit['key'] . '_desc') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Achievement Modal -->
<?php if (!empty($newAchievements)): ?>
<div id="achievement-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 max-w-sm w-full mx-4 text-center animate-fadeIn">
        <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center" style="background-color: <?= $newAchievements[0]['color'] ?>20">
            <i data-feather="<?= $newAchievements[0]['icon'] ?: 'award' ?>" class="w-10 h-10" style="color: <?= $newAchievements[0]['color'] ?>"></i>
        </div>
        <h3 class="text-xl font-bold mb-2"><?= __('achievement_unlocked') ?></h3>
        <p class="text-lg font-semibold text-primary-600 mb-2"><?= e(getCurrentLanguage() === 'ko' ? $newAchievements[0]['name_ko'] : $newAchievements[0]['name_en']) ?></p>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-4"><?= e(getCurrentLanguage() === 'ko' ? $newAchievements[0]['description_ko'] : $newAchievements[0]['description_en']) ?></p>
        <?php if ($newAchievements[0]['points_reward'] > 0): ?>
        <p class="text-yellow-600 font-semibold mb-4">+<?= $newAchievements[0]['points_reward'] ?> <?= __('points') ?></p>
        <?php endif; ?>
        <button onclick="closeAchievementModal()" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <?= __('confirm') ?>
        </button>
    </div>
</div>
<?php endif; ?>

<script>
    // Weekly chart data
    const weeklyData = <?= json_encode($weeklySummary['daily_data']) ?>;
    const days = ['<?= __('mon') ?>', '<?= __('tue') ?>', '<?= __('wed') ?>', '<?= __('thu') ?>', '<?= __('fri') ?>', '<?= __('sat') ?>', '<?= __('sun') ?>'];

    // Prepare chart data
    const chartLabels = [];
    const chartScores = [];
    const chartPoints = [];

    let currentDate = new Date('<?= $weeklySummary['start_date'] ?>');
    for (let i = 0; i < 7; i++) {
        const dateStr = currentDate.toISOString().split('T')[0];
        chartLabels.push(days[i]);

        if (weeklyData[dateStr]) {
            chartScores.push(weeklyData[dateStr].execution_score);
            chartPoints.push(weeklyData[dateStr].points_earned);
        } else {
            chartScores.push(0);
            chartPoints.push(0);
        }

        currentDate.setDate(currentDate.getDate() + 1);
    }

    // Create chart
    const ctx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: '<?= __('execution_score') ?>',
                data: chartScores,
                backgroundColor: 'rgba(59, 130, 246, 0.5)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y'
            }, {
                label: '<?= __('points') ?>',
                data: chartPoints,
                type: 'line',
                borderColor: 'rgb(245, 158, 11)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    max: 100,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Toggle task completion
    function toggleTaskComplete(taskId) {
        fetch('api/tasks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'toggle_complete',
                task_id: taskId,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || '<?= __('error_generic') ?>', 'error');
            }
        });
    }

    // Accept suggestion
    function acceptSuggestion(type, taskId, value) {
        fetch('api/suggestions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'accept',
                type: type,
                task_id: taskId,
                value: value,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('<?= __('changes_saved') ?>', 'success');
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    // Close achievement modal
    function closeAchievementModal() {
        document.getElementById('achievement-modal').remove();
    }

    // Initialize
    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
