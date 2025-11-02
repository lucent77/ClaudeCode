<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$user = \App\Core\Session::getUser();
$isAdmin = in_array($user['role'] ?? '', ['admin', 'super_admin']);
?>

<nav class="bg-white shadow-lg" x-data="{ mobileMenuOpen: false, profileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and Navigation Links -->
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="/dashboard" class="text-2xl font-bold text-blue-600">
                        CREODENT
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden sm:ml-8 sm:flex sm:space-x-4">
                    <a href="/dashboard"
                       class="<?= $currentPath === '/dashboard' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>

                    <a href="/cases"
                       class="<?= str_starts_with($currentPath, '/cases') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium">
                        Cases
                    </a>

                    <?php if ($isAdmin): ?>
                    <a href="/import"
                       class="<?= $currentPath === '/import' ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium">
                        Import
                    </a>

                    <a href="/admin/users"
                       class="<?= str_starts_with($currentPath, '/admin') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium">
                        Admin
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <!-- User Menu -->
                <div class="ml-3 relative" @click.away="profileMenuOpen = false">
                    <div>
                        <button @click="profileMenuOpen = !profileMenuOpen"
                                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="ml-2 text-gray-700 font-medium"><?= htmlspecialchars($user['name'] ?? 'User') ?></span>
                            <svg class="ml-2 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Dropdown Menu -->
                    <div x-show="profileMenuOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                        <div class="py-1">
                            <div class="px-4 py-2 text-xs text-gray-500 border-b">
                                <?= htmlspecialchars($user['role'] ?? '') ?> - <?= htmlspecialchars($user['department_name'] ?? 'No Department') ?>
                            </div>
                            <a href="/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center sm:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen" x-cloak class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <a href="/dashboard" class="<?= $currentPath === '/dashboard' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Dashboard
            </a>
            <a href="/cases" class="<?= str_starts_with($currentPath, '/cases') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Cases
            </a>
            <?php if ($isAdmin): ?>
            <a href="/import" class="<?= $currentPath === '/import' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Import
            </a>
            <a href="/admin/users" class="<?= str_starts_with($currentPath, '/admin') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                Admin
            </a>
            <?php endif; ?>
        </div>
        <div class="pt-4 pb-3 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                <div class="font-medium text-sm text-gray-500"><?= htmlspecialchars($user['role'] ?? '') ?></div>
            </div>
            <div class="mt-3 space-y-1">
                <a href="/logout" class="block px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                    Sign Out
                </a>
            </div>
        </div>
    </div>
</nav>
