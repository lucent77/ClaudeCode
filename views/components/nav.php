<?php
$user = \App\Core\Session::getUser();
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';
$isActive = function($path) use ($currentPath) {
    return str_starts_with($currentPath, $path) ? 'bg-indigo-700' : '';
};
?>

<nav x-data="{ mobileMenuOpen: false, userMenuOpen: false }" class="bg-gradient-to-r from-indigo-600 to-purple-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and Main Navigation -->
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="/dashboard" class="text-white font-bold text-xl flex items-center space-x-2">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span>CREODENT</span>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:ml-8 md:flex md:space-x-2">
                    <a href="/dashboard" class="<?= $isActive('/dashboard') ?> text-white hover:bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium transition">
                        Dashboard
                    </a>
                    <a href="/cases" class="<?= $isActive('/cases') ?> text-white hover:bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium transition">
                        Cases
                    </a>

                    <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin'])): ?>
                        <a href="/import" class="<?= $isActive('/import') ?> text-white hover:bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium transition">
                            Import
                        </a>
                    <?php endif; ?>

                    <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin'])): ?>
                        <a href="/admin/users" class="<?= $isActive('/admin') ?> text-white hover:bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium transition">
                            Admin
                        </a>
                    <?php endif; ?>

                    <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin'])): ?>
                        <a href="/audit" class="<?= $isActive('/audit') ?> text-white hover:bg-indigo-700 px-3 py-2 rounded-md text-sm font-medium transition">
                            Audit Logs
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Menu (Desktop) -->
            <div class="hidden md:flex md:items-center md:space-x-4">
                <!-- Notifications (Future Feature) -->
                <!--
                <button class="text-white hover:text-indigo-100 p-2 rounded-full hover:bg-indigo-700 transition">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </button>
                -->

                <!-- User Dropdown -->
                <div class="relative" @click.away="userMenuOpen = false">
                    <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-3 text-white hover:bg-indigo-700 px-3 py-2 rounded-md transition">
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 rounded-full bg-indigo-800 flex items-center justify-center text-sm font-bold">
                                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="text-left">
                                <div class="text-sm font-medium"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                                <div class="text-xs text-indigo-200"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role'] ?? 'user'))) ?></div>
                            </div>
                        </div>
                        <svg class="h-5 w-5" :class="{'rotate-180': userMenuOpen}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="userMenuOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-50">
                        <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <div class="flex items-center space-x-2">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Settings</span>
                            </div>
                        </a>
                        <div class="border-t border-gray-100"></div>
                        <form method="POST" action="/logout">
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <div class="flex items-center space-x-2">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    <span>Logout</span>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-white hover:bg-indigo-700 p-2 rounded-md">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-indigo-700">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/dashboard" class="<?= $isActive('/dashboard') ?> text-white hover:bg-indigo-800 block px-3 py-2 rounded-md text-base font-medium">
                Dashboard
            </a>
            <a href="/cases" class="<?= $isActive('/cases') ?> text-white hover:bg-indigo-800 block px-3 py-2 rounded-md text-base font-medium">
                Cases
            </a>

            <?php if (\App\Core\Session::hasAnyRole(['super_admin', 'admin'])): ?>
                <a href="/import" class="<?= $isActive('/import') ?> text-white hover:bg-indigo-800 block px-3 py-2 rounded-md text-base font-medium">
                    Import
                </a>
                <a href="/admin/users" class="<?= $isActive('/admin') ?> text-white hover:bg-indigo-800 block px-3 py-2 rounded-md text-base font-medium">
                    Admin
                </a>
                <a href="/audit" class="<?= $isActive('/audit') ?> text-white hover:bg-indigo-800 block px-3 py-2 rounded-md text-base font-medium">
                    Audit Logs
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile User Menu -->
        <div class="pt-4 pb-3 border-t border-indigo-800">
            <div class="px-5 flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-indigo-800 flex items-center justify-center text-lg font-bold text-white">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div class="text-base font-medium text-white"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                    <div class="text-sm text-indigo-200"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>
            </div>
            <div class="mt-3 px-2 space-y-1">
                <a href="/settings" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-800">
                    Settings
                </a>
                <form method="POST" action="/logout">
                    <button type="submit" class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-white hover:bg-indigo-800">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
