<?php
$user = auth()->user();
$userRole = auth()->userRole();
$userDept = auth()->userDepartment();
$isSuperAdmin = auth()->isSuperAdmin();
$isDeptManager = auth()->isDepartmentManager();

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<!-- Logo -->
<div class="flex items-center h-16 flex-shrink-0 px-4 border-b border-gray-200">
    <a href="<?= url('/') ?>" class="flex items-center">
        <svg class="h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
        </svg>
        <span class="ml-2 text-xl font-bold text-gray-900">CAD/CAM</span>
    </a>
</div>

<!-- Navigation -->
<nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
    <!-- Dashboard -->
    <?php if ($isSuperAdmin): ?>
        <a href="<?= url('/admin/dashboard.php') ?>"
           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/admin/dashboard') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
            <svg class="mr-3 h-5 w-5 <?= strpos($currentPath, '/admin/dashboard') !== false ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Admin Dashboard
        </a>
    <?php endif; ?>

    <!-- Operator Tasks -->
    <?php if ($userRole === ROLE_OPERATOR): ?>
        <a href="<?= url('/operator/tasks.php') ?>"
           class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/operator/tasks') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
            <svg class="mr-3 h-5 w-5 <?= strpos($currentPath, '/operator/tasks') !== false ? 'text-primary-600' : 'text-gray-400 group-hover:text-gray-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            My Tasks
        </a>
    <?php endif; ?>

    <!-- Department Sections -->
    <?php if ($isSuperAdmin || $isDeptManager || $userDept === 'ALL' || in_array($userDept, ['COCR', 'ALL'])): ?>
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">COCR Department</p>
            <div class="mt-2 space-y-1">
                <a href="<?= url('/department/cocr/dashboard.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/department/cocr') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <span class="w-2 h-2 mr-3 bg-blue-500 rounded-full"></span>
                    COCR Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isSuperAdmin || $isDeptManager || $userDept === 'ALL' || in_array($userDept, ['SOLIDEX', 'ALL'])): ?>
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">SOLIDEX Department</p>
            <div class="mt-2 space-y-1">
                <a href="<?= url('/department/solidex/dashboard.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/department/solidex') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <span class="w-2 h-2 mr-3 bg-emerald-500 rounded-full"></span>
                    SOLIDEX Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($isSuperAdmin || $isDeptManager || $userDept === 'ALL' || in_array($userDept, ['3D_PRINT', 'ALL'])): ?>
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">3D Print Department</p>
            <div class="mt-2 space-y-1">
                <a href="<?= url('/department/3d-print/dashboard.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/department/3d-print') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <span class="w-2 h-2 mr-3 bg-violet-500 rounded-full"></span>
                    3D Print Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cases -->
    <div class="pt-4">
        <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Case Management</p>
        <div class="mt-2 space-y-1">
            <a href="<?= url('/cases/index.php') ?>"
               class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/cases/index') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                All Cases
            </a>
            <?php if ($isDeptManager || $isSuperAdmin): ?>
                <a href="<?= url('/cases/create.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/cases/create') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Case
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- HOLD Management -->
    <?php if ($isDeptManager || $isSuperAdmin): ?>
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Management</p>
            <div class="mt-2 space-y-1">
                <a href="<?= url('/hold/index.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/hold/') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="mr-3 h-5 w-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Hold Management
                </a>
                <a href="<?= url('/reports/index.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/reports/') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Reports
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Admin Settings -->
    <?php if ($isSuperAdmin): ?>
        <div class="pt-4">
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</p>
            <div class="mt-2 space-y-1">
                <a href="<?= url('/admin/users.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/admin/users') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Users
                </a>
                <a href="<?= url('/admin/settings.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/admin/settings') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
                <a href="<?= url('/admin/workflow-config.php') ?>"
                   class="sidebar-link group flex items-center px-3 py-2 text-sm font-medium rounded-md <?= strpos($currentPath, '/admin/workflow') !== false ? 'active' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    Workflow Config
                </a>
            </div>
        </div>
    <?php endif; ?>
</nav>

<!-- User info -->
<div class="flex-shrink-0 flex border-t border-gray-200 p-4">
    <div class="flex-shrink-0 w-full group block">
        <div class="flex items-center">
            <div class="inline-flex items-center justify-center h-9 w-9 rounded-full bg-primary-600">
                <span class="text-sm font-medium leading-none text-white"><?= e($user['initials'] ?? 'U') ?></span>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-gray-700"><?= e($user['full_name'] ?? 'User') ?></p>
                <p class="text-xs font-medium text-gray-500"><?= e(ucwords(str_replace('_', ' ', $userRole ?? 'operator'))) ?></p>
            </div>
            <a href="<?= url('/logout.php') ?>" class="text-gray-400 hover:text-gray-600" title="Logout">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </a>
        </div>
    </div>
</div>
