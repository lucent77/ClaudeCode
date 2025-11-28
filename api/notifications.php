<?php
/**
 * Notifications API
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (!isAjax()) {
    jsonResponse(['error' => 'Invalid request'], 400);
}

Auth::require();

$userId = Auth::id();

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'list':
            $notifications = Notification::getByUser($userId);
            jsonResponse([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => Notification::getUnreadCount($userId)
            ]);
            break;

        case 'unread_count':
            jsonResponse([
                'success' => true,
                'count' => Notification::getUnreadCount($userId)
            ]);
            break;

        default:
            jsonResponse(['error' => 'Invalid action'], 400);
    }
}

// Handle POST requests
$input = json_decode(file_get_contents('php://input'), true);

if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
    jsonResponse(['error' => __('error_csrf')], 403);
}

$action = $input['action'] ?? '';

switch ($action) {
    case 'mark_read':
        $id = (int)($input['id'] ?? 0);

        if ($id) {
            // Verify ownership
            $notification = Database::fetch(
                "SELECT * FROM notifications WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );

            if ($notification) {
                Notification::markAsRead($id);
            }
        }

        jsonResponse(['success' => true]);
        break;

    case 'mark_all_read':
        Notification::markAllAsRead($userId);
        jsonResponse(['success' => true]);
        break;

    case 'delete':
        $id = (int)($input['id'] ?? 0);

        if ($id) {
            Database::delete(
                "DELETE FROM notifications WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );
        }

        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}
