<?php
/**
 * Daily Tracker API
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
$date = $input['date'] ?? date('Y-m-d');

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    jsonResponse(['success' => false, 'message' => 'Invalid date']);
}

switch ($action) {
    case 'add_to_plan':
        $taskIds = $input['task_ids'] ?? [];

        if (empty($taskIds)) {
            jsonResponse(['success' => false, 'message' => 'No tasks selected']);
        }

        $added = 0;
        foreach ($taskIds as $taskId) {
            $taskId = (int)$taskId;

            // Verify ownership
            $task = Task::getById($taskId);
            if (!$task) continue;

            $goal = Goal::getById($task['goal_id']);
            if (!$goal || $goal['user_id'] !== $userId) continue;

            // Check if already in plan
            $existing = Database::fetch(
                "SELECT id FROM daily_plans WHERE user_id = ? AND task_id = ? AND plan_date = ?",
                [$userId, $taskId, $date]
            );

            if (!$existing) {
                Database::insert(
                    "INSERT INTO daily_plans (user_id, task_id, plan_date) VALUES (?, ?, ?)",
                    [$userId, $taskId, $date]
                );
                $added++;
            }
        }

        // Update daily log
        DailyLog::getOrCreate($userId, $date);

        jsonResponse(['success' => true, 'added' => $added]);
        break;

    case 'remove_from_plan':
        $taskId = (int)($input['task_id'] ?? 0);

        if (!$taskId) {
            jsonResponse(['success' => false, 'message' => 'Invalid task']);
        }

        Database::delete(
            "DELETE FROM daily_plans WHERE user_id = ? AND task_id = ? AND plan_date = ?",
            [$userId, $taskId, $date]
        );

        // Also remove task log if exists
        Database::delete(
            "DELETE FROM task_logs WHERE user_id = ? AND task_id = ? AND log_date = ?",
            [$userId, $taskId, $date]
        );

        // Recalculate score
        DailyLog::calculateScore($userId, $date);

        jsonResponse(['success' => true]);
        break;

    case 'set_priority':
        $taskId = (int)($input['task_id'] ?? 0);
        $isPriority = (bool)($input['is_priority'] ?? false);

        if (!$taskId) {
            jsonResponse(['success' => false, 'message' => 'Invalid task']);
        }

        Database::update(
            "UPDATE daily_plans SET is_priority = ? WHERE user_id = ? AND task_id = ? AND plan_date = ?",
            [$isPriority ? 1 : 0, $userId, $taskId, $date]
        );

        jsonResponse(['success' => true]);
        break;

    case 'update_reflection':
        $field = $input['field'] ?? '';
        $value = $input['value'] ?? '';

        $allowedFields = ['mood', 'energy', 'notes', 'reflection'];
        if (!in_array($field, $allowedFields)) {
            jsonResponse(['success' => false, 'message' => 'Invalid field']);
        }

        DailyLog::update($userId, $date, [$field => $value]);

        jsonResponse(['success' => true]);
        break;

    case 'calculate_score':
        $score = DailyLog::calculateScore($userId, $date);

        // Get points earned today
        $points = Database::fetch(
            "SELECT points_earned FROM daily_logs WHERE user_id = ? AND log_date = ?",
            [$userId, $date]
        )['points_earned'] ?? 0;

        // Check achievements
        $newAchievements = Achievement::checkAndAward($userId);

        // Check level up
        $levelUp = User::checkLevelUp($userId);

        jsonResponse([
            'success' => true,
            'score' => $score,
            'points' => $points,
            'new_achievements' => $newAchievements,
            'level_up' => $levelUp
        ]);
        break;

    case 'get_summary':
        $log = DailyLog::getOrCreate($userId, $date);

        $tasks = Database::fetchAll(
            "SELECT dp.*, t.title, t.priority, s.color,
                    CASE WHEN tl.id IS NOT NULL THEN 1 ELSE 0 END as completed
             FROM daily_plans dp
             JOIN tasks t ON dp.task_id = t.id
             JOIN subgoals s ON t.subgoal_id = s.id
             LEFT JOIN task_logs tl ON t.id = tl.task_id AND tl.log_date = ?
             WHERE dp.user_id = ? AND dp.plan_date = ?
             ORDER BY dp.is_priority DESC, dp.sort_order",
            [$date, $userId, $date]
        );

        jsonResponse([
            'success' => true,
            'log' => $log,
            'tasks' => $tasks
        ]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
