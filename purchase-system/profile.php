<?php
/**
 * User Profile
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$pageTitle = 'Profile';
$db = Database::getInstance();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!empty($currentPassword) && !empty($newPassword) && !empty($confirmPassword)) {
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

        if (password_verify($currentPassword, $user['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->update('users', ['password' => $hashedPassword], 'id = ?', [$_SESSION['user_id']]);
                setFlash('Password updated successfully', 'success');
            } else {
                setFlash('New passwords do not match', 'error');
            }
        } else {
            setFlash('Current password is incorrect', 'error');
        }
    }
}

// Get user data
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

include __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
        <p class="mt-2 text-sm text-gray-600">Manage your account settings</p>
    </div>

    <!-- User Information -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Account Information</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Username</p>
                <p class="text-lg font-semibold text-gray-900"><?php echo e($user['username']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Department</p>
                <p class="text-lg font-semibold text-gray-900"><?php echo e($user['department']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Role</p>
                <p class="text-lg font-semibold text-gray-900"><?php echo ucfirst($user['role']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Email</p>
                <p class="text-lg font-semibold text-gray-900"><?php echo e($user['email'] ?? 'Not set'); ?></p>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Change Password</h2>
        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                <input type="password" name="current_password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                <input type="password" name="new_password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Password</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
