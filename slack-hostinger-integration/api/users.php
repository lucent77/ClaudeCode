<?php
/**
 * Users API Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($input['action'] ?? '') {
        case 'toggle_status':
            $userId = intval($input['user_id'] ?? 0);
            $isActive = filter_var($input['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$userId) {
                jsonResponse(['success' => false, 'message' => 'Invalid user ID'], 400);
            }

            // Prevent deactivating yourself
            if ($userId === $_SESSION['user_id']) {
                jsonResponse(['success' => false, 'message' => 'Cannot deactivate your own account'], 400);
            }

            $result = Auth::updateUserStatus($userId, $isActive);
            jsonResponse($result);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }

} catch (Exception $e) {
    error_log("API error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Internal server error'], 500);
}
