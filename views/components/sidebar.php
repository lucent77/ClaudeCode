<?php
$user = auth()->user();
$userRole = auth()->userRole();
$userDept = auth()->userDepartment();
?>

<!-- Mobile sidebar -->
<div x-show="sidebarOpen" x-cloak
     x-transition:enter="transition ease-in-out duration-300 transform"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in-out duration-300 transform"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-xl lg:hidden">
    <div class="h-full flex flex-col">
        <?php include __DIR__ . '/sidebar-content.php'; ?>
    </div>
</div>

<!-- Desktop sidebar -->
<div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
    <div class="flex-1 flex flex-col min-h-0 bg-white border-r border-gray-200">
        <?php include __DIR__ . '/sidebar-content.php'; ?>
    </div>
</div>
