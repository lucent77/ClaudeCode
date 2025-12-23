<?php
/**
 * URL Collector - Dashboard Login
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$config = require APP_ROOT . '/config/config.php';

require_once APP_ROOT . '/lib/db.php';
require_once APP_ROOT . '/lib/security.php';
require_once APP_ROOT . '/lib/util.php';

Security::init($config);
Util::init($config);
Security::startSession();

// Redirect if already logged in
if (Security::isLoggedIn()) {
    Util::redirect('index.php');
}

$error = '';

// Process login
if (Util::isPost()) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Rate limiting for login attempts
    $clientIp = Security::getClientIp();
    $rateLimit = Security::checkRateLimit($clientIp, 5); // 5 attempts per minute

    if (!$rateLimit['allowed']) {
        $error = 'Too many login attempts. Please wait a moment.';
    } elseif (Security::verifyAdminLogin($username, $password)) {
        Security::setLoggedIn(true);
        Util::logInfo("Admin login successful from IP: {$clientIp}");
        Util::redirect('index.php');
    } else {
        Util::logWarning("Failed login attempt for user '{$username}' from IP: {$clientIp}");
        $error = 'Invalid username or password.';
    }
}

$e = fn($s) => Security::escape($s);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - URL Collector</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔗</text></svg>">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
            <div class="text-center mb-8">
                <div class="text-5xl mb-4">🔗</div>
                <h1 class="text-2xl font-bold text-white">URL Collector</h1>
                <p class="text-purple-200 mt-2">Dashboard Login</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-200 px-4 py-3 rounded-lg mb-6">
                <?= $e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-purple-200 mb-2">
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        autocomplete="username"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="Enter username"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-purple-200 mb-2">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-purple-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="Enter password"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-transparent transition-all duration-200 shadow-lg"
                >
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-purple-300/50 text-sm mt-6">
            Secure URL collection & analysis
        </p>
    </div>
</body>
</html>
