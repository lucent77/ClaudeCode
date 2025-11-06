<?php
/**
 * Header Template
 * Purchase Management System
 */

if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> - <?php echo APP_NAME; ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Styles -->
    <style>
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/purchase-system/dashboard.php" class="text-xl font-bold text-blue-600">
                            📦 <?php echo APP_NAME; ?>
                        </a>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/purchase-system/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>

                        <a href="/purchase-system/new-request.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'new-request.php' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            New Request
                        </a>

                        <?php if (isAdmin()): ?>
                        <a href="/purchase-system/admin/index.php" class="<?php echo str_contains_polyfill($_SERVER['PHP_SELF'], '/admin/') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Admin Panel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right side: User menu -->
                <div class="flex items-center">
                    <!-- Notifications -->
                    <?php
                    $unreadCount = isLoggedIn() ? getUnreadNotificationCount($_SESSION['user_id']) : 0;
                    ?>
                    <?php if ($unreadCount > 0): ?>
                    <a href="/purchase-system/notifications.php" class="mr-4 relative">
                        <svg class="h-6 w-6 text-gray-600 hover:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $unreadCount; ?></span>
                    </a>
                    <?php endif; ?>

                    <!-- User Dropdown -->
                    <div class="ml-3 relative dropdown">
                        <button class="flex items-center text-sm focus:outline-none">
                            <div class="flex items-center space-x-3">
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                                </div>
                                <span class="text-gray-700 font-medium"><?php echo e($_SESSION['username'] ?? 'User'); ?></span>
                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </button>

                        <!-- Dropdown menu -->
                        <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <div class="px-4 py-2 text-xs text-gray-500 border-b">
                                    <?php echo e($_SESSION['department'] ?? 'Department'); ?>
                                    <br>
                                    <span class="font-semibold"><?php echo isAdmin() ? 'Administrator' : 'User'; ?></span>
                                </div>
                                <a href="/purchase-system/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                                <?php if (isAdmin()): ?>
                                <a href="/purchase-system/admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <?php endif; ?>
                                <a href="/purchase-system/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1">
                <a href="/purchase-system/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Dashboard
                </a>
                <a href="/purchase-system/new-request.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'new-request.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    New Request
                </a>
                <?php if (isAdmin()): ?>
                <a href="/purchase-system/admin/index.php" class="<?php echo str_contains_polyfill($_SERVER['PHP_SELF'], '/admin/') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Admin Panel
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Messages -->
        <?php echo displayFlash(); ?>
