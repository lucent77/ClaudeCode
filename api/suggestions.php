<?php
/**
 * Suggestions API
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
    case 'accept':
        $type = $input['type'] ?? '';
        $taskId = (int)($input['task_id'] ?? 0);
        $value = $input['value'] ?? null;

        if ($type === 'reduce_frequency' && $taskId && $value) {
            // Verify ownership
            $task = Task::getById($taskId);
            if (!$task) {
                jsonResponse(['success' => false, 'message' => 'Task not found']);
            }

            $goal = Goal::getById($task['goal_id']);
            if (!$goal || $goal['user_id'] !== $userId) {
                jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // Update task frequency
            Task::update($taskId, [
                'frequency' => (int)$value,
                'task_type' => 'weekly'
            ]);

            jsonResponse(['success' => true, 'message' => __('changes_saved')]);
        }

        jsonResponse(['success' => false, 'message' => 'Invalid suggestion type']);
        break;

    case 'dismiss':
        // In a more complete implementation, you'd track dismissed suggestions
        jsonResponse(['success' => true]);
        break;

    case 'get':
        $suggestions = Suggestion::generate($userId);
        jsonResponse(['success' => true, 'suggestions' => $suggestions]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
