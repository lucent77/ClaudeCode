<?php
/**
 * Users API Endpoint
 * Handles user management operations (admin only)
 */

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Security.php';
require_once __DIR__ . '/../includes/UserManager.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireLogin();
$auth->requireAdmin(); // Only admins can manage users

$userManager = new UserManager();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get users or single user
            if (isset($_GET['id'])) {
                $user = $userManager->getUserById($_GET['id']);
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                $users = $userManager->getAllUsers();
                echo json_encode(['success' => true, 'users' => $users]);
            }
            break;

        case 'POST':
            // Create new user
            $data = [];

            // Handle both form data and JSON
            if (!empty($_POST)) {
                $data = $_POST;
            } else {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
            }

            $result = $userManager->createUser($data);
            echo json_encode($result);
            break;

        case 'PUT':
            // Update user
            $userId = $_GET['id'] ?? null;

            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $result = $userManager->updateUser($userId, $data);
            echo json_encode($result);
            break;

        case 'DELETE':
            // Delete user
            $userId = $_GET['id'] ?? null;

            if (!$userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                exit;
            }

            // Don't allow deleting self
            if ($userId == $auth->getUserId()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                exit;
            }

            $result = $userManager->deleteUser($userId);
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
