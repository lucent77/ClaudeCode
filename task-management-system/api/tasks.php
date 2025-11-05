<?php
/**
 * Tasks API Endpoint
 * Handles CRUD operations for tasks
 */

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/TaskManager.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();

$taskManager = new TaskManager();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get tasks or single task
            if (isset($_GET['id'])) {
                $task = $taskManager->getTaskById($_GET['id']);
                echo json_encode(['success' => true, 'task' => $task]);
            } elseif (isset($_GET['since'])) {
                // Get updated tasks since timestamp (for real-time updates)
                $since = $_GET['since'];
                $departmentId = $_GET['department_id'] ?? null;

                // Check permission
                if ($departmentId && !$auth->canAccessDepartment($departmentId)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    exit;
                }

                $tasks = $taskManager->getUpdatedTasksSince($since, $departmentId);
                echo json_encode(['success' => true, 'tasks' => $tasks, 'timestamp' => date('Y-m-d H:i:s')]);
            } elseif (isset($_GET['history'])) {
                // Get task history
                $history = $taskManager->getTaskHistory($_GET['history']);
                echo json_encode(['success' => true, 'history' => $history]);
            } else {
                // Get all tasks with filters
                $filters = [];
                if (isset($_GET['department_id'])) {
                    $filters['department_id'] = $_GET['department_id'];

                    // Check permission
                    if (!$auth->canAccessDepartment($_GET['department_id'])) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Access denied']);
                        exit;
                    }
                }

                $tasks = $taskManager->getTasks($filters);
                echo json_encode(['success' => true, 'tasks' => $tasks]);
            }
            break;

        case 'POST':
            // Create new task
            $data = [];

            // Handle both form data and JSON
            if (!empty($_POST)) {
                $data = $_POST;
            } else {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
            }

            // Set created_by
            $data['created_by'] = $auth->getUserId();

            // Check if user can create task in this department
            if (isset($data['department_id']) && !$auth->canAccessDepartment($data['department_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }

            $result = $taskManager->createTask($data);
            echo json_encode($result);
            break;

        case 'PUT':
            // Update task
            $taskId = $_GET['id'] ?? null;

            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                exit;
            }

            // Get task to check permissions
            $task = $taskManager->getTaskById($taskId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Task not found']);
                exit;
            }

            if (!$auth->canAccessDepartment($task['department_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit;
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $result = $taskManager->updateTask($taskId, $data, $auth->getUserId());
            echo json_encode($result);
            break;

        case 'DELETE':
            // Delete task (admin only)
            if (!$auth->isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                exit;
            }

            $taskId = $_GET['id'] ?? null;

            if (!$taskId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Task ID required']);
                exit;
            }

            $result = $taskManager->deleteTask($taskId, $auth->getUserId());
            echo json_encode($result);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    error_log($e->getMessage());
}
