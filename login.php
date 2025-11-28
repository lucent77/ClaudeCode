<?php
/**
 * Login Page
 * LifeMandalart - Self Management Web Service
 */

require_once 'config/config.php';
require_once INCLUDES_PATH . '/auth.php';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: login.php");
    exit;
}

// Redirect if already logged in
Auth::requireGuest();

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = __('error_csrf');
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = __('error_validation');
        } else {
            if (Auth::attempt($email, $password, $remember)) {
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirect);
            } else {
                $error = __('login_error');
            }
        }
    }
}

// Check for registration success message
if (isset($_SESSION['register_success'])) {
    $success = __('register_success');
    unset($_SESSION['register_success']);
}

$pageTitle = __('login');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-900 dark:to-gray-800">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= e(__('app_name')) ?></h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?= e(__('app_tagline')) ?></p>
        </div>

        <!-- Login Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= e(__('login')) ?></h3>

            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 text-sm flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-600 dark:text-green-400 text-sm flex items-center">
                <i data-feather="check-circle" class="w-5 h-5 mr-2"></i>
                <?= e($success) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?= e(__('email')) ?>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="mail" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" required
                               value="<?= e($_POST['email'] ?? '') ?>"
                               class="block w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?= e(__('password')) ?>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="lock" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               class="block w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400"><?= e(__('remember_me')) ?></span>
                    </label>
                    <a href="forgot-password.php" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        <?= e(__('forgot_password')) ?>
                    </a>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-lg hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transform transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <?= e(__('login')) ?>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <?= e(__('no_account')) ?>
                    <a href="register.php" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        <?= e(__('register')) ?>
                    </a>
                </p>
            </div>
        </div>

        <!-- Features Preview -->
        <div class="mt-8 grid grid-cols-3 gap-4 text-center">
            <div class="p-4">
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mx-auto mb-2">
                    <i data-feather="target" class="w-6 h-6 text-primary-600 dark:text-primary-400"></i>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400"><?= e(__('nav_mandalart')) ?></p>
            </div>
            <div class="p-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mx-auto mb-2">
                    <i data-feather="check-circle" class="w-6 h-6 text-green-600 dark:text-green-400"></i>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400"><?= e(__('seven_habits')) ?></p>
            </div>
            <div class="p-4">
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center mx-auto mb-2">
                    <i data-feather="award" class="w-6 h-6 text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400"><?= e(__('achievements')) ?></p>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
