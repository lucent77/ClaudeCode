<?php
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/Security.php';
require_once __DIR__ . '/includes/TaskManager.php';

$auth = new Auth();
$auth->requireLogin();

$taskManager = new TaskManager();
$user = $auth->getCurrentUser();

// Get stats based on user role
$stats = [];
if ($auth->isAdmin() || $auth->isFrontDesk()) {
    $stats = $taskManager->getDashboardStats();
} else {
    $stats = $taskManager->getDashboardStats($auth->getDepartmentId());
}

$pageTitle = 'Dashboard - Task Management System';
$showNav = true;
?>

<?php include __DIR__ . '/views/layouts/header.php'; ?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            Welcome, <?php echo Security::escape($user['name']); ?>!
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            <?php echo Security::escape($user['role_name']); ?>
            <?php if ($user['department_name']): ?>
                - <?php echo Security::escape($user['department_name']); ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Total Tasks -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Total Tasks</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                <?php echo number_format($stats['total']); ?>
            </dd>
        </div>

        <!-- Pending -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Pending</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">
                <?php echo number_format($stats['pending']); ?>
            </dd>
        </div>

        <!-- In Progress -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">In Progress</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-600">
                <?php echo number_format($stats['in_progress']); ?>
            </dd>
        </div>

        <!-- Completed -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Completed</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">
                <?php echo number_format($stats['completed']); ?>
            </dd>
        </div>

        <!-- Due Today -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Due Today</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-orange-600">
                <?php echo number_format($stats['due_today']); ?>
            </dd>
        </div>

        <!-- Overdue -->
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Overdue</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">
                <?php echo number_format($stats['overdue']); ?>
            </dd>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900">Quick Navigation</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <?php if ($auth->isAdmin() || $auth->isFrontDesk()): ?>
            <a href="/front-desk.php" class="block rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-th-list text-3xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Front Desk</h3>
                        <p class="text-sm text-gray-500">Manage all tasks</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($auth->isAdmin() || $auth->getDepartmentId() == 2): ?>
            <a href="/solidex.php" class="block rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-tooth text-3xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Solidex</h3>
                        <p class="text-sm text-gray-500">Solidex department</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($auth->isAdmin() || $auth->getDepartmentId() == 3): ?>
            <a href="/cocr.php" class="block rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-crown text-3xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">COCR</h3>
                        <p class="text-sm text-gray-500">COCR department</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($auth->isAdmin() || $auth->getDepartmentId() == 4): ?>
            <a href="/3d-print.php" class="block rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-cube text-3xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">3D Print</h3>
                        <p class="text-sm text-gray-500">3D printing dept</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($auth->isAdmin()): ?>
            <a href="/admin.php" class="block rounded-lg bg-white p-6 shadow hover:bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-cog text-3xl text-gray-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Admin</h3>
                        <p class="text-sm text-gray-500">System settings</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/views/layouts/footer.php'; ?>
