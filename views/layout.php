<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($title ?? 'CREODENT Work Manager') ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for reactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }

        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .toast {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>

    <?php if (isset($additionalStyles)): ?>
        <?= $additionalStyles ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Toast Notifications -->
    <div x-data="toastManager()"
         @toast.window="show($event.detail)"
         class="fixed top-4 right-4 z-50 space-y-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 :class="{
                     'bg-green-500': toast.type === 'success',
                     'bg-red-500': toast.type === 'error',
                     'bg-yellow-500': toast.type === 'warning',
                     'bg-blue-500': toast.type === 'info'
                 }"
                 class="toast px-6 py-4 rounded-lg shadow-lg text-white max-w-sm">
                <div class="flex items-start">
                    <div class="flex-1" x-text="toast.message"></div>
                    <button @click="remove(toast.id)" class="ml-4 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <?php if (!\App\Core\Session::isLoggedIn()): ?>
        <!-- Content for non-authenticated pages (login) -->
        <?= $content ?>
    <?php else: ?>
        <!-- Navigation -->
        <?php include __DIR__ . '/components/nav.php'; ?>

        <!-- Main Content -->
        <main class="fade-in">
            <?= $content ?>
        </main>
    <?php endif; ?>

    <!-- Global Scripts -->
    <script>
        // CSRF Token
        const csrfToken = '<?= \App\Core\Session::generateCsrfToken() ?>';

        // API Request Helper
        async function apiRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };

            const response = await fetch(url, {
                ...defaultOptions,
                ...options,
                headers: {
                    ...defaultOptions.headers,
                    ...(options.headers || {})
                }
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Request failed');
            }

            return data;
        }

        // Toast Manager (Alpine.js)
        function toastManager() {
            return {
                toasts: [],
                nextId: 1,

                show(options) {
                    const toast = {
                        id: this.nextId++,
                        message: options.message || 'Notification',
                        type: options.type || 'info',
                        visible: true
                    };

                    this.toasts.push(toast);

                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        this.remove(toast.id);
                    }, options.duration || 5000);
                },

                remove(id) {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index !== -1) {
                        this.toasts[index].visible = false;
                        setTimeout(() => {
                            this.toasts.splice(index, 1);
                        }, 200);
                    }
                }
            }
        }

        // Show flash messages as toasts
        <?php
        $flashSuccess = \App\Core\Session::getFlash('success');
        $flashError = \App\Core\Session::getFlash('error');
        $flashWarning = \App\Core\Session::getFlash('warning');
        $flashInfo = \App\Core\Session::getFlash('info');
        ?>

        <?php if ($flashSuccess): ?>
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message: <?= json_encode($flashSuccess) ?>, type: 'success' }
            }));
        <?php endif; ?>

        <?php if ($flashError): ?>
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message: <?= json_encode($flashError) ?>, type: 'error' }
            }));
        <?php endif; ?>

        <?php if ($flashWarning): ?>
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message: <?= json_encode($flashWarning) ?>, type: 'warning' }
            }));
        <?php endif; ?>

        <?php if ($flashInfo): ?>
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { message: <?= json_encode($flashInfo) ?>, type: 'info' }
            }));
        <?php endif; ?>
    </script>

    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>
