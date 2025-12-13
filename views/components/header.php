<?php
$user = auth()->user();
?>

<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Mobile menu button -->
            <div class="flex items-center lg:hidden">
                <button @click="sidebarOpen = !sidebarOpen" type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- Search bar -->
            <div class="flex-1 flex items-center justify-center px-2 lg:ml-6 lg:justify-start">
                <div class="max-w-lg w-full lg:max-w-xs">
                    <label for="search" class="sr-only">Search cases</label>
                    <form action="<?= url('/cases/index.php') ?>" method="GET" class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input id="search" name="search" type="search" placeholder="Search cases..."
                               value="<?= e($_GET['search'] ?? '') ?>"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-gray-50 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </form>
                </div>
            </div>

            <!-- Right side items -->
            <div class="flex items-center space-x-4">
                <!-- Quick stats -->
                <?php
                $stats = caseManager()->getDashboardStats(auth()->userDepartment() !== 'ALL' ? auth()->userDepartment() : null);
                ?>
                <div class="hidden md:flex items-center space-x-4 text-sm">
                    <?php if ($stats['overdue'] > 0): ?>
                        <a href="<?= url('/cases/index.php?status=overdue') ?>" class="flex items-center text-red-600 hover:text-red-700">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <?= $stats['overdue'] ?> Overdue
                        </a>
                    <?php endif; ?>
                    <?php if ($stats['due_today'] > 0): ?>
                        <a href="<?= url('/cases/index.php?due_date=' . date('Y-m-d')) ?>" class="flex items-center text-orange-600 hover:text-orange-700">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <?= $stats['due_today'] ?> Due Today
                        </a>
                    <?php endif; ?>
                    <?php if ($stats['on_hold'] > 0): ?>
                        <a href="<?= url('/hold/index.php') ?>" class="flex items-center text-yellow-600 hover:text-yellow-700">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <?= $stats['on_hold'] ?> On Hold
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Notifications dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                            class="p-2 rounded-full text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="sr-only">View notifications</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($stats['overdue'] > 0 || $stats['on_hold'] > 0): ?>
                            <span class="absolute top-1 right-1 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
                        <?php endif; ?>
                    </button>

                    <!-- Notifications dropdown panel -->
                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <?php if ($stats['overdue'] > 0): ?>
                                <a href="<?= url('/cases/index.php?status=overdue') ?>" class="block px-4 py-3 hover:bg-gray-50">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100">
                                                <svg class="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="ml-3 w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900"><?= $stats['overdue'] ?> Overdue Cases</p>
                                            <p class="mt-0.5 text-xs text-gray-500">These cases have passed their due date</p>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <?php if ($stats['on_hold'] > 0): ?>
                                <a href="<?= url('/hold/index.php') ?>" class="block px-4 py-3 hover:bg-gray-50">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-yellow-100">
                                                <svg class="h-4 w-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="ml-3 w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900"><?= $stats['on_hold'] ?> Cases On Hold</p>
                                            <p class="mt-0.5 text-xs text-gray-500">Review and resolve held cases</p>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <?php if ($stats['overdue'] === 0 && $stats['on_hold'] === 0): ?>
                                <div class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">All caught up!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User menu -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button"
                            class="flex items-center space-x-2 p-2 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-primary-600">
                            <span class="text-sm font-medium leading-none text-white"><?= e($user['initials'] ?? 'U') ?></span>
                        </span>
                        <span class="hidden md:block text-sm font-medium text-gray-700"><?= e($user['full_name'] ?? 'User') ?></span>
                        <svg class="hidden md:block h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- User dropdown panel -->
                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900"><?= e($user['full_name'] ?? 'User') ?></p>
                            <p class="text-xs text-gray-500"><?= e($user['email'] ?? '') ?></p>
                        </div>
                        <a href="<?= url('/profile.php') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <svg class="inline-block w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profile Settings
                        </a>
                        <a href="<?= url('/logout.php') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <svg class="inline-block w-4 h-4 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sign out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
