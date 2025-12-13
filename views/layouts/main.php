<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrfToken() ?>">
    <title><?= e($pageTitle ?? 'CAD/CAM Workflow System') ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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

    <!-- Alpine.js for interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link.active {
            background-color: #eff6ff;
            color: #1d4ed8;
            border-right: 3px solid #2563eb;
        }
        .step-badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }
        .btn {
            @apply inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
        }
        .btn-primary {
            @apply bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500;
        }
        .btn-secondary {
            @apply bg-white text-gray-700 border-gray-300 hover:bg-gray-50 focus:ring-primary-500;
        }
        .btn-danger {
            @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500;
        }
        .btn-success {
            @apply bg-green-600 text-white hover:bg-green-700 focus:ring-green-500;
        }
        .card {
            @apply bg-white shadow rounded-lg;
        }
        .table-container {
            @apply overflow-x-auto;
        }
        .data-table {
            @apply min-w-full divide-y divide-gray-200;
        }
        .data-table th {
            @apply px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50;
        }
        .data-table td {
            @apply px-6 py-4 whitespace-nowrap text-sm;
        }
        .data-table tbody tr {
            @apply hover:bg-gray-50 transition-colors;
        }
        .form-input {
            @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm;
        }
        .form-label {
            @apply block text-sm font-medium text-gray-700 mb-1;
        }
        .alert {
            @apply rounded-md p-4 mb-4;
        }
        .alert-success {
            @apply bg-green-50 text-green-800;
        }
        .alert-error {
            @apply bg-red-50 text-red-800;
        }
        .alert-warning {
            @apply bg-yellow-50 text-yellow-800;
        }
        .alert-info {
            @apply bg-blue-50 text-blue-800;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    <div class="min-h-full">
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>
        </div>

        <!-- Sidebar -->
        <?php include APP_ROOT . '/views/components/sidebar.php'; ?>

        <!-- Main content area -->
        <div class="lg:pl-64 flex flex-col min-h-screen">
            <!-- Top header -->
            <?php include APP_ROOT . '/views/components/header.php'; ?>

            <!-- Page content -->
            <main class="flex-1">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <!-- Flash messages -->
                        <?php if (hasFlash('success')): ?>
                            <div class="alert alert-success flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <?= e(flash('success')) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (hasFlash('error')): ?>
                            <div class="alert alert-error flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <?= e(flash('error')) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Page header -->
                        <?php if (!empty($pageTitle)): ?>
                            <div class="mb-6">
                                <h1 class="text-2xl font-semibold text-gray-900"><?= e($pageTitle) ?></h1>
                                <?php if (!empty($pageDescription)): ?>
                                    <p class="mt-1 text-sm text-gray-500"><?= e($pageDescription) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Main content -->
                        <?= $content ?? '' ?>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-sm text-gray-500">
                        CAD/CAM Workflow System v<?= APP_VERSION ?> &copy; <?= date('Y') ?>
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Global JavaScript -->
    <script>
        // CSRF token for AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Helper for AJAX requests
        async function fetchApi(url, options = {}) {
            const defaults = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            };

            const response = await fetch(url, { ...defaults, ...options });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Request failed');
            }

            return data;
        }

        // Toast notifications
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 transition-opacity duration-300 ${
                type === 'success' ? 'bg-green-600' :
                type === 'error' ? 'bg-red-600' :
                type === 'warning' ? 'bg-yellow-600' : 'bg-blue-600'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Confirm dialog
        function confirmAction(message) {
            return confirm(message);
        }

        // Format date
        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }
    </script>

    <?php if (!empty($scripts)): ?>
        <?= $scripts ?>
    <?php endif; ?>
</body>
</html>
