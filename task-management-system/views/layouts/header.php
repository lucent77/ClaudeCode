<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Task Management System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full">
    <?php if (isset($showNav) && $showNav): ?>
    <div class="min-h-full">
        <nav class="bg-white border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex flex-shrink-0 items-center">
                            <h1 class="text-xl font-bold text-gray-900">Task Management</h1>
                        </div>
                        <div class="hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-8">
                            <?php if (isset($_SESSION['role_name']) && ($_SESSION['role_name'] === 'admin' || $_SESSION['role_name'] === 'front_desk')): ?>
                            <a href="/front-desk.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'front-desk.php') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                <i class="fas fa-th-list mr-2"></i> Front Desk
                            </a>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['role_name']) && ($_SESSION['role_name'] === 'admin' || $_SESSION['department_code'] === 'SOLIDEX')): ?>
                            <a href="/solidex.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'solidex.php') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                <i class="fas fa-tooth mr-2"></i> Solidex
                            </a>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['role_name']) && ($_SESSION['role_name'] === 'admin' || $_SESSION['department_code'] === 'COCR')): ?>
                            <a href="/cocr.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'cocr.php') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                <i class="fas fa-crown mr-2"></i> COCR
                            </a>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['role_name']) && ($_SESSION['role_name'] === 'admin' || $_SESSION['department_code'] === '3D_PRINT')): ?>
                            <a href="/3d-print.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === '3d-print.php') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                <i class="fas fa-cube mr-2"></i> 3D Print
                            </a>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'admin'): ?>
                            <a href="/admin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) === 'admin.php') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                <i class="fas fa-cog mr-2"></i> Admin
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <div class="relative ml-3 flex items-center space-x-4">
                            <span class="text-sm text-gray-700">
                                <i class="fas fa-user-circle mr-1"></i>
                                <?php echo Security::escape($_SESSION['user_name'] ?? 'User'); ?>
                            </span>
                            <a href="/logout.php" class="text-sm text-gray-500 hover:text-gray-700">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="py-6">
            <?php endif; ?>
