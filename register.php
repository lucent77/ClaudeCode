<?php
/**
 * Registration Page
 * LifeMandalart - Self Management Web Service
 */

require_once 'config/config.php';
require_once INCLUDES_PATH . '/auth.php';

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
    $_SESSION['language'] = $_GET['lang'];
    header("Location: register.php");
    exit;
}

// Redirect if already logged in
Auth::requireGuest();

$errors = [];
$formData = [];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = __('error_csrf');
    } else {
        $formData = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'language' => getCurrentLanguage()
        ];

        $result = Auth::register($formData);

        if ($result['success']) {
            $_SESSION['register_success'] = true;
            redirect('index.php');
        } else {
            $errors = $result['errors'];
        }
    }
}

$pageTitle = __('register');
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
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= e(__('register')) ?></h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?= e(__('app_description')) ?></p>
        </div>

        <!-- Registration Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
            <?php if (isset($errors['general'])): ?>
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 text-sm flex items-center">
                <i data-feather="alert-circle" class="w-5 h-5 mr-2"></i>
                <?= e($errors['general']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?= e(__('name')) ?>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="user" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="text" id="name" name="name" required
                               value="<?= e($formData['name'] ?? '') ?>"
                               class="block w-full pl-10 pr-4 py-3 border <?= isset($errors['name']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' ?> rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               placeholder="<?= getCurrentLanguage() === 'ko' ? '홍길동' : 'John Doe' ?>">
                    </div>
                    <?php if (isset($errors['name'])): ?>
                    <p class="mt-1 text-sm text-red-500"><?= e($errors['name']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?= e(__('username')) ?>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="at-sign" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required
                               value="<?= e($formData['username'] ?? '') ?>"
                               pattern="[a-zA-Z0-9_]+"
                               class="block w-full pl-10 pr-4 py-3 border <?= isset($errors['username']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' ?> rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               placeholder="username">
                    </div>
                    <?php if (isset($errors['username'])): ?>
                    <p class="mt-1 text-sm text-red-500"><?= e($errors['username']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?= e(__('email')) ?>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="mail" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" required
                               value="<?= e($formData['email'] ?? '') ?>"
                               class="block w-full pl-10 pr-4 py-3 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' ?> rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               placeholder="you@example.com">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-500"><?= e($errors['email']) ?></p>
                    <?php endif; ?>
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
                               minlength="<?= PASSWORD_MIN_LENGTH ?>"
                               class="block w-full pl-10 pr-4 py-3 border <?= isset($errors['password']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' ?> rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               placeholder="••••••••">
                    </div>
                    <?php if (isset($errors['password'])): ?>
                    <p class="mt-1 text-sm text-red-500"><?= e($errors['password']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?= e(__('password_confirm')) ?>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-feather="lock" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="password" id="password_confirm" name="password_confirm" required
                               class="block w-full pl-10 pr-4 py-3 border <?= isset($errors['password_confirm']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' ?> rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               placeholder="••••••••">
                    </div>
                    <?php if (isset($errors['password_confirm'])): ?>
                    <p class="mt-1 text-sm text-red-500"><?= e($errors['password_confirm']) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-lg hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transform transition-all hover:scale-[1.02] active:scale-[0.98]">
                    <?= e(__('register')) ?>
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <?= e(__('have_account')) ?>
                    <a href="login.php" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        <?= e(__('login')) ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>
</body>
</html>
