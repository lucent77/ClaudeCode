<?php
/**
 * Login Page
 * Purchase Management System
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/purchase-system/dashboard.php');
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $db = Database::getInstance();

        try {
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE username = ?",
                [$username]
            );

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Redirect to intended page or dashboard
                $redirectUrl = $_SESSION['redirect_after_login'] ?? '/purchase-system/dashboard.php';
                unset($_SESSION['redirect_after_login']);

                redirect($redirectUrl);
            } else {
                $error = 'Invalid username or password';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-8">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="text-5xl mb-4">📦</div>
            <h1 class="text-3xl font-bold text-gray-800"><?php echo APP_NAME; ?></h1>
            <p class="text-gray-600 mt-2">Sign in to your account</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo e($error); ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="space-y-6">
            <!-- Username -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                    Username
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autocomplete="username"
                    value="<?php echo e($_POST['username'] ?? ''); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter your username"
                >
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter your password"
                >
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input
                        id="remember_me"
                        name="remember_me"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <a href="/purchase-system/forgot-password.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Forgot password?
                    </a>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-semibold"
            >
                Sign In
            </button>
        </form>

        <!-- Default Credentials Info -->
        <div class="mt-6 p-4 bg-gray-100 rounded-lg">
            <p class="text-xs text-gray-600 text-center">
                <strong>Default Login:</strong><br>
                Admin: admin / admin123<br>
                User: it_dept / password123
            </p>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Need an account? Contact your administrator.</p>
        </div>
    </div>

</body>
</html>
