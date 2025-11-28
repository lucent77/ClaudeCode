<?php
/**
 * Settings Page
 * LifeMandalart - Self Management Web Service
 */

require_once '../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: settings.php");
    exit;
}

Auth::require();

$user = Auth::user();
$userId = Auth::id();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = __('error_csrf');
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'language' => $_POST['language'] ?? 'ko',
                'theme' => $_POST['theme'] ?? 'system',
                'timezone' => $_POST['timezone'] ?? 'Asia/Seoul',
                'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0
            ];

            if (strlen($data['name']) < 2) {
                $error = __('error_validation');
            } else {
                User::update($userId, $data);
                $_SESSION['language'] = $data['language'];
                $_SESSION['theme'] = $data['theme'];
                $success = __('changes_saved');
                $user = Auth::user(); // Reload user
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                $error = __('error_validation');
            } elseif (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                $error = __('error_validation');
            } elseif (!Auth::changePassword($userId, $currentPassword, $newPassword)) {
                $error = __('error_validation');
            } else {
                $success = __('changes_saved');
            }
        }
    }
}

$pageTitle = __('settings');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6"><?= __('settings') ?></h1>

    <?php if ($success): ?>
    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-600 dark:text-green-400 flex items-center">
        <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
        <?= e($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 flex items-center">
        <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
        <?= e($error) ?>
    </div>
    <?php endif; ?>

    <!-- Profile Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-6">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold flex items-center">
                <i data-feather="user" class="w-5 h-5 mr-2"></i>
                <?= __('profile') ?>
            </h2>
        </div>
        <form method="POST" class="p-4 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="update_profile">

            <div>
                <label class="block text-sm font-medium mb-2"><?= __('name') ?></label>
                <input type="text" name="name" value="<?= e($user['name']) ?>" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2"><?= __('email') ?></label>
                <input type="email" value="<?= e($user['email']) ?>" disabled
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-600 text-gray-500">
                <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2"><?= __('language') ?></label>
                <select name="language"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="ko" <?= $user['language'] === 'ko' ? 'selected' : '' ?>>한국어</option>
                    <option value="en" <?= $user['language'] === 'en' ? 'selected' : '' ?>>English</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2"><?= __('theme') ?></label>
                <select name="theme"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="light" <?= $user['theme'] === 'light' ? 'selected' : '' ?>><?= __('theme_light') ?></option>
                    <option value="dark" <?= $user['theme'] === 'dark' ? 'selected' : '' ?>><?= __('theme_dark') ?></option>
                    <option value="system" <?= $user['theme'] === 'system' ? 'selected' : '' ?>><?= __('theme_system') ?></option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Timezone</label>
                <select name="timezone"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <?php
                    $timezones = ['Asia/Seoul', 'Asia/Tokyo', 'America/New_York', 'America/Los_Angeles', 'Europe/London', 'UTC'];
                    foreach ($timezones as $tz): ?>
                    <option value="<?= $tz ?>" <?= $user['timezone'] === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center space-x-3">
                <input type="checkbox" id="email_notifications" name="email_notifications"
                       <?= $user['email_notifications'] ? 'checked' : '' ?>
                       class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                <label for="email_notifications" class="text-sm"><?= __('email_notifications') ?></label>
            </div>

            <button type="submit" class="w-full py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors">
                <?= __('save_changes') ?>
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm mb-6">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold flex items-center">
                <i data-feather="lock" class="w-5 h-5 mr-2"></i>
                <?= __('change_password') ?>
            </h2>
        </div>
        <form method="POST" class="p-4 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="change_password">

            <div>
                <label class="block text-sm font-medium mb-2"><?= __('password') ?> (Current)</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2"><?= __('password') ?> (New)</label>
                <input type="password" name="new_password" required minlength="<?= PASSWORD_MIN_LENGTH ?>"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2"><?= __('password_confirm') ?></label>
                <input type="password" name="confirm_password" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>

            <button type="submit" class="w-full py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors">
                <?= __('change_password') ?>
            </button>
        </form>
    </div>

    <!-- 7 Habits Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold flex items-center">
                <i data-feather="book" class="w-5 h-5 mr-2"></i>
                <?= __('seven_habits') ?>
            </h2>
        </div>
        <div class="p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"><?= __('habit_2_desc') ?></p>
            <div class="space-y-3">
                <?php for ($i = 1; $i <= 7; $i++): ?>
                <div class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-sm">
                        <?= $i ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm"><?= __("habit_$i") ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __("habit_{$i}_desc") ?></p>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
