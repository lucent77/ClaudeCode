<?php
/**
 * Tasks API Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = getDB();

    switch ($input['action'] ?? '') {
        case 'update_status':
            requireAdmin();

            $taskId = intval($input['task_id'] ?? 0);
            $completed = filter_var($input['completed'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$taskId) {
                jsonResponse(['success' => false, 'message' => 'Invalid task ID'], 400);
            }

            $stmt = $db->prepare("UPDATE tasks SET completed = ? WHERE id = ?");
            $stmt->execute([$completed ? 1 : 0, $taskId]);

            logActivity($_SESSION['user_id'], 'update_task_status', 'task', $taskId,
                "Set task status to " . ($completed ? 'completed' : 'pending'));

            jsonResponse([
                'success' => true,
                'message' => 'Task status updated successfully'
            ]);
            break;

        case 'get_tasks':
            $page = max(1, intval($input['page'] ?? 1));
            $limit = intval($input['limit'] ?? ITEMS_PER_PAGE);
            $offset = ($page - 1) * $limit;

            $where = ['1=1'];
            $params = [];

            if (!empty($input['search'])) {
                $where[] = "(patient_name LIKE ? OR notes LIKE ?)";
                $params[] = "%{$input['search']}%";
                $params[] = "%{$input['search']}%";
            }

            if (isset($input['completed'])) {
                $where[] = "completed = ?";
                $params[] = filter_var($input['completed'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }

            $whereClause = implode(' AND ', $where);

            $countStmt = $db->prepare("SELECT COUNT(*) as total FROM tasks WHERE $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];

            $stmt = $db->prepare("
                SELECT * FROM tasks
                WHERE $whereClause
                ORDER BY surgery_date ASC, due_date ASC, created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $tasks = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'tasks' => $tasks,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;

        case 'get_task':
            $taskId = intval($input['task_id'] ?? 0);

            if (!$taskId) {
                jsonResponse(['success' => false, 'message' => 'Invalid task ID'], 400);
            }

            $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $task = $stmt->fetch();

            if (!$task) {
                jsonResponse(['success' => false, 'message' => 'Task not found'], 404);
            }

            jsonResponse([
                'success' => true,
                'task' => $task
            ]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }

} catch (PDOException $e) {
    error_log("API error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Database error'], 500);
} catch (Exception $e) {
    error_log("API error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}
