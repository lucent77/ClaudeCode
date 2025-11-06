<?php
/**
 * User Notifications
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$pageTitle = 'Notifications';
$db = Database::getInstance();

// Mark notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notifId = (int)$_GET['mark_read'];
    $db->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$notifId, $_SESSION['user_id']]);
    redirect('/purchase-system/notifications.php');
}

// Get notifications
$notifications = $db->fetchAll(
    "SELECT n.*, pr.product_name
     FROM notifications n
     LEFT JOIN purchase_requests pr ON n.purchase_request_id = pr.id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC
     LIMIT 50",
    [$_SESSION['user_id']]
);

include __DIR__ . '/includes/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
    <p class="mt-2 text-sm text-gray-600">Your recent notifications and updates</p>
</div>

<div class="bg-white rounded-lg shadow">
    <?php if (empty($notifications)): ?>
    <div class="p-8 text-center text-gray-500">No notifications</div>
    <?php else: ?>
    <div class="divide-y divide-gray-200">
        <?php foreach ($notifications as $notif): ?>
        <div class="p-4 <?php echo $notif['is_read'] ? 'bg-white' : 'bg-blue-50'; ?> hover:bg-gray-50">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm text-gray-900"><?php echo e($notif['message']); ?></p>
                    <p class="text-xs text-gray-500 mt-1"><?php echo formatDate($notif['created_at'], 'M d, Y g:i A'); ?></p>
                </div>
                <div class="ml-4 flex gap-2">
                    <?php if ($notif['purchase_request_id']): ?>
                    <a href="/purchase-system/view-request.php?id=<?php echo $notif['purchase_request_id']; ?>" class="text-sm text-blue-600 hover:text-blue-800">View</a>
                    <?php endif; ?>
                    <?php if (!$notif['is_read']): ?>
                    <a href="?mark_read=<?php echo $notif['id']; ?>" class="text-sm text-gray-600 hover:text-gray-800">Mark Read</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
