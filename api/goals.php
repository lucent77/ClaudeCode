<?php
/**
 * Goals API
 * 목표 관리 API 엔드포인트
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/Goal.php';

header('Content-Type: application/json');

// Require authentication
if (!Session::isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => '로그인이 필요합니다.'], 401);
}

$user = Session::getUser();
$goal = new Goal();

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_core_goals':
            $goals = $goal->getUserCoreGoals($user->getId());
            jsonResponse(['success' => true, 'goals' => $goals]);
            break;

        case 'get_core_goal':
            $goalData = $goal->getCoreGoal($_GET['id'] ?? 0, $user->getId());
            if ($goalData) {
                jsonResponse(['success' => true, 'goal' => $goalData]);
            } else {
                jsonResponse(['success' => false, 'error' => '목표를 찾을 수 없습니다.'], 404);
            }
            break;

        case 'get_sub_goal':
            $subGoal = $goal->getSubGoal($_GET['id'] ?? 0);
            if ($subGoal) {
                jsonResponse(['success' => true, 'sub_goal' => $subGoal]);
            } else {
                jsonResponse(['success' => false, 'error' => '세부 목표를 찾을 수 없습니다.'], 404);
            }
            break;

        case 'get_task':
            $task = $goal->getTask($_GET['id'] ?? 0);
            if ($task) {
                jsonResponse(['success' => true, 'task' => $task]);
            } else {
                jsonResponse(['success' => false, 'error' => '과제를 찾을 수 없습니다.'], 404);
            }
            break;

        case 'get_mandalart':
            $mandalart = $goal->getFullMandalart($_GET['goal_id'] ?? 0);
            if ($mandalart) {
                jsonResponse(['success' => true, 'mandalart' => $mandalart]);
            } else {
                jsonResponse(['success' => false, 'error' => '만다라트를 찾을 수 없습니다.'], 404);
            }
            break;

        case 'get_active_tasks':
            $tasks = $goal->getActiveTasksForUser($user->getId());
            jsonResponse(['success' => true, 'tasks' => $tasks]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => '잘못된 요청입니다.'], 400);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(['success' => false, 'error' => '잘못된 요청 형식입니다.'], 400);
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'create_core_goal':
            if (empty($input['title'])) {
                jsonResponse(['success' => false, 'error' => '목표 제목을 입력해주세요.']);
            }

            $goalId = $goal->createCoreGoal(
                $user->getId(),
                $input['title'],
                $input['description'] ?? '',
                $input['target_date'] ?? null
            );

            // Award achievement for first goal
            $user->checkAchievements();

            jsonResponse(['success' => true, 'goal_id' => $goalId]);
            break;

        case 'update_core_goal':
            $result = $goal->updateCoreGoal($input['id'] ?? 0, $user->getId(), $input);
            jsonResponse(['success' => $result]);
            break;

        case 'delete_core_goal':
            $goal->deleteCoreGoal($input['id'] ?? 0, $user->getId());
            jsonResponse(['success' => true]);
            break;

        case 'create_sub_goal':
            if (empty($input['title'])) {
                jsonResponse(['success' => false, 'error' => '세부 목표명을 입력해주세요.']);
            }

            // Verify user owns the core goal
            $coreGoal = $goal->getCoreGoal($input['core_goal_id'] ?? 0, $user->getId());
            if (!$coreGoal) {
                jsonResponse(['success' => false, 'error' => '권한이 없습니다.'], 403);
            }

            $subGoalId = $goal->createSubGoal(
                $input['core_goal_id'],
                $input['position'],
                $input['title'],
                $input['description'] ?? '',
                $input['color'] ?? '#6366f1'
            );

            jsonResponse(['success' => true, 'sub_goal_id' => $subGoalId]);
            break;

        case 'update_sub_goal':
            $result = $goal->updateSubGoal($input['id'] ?? 0, $input);
            jsonResponse(['success' => $result]);
            break;

        case 'delete_sub_goal':
            $goal->deleteSubGoal($input['id'] ?? 0);
            jsonResponse(['success' => true]);
            break;

        case 'create_task':
            if (empty($input['title'])) {
                jsonResponse(['success' => false, 'error' => '과제명을 입력해주세요.']);
            }

            $taskId = $goal->createTask(
                $input['sub_goal_id'],
                $input['position'],
                $input
            );

            jsonResponse(['success' => true, 'task_id' => $taskId]);
            break;

        case 'update_task':
            $result = $goal->updateTask($input['id'] ?? 0, $input);
            jsonResponse(['success' => $result]);
            break;

        case 'delete_task':
            $goal->deleteTask($input['id'] ?? 0);
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => '잘못된 요청입니다.'], 400);
    }
}
