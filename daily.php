<?php
/**
 * Daily Task Page
 * 오늘의 할일 페이지
 */

$pageTitle = '오늘의 할일';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Goal.php';
require_once __DIR__ . '/classes/DailyTracker.php';

Session::requireAuth();

$user = Session::getUser();
$goal = new Goal();
$tracker = new DailyTracker();

$today = date('Y-m-d');
$selectedDate = $_GET['date'] ?? $today;

// Get daily plan and logs
$dailyPlan = $tracker->getDailyPlan($user->getId(), $selectedDate);
$taskLogs = $tracker->getTaskLogs($user->getId(), $selectedDate);
$dailyScore = $tracker->getDailyScore($user->getId(), $selectedDate);

// Get available tasks for adding
$availableTasks = $goal->getActiveTasksForUser($user->getId());
$plannedTaskIds = array_column($taskLogs, 'task_id');
$unplannedTasks = array_filter($availableTasks, function($task) use ($plannedTaskIds) {
    return !in_array($task['id'], $plannedTaskIds);
});

// Get suggested tasks
$suggestedTasks = $tracker->suggestTasksForToday($user->getId());

// Calculate current stats
$completedCount = count(array_filter($taskLogs, fn($t) => $t['status'] === 'completed'));
$totalCount = count($taskLogs);
$completionRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
$priorityTasks = array_filter($taskLogs, fn($t) => $t['is_priority']);
$priorityCompleted = count(array_filter($priorityTasks, fn($t) => $t['status'] === 'completed'));

