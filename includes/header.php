<?php
/**
 * Header Template
 * LifeMandalart - Self Management Web Service
 */

// Prevent direct access
if (!defined('LIFE_MANDALART')) {
    die('Direct access not permitted');
}

$currentUser = Auth::user();
$currentTheme = getCurrentTheme();
$currentLang = getCurrentLanguage();
$notificationCount = $currentUser ? Notification::getUnreadCount($currentUser['id']) : 0;

// Get user stats for sidebar
$userStats = $currentUser ? User::getStats($currentUser['id']) : null;
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" class="<?= $currentTheme === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(__('app_description')) ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= e(__('app_name')) ?></title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
        }

        /* Mandalart grid */
        .mandalart-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }
        .mandalart-cell {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            padding: 4px;
            text-align: center;
            word-break: break-word;
            transition: all 0.2s ease;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        .pulse-ring::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: inherit;
            animation: pulse-ring 1.5s ease-out infinite;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 9999;
        }
        .toast.show {
            transform: translateX(0);
        }

        /* Level badge */
        .level-badge {
            background: linear-gradient(135deg, var(--level-color) 0%, var(--level-color-dark) 100%);
        }

        /* Progress ring */
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring__circle {
            transition: stroke-dashoffset 0.5s ease;
        }
    </style>

    <script>
        // Theme handling
        (function() {
            const theme = '<?= $currentTheme ?>';
            if (theme === 'system') {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                }
            } else if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <?php if ($currentUser): ?>
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-800 shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-40">
        <!-- Logo -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <a href="index.php" class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-800 dark:text-white"><?= e(__('app_name')) ?></span>
            </a>
        </div>

        <!-- User Info -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-lg">
                        <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center text-white text-xs font-bold">
                        <?= $currentUser['level'] ?>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white truncate"><?= e($currentUser['name']) ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center">
                            <i data-feather="zap" class="w-3 h-3 mr-1 text-yellow-500"></i>
                            <?= number_format($userStats['points']) ?> <?= __('points') ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Streak -->
            <?php if ($userStats['streak_days'] > 0): ?>
            <div class="mt-3 flex items-center justify-between bg-orange-50 dark:bg-orange-900/20 rounded-lg px-3 py-2">
                <span class="text-sm text-orange-700 dark:text-orange-300">
                    <i data-feather="flame" class="w-4 h-4 inline mr-1"></i>
                    <?= __('streak_days') ?>
                </span>
                <span class="font-bold text-orange-600 dark:text-orange-400"><?= $userStats['streak_days'] ?> <?= __('days') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <nav class="p-4 space-y-1">
            <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> transition-colors">
                <i data-feather="home" class="w-5 h-5"></i>
                <span><?= __('nav_dashboard') ?></span>
            </a>
            <a href="pages/mandalart.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($_SERVER['PHP_SELF']) === 'mandalart.php' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> transition-colors">
                <i data-feather="grid" class="w-5 h-5"></i>
                <span><?= __('nav_mandalart') ?></span>
            </a>
            <a href="pages/daily.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($_SERVER['PHP_SELF']) === 'daily.php' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> transition-colors">
                <i data-feather="check-square" class="w-5 h-5"></i>
                <span><?= __('nav_daily') ?></span>
            </a>
            <a href="pages/tracker.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($_SERVER['PHP_SELF']) === 'tracker.php' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> transition-colors">
                <i data-feather="bar-chart-2" class="w-5 h-5"></i>
                <span><?= __('nav_tracker') ?></span>
            </a>
            <a href="pages/achievements.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($_SERVER['PHP_SELF']) === 'achievements.php' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> transition-colors">
                <i data-feather="award" class="w-5 h-5"></i>
                <span><?= __('nav_achievements') ?></span>
            </a>
            <a href="pages/leaderboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg <?= basename($_SERVER['PHP_SELF']) === 'leaderboard.php' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> transition-colors">
                <i data-feather="trending-up" class="w-5 h-5"></i>
                <span><?= __('nav_leaderboard') ?></span>
            </a>
        </nav>

        <!-- Bottom Links -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
            <a href="pages/settings.php" class="flex items-center space-x-3 px-4 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                <i data-feather="settings" class="w-5 h-5"></i>
                <span><?= __('nav_settings') ?></span>
            </a>
            <a href="logout.php" class="flex items-center space-x-3 px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                <i data-feather="log-out" class="w-5 h-5"></i>
                <span><?= __('nav_logout') ?></span>
            </a>
        </div>
    </aside>

    <!-- Mobile Header -->
    <header class="lg:hidden fixed top-0 left-0 right-0 h-16 bg-white dark:bg-gray-800 shadow-sm z-30 flex items-center justify-between px-4">
        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
            <i data-feather="menu" class="w-6 h-6"></i>
        </button>
        <span class="font-bold text-lg"><?= e(__('app_name')) ?></span>
        <div class="flex items-center space-x-2">
            <button id="notifications-btn" class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                <i data-feather="bell" class="w-5 h-5"></i>
                <?php if ($notificationCount > 0): ?>
                <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 rounded-full text-white text-xs flex items-center justify-center"><?= $notificationCount ?></span>
                <?php endif; ?>
            </button>
        </div>
    </header>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

    <!-- Main Content Wrapper -->
    <main class="lg:ml-64 pt-16 lg:pt-0 min-h-screen">
        <!-- Top Bar (Desktop) -->
        <div class="hidden lg:flex h-16 bg-white dark:bg-gray-800 shadow-sm items-center justify-between px-6">
            <div class="flex items-center space-x-4">
                <h1 class="text-xl font-semibold"><?= isset($pageTitle) ? e($pageTitle) : e(__('nav_dashboard')) ?></h1>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button onclick="toggleLangDropdown()" class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <span class="text-sm font-medium"><?= $currentLang === 'ko' ? '한국어' : 'English' ?></span>
                        <i data-feather="chevron-down" class="w-4 h-4"></i>
                    </button>
                    <div id="lang-dropdown" class="hidden absolute right-0 mt-2 w-40 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                        <a href="?lang=ko" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 <?= $currentLang === 'ko' ? 'text-primary-600' : '' ?>">한국어</a>
                        <a href="?lang=en" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 <?= $currentLang === 'en' ? 'text-primary-600' : '' ?>">English</a>
                    </div>
                </div>

                <!-- Theme Toggle -->
                <button onclick="toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-feather="sun" class="w-5 h-5 hidden dark:block"></i>
                    <i data-feather="moon" class="w-5 h-5 dark:hidden"></i>
                </button>

                <!-- Notifications -->
                <button id="notifications-btn-desktop" class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <i data-feather="bell" class="w-5 h-5"></i>
                    <?php if ($notificationCount > 0): ?>
                    <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </button>

                <!-- User Menu -->
                <div class="relative">
                    <button onclick="toggleUserMenu()" class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-sm">
                            <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                        </div>
                        <span class="text-sm font-medium"><?= e($currentUser['name']) ?></span>
                        <i data-feather="chevron-down" class="w-4 h-4"></i>
                    </button>
                    <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                        <a href="pages/profile.php" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i data-feather="user" class="w-4 h-4 inline mr-2"></i>
                            <?= __('nav_profile') ?>
                        </a>
                        <a href="pages/settings.php" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i data-feather="settings" class="w-4 h-4 inline mr-2"></i>
                            <?= __('nav_settings') ?>
                        </a>
                        <hr class="my-1 border-gray-200 dark:border-gray-700">
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                            <i data-feather="log-out" class="w-4 h-4 inline mr-2"></i>
                            <?= __('nav_logout') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="p-6">
    <?php else: ?>
    <!-- Guest Header -->
    <header class="fixed top-0 left-0 right-0 h-16 bg-white dark:bg-gray-800 shadow-sm z-30">
        <div class="max-w-7xl mx-auto h-full flex items-center justify-between px-4">
            <a href="index.php" class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-800 dark:text-white"><?= e(__('app_name')) ?></span>
            </a>
            <div class="flex items-center space-x-4">
                <!-- Language Switcher -->
                <button onclick="toggleLangDropdown()" class="flex items-center space-x-1 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                    <span><?= $currentLang === 'ko' ? '한국어' : 'EN' ?></span>
                    <i data-feather="globe" class="w-4 h-4"></i>
                </button>
                <div id="lang-dropdown" class="hidden absolute top-14 right-20 w-32 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                    <a href="?lang=ko" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">한국어</a>
                    <a href="?lang=en" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">English</a>
                </div>

                <!-- Theme Toggle -->
                <button onclick="toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i data-feather="sun" class="w-5 h-5 hidden dark:block"></i>
                    <i data-feather="moon" class="w-5 h-5 dark:hidden"></i>
                </button>

                <a href="login.php" class="text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"><?= __('login') ?></a>
                <a href="register.php" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors"><?= __('register') ?></a>
            </div>
        </div>
    </header>
    <main class="pt-16">
    <?php endif; ?>
