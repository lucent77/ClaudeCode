<?php
/**
 * Progress Tracker Page
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: tracker.php");
    exit;
}

Auth::require();

$userId = Auth::id();

// Get date range
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Get monthly data
$monthlyData = DailyLog::getMonthlyData($userId, $year, $month);

// Get weekly summary
$weeklySummary = DailyLog::getWeeklySummary($userId);

// Get overall stats
$stats = User::getStats($userId);

// Calculate monthly stats
$monthlyStats = [
    'total_points' => array_sum(array_column($monthlyData, 'points_earned')),
    'avg_score' => count($monthlyData) > 0 ? array_sum(array_column($monthlyData, 'execution_score')) / count($monthlyData) : 0,
    'active_days' => count($monthlyData),
    'total_tasks' => array_sum(array_column($monthlyData, 'completed_tasks'))
];

// Get subgoal progress
$activeGoal = Goal::getActiveByUser($userId);
$subgoalProgress = [];
if ($activeGoal) {
    $subgoals = SubGoal::getAllByGoal($activeGoal['id']);
    foreach ($subgoals as $sg) {
        $subgoalProgress[] = [
            'title' => $sg['title'],
            'color' => $sg['color'],
            'progress' => $sg['progress']
        ];
    }
}

$pageTitle = __('nav_tracker');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><?= __('nav_tracker') ?></h1>
            <p class="text-gray-500 dark:text-gray-400"><?= __('weekly_progress') ?></p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="?year=<?= $month == 1 ? $year - 1 : $year ?>&month=<?= $month == 1 ? 12 : $month - 1 ?>"
               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i data-feather="chevron-left" class="w-5 h-5"></i>
            </a>
            <span class="px-4 py-2 font-medium"><?= $year ?>-<?= str_pad($month, 2, '0', STR_PAD_LEFT) ?></span>
            <a href="?year=<?= $month == 12 ? $year + 1 : $year ?>&month=<?= $month == 12 ? 1 : $month + 1 ?>"
               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i data-feather="chevron-right" class="w-5 h-5"></i>
            </a>
        </div>
    </div>

    <!-- Monthly Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('monthly_score') ?></p>
            <p class="text-2xl font-bold mt-1"><?= number_format($monthlyStats['avg_score'], 1) ?>%</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('points') ?></p>
            <p class="text-2xl font-bold mt-1 text-yellow-600"><?= number_format($monthlyStats['total_points']) ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('days') ?></p>
            <p class="text-2xl font-bold mt-1"><?= $monthlyStats['active_days'] ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('tasks') ?></p>
            <p class="text-2xl font-bold mt-1 text-green-600"><?= $monthlyStats['total_tasks'] ?></p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Charts -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Monthly Trend Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('execution_score') ?> - <?= __('this_month') ?></h2>
                </div>
                <div class="p-4">
                    <canvas id="monthlyChart" height="200"></canvas>
                </div>
            </div>

            <!-- Calendar Heatmap -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('daily_score') ?></h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        <?php foreach ([__('sun'), __('mon'), __('tue'), __('wed'), __('thu'), __('fri'), __('sat')] as $day): ?>
                        <div class="text-center text-xs text-gray-500"><?= $day ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid grid-cols-7 gap-1" id="calendar-heatmap">
                        <?php
                        $firstDay = date('w', strtotime("$year-$month-01"));
                        $daysInMonth = date('t', strtotime("$year-$month-01"));
                        $dataByDate = [];
                        foreach ($monthlyData as $data) {
                            $dataByDate[$data['log_date']] = $data;
                        }

                        // Empty cells before first day
                        for ($i = 0; $i < $firstDay; $i++) {
                            echo '<div class="aspect-square"></div>';
                        }

                        // Days
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $date = sprintf('%s-%s-%02d', $year, $month, $day);
                            $score = $dataByDate[$date]['execution_score'] ?? 0;

                            $bgColor = 'bg-gray-100 dark:bg-gray-700';
                            if ($score > 0) {
                                if ($score >= 90) $bgColor = 'bg-green-500';
                                elseif ($score >= 70) $bgColor = 'bg-green-400';
                                elseif ($score >= 50) $bgColor = 'bg-green-300';
                                elseif ($score >= 30) $bgColor = 'bg-yellow-300';
                                else $bgColor = 'bg-red-300';
                            }

                            echo "<div class='aspect-square rounded $bgColor flex items-center justify-center text-xs' title='$date: {$score}%'>$day</div>";
                        }
                        ?>
                    </div>
                    <div class="flex items-center justify-center space-x-4 mt-4 text-xs text-gray-500">
                        <span class="flex items-center"><span class="w-3 h-3 bg-gray-200 dark:bg-gray-700 rounded mr-1"></span> 0%</span>
                        <span class="flex items-center"><span class="w-3 h-3 bg-red-300 rounded mr-1"></span> &lt;30%</span>
                        <span class="flex items-center"><span class="w-3 h-3 bg-yellow-300 rounded mr-1"></span> 30-50%</span>
                        <span class="flex items-center"><span class="w-3 h-3 bg-green-300 rounded mr-1"></span> 50-70%</span>
                        <span class="flex items-center"><span class="w-3 h-3 bg-green-400 rounded mr-1"></span> 70-90%</span>
                        <span class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded mr-1"></span> &gt;90%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Subgoal Progress -->
            <?php if (!empty($subgoalProgress)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('sub_goals') ?> <?= __('level_progress') ?></h2>
                </div>
                <div class="p-4 space-y-4">
                    <?php foreach ($subgoalProgress as $sg): ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="flex items-center">
                                <span class="w-2 h-2 rounded-full mr-2" style="background-color: <?= $sg['color'] ?>"></span>
                                <?= e($sg['title']) ?>
                            </span>
                            <span class="font-medium"><?= number_format($sg['progress'], 0) ?>%</span>
                        </div>
                        <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" style="width: <?= $sg['progress'] ?>%; background-color: <?= $sg['color'] ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Overall Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('all_time_ranking') ?></h2>
                </div>
                <div class="p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400"><?= __('total_points') ?></span>
                        <span class="font-bold text-yellow-600"><?= number_format($stats['points']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400"><?= __('current_level') ?></span>
                        <span class="font-bold">Lv.<?= $stats['level'] ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400"><?= __('streak_days') ?></span>
                        <span class="font-bold text-orange-600"><?= $stats['streak_days'] ?> <?= __('days') ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400"><?= __('tasks') ?></span>
                        <span class="font-bold text-green-600"><?= number_format($stats['total_tasks_completed']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400"><?= __('badges') ?></span>
                        <span class="font-bold text-purple-600"><?= $stats['badge_count'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Weekly Comparison -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('this_week') ?></h2>
                </div>
                <div class="p-4">
                    <div class="text-center mb-4">
                        <p class="text-4xl font-bold text-primary-600"><?= $weeklySummary['avg_score'] ?>%</p>
                        <p class="text-sm text-gray-500"><?= __('average_score') ?></p>
                    </div>
                    <div class="flex justify-between text-center text-sm">
                        <div>
                            <p class="font-bold"><?= $weeklySummary['total_points'] ?></p>
                            <p class="text-gray-500 text-xs"><?= __('points') ?></p>
                        </div>
                        <div>
                            <p class="font-bold"><?= $weeklySummary['active_days'] ?>/7</p>
                            <p class="text-gray-500 text-xs"><?= __('days') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Monthly chart data
    const monthlyData = <?= json_encode($monthlyData) ?>;

    const labels = monthlyData.map(d => d.log_date.substring(8));
    const scores = monthlyData.map(d => d.execution_score);
    const points = monthlyData.map(d => d.points_earned);

    const ctx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '<?= __('execution_score') ?>',
                data: scores,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
