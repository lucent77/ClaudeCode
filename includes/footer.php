<?php
/**
 * Footer Template
 * LifeMandalart - Self Management Web Service
 */

// Prevent direct access
if (!defined('LIFE_MANDALART')) {
    die('Direct access not permitted');
}

$currentUser = Auth::user();
?>
        </div>
    </main>

    <?php if (!$currentUser): ?>
    <!-- Guest Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        <?= __('footer_copyright', ['year' => date('Y')]) ?>
                    </span>
                </div>
                <div class="flex items-center space-x-6 text-sm text-gray-600 dark:text-gray-400">
                    <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400"><?= __('footer_privacy') ?></a>
                    <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400"><?= __('footer_terms') ?></a>
                    <a href="#" class="hover:text-primary-600 dark:hover:text-primary-400"><?= __('footer_contact') ?></a>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- Notification Panel -->
    <div id="notification-panel" class="fixed right-0 top-0 h-full w-80 bg-white dark:bg-gray-800 shadow-lg transform translate-x-full transition-transform duration-300 z-50">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold"><?= __('notifications') ?></h3>
            <button onclick="closeNotifications()" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="overflow-y-auto h-full pb-20" id="notification-list">
            <!-- Notifications will be loaded here -->
        </div>
    </div>

    <!-- Main JavaScript -->
    <script src="assets/js/app.js"></script>

    <script>
        // Initialize Feather Icons
        feather.replace();

        // Language dropdown toggle
        function toggleLangDropdown() {
            const dropdown = document.getElementById('lang-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // User menu toggle
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');

            if (isDark) {
                html.classList.remove('dark');
                saveTheme('light');
            } else {
                html.classList.add('dark');
                saveTheme('dark');
            }
        }

        function saveTheme(theme) {
            fetch('api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'update_theme', theme: theme })
            });
        }

        // Sidebar toggle (mobile)
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                sidebarOverlay.classList.toggle('hidden');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
            });
        }

        // Notifications
        function openNotifications() {
            const panel = document.getElementById('notification-panel');
            panel.classList.remove('translate-x-full');
            loadNotifications();
        }

        function closeNotifications() {
            const panel = document.getElementById('notification-panel');
            panel.classList.add('translate-x-full');
        }

        function loadNotifications() {
            fetch('api/notifications.php?action=list', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('notification-list');
                if (data.notifications && data.notifications.length > 0) {
                    list.innerHTML = data.notifications.map(n => `
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700 ${n.is_read ? 'opacity-60' : ''}" onclick="markNotificationRead(${n.id})">
                            <p class="font-medium text-sm">${n['title_<?= getCurrentLanguage() ?>']}</p>
                            <p class="text-sm text-gray-500 mt-1">${n['message_<?= getCurrentLanguage() ?>'] || ''}</p>
                            <p class="text-xs text-gray-400 mt-2">${n.created_at}</p>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                }
            });
        }

        function markNotificationRead(id) {
            fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'mark_read', id: id })
            });
        }

        // Notification buttons
        const notifBtn = document.getElementById('notifications-btn');
        const notifBtnDesktop = document.getElementById('notifications-btn-desktop');

        if (notifBtn) notifBtn.addEventListener('click', openNotifications);
        if (notifBtnDesktop) notifBtnDesktop.addEventListener('click', openNotifications);

        // Toast notification
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');

            const bgColors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            toast.className = `toast ${bgColors[type]} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2`;
            toast.innerHTML = `
                <i data-feather="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}" class="w-5 h-5"></i>
                <span>${message}</span>
            `;

            container.appendChild(toast);
            feather.replace();

            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            const langDropdown = document.getElementById('lang-dropdown');
            const userMenu = document.getElementById('user-menu');

            if (!e.target.closest('[onclick*="toggleLangDropdown"]') && langDropdown) {
                langDropdown.classList.add('hidden');
            }
            if (!e.target.closest('[onclick*="toggleUserMenu"]') && userMenu) {
                userMenu.classList.add('hidden');
            }
        });

        // CSRF Token
        const csrfToken = '<?= generateCsrfToken() ?>';
    </script>
</body>
</html>