// Get score history for mini chart
$scoreHistory = $tracker->getScoreHistory($user->getId(), 7);
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">오늘의 할일</h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                <?= $selectedDate === $today ? '오늘' : date('n월 j일', strtotime($selectedDate)) ?>의 목표를 달성하세요
            </p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Date Navigation -->
            <div class="flex items-center bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <a href="?date=<?= date('Y-m-d', strtotime($selectedDate . ' -1 day')) ?>"
                   class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-l-xl transition-colors">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <input type="date" id="dateSelector" value="<?= $selectedDate ?>"
                       onchange="window.location.href='?date=' + this.value"
                       class="px-3 py-2 bg-transparent text-gray-900 dark:text-white border-0 focus:ring-0 text-center w-40">
                <a href="?date=<?= date('Y-m-d', strtotime($selectedDate . ' +1 day')) ?>"
                   class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-r-xl transition-colors">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <?php if ($selectedDate !== $today): ?>
            <a href="?date=<?= $today ?>" class="px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 bg-primary-50 dark:bg-primary-900/30 rounded-xl">
                오늘로
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Completion Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">완료율</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?= $completionRate ?>%</p>
                </div>
                <div class="relative w-14 h-14">
                    <svg class="w-14 h-14 transform -rotate-90">
                        <circle cx="28" cy="28" r="24" stroke="#e5e7eb" stroke-width="5" fill="none"/>
                        <circle cx="28" cy="28" r="24"
                                stroke="<?= $completionRate >= 80 ? '#22c55e' : ($completionRate >= 50 ? '#f59e0b' : '#ef4444') ?>"
                                stroke-width="5" fill="none"
                                stroke-dasharray="150.8"
                                stroke-dashoffset="<?= 150.8 - (150.8 * $completionRate / 100) ?>"
                                stroke-linecap="round"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-lg"><?= $completionRate >= 80 ? '😊' : ($completionRate >= 50 ? '😐' : '😔') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Completed -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">완료한 과제</p>
            <div class="mt-1 flex items-baseline gap-1">
                <span class="text-2xl font-bold text-gray-900 dark:text-white"><?= $completedCount ?></span>
                <span class="text-gray-500">/ <?= $totalCount ?></span>
            </div>
            <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-primary-500 to-purple-500 transition-all duration-500"
                     style="width: <?= $completionRate ?>%"></div>
            </div>
        </div>

        <!-- Priority Tasks -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">중요 과제</p>
            <div class="mt-1 flex items-baseline gap-1">
                <span class="text-2xl font-bold text-gray-900 dark:text-white"><?= $priorityCompleted ?></span>
                <span class="text-gray-500">/ <?= count($priorityTasks) ?></span>
            </div>
            <p class="mt-2 text-xs text-gray-500">
                <?php if (count($priorityTasks) > 0 && $priorityCompleted === count($priorityTasks)): ?>
                    <span class="text-green-500">✓ 모든 중요 과제 완료!</span>
                <?php else: ?>
                    소중한 것을 먼저 하세요
                <?php endif; ?>
            </p>
        </div>

        <!-- Execution Score -->
        <div class="bg-gradient-to-br from-primary-500 to-purple-600 rounded-2xl shadow-sm p-5 text-white">
            <p class="text-sm text-white/80">실행 점수</p>
            <p class="text-3xl font-bold mt-1"><?= $dailyScore ? $dailyScore['execution_score'] : 0 ?></p>
            <p class="mt-1 text-sm text-white/80">
                +<?= $dailyScore ? $dailyScore['points_earned'] : 0 ?> 포인트
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Task List -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Morning Intention -->
            <?php if ($selectedDate === $today): ?>
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-2xl p-5 border border-amber-200 dark:border-amber-800">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🌅</span>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-900 dark:text-amber-100">오늘의 의도</h3>
                        <textarea id="morningIntention"
                                  placeholder="오늘 가장 중요하게 집중할 것은 무엇인가요?"
                                  class="mt-2 w-full bg-transparent border-0 p-0 text-amber-800 dark:text-amber-200 placeholder-amber-400 resize-none focus:ring-0"
                                  rows="2"
                                  onblur="saveDailyPlan()"><?= e($dailyPlan['morning_intention'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Task List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">할 일 목록</h2>
                        <button onclick="openAddTaskModal()"
                                class="flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            과제 추가
                        </button>
                    </div>
                </div>

                <?php if (empty($taskLogs)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                        <span class="text-2xl">📋</span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">오늘 계획된 할 일이 없습니다</p>
                    <button onclick="openAddTaskModal()"
                            class="px-4 py-2 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition-colors">
                        할 일 추가하기
                    </button>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php foreach ($taskLogs as $log): ?>
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors task-item"
                         data-log-id="<?= $log['id'] ?>"
                         data-status="<?= $log['status'] ?>">
                        <div class="flex items-start gap-4">
                            <!-- Checkbox -->
                            <button onclick="toggleTask(<?= $log['id'] ?>, '<?= $log['status'] ?>')"
                                    class="mt-0.5 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all
                                           <?= $log['status'] === 'completed'
                                               ? 'bg-green-500 border-green-500 text-white'
                                               : 'border-gray-300 dark:border-gray-600 hover:border-primary-500' ?>">
                                <?php if ($log['status'] === 'completed'): ?>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <?php endif; ?>
                            </button>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: <?= e($log['color']) ?>"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"><?= e($log['sub_goal_title']) ?></span>
                                    <?php if ($log['is_priority']): ?>
                                    <span class="px-1.5 py-0.5 text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded">중요</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="mt-1 font-medium <?= $log['status'] === 'completed' ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-white' ?>">
                                    <?= e($log['title']) ?>
                                </h3>
                                <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span><?= $log['frequency'] === 'daily' ? '매일' : ($log['frequency'] === 'weekly' ? '매주' : '매월') ?></span>
                                    <span>+<?= $log['points_value'] ?>점</span>
                                    <?php if ($log['completion_time']): ?>
                                    <span class="text-green-500">✓ <?= date('H:i', strtotime($log['completion_time'])) ?> 완료</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1">
                                <button onclick="togglePriority(<?= $log['id'] ?>, <?= $log['is_priority'] ? 'false' : 'true' ?>)"
                                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                        title="<?= $log['is_priority'] ? '중요 해제' : '중요 표시' ?>">
                                    <svg class="w-5 h-5 <?= $log['is_priority'] ? 'text-red-500' : 'text-gray-400' ?>" fill="<?= $log['is_priority'] ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                </button>
                                <button onclick="removeTask(<?= $log['id'] ?>)"
                                        class="p-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-400 hover:text-red-500 transition-colors"
                                        title="삭제">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Evening Reflection -->
            <?php if ($selectedDate === $today && $completedCount > 0): ?>
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-5 border border-indigo-200 dark:border-indigo-800">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">🌙</span>
                    <div class="flex-1">
                        <h3 class="font-semibold text-indigo-900 dark:text-indigo-100">오늘의 회고</h3>
                        <textarea id="eveningReflection"
                                  placeholder="오늘 하루를 돌아보며, 무엇을 배웠나요?"
                                  class="mt-2 w-full bg-transparent border-0 p-0 text-indigo-800 dark:text-indigo-200 placeholder-indigo-400 resize-none focus:ring-0"
                                  rows="2"
                                  onblur="saveDailyPlan()"><?= e($dailyPlan['evening_reflection'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Add Suggestions -->
            <?php if (!empty($suggestedTasks) && $selectedDate === $today): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">추천 과제</h3>
                <div class="space-y-3">
                    <?php foreach (array_slice($suggestedTasks, 0, 5) as $task): ?>
                    <button onclick="quickAddTask(<?= $task['id'] ?>)"
                            class="w-full flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all text-left">
                        <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: <?= e($task['color']) ?>"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?= e($task['title']) ?></p>
                            <p class="text-xs text-gray-500"><?= e($task['sub_goal_title']) ?></p>
                        </div>
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Weekly Progress -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">주간 실행률</h3>
                <div class="grid grid-cols-7 gap-1">
                    <?php
                    $weekDays = ['월', '화', '수', '목', '금', '토', '일'];
                    $scoresByDate = [];
                    foreach ($scoreHistory as $score) {
                        $scoresByDate[$score['score_date']] = $score['execution_score'];
                    }

                    for ($i = 6; $i >= 0; $i--):
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $dayIndex = (date('N', strtotime($date)) - 1);
                        $score = $scoresByDate[$date] ?? 0;
                        $isToday = $date === $today;
                    ?>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1"><?= $weekDays[$dayIndex] ?></p>
                        <div class="w-8 h-8 mx-auto rounded-lg flex items-center justify-center text-xs font-medium
                                    <?= $isToday ? 'ring-2 ring-primary-500' : '' ?>
                                    <?= $score >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' :
                                        ($score >= 50 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                        ($score > 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' :
                                        'bg-gray-100 text-gray-400 dark:bg-gray-700')) ?>">
                            <?= $score > 0 ? $score : '-' ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Today's Mood -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">오늘의 컨디션</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">에너지 레벨</label>
                        <div class="flex gap-2 mt-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button onclick="setEnergyLevel(<?= $i ?>)"
                                    class="flex-1 py-2 rounded-lg border text-sm transition-all
                                           <?= ($dailyPlan['energy_level'] ?? 0) == $i
                                               ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700'
                                               : 'border-gray-200 dark:border-gray-700 hover:border-primary-300' ?>">
                                <?= ['😫', '😔', '😐', '😊', '🔥'][$i-1] ?>
                            </button>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7 Habits Tip -->
            <div class="bg-gradient-to-br from-primary-500 to-purple-600 rounded-2xl p-5 text-white">
                <h3 class="font-semibold mb-2">💡 오늘의 습관 팁</h3>
                <p class="text-sm text-white/90">
                    <?php
                    $tips = [
                        '"소중한 것을 먼저 하라" - 가장 중요한 과제를 아침에 먼저 처리하세요.',
                        '"끝을 생각하며 시작하라" - 오늘의 일이 장기 목표에 어떻게 기여하는지 생각해보세요.',
                        '"주도적으로 행동하라" - 환경 탓보다 내가 할 수 있는 것에 집중하세요.',
                        '"끊임없이 쇄신하라" - 오늘 하루도 성장의 기회입니다.',
                        '"시너지를 내라" - 다른 사람과 협력하면 더 큰 성과를 낼 수 있습니다.'
                    ];
                    echo $tips[array_rand($tips)];
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div id="addTaskModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddTaskModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">오늘 할 일 추가</h3>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <?php if (empty($unplannedTasks)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">모든 활성 과제가 이미 추가되어 있습니다</p>
                    <a href="/mandalart.php" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">
                        만다라트에서 새 과제 만들기 →
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-2">
                    <?php
                    // Group by sub goal
                    $tasksBySubGoal = [];
                    foreach ($unplannedTasks as $task) {
                        $sgTitle = $task['sub_goal_title'];
                        if (!isset($tasksBySubGoal[$sgTitle])) {
                            $tasksBySubGoal[$sgTitle] = ['color' => $task['color'], 'tasks' => []];
                        }
                        $tasksBySubGoal[$sgTitle]['tasks'][] = $task;
                    }
                    ?>

                    <?php foreach ($tasksBySubGoal as $sgTitle => $group): ?>
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-3 h-3 rounded-full" style="background-color: <?= e($group['color']) ?>"></span>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white"><?= e($sgTitle) ?></h4>
                        </div>
                        <div class="pl-5 space-y-2">
                            <?php foreach ($group['tasks'] as $task): ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-300 cursor-pointer transition-all">
                                <input type="checkbox" name="tasks[]" value="<?= $task['id'] ?>"
                                       class="w-5 h-5 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= e($task['title']) ?></p>
                                    <p class="text-xs text-gray-500">
                                        <?= $task['frequency'] === 'daily' ? '매일' : ($task['frequency'] === 'weekly' ? '매주' : '매월') ?>
                                        · +<?= $task['points_value'] ?>점
                                    </p>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="p-5 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button onclick="closeAddTaskModal()"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                    취소
                </button>
                <button onclick="addSelectedTasks()"
                        class="px-6 py-2 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-all">
                    추가하기
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const selectedDate = '<?= $selectedDate ?>';
const dailyPlanId = <?= $dailyPlan['id'] ?>;

// Toggle task completion
async function toggleTask(logId, currentStatus) {
    const action = currentStatus === 'completed' ? 'uncomplete' : 'complete';
    const response = await apiRequest('/api/daily.php', 'POST', {
        action,
        log_id: logId
    });

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

// Toggle priority
async function togglePriority(logId, isPriority) {
    const response = await apiRequest('/api/daily.php', 'POST', {
        action: 'set_priority',
        log_id: logId,
        is_priority: isPriority
    });

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

// Remove task from plan
async function removeTask(logId) {
    if (!confirm('이 과제를 오늘 계획에서 제거하시겠습니까?')) return;

    const response = await apiRequest('/api/daily.php', 'POST', {
        action: 'remove',
        log_id: logId
    });

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

// Quick add task
async function quickAddTask(taskId) {
    const response = await apiRequest('/api/daily.php', 'POST', {
        action: 'add',
        task_id: taskId,
        date: selectedDate
    });

    if (response.success) {
        location.reload();
    } else {
        showToast(response.error || '오류가 발생했습니다', 'error');
    }
}

// Add Task Modal
function openAddTaskModal() {
    document.getElementById('addTaskModal').classList.remove('hidden');
}

function closeAddTaskModal() {
    document.getElementById('addTaskModal').classList.add('hidden');
}

async function addSelectedTasks() {
    const checkboxes = document.querySelectorAll('#addTaskModal input[type="checkbox"]:checked');
    const taskIds = Array.from(checkboxes).map(cb => cb.value);

    if (taskIds.length === 0) {
        showToast('추가할 과제를 선택해주세요', 'warning');
        return;
    }

    for (const taskId of taskIds) {
        await apiRequest('/api/daily.php', 'POST', {
            action: 'add',
            task_id: taskId,
            date: selectedDate
        });
    }

    location.reload();
}

// Save daily plan
async function saveDailyPlan() {
    const morningIntention = document.getElementById('morningIntention')?.value || '';
    const eveningReflection = document.getElementById('eveningReflection')?.value || '';

    await apiRequest('/api/daily.php', 'POST', {
        action: 'update_plan',
        plan_id: dailyPlanId,
        morning_intention: morningIntention,
        evening_reflection: eveningReflection
    });
}

// Set energy level
async function setEnergyLevel(level) {
    await apiRequest('/api/daily.php', 'POST', {
        action: 'update_plan',
        plan_id: dailyPlanId,
        energy_level: level
    });
    location.reload();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
