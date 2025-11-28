<?php
/**
 * Daily Tracker API
 * 일일 계획 관리 API
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../classes/DailyTracker.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => '로그인이 필요합니다.'], 401);
}

$user = Session::getUser();
$tracker = new DailyTracker();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_plan':
            $date = $_GET['date'] ?? date('Y-m-d');
            $plan = $tracker->getDailyPlan($user->getId(), $date);
            jsonResponse(['success' => true, 'plan' => $plan]);
            break;

        case 'get_logs':
            $date = $_GET['date'] ?? date('Y-m-d');
            $logs = $tracker->getTaskLogs($user->getId(), $date);
            jsonResponse(['success' => true, 'logs' => $logs]);
            break;

        case 'get_score':
            $date = $_GET['date'] ?? date('Y-m-d');
            $score = $tracker->getDailyScore($user->getId(), $date);
            jsonResponse(['success' => true, 'score' => $score]);
            break;

        case 'get_history':
            $days = $_GET['days'] ?? 30;
            $history = $tracker->getScoreHistory($user->getId(), $days);
            jsonResponse(['success' => true, 'history' => $history]);
            break;

        case 'get_weekly_stats':
            $stats = $tracker->getWeeklyStats($user->getId());
            jsonResponse(['success' => true, 'stats' => $stats]);
            break;

        case 'get_suggestions':
            $suggestions = $tracker->suggestTasksForToday($user->getId());
            jsonResponse(['success' => true, 'suggestions' => $suggestions]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => '잘못된 요청입니다.'], 400);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(['success' => false, 'error' => '잘못된 요청 형식입니다.'], 400);
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'add':
            $taskId = $input['task_id'] ?? 0;
            $date = $input['date'] ?? date('Y-m-d');
            $isPriority = $input['is_priority'] ?? false;

            $logId = $tracker->addTaskToPlan($user->getId(), $taskId, $date, $isPriority);
            jsonResponse(['success' => true, 'log_id' => $logId]);
            break;

        case 'remove':
            $logId = $input['log_id'] ?? 0;
            $tracker->removeTaskFromPlan($logId, $user->getId());
            jsonResponse(['success' => true]);
            break;

        case 'complete':
            $logId = $input['log_id'] ?? 0;
            $notes = $input['notes'] ?? '';

            $points = $tracker->completeTask($logId, $user->getId(), $notes);

            if ($points !== false) {
                // Add points to user
                $user->addPoints($points);

                // Update daily score
                $tracker->calculateDailyScore($user->getId());

                // Update user streak
                $user->updateStreak();

                // Check achievements
                $user->checkAchievements();

                jsonResponse(['success' => true, 'points_earned' => $points]);
            } else {
                jsonResponse(['success' => false, 'error' => '과제를 완료할 수 없습니다.']);
            }
            break;

        case 'uncomplete':
            $logId = $input['log_id'] ?? 0;
            $result = $tracker->uncompleteTask($logId, $user->getId());

            if ($result) {
                $tracker->calculateDailyScore($user->getId());
                jsonResponse(['success' => true]);
            } else {
                jsonResponse(['success' => false, 'error' => '변경할 수 없습니다.']);
            }
            break;

        case 'skip':
            $logId = $input['log_id'] ?? 0;
            $reason = $input['reason'] ?? '';
            $tracker->skipTask($logId, $user->getId(), $reason);
            $tracker->calculateDailyScore($user->getId());
            jsonResponse(['success' => true]);
            break;

        case 'set_priority':
            $logId = $input['log_id'] ?? 0;
            $isPriority = $input['is_priority'] ?? false;
            $tracker->setTaskPriority($logId, $user->getId(), $isPriority);
            jsonResponse(['success' => true]);
            break;

        case 'update_plan':
            $planId = $input['plan_id'] ?? 0;
            $result = $tracker->updateDailyPlan($planId, $input);
            jsonResponse(['success' => $result]);
            break;

        case 'calculate_score':
            $date = $input['date'] ?? date('Y-m-d');
            $score = $tracker->calculateDailyScore($user->getId(), $date);
            jsonResponse(['success' => true, 'score' => $score]);
            break;

        default:
            jsonResponse(['success' => false, 'error' => '잘못된 요청입니다.'], 400);
    }
}
