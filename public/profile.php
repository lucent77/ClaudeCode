<?php
/**
 * User Profile Page
 */

require_once __DIR__ . '/../includes/bootstrap.php';

auth()->requireLogin();

$pageTitle = 'Profile Settings';
$user = auth()->user();
$error = null;
$success = null;

// Handle profile update
if (isPost()) {
    auth()->requireCsrf();

    $action = input('action');

    if ($action === 'update_profile') {
        $fullName = trim(input('full_name', ''));
        $email = trim(input('email', ''));

        if (empty($fullName) || empty($email)) {
            $error = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email is taken by another user
            $existing = db()->fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$email, $user['id']]
            );

            if ($existing) {
                $error = 'This email is already in use.';
            } else {
                db()->update('users', [
                    'full_name' => $fullName,
                    'email' => $email
                ], ['id' => $user['id']]);

                $success = 'Profile updated successfully.';
                // Refresh user data
                $user = db()->fetchOne("SELECT * FROM users WHERE id = ?", [$user['id']]);
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = input('current_password', '');
        $newPassword = input('new_password', '');
        $confirmPassword = input('confirm_password', '');

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            auth()->updatePassword($user['id'], $newPassword);
            $success = 'Password changed successfully.';
        }
    }
}

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <?php if ($error): ?>
        <div class="alert alert-error mb-6"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success mb-6"><?= e($success) ?></div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Profile Information</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" value="<?= e($user['username']) ?>" class="form-input bg-gray-50" disabled>
                            <p class="mt-1 text-xs text-gray-500">Username cannot be changed.</p>
                        </div>
                        <div>
                            <label for="initials" class="form-label">Initials</label>
                            <input type="text" id="initials" value="<?= e($user['initials']) ?>" class="form-input bg-gray-50" disabled>
                            <p class="mt-1 text-xs text-gray-500">Contact admin to change initials.</p>
                        </div>
                    </div>

                    <div>
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" class="form-input" required>
                    </div>

                    <div>
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" class="form-input" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Role</label>
                            <p class="text-sm text-gray-900"><?= e(ucwords(str_replace('_', ' ', $user['role']))) ?></p>
                        </div>
                        <div>
                            <label class="form-label">Department</label>
                            <p class="text-sm text-gray-900"><?= e($user['department'] ?: 'All') ?></p>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Change Password</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" required>
                    </div>

                    <div>
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        <p class="mt-1 text-xs text-gray-500">Minimum <?= PASSWORD_MIN_LENGTH ?> characters.</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Info -->
    <div class="mt-6 text-sm text-gray-500">
        <p>Last login: <?= $user['last_login'] ? formatDateTime($user['last_login']) : 'Never' ?></p>
        <p>Account created: <?= formatDateTime($user['created_at']) ?></p>
    </div>
</div>

<?php
$content = ob_get_clean();
include APP_ROOT . '/views/layouts/main.php';
?>
