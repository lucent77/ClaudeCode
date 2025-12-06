<?php
/**
 * Creodent Dashboard - Header Component
 *
 * Usage: include this file at the top of each page
 * Required variables:
 *   $pageTitle - string - The title of the current page
 *   $currentPage - string - The current page key for navigation highlighting
 */

use Creodent\Core\Auth;

$routes = require dirname(__DIR__) . '/config/routes.php';
$username = Auth::username();
$isAdmin = Auth::isAdmin();

// Navigation groups
$navGroups = [
    'main' => ['dashboard'],
    'customers' => ['customers', 'customer-compare', 'inactive-customers', 'new-customers', 'customer-groups'],
    'products' => ['products', 'product-compare', 'parts'],
    'remakes' => ['remakes', 'remake-customers', 'remake-products'],
    'analysis' => ['discounts', 'risk-insights']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .nav-link { @apply flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg transition-colors; }
        .nav-link:hover { @apply bg-gray-100 text-gray-900; }
        .nav-link.active { @apply bg-blue-50 text-blue-700; }
        .nav-group-title { @apply px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        branch: {
                            all: '#3b82f6',
                            nyc: '#f59e0b',
                            hv: '#10b981'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Mobile Menu Button -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
            <h1 class="font-semibold text-gray-900"><?= APP_NAME ?></h1>
            <button id="mobile-menu-btn" class="p-2 text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">
        <!-- Logo -->
        <div class="h-16 flex items-center gap-3 px-6 border-b border-gray-200">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <span class="font-semibold text-gray-900"><?= APP_NAME ?></span>
        </div>

        <!-- Navigation -->
        <nav class="p-4 space-y-6 overflow-y-auto h-[calc(100vh-8rem)]">
            <!-- Main -->
            <div>
                <a href="dashboard.php" class="nav-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : 'text-gray-600' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>
            </div>

            <!-- Customers -->
            <div>
                <p class="nav-group-title">Customers</p>
                <div class="space-y-1">
                    <a href="customers.php" class="nav-link <?= ($currentPage ?? '') === 'customers' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        All Customers
                    </a>
                    <a href="customer-compare.php" class="nav-link <?= ($currentPage ?? '') === 'customer-compare' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                        Comparison
                    </a>
                    <a href="inactive-customers.php" class="nav-link <?= ($currentPage ?? '') === 'inactive-customers' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                        </svg>
                        Inactive
                    </a>
                    <a href="new-customers.php" class="nav-link <?= ($currentPage ?? '') === 'new-customers' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        New Customers
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="customer-groups.php" class="nav-link <?= ($currentPage ?? '') === 'customer-groups' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        Groups
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products -->
            <div>
                <p class="nav-group-title">Products</p>
                <div class="space-y-1">
                    <a href="products.php" class="nav-link <?= ($currentPage ?? '') === 'products' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        All Products
                    </a>
                    <a href="product-compare.php" class="nav-link <?= ($currentPage ?? '') === 'product-compare' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Comparison
                    </a>
                    <a href="parts.php" class="nav-link <?= ($currentPage ?? '') === 'parts' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Parts (REF)
                    </a>
                </div>
            </div>

            <!-- Remakes -->
            <div>
                <p class="nav-group-title">Remakes</p>
                <div class="space-y-1">
                    <a href="remakes.php" class="nav-link <?= ($currentPage ?? '') === 'remakes' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Overview
                    </a>
                    <a href="remake-customers.php" class="nav-link <?= ($currentPage ?? '') === 'remake-customers' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        By Customer
                    </a>
                    <a href="remake-products.php" class="nav-link <?= ($currentPage ?? '') === 'remake-products' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        By Product
                    </a>
                </div>
            </div>

            <!-- Analysis -->
            <div>
                <p class="nav-group-title">Analysis</p>
                <div class="space-y-1">
                    <a href="discounts.php" class="nav-link <?= ($currentPage ?? '') === 'discounts' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Discounts
                    </a>
                    <a href="risk-insights.php" class="nav-link <?= ($currentPage ?? '') === 'risk-insights' ? 'active' : 'text-gray-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Risk Insights
                    </a>
                </div>
            </div>
        </nav>

        <!-- User Menu -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($username) ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= Auth::role() ?></p>
                    </div>
                </div>
                <a href="../logout.php" class="p-2 text-gray-400 hover:text-gray-600" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- Mobile overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <!-- Main Content -->
    <main class="lg:pl-64 pt-16 lg:pt-0">
        <div class="p-6">
