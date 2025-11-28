<?php
/**
 * Tasks API
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (!isAjax()) {
    jsonResponse(['error' => 'Invalid request'], 400);
}

Auth::require();

$userId = Auth::id();
$input = json_decode(file_get_contents('php://input'), true);

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    jsonResponse(['error' => __('error_csrf')], 403);
}

$action = $input['action'] ?? '';

switch ($action) {
    case 'save':
        $subgoalId = (int)($input['subgoal_id'] ?? 0);
        $position = (int)($input['position'] ?? 0);
        $title = trim($input['title'] ?? '');
        $taskId = (int)($input['task_id'] ?? 0);

        if (!$subgoalId || empty($title)) {
            jsonResponse(['success' => false, 'message' => 'Invalid data']);
        }

        // Verify ownership
        $subgoal = SubGoal::getById($subgoalId);
        if (!$subgoal) {
            jsonResponse(['success' => false, 'message' => 'Subgoal not found']);
        }

        $goal = Goal::getById($subgoal['goal_id']);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($taskId) {
            // Update existing task
            Task::update($taskId, [
                'title' => $title,
                'task_type' => $input['task_type'] ?? 'one_time',
                'frequency' => $input['frequency'] ?? null,
                'priority' => $input['priority'] ?? 'medium'
            ]);
        } else {
            // Create new task
            $taskId = Task::create([
                'subgoal_id' => $subgoalId,
                'position' => $position ?: null,
                'title' => $title,
                'task_type' => $input['task_type'] ?? 'one_time',
                'frequency' => $input['frequency'] ?? null,
                'priority' => $input['priority'] ?? 'medium'
            ]);
        }

        // Update subgoal and goal progress
        SubGoal::updateProgress($subgoalId);
        Goal::updateProgress($subgoal['goal_id']);

        jsonResponse([
            'success' => true,
            'task_id' => $taskId,
            'message' => __('task_saved')
        ]);
        break;

    case 'toggle_complete':
        $taskId = (int)($input['task_id'] ?? 0);
        $date = $input['date'] ?? date('Y-m-d');

        if (!$taskId) {
            jsonResponse(['success' => false, 'message' => 'Invalid task']);
        }

        // Verify ownership through task chain
        $task = Task::getById($taskId);
        if (!$task) {
            jsonResponse(['success' => false, 'message' => 'Task not found']);
        }

        $goal = Goal::getById($task['goal_id']);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if already completed today
        $existingLog = Database::fetch(
            "SELECT id FROM task_logs WHERE task_id = ? AND log_date = ? AND user_id = ?",
            [$taskId, $date, $userId]
        );

        if ($existingLog) {
            // Uncomplete
            Task::uncomplete($taskId, $userId);
            $message = __('mark_incomplete');
            $points = 0;
        } else {
            // Complete
            $result = Task::complete($taskId, $userId);
            $message = __('task_completed');
            $points = $result['points'] ?? 0;
        }

        // Recalculate daily score
        DailyLog::calculateScore($userId, $date);

        // Check for level up
        $levelUp = User::checkLevelUp($userId);

        // Check achievements
        Achievement::checkAndAward($userId);

        jsonResponse([
            'success' => true,
            'message' => $message,
            'points' => $points,
            'level_up' => $levelUp
        ]);
        break;

    case 'delete':
        $taskId = (int)($input['task_id'] ?? 0);

        if (!$taskId) {
            jsonResponse(['success' => false, 'message' => 'Invalid task']);
        }

        $task = Task::getById($taskId);
        if (!$task) {
            jsonResponse(['success' => false, 'message' => 'Task not found']);
        }

        $goal = Goal::getById($task['goal_id']);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $subgoalId = $task['subgoal_id'];
        Task::delete($taskId);

        // Update progress
        SubGoal::updateProgress($subgoalId);
        Goal::updateProgress($task['goal_id']);

        jsonResponse(['success' => true, 'message' => __('task_deleted')]);
        break;

    case 'update_status':
        $taskId = (int)($input['task_id'] ?? 0);
        $status = $input['status'] ?? '';

        if (!$taskId || !in_array($status, ['pending', 'in_progress', 'completed', 'skipped'])) {
            jsonResponse(['success' => false, 'message' => 'Invalid data']);
        }

        $task = Task::getById($taskId);
        if (!$task) {
            jsonResponse(['success' => false, 'message' => 'Task not found']);
        }

        $goal = Goal::getById($task['goal_id']);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Task::update($taskId, ['status' => $status]);

        if ($status === 'completed') {
            Task::update($taskId, ['completed_at' => date('Y-m-d H:i:s')]);
        }

        SubGoal::updateProgress($task['subgoal_id']);
        Goal::updateProgress($task['goal_id']);

        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
