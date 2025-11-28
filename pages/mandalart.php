<?php
/**
 * Mandalart Page
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: mandalart.php");
    exit;
}

Auth::require();

$user = Auth::user();
$userId = Auth::id();

// Get or create active goal
$activeGoal = Goal::getActiveByUser($userId);
$subgoals = $activeGoal ? SubGoal::getAllByGoal($activeGoal['id']) : [];

// Organize subgoals by position
$subgoalsByPosition = [];
foreach ($subgoals as $sg) {
    $subgoalsByPosition[$sg['position']] = $sg;
}

// Get tasks for each subgoal
$tasksBySubgoal = [];
foreach ($subgoals as $sg) {
    $tasksBySubgoal[$sg['id']] = Task::getAllBySubgoal($sg['id']);
}

// Default colors for subgoals
$defaultColors = [
    '#EF4444', // red
    '#F59E0B', // amber
    '#10B981', // emerald
    '#3B82F6', // blue
    '#8B5CF6', // violet
    '#EC4899', // pink
    '#06B6D4', // cyan
    '#84CC16', // lime
];

$pageTitle = __('mandalart_title');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold"><?= __('mandalart_title') ?></h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1"><?= __('mandalart_tip') ?></p>
    </div>

    <!-- Main Mandalart Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <?php if (!$activeGoal): ?>
        <!-- Create New Goal Form -->
        <div class="max-w-md mx-auto py-8">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-feather="target" class="w-10 h-10 text-primary-600 dark:text-primary-400"></i>
                </div>
                <h2 class="text-xl font-bold mb-2"><?= __('create_mandalart') ?></h2>
                <p class="text-gray-500 dark:text-gray-400"><?= __('enter_core_goal') ?></p>
            </div>
            <form id="create-goal-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2"><?= __('core_goal') ?></label>
                    <input type="text" id="core-goal-input" name="title" required maxlength="200"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="<?= __('goal_placeholder') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2"><?= __('task_description') ?> (<?= __('none') ?>)</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                              placeholder=""></textarea>
                </div>
                <button type="submit"
                        class="w-full py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                    <?= __('start') ?>
                </button>
            </form>
        </div>
        <?php else: ?>
        <!-- Mandalart Grid View -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold"><?= e($activeGoal['title']) ?></h2>
                <?php if ($activeGoal['description']): ?>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1"><?= e($activeGoal['description']) ?></p>
                <?php endif; ?>
            </div>
            <button onclick="openEditGoalModal()" class="px-4 py-2 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors">
                <i data-feather="edit-2" class="w-4 h-4 inline mr-1"></i>
                <?= __('edit') ?>
            </button>
        </div>

        <!-- 9x9 Grid -->
        <div class="grid grid-cols-3 gap-4">
            <?php
            // Position mapping for 3x3 outer grid
            // Each cell contains a 3x3 inner grid
            $outerPositions = [
                [1, 2, 3],  // Top row: subgoal 1, 2, 3
                [4, 0, 5],  // Middle row: subgoal 4, CENTER, 5
                [6, 7, 8]   // Bottom row: subgoal 6, 7, 8
            ];

            foreach ($outerPositions as $rowIdx => $row):
                foreach ($row as $colIdx => $position):
                    if ($position === 0):
                        // Center - Core Goal with its 8 subgoals
                    ?>
                    <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-3 border-2 border-primary-300 dark:border-primary-700">
                        <div class="grid grid-cols-3 gap-1">
                            <?php
                            $centerPositions = [1, 2, 3, 4, 0, 5, 6, 7, 8];
                            foreach ($centerPositions as $cp):
                                if ($cp === 0): ?>
                                <div class="mandalart-cell bg-primary-500 text-white rounded-lg font-semibold text-xs p-2 cursor-pointer hover:bg-primary-600 transition-colors"
                                     onclick="openEditGoalModal()">
                                    <?= e(mb_substr($activeGoal['title'], 0, 20)) ?>
                                </div>
                                <?php else:
                                    $sg = $subgoalsByPosition[$cp] ?? null;
                                    $color = $sg ? $sg['color'] : $defaultColors[$cp - 1];
                                ?>
                                <div class="mandalart-cell rounded-lg text-xs p-1 cursor-pointer transition-all hover:scale-105"
                                     style="background-color: <?= $color ?>20; color: <?= $color ?>"
                                     onclick="openSubgoalModal(<?= $cp ?>, <?= $sg ? "'" . e(addslashes($sg['title'])) . "'" : 'null' ?>, '<?= $color ?>')">
                                    <?= $sg ? e(mb_substr($sg['title'], 0, 8)) : '+' ?>
                                </div>
                                <?php endif;
                            endforeach; ?>
                        </div>
                    </div>
                    <?php else:
                        // Subgoal cell with its 8 tasks
                        $sg = $subgoalsByPosition[$position] ?? null;
                        $tasks = $sg ? ($tasksBySubgoal[$sg['id']] ?? []) : [];
                        $color = $sg ? $sg['color'] : $defaultColors[$position - 1];
                    ?>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 border border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 transition-colors">
                        <div class="grid grid-cols-3 gap-1">
                            <?php
                            $taskPositions = [1, 2, 3, 4, 0, 5, 6, 7, 8];
                            foreach ($taskPositions as $tp):
                                if ($tp === 0): ?>
                                <!-- Center - Subgoal -->
                                <div class="mandalart-cell rounded-lg font-semibold text-xs p-1 cursor-pointer transition-all hover:scale-105"
                                     style="background-color: <?= $color ?>; color: white"
                                     onclick="openSubgoalModal(<?= $position ?>, <?= $sg ? "'" . e(addslashes($sg['title'])) . "'" : 'null' ?>, '<?= $color ?>')"
                                     title="<?= $sg ? e($sg['title']) : __('enter_sub_goal') ?>">
                                    <?= $sg ? e(mb_substr($sg['title'], 0, 8)) : '+' ?>
                                </div>
                                <?php else:
                                    $task = array_filter($tasks, fn($t) => $t['position'] == $tp);
                                    $task = reset($task);
                                ?>
                                <div class="mandalart-cell bg-white dark:bg-gray-800 rounded text-xs p-1 cursor-pointer border border-gray-200 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-400 transition-colors <?= $task && $task['status'] === 'completed' ? 'line-through opacity-50' : '' ?>"
                                     onclick="<?= $sg ? "openTaskModal({$sg['id']}, $tp, " . ($task ? "'" . e(addslashes($task['title'])) . "', {$task['id']}" : 'null, null') . ")" : '' ?>"
                                     title="<?= $task ? e($task['title']) : ($sg ? __('enter_task') : '') ?>">
                                    <?= $task ? e(mb_substr($task['title'], 0, 8)) : ($sg ? '+' : '-') ?>
                                </div>
                                <?php endif;
                            endforeach; ?>
                        </div>
                        <?php if ($sg): ?>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="text-gray-500"><?= count(array_filter($tasks, fn($t) => $t['status'] === 'completed')) ?>/<?= count($tasks) ?></span>
                            <div class="flex-1 mx-2 h-1 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: <?= $sg['progress'] ?>%; background-color: <?= $color ?>"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif;
                endforeach;
            endforeach; ?>
        </div>

        <!-- Legend -->
        <div class="mt-6 flex flex-wrap items-center justify-center gap-4 text-sm">
            <?php foreach ($subgoals as $sg): ?>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded" style="background-color: <?= $sg['color'] ?>"></div>
                <span class="text-gray-600 dark:text-gray-400"><?= e($sg['title']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Progress Summary -->
    <?php if ($activeGoal): ?>
    <div class="mt-6 grid md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-500 dark:text-gray-400"><?= __('sub_goals') ?></span>
                <span class="text-2xl font-bold"><?= count($subgoals) ?>/8</span>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-500 dark:text-gray-400"><?= __('action_tasks') ?></span>
                <?php $totalTasks = array_sum(array_map('count', $tasksBySubgoal)); ?>
                <span class="text-2xl font-bold"><?= $totalTasks ?>/64</span>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-500 dark:text-gray-400"><?= __('level_progress') ?></span>
                <span class="text-2xl font-bold"><?= number_format($activeGoal['progress'], 1) ?>%</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Subgoal Modal -->
<div id="subgoal-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full mx-4 animate-fadeIn">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold"><?= __('sub_goals') ?></h3>
            <button onclick="closeSubgoalModal()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="subgoal-form" class="space-y-4">
            <input type="hidden" id="subgoal-position" name="position">
            <div>
                <label class="block text-sm font-medium mb-2"><?= __('enter_sub_goal') ?></label>
                <input type="text" id="subgoal-title" name="title" required maxlength="200"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2"><?= __('task_description') ?></label>
                <textarea id="subgoal-description" name="description" rows="2"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Color</label>
                <div class="flex space-x-2">
                    <?php foreach ($defaultColors as $idx => $color): ?>
                    <button type="button" onclick="selectSubgoalColor('<?= $color ?>')"
                            class="w-8 h-8 rounded-full border-2 border-transparent hover:scale-110 transition-transform color-option"
                            style="background-color: <?= $color ?>" data-color="<?= $color ?>"></button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="subgoal-color" name="color" value="<?= $defaultColors[0] ?>">
            </div>
            <div class="flex space-x-3">
                <button type="submit" class="flex-1 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                    <?= __('save') ?>
                </button>
                <button type="button" onclick="closeSubgoalModal()" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <?= __('cancel') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Task Modal -->
<div id="task-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full mx-4 animate-fadeIn">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold"><?= __('action_tasks') ?></h3>
            <button onclick="closeTaskModal()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="task-form" class="space-y-4">
            <input type="hidden" id="task-subgoal-id" name="subgoal_id">
            <input type="hidden" id="task-position" name="position">
            <input type="hidden" id="task-id" name="task_id">
            <div>
                <label class="block text-sm font-medium mb-2"><?= __('task_title') ?></label>
                <input type="text" id="task-title" name="title" required maxlength="200"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2"><?= __('task_type') ?></label>
                <select id="task-type" name="task_type"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="one_time"><?= __('task_type_one_time') ?></option>
                    <option value="daily"><?= __('task_type_daily') ?></option>
                    <option value="weekly"><?= __('task_type_weekly') ?></option>
                    <option value="milestone"><?= __('task_type_milestone') ?></option>
                </select>
            </div>
            <div id="frequency-field" class="hidden">
                <label class="block text-sm font-medium mb-2"><?= __('task_frequency') ?></label>
                <select id="task-frequency" name="frequency"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                    <option value="<?= $i ?>"><?= __('times_per_week', ['count' => $i]) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2"><?= __('task_priority') ?></label>
                <div class="flex space-x-2">
                    <button type="button" onclick="selectPriority('low')" class="flex-1 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors priority-btn" data-priority="low">
                        <?= __('priority_low') ?>
                    </button>
                    <button type="button" onclick="selectPriority('medium')" class="flex-1 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors priority-btn" data-priority="medium">
                        <?= __('priority_medium') ?>
                    </button>
                    <button type="button" onclick="selectPriority('high')" class="flex-1 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors priority-btn" data-priority="high">
                        <?= __('priority_high') ?>
                    </button>
                </div>
                <input type="hidden" id="task-priority" name="priority" value="medium">
            </div>
            <div class="flex space-x-3">
                <button type="submit" class="flex-1 py-3 bg-primary-600 text-white rounded-lg font-semibold hover:bg-primary-700 transition-colors">
                    <?= __('save') ?>
                </button>
                <button type="button" id="delete-task-btn" onclick="deleteTask()" class="hidden px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i data-feather="trash-2" class="w-5 h-5"></i>
                </button>
                <button type="button" onclick="closeTaskModal()" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <?= __('cancel') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const goalId = <?= $activeGoal ? $activeGoal['id'] : 'null' ?>;

    // Create goal form
    document.getElementById('create-goal-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/goals.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'create',
                title: formData.get('title'),
                description: formData.get('description'),
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('<?= __('mandalart_saved') ?>', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || '<?= __('error_generic') ?>', 'error');
            }
        });
    });

    // Subgoal modal
    function openSubgoalModal(position, title, color) {
        document.getElementById('subgoal-position').value = position;
        document.getElementById('subgoal-title').value = title || '';
        document.getElementById('subgoal-color').value = color;
        selectSubgoalColor(color);
        document.getElementById('subgoal-modal').classList.remove('hidden');
        document.getElementById('subgoal-modal').classList.add('flex');
    }

    function closeSubgoalModal() {
        document.getElementById('subgoal-modal').classList.add('hidden');
        document.getElementById('subgoal-modal').classList.remove('flex');
    }

    function selectSubgoalColor(color) {
        document.getElementById('subgoal-color').value = color;
        document.querySelectorAll('.color-option').forEach(btn => {
            btn.classList.toggle('border-gray-800', btn.dataset.color === color);
            btn.classList.toggle('dark:border-white', btn.dataset.color === color);
            btn.classList.toggle('border-transparent', btn.dataset.color !== color);
        });
    }

    document.getElementById('subgoal-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/goals.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'save_subgoal',
                goal_id: goalId,
                position: formData.get('position'),
                title: formData.get('title'),
                description: formData.get('description'),
                color: formData.get('color'),
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('<?= __('mandalart_saved') ?>', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || '<?= __('error_generic') ?>', 'error');
            }
        });
    });

    // Task modal
    function openTaskModal(subgoalId, position, title, taskId) {
        document.getElementById('task-subgoal-id').value = subgoalId;
        document.getElementById('task-position').value = position;
        document.getElementById('task-id').value = taskId || '';
        document.getElementById('task-title').value = title || '';
        document.getElementById('task-type').value = 'one_time';
        document.getElementById('frequency-field').classList.add('hidden');
        selectPriority('medium');

        if (taskId) {
            document.getElementById('delete-task-btn').classList.remove('hidden');
        } else {
            document.getElementById('delete-task-btn').classList.add('hidden');
        }

        document.getElementById('task-modal').classList.remove('hidden');
        document.getElementById('task-modal').classList.add('flex');
    }

    function closeTaskModal() {
        document.getElementById('task-modal').classList.add('hidden');
        document.getElementById('task-modal').classList.remove('flex');
    }

    function selectPriority(priority) {
        document.getElementById('task-priority').value = priority;
        document.querySelectorAll('.priority-btn').forEach(btn => {
            const isSelected = btn.dataset.priority === priority;
            btn.classList.toggle('bg-primary-600', isSelected);
            btn.classList.toggle('text-white', isSelected);
            btn.classList.toggle('border-primary-600', isSelected);
        });
    }

    document.getElementById('task-type')?.addEventListener('change', function() {
        const showFrequency = this.value === 'weekly';
        document.getElementById('frequency-field').classList.toggle('hidden', !showFrequency);
    });

    document.getElementById('task-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/tasks.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'save',
                task_id: formData.get('task_id') || null,
                subgoal_id: formData.get('subgoal_id'),
                position: formData.get('position'),
                title: formData.get('title'),
                task_type: formData.get('task_type'),
                frequency: formData.get('frequency'),
                priority: formData.get('priority'),
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('<?= __('task_saved') ?>', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || '<?= __('error_generic') ?>', 'error');
            }
        });
    });

    function deleteTask() {
        if (!confirm('<?= __('confirm') ?>?')) return;

        const taskId = document.getElementById('task-id').value;
        fetch('../api/tasks.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'delete',
                task_id: taskId,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('<?= __('task_deleted') ?>', 'success');
                setTimeout(() => location.reload(), 500);
            }
        });
    }

    function openEditGoalModal() {
        // Simple prompt for now - could be enhanced to a modal
        const newTitle = prompt('<?= __('core_goal') ?>:', '<?= e($activeGoal['title'] ?? '') ?>');
        if (newTitle && newTitle.trim()) {
            fetch('../api/goals.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({
                    action: 'update',
                    goal_id: goalId,
                    title: newTitle.trim(),
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
    }

    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
