<?php
/**
 * Daily Tracker Page
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: daily.php");
    exit;
}

Auth::require();

$user = Auth::user();
$userId = Auth::id();

$today = date('Y-m-d');
$selectedDate = $_GET['date'] ?? $today;

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = $today;
}

// Get daily log
$dailyLog = DailyLog::getOrCreate($userId, $selectedDate);

// Get all available tasks
$allTasks = Task::getAllByUser($userId);

// Get planned tasks for selected date
$plannedTasks = Database::fetchAll(
    "SELECT dp.*, t.title, t.priority, t.task_type, t.frequency, s.color as subgoal_color, s.title as subgoal_title,
            CASE WHEN tl.id IS NOT NULL THEN 1 ELSE 0 END as is_completed,
            tl.notes as completion_notes
     FROM daily_plans dp
     JOIN tasks t ON dp.task_id = t.id
     JOIN subgoals s ON t.subgoal_id = s.id
     LEFT JOIN task_logs tl ON t.id = tl.task_id AND tl.log_date = ? AND tl.user_id = ?
     WHERE dp.user_id = ? AND dp.plan_date = ?
     ORDER BY dp.is_priority DESC, dp.sort_order",
    [$selectedDate, $userId, $userId, $selectedDate]
);

// Get recurring tasks that should appear today
$dayOfWeek = date('N', strtotime($selectedDate)); // 1=Mon, 7=Sun
$recurringTasks = Database::fetchAll(
    "SELECT t.*, s.color as subgoal_color, s.title as subgoal_title,
            CASE WHEN tl.id IS NOT NULL THEN 1 ELSE 0 END as is_completed
     FROM tasks t
     JOIN subgoals s ON t.subgoal_id = s.id
     JOIN goals g ON s.goal_id = g.id
     LEFT JOIN task_logs tl ON t.id = tl.task_id AND tl.log_date = ? AND tl.user_id = ?
     WHERE g.user_id = ? AND g.status = 'active'
     AND (t.task_type = 'daily'
          OR (t.task_type = 'weekly' AND FIND_IN_SET(?, t.frequency_days) > 0))",
    [$selectedDate, $userId, $userId, $dayOfWeek]
);

// Calculate stats
$completedCount = count(array_filter($plannedTasks, fn($t) => $t['is_completed']));
$totalCount = count($plannedTasks);
$priorityCount = count(array_filter($plannedTasks, fn($t) => $t['is_priority']));
$priorityCompleted = count(array_filter($plannedTasks, fn($t) => $t['is_priority'] && $t['is_completed']));

$pageTitle = __('daily_tracker');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Header with Date Navigation -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold"><?= __('daily_tracker') ?></h1>
            <p class="text-gray-500 dark:text-gray-400"><?= __('first_things_first') ?></p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="?date=<?= date('Y-m-d', strtotime($selectedDate . ' -1 day')) ?>"
               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i data-feather="chevron-left" class="w-5 h-5"></i>
            </a>
            <input type="date" value="<?= $selectedDate ?>" onchange="location.href='?date='+this.value"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
            <a href="?date=<?= date('Y-m-d', strtotime($selectedDate . ' +1 day')) ?>"
               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg <?= $selectedDate >= $today ? 'opacity-50 pointer-events-none' : '' ?>">
                <i data-feather="chevron-right" class="w-5 h-5"></i>
            </a>
            <?php if ($selectedDate !== $today): ?>
            <a href="?date=<?= $today ?>" class="px-3 py-2 bg-primary-600 text-white rounded-lg text-sm">
                <?= __('today') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('execution_score') ?></p>
            <p class="text-3xl font-bold mt-1 <?= $dailyLog['execution_score'] >= SCORE_GOOD ? 'text-green-600' : ($dailyLog['execution_score'] >= SCORE_AVERAGE ? 'text-yellow-600' : 'text-red-600') ?>">
                <?= number_format($dailyLog['execution_score'], 0) ?>%
            </p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('completed') ?></p>
            <p class="text-3xl font-bold mt-1"><?= $completedCount ?>/<?= $totalCount ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('set_priority') ?></p>
            <p class="text-3xl font-bold mt-1"><?= $priorityCompleted ?>/<?= $priorityCount ?></p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('points') ?></p>
            <p class="text-3xl font-bold mt-1 text-yellow-600">+<?= $dailyLog['points_earned'] ?></p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Task List -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Today's Tasks -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-lg"><?= __('tasks_today') ?></h2>
                    <button onclick="openAddTasksModal()" class="px-3 py-1 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition-colors">
                        <i data-feather="plus" class="w-4 h-4 inline mr-1"></i>
                        <?= __('add') ?>
                    </button>
                </div>

                <?php if (empty($plannedTasks)): ?>
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-feather="calendar" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 mb-4"><?= __('no_tasks_today') ?></p>
                    <button onclick="openAddTasksModal()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <?= __('plan_your_day') ?>
                    </button>
                </div>
                <?php else: ?>
                <div class="divide-y divide-gray-100 dark:divide-gray-700" id="task-list">
                    <?php foreach ($plannedTasks as $task): ?>
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors task-item <?= $task['is_completed'] ? 'completed' : '' ?>"
                         data-task-id="<?= $task['task_id'] ?>">
                        <div class="flex items-start space-x-3">
                            <button onclick="toggleTask(<?= $task['task_id'] ?>)" class="mt-1 flex-shrink-0">
                                <?php if ($task['is_completed']): ?>
                                <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                    <i data-feather="check" class="w-4 h-4 text-white"></i>
                                </div>
                                <?php else: ?>
                                <div class="w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600 hover:border-primary-500 transition-colors"></div>
                                <?php endif; ?>
                            </button>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <p class="font-medium <?= $task['is_completed'] ? 'line-through text-gray-500' : '' ?>">
                                        <?= e($task['title']) ?>
                                    </p>
                                    <?php if ($task['is_priority']): ?>
                                    <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs rounded-full flex items-center">
                                        <i data-feather="star" class="w-3 h-3 mr-1"></i>
                                        <?= __('set_priority') ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center space-x-3 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <span class="w-2 h-2 rounded-full mr-1" style="background-color: <?= $task['subgoal_color'] ?>"></span>
                                        <?= e($task['subgoal_title']) ?>
                                    </span>
                                    <?php if ($task['task_type'] !== 'one_time'): ?>
                                    <span class="text-xs px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded">
                                        <?= __('task_type_' . $task['task_type']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button onclick="togglePriority(<?= $task['task_id'] ?>, <?= $task['is_priority'] ? 'false' : 'true' ?>)"
                                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors"
                                    title="<?= __('set_priority') ?>">
                                <i data-feather="star" class="w-5 h-5 <?= $task['is_priority'] ? 'text-yellow-500 fill-yellow-500' : 'text-gray-400' ?>"></i>
                            </button>
                            <button onclick="removeFromPlan(<?= $task['task_id'] ?>)"
                                    class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-500 rounded-lg transition-colors">
                                <i data-feather="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Submit Button -->
                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    <button onclick="calculateScore()" class="w-full py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        <i data-feather="check-circle" class="w-5 h-5 inline mr-2"></i>
                        <?= __('submit_daily_log') ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recurring Tasks Suggestion -->
            <?php if (!empty($recurringTasks) && $selectedDate === $today): ?>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-3 flex items-center">
                    <i data-feather="repeat" class="w-5 h-5 mr-2"></i>
                    <?= __('task_type_daily') ?> / <?= __('task_type_weekly') ?>
                </h3>
                <div class="space-y-2">
                    <?php foreach ($recurringTasks as $task):
                        $isPlanned = in_array($task['id'], array_column($plannedTasks, 'task_id'));
                        if ($isPlanned) continue;
                    ?>
                    <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg p-3">
                        <div class="flex items-center space-x-3">
                            <span class="w-3 h-3 rounded-full" style="background-color: <?= $task['subgoal_color'] ?>"></span>
                            <span class="text-sm"><?= e($task['title']) ?></span>
                        </div>
                        <button onclick="addToPlan(<?= $task['id'] ?>)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                            <?= __('add') ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Daily Reflection -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold"><?= __('daily_reflection') ?></h2>
                </div>
                <div class="p-4 space-y-4">
                    <!-- Mood -->
                    <div>
                        <label class="block text-sm font-medium mb-2"><?= __('mood') ?></label>
                        <div class="flex justify-between">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button onclick="setMood(<?= $i ?>)"
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-2xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors mood-btn <?= ($dailyLog['mood'] ?? 0) == $i ? 'bg-primary-100 dark:bg-primary-900/30' : '' ?>"
                                    data-mood="<?= $i ?>">
                                <?= ['', '😢', '😕', '😐', '🙂', '😄'][$i] ?>
                            </button>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Energy -->
                    <div>
                        <label class="block text-sm font-medium mb-2"><?= __('energy_level') ?></label>
                        <div class="flex justify-between">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button onclick="setEnergy(<?= $i ?>)"
                                    class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors energy-btn <?= ($dailyLog['energy'] ?? 0) == $i ? 'bg-yellow-100 dark:bg-yellow-900/30' : '' ?>"
                                    data-energy="<?= $i ?>">
                                <i data-feather="battery" class="w-5 h-5" style="opacity: <?= $i * 0.2 ?>"></i>
                            </button>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium mb-2"><?= __('notes') ?></label>
                        <textarea id="daily-notes" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                  placeholder="<?= __('reflection_prompt') ?>"><?= e($dailyLog['notes'] ?? '') ?></textarea>
                    </div>

                    <button onclick="saveReflection()" class="w-full py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 transition-colors">
                        <?= __('save') ?>
                    </button>
                </div>
            </div>

            <!-- Score Feedback -->
            <div class="bg-gradient-to-br <?= $dailyLog['execution_score'] >= SCORE_GOOD ? 'from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-green-200 dark:border-green-800' : ($dailyLog['execution_score'] >= SCORE_AVERAGE ? 'from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-yellow-200 dark:border-yellow-800' : 'from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-red-200 dark:border-red-800') ?> rounded-xl p-4 border">
                <div class="text-center">
                    <div class="text-4xl mb-2">
                        <?= $dailyLog['execution_score'] >= SCORE_EXCELLENT ? '🎉' : ($dailyLog['execution_score'] >= SCORE_GOOD ? '👍' : ($dailyLog['execution_score'] >= SCORE_AVERAGE ? '💪' : '🌱')) ?>
                    </div>
                    <p class="font-semibold <?= $dailyLog['execution_score'] >= SCORE_GOOD ? 'text-green-800 dark:text-green-200' : ($dailyLog['execution_score'] >= SCORE_AVERAGE ? 'text-yellow-800 dark:text-yellow-200' : 'text-red-800 dark:text-red-200') ?>">
                        <?= $dailyLog['execution_score'] >= SCORE_EXCELLENT ? __('score_excellent') : ($dailyLog['execution_score'] >= SCORE_GOOD ? __('score_good') : ($dailyLog['execution_score'] >= SCORE_AVERAGE ? __('score_average') : __('score_poor'))) ?>
                    </p>
                </div>
            </div>

            <!-- 7 Habits Reminder -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                        <i data-feather="book-open" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm"><?= __('habit_3') ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= __('habit_3_desc') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Tasks Modal -->
<div id="add-tasks-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden flex flex-col animate-fadeIn">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold"><?= __('select_tasks') ?></h3>
            <button onclick="closeAddTasksModal()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="overflow-y-auto flex-1">
            <?php
            $tasksBySubgoal = [];
            foreach ($allTasks as $task) {
                $tasksBySubgoal[$task['subgoal_title']][] = $task;
            }
            foreach ($tasksBySubgoal as $subgoalTitle => $tasks): ?>
            <div class="mb-4">
                <h4 class="font-medium text-sm text-gray-500 dark:text-gray-400 mb-2 flex items-center">
                    <span class="w-3 h-3 rounded-full mr-2" style="background-color: <?= $tasks[0]['subgoal_color'] ?>"></span>
                    <?= e($subgoalTitle) ?>
                </h4>
                <div class="space-y-1">
                    <?php foreach ($tasks as $task):
                        $isPlanned = in_array($task['id'], array_column($plannedTasks, 'task_id'));
                    ?>
                    <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <label class="flex items-center space-x-3 flex-1 cursor-pointer">
                            <input type="checkbox" class="task-checkbox w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500"
                                   value="<?= $task['id'] ?>" <?= $isPlanned ? 'checked disabled' : '' ?>>
                            <span class="text-sm <?= $isPlanned ? 'text-gray-400' : '' ?>"><?= e($task['title']) ?></span>
                        </label>
                        <span class="text-xs text-gray-400"><?= __('task_type_' . $task['task_type']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="addSelectedTasks()" class="w-full py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                <?= __('add') ?>
            </button>
        </div>
    </div>
</div>

<script>
    const selectedDate = '<?= $selectedDate ?>';

    function openAddTasksModal() {
        document.getElementById('add-tasks-modal').classList.remove('hidden');
        document.getElementById('add-tasks-modal').classList.add('flex');
    }

    function closeAddTasksModal() {
        document.getElementById('add-tasks-modal').classList.add('hidden');
        document.getElementById('add-tasks-modal').classList.remove('flex');
    }

    function addSelectedTasks() {
        const checkboxes = document.querySelectorAll('.task-checkbox:checked:not(:disabled)');
        const taskIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        if (taskIds.length === 0) {
            closeAddTasksModal();
            return;
        }

        fetch('../api/daily.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'add_to_plan',
                task_ids: taskIds,
                date: selectedDate,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function addToPlan(taskId) {
        fetch('../api/daily.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'add_to_plan',
                task_ids: [taskId],
                date: selectedDate,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function removeFromPlan(taskId) {
        fetch('../api/daily.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'remove_from_plan',
                task_id: taskId,
                date: selectedDate,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function toggleTask(taskId) {
        fetch('../api/tasks.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'toggle_complete',
                task_id: taskId,
                date: selectedDate,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 300);
            }
        });
    }

    function togglePriority(taskId, isPriority) {
        fetch('../api/daily.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'set_priority',
                task_id: taskId,
                is_priority: isPriority,
                date: selectedDate,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function calculateScore() {
        fetch('../api/daily.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'calculate_score',
                date: selectedDate,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('<?= __('points_earned', ['points' => "' + data.points + '"]) ?>', 'success');
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    function setMood(mood) {
        document.querySelectorAll('.mood-btn').forEach(btn => {
            btn.classList.toggle('bg-primary-100', btn.dataset.mood == mood);
            btn.classList.toggle('dark:bg-primary-900/30', btn.dataset.mood == mood);
        });
        saveReflectionField('mood', mood);
    }

    function setEnergy(energy) {
        document.querySelectorAll('.energy-btn').forEach(btn => {
            btn.classList.toggle('bg-yellow-100', btn.dataset.energy == energy);
            btn.classList.toggle('dark:bg-yellow-900/30', btn.dataset.energy == energy);
        });
        saveReflectionField('energy', energy);
    }

    function saveReflectionField(field, value) {
        fetch('../api/daily.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'update_reflection',
                date: selectedDate,
                field: field,
                value: value,
                csrf_token: csrfToken
            })
        });
    }

    function saveReflection() {
        const notes = document.getElementById('daily-notes').value;
        fetch('../api/daily.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'update_reflection',
                date: selectedDate,
                field: 'notes',
                value: notes,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('<?= __('changes_saved') ?>', 'success');
            }
        });
    }

    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
