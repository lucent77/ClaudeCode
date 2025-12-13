<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Redirect if already logged in
if (auth()->isLoggedIn()) {
    redirect(auth()->getRedirectAfterLogin());
}

$error = null;

// Handle login form submission
if (isPost()) {
    $username = input('username', '');
    $password = input('password', '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (auth()->login($username, $password)) {
        redirect(auth()->getRedirectAfterLogin());
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CAD/CAM Workflow System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo -->
            <div class="flex justify-center">
                <svg class="h-16 w-16 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                CAD/CAM Workflow System
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sign in to your account
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <?php if ($error): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="" method="POST">
                    <?= csrfField() ?>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            Username or Email
                        </label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" autocomplete="username" required
                                   value="<?= e(input('username', '')) ?>"
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                   class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-900">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Sign in
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                Default credentials
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 text-center text-sm text-gray-600">
                        <p>Username: <code class="bg-gray-100 px-1 rounded">admin</code></p>
                        <p>Password: <code class="bg-gray-100 px-1 rounded">password</code></p>
                        <p class="mt-2 text-xs text-red-500">Change these after first login!</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-xs text-gray-500">
                CAD/CAM Workflow System v<?= APP_VERSION ?>
            </p>
        </div>
    </div>
</body>
</html>
