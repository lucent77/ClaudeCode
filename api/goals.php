<?php
/**
 * Goals API
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

// Require AJAX
if (!isAjax()) {
    jsonResponse(['error' => 'Invalid request'], 400);
}

// Require authentication
Auth::require();

$userId = Auth::id();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    jsonResponse(['error' => __('error_csrf')], 403);
}

$action = $input['action'] ?? '';

switch ($action) {
    case 'create':
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $targetDate = $input['target_date'] ?? null;

        if (empty($title)) {
            jsonResponse(['success' => false, 'message' => 'Title is required']);
        }

        // Archive existing active goals
        Database::update(
            "UPDATE goals SET status = 'archived' WHERE user_id = ? AND status = 'active'",
            [$userId]
        );

        $goalId = Goal::create($userId, $title, $description ?: null, $targetDate);

        // Check for first goal achievement
        Achievement::checkAndAward($userId);

        jsonResponse([
            'success' => true,
            'goal_id' => $goalId,
            'message' => __('mandalart_saved')
        ]);
        break;

    case 'update':
        $goalId = (int)($input['goal_id'] ?? 0);
        $title = trim($input['title'] ?? '');

        if (!$goalId || empty($title)) {
            jsonResponse(['success' => false, 'message' => 'Invalid data']);
        }

        // Verify ownership
        $goal = Goal::getById($goalId);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Goal::update($goalId, [
            'title' => $title,
            'description' => $input['description'] ?? null,
            'target_date' => $input['target_date'] ?? null
        ]);

        jsonResponse(['success' => true, 'message' => __('mandalart_saved')]);
        break;

    case 'save_subgoal':
        $goalId = (int)($input['goal_id'] ?? 0);
        $position = (int)($input['position'] ?? 0);
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = $input['color'] ?? '#3B82F6';

        if (!$goalId || $position < 1 || $position > 8 || empty($title)) {
            jsonResponse(['success' => false, 'message' => 'Invalid data']);
        }

        // Verify ownership
        $goal = Goal::getById($goalId);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $subgoalId = SubGoal::upsert($goalId, $position, $title, $description ?: null, $color);

        jsonResponse([
            'success' => true,
            'subgoal_id' => $subgoalId,
            'message' => __('mandalart_saved')
        ]);
        break;

    case 'delete':
        $goalId = (int)($input['goal_id'] ?? 0);

        // Verify ownership
        $goal = Goal::getById($goalId);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Goal::delete($goalId);
        jsonResponse(['success' => true]);
        break;

    case 'archive':
        $goalId = (int)($input['goal_id'] ?? 0);

        // Verify ownership
        $goal = Goal::getById($goalId);
        if (!$goal || $goal['user_id'] !== $userId) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Goal::update($goalId, ['status' => 'archived']);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
