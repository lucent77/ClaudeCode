<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Creodent Voice') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center">
                        <span class="text-xl font-bold text-primary-600">Creodent</span>
                        <span class="text-xl font-light text-gray-600 ml-1">Voice</span>
                    </a>
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-4">
                        <a href="/posts" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md">
                            Browse
                        </a>
                        <a href="/submit" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md">
                            Submit Feedback
                        </a>
                        <a href="/digests" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md">
                            Weekly Updates
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <a href="/admin" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Admin
                        </a>
                        <form action="/auth/logout" method="POST" class="inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                                Logout
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="/auth/login" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Staff Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php $flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); ?>
    <?php if (!empty($flash)): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <?php if (!empty($flash['success'])): ?>
                <div class="rounded-md bg-green-50 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($flash['success']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="rounded-md bg-red-50 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($flash['error']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($flash['warning'])): ?>
                <div class="rounded-md bg-yellow-50 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800"><?= htmlspecialchars($flash['warning']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="min-h-[calc(100vh-12rem)]">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row justify-between items-center">
                <p class="text-sm text-gray-500">
                    Creodent Anonymous Voice - Your feedback matters
                </p>
                <div class="flex items-center space-x-4 mt-4 sm:mt-0">
                    <a href="/posts" class="text-sm text-gray-500 hover:text-gray-700">Browse</a>
                    <a href="/submit" class="text-sm text-gray-500 hover:text-gray-700">Submit</a>
                    <a href="/digests" class="text-sm text-gray-500 hover:text-gray-700">Updates</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"], [class*="bg-yellow-50"]');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
