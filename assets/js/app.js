/**
 * LifeMandalart - Main JavaScript
 * Self Management Web Service
 */

// Global app object
const App = {
    // Configuration
    config: {
        apiBase: 'api/',
        toastDuration: 3000,
        animationDuration: 300
    },

    // Initialize application
    init() {
        this.initTheme();
        this.initEventListeners();
        this.initFeatherIcons();
        console.log('LifeMandalart initialized');
    },

    // Initialize theme based on user preference
    initTheme() {
        const savedTheme = document.documentElement.dataset.theme;
        if (savedTheme === 'system') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                document.documentElement.classList.add('dark');
            }
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (document.documentElement.dataset.theme === 'system') {
                document.documentElement.classList.toggle('dark', e.matches);
            }
        });
    },

    // Initialize Feather icons
    initFeatherIcons() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    },

    // Initialize global event listeners
    initEventListeners() {
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-trigger')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // Handle escape key for modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        // Handle form submissions with loading state
        document.querySelectorAll('form[data-ajax]').forEach(form => {
            form.addEventListener('submit', this.handleAjaxForm.bind(this));
        });
    },

    // Close all open modals
    closeAllModals() {
        document.querySelectorAll('[id$="-modal"]').forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    },

    // Handle AJAX form submission
    async handleAjaxForm(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn?.innerHTML;

        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner inline-block mr-2"></span> Loading...';
            }

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            const response = await this.api(form.dataset.action || form.action, {
                method: form.method || 'POST',
                body: JSON.stringify(data)
            });

            if (response.success) {
                this.toast(response.message || 'Success', 'success');
                if (form.dataset.redirect) {
                    setTimeout(() => {
                        window.location.href = form.dataset.redirect;
                    }, 500);
                } else if (form.dataset.reload) {
                    setTimeout(() => location.reload(), 500);
                }
            } else {
                this.toast(response.message || 'Error occurred', 'error');
            }
        } catch (error) {
            this.toast('Network error. Please try again.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    },

    // API request helper
    async api(endpoint, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const mergedOptions = { ...defaultOptions, ...options };

        if (typeof csrfToken !== 'undefined' && mergedOptions.method !== 'GET') {
            if (mergedOptions.body) {
                const body = JSON.parse(mergedOptions.body);
                body.csrf_token = csrfToken;
                mergedOptions.body = JSON.stringify(body);
            }
        }

        const response = await fetch(this.config.apiBase + endpoint, mergedOptions);
        return response.json();
    },

    // Toast notification
    toast(message, type = 'info') {
        const container = document.getElementById('toast-container') || this.createToastContainer();

        const toast = document.createElement('div');
        const bgColors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        const icons = {
            success: 'check-circle',
            error: 'x-circle',
            warning: 'alert-triangle',
            info: 'info'
        };

        toast.className = `toast ${bgColors[type]} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2`;
        toast.innerHTML = `
            <i data-feather="${icons[type]}" class="w-5 h-5"></i>
            <span>${this.escapeHtml(message)}</span>
        `;

        container.appendChild(toast);
        if (typeof feather !== 'undefined') feather.replace();

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Remove after duration
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), this.config.animationDuration);
        }, this.config.toastDuration);
    },

    // Create toast container if not exists
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
        return container;
    },

    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // Format number with locale
    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    },

    // Format date
    formatDate(date, options = {}) {
        return new Intl.DateTimeFormat(document.documentElement.lang || 'ko', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            ...options
        }).format(new Date(date));
    },

    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Show confirmation dialog
    async confirm(message, title = 'Confirm') {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4 animate-fadeIn">
                    <h3 class="text-lg font-bold mb-2">${this.escapeHtml(title)}</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">${this.escapeHtml(message)}</p>
                    <div class="flex space-x-3">
                        <button class="flex-1 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700" data-action="confirm">Yes</button>
                        <button class="flex-1 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700" data-action="cancel">No</button>
                    </div>
                </div>
            `;

            modal.querySelector('[data-action="confirm"]').addEventListener('click', () => {
                modal.remove();
                resolve(true);
            });

            modal.querySelector('[data-action="cancel"]').addEventListener('click', () => {
                modal.remove();
                resolve(false);
            });

            document.body.appendChild(modal);
        });
    },

    // Show loading overlay
    showLoading(container = document.body) {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay fixed inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-50';
        overlay.innerHTML = '<div class="spinner w-12 h-12 border-4 border-primary-600"></div>';
        container.appendChild(overlay);
        return overlay;
    },

    // Hide loading overlay
    hideLoading(overlay) {
        if (overlay) overlay.remove();
    },

    // Local storage helpers
    storage: {
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(key);
                return item ? JSON.parse(item) : defaultValue;
            } catch {
                return defaultValue;
            }
        },

        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch {
                return false;
            }
        },

        remove(key) {
            localStorage.removeItem(key);
        }
    },

    // Animate progress bar
    animateProgress(element, targetValue, duration = 500) {
        const start = parseFloat(element.style.width) || 0;
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const current = start + (targetValue - start) * this.easeOutCubic(progress);
            element.style.width = `${current}%`;

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    },

    // Easing function
    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    },

    // Confetti effect for achievements
    confetti(container = document.body) {
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        const confettiCount = 50;

        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.style.cssText = `
                position: fixed;
                width: 10px;
                height: 10px;
                background: ${colors[Math.floor(Math.random() * colors.length)]};
                left: ${Math.random() * 100}vw;
                top: 100vh;
                border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
                animation: confetti ${1 + Math.random()}s ease-out forwards;
                z-index: 9999;
            `;
            container.appendChild(confetti);
            setTimeout(() => confetti.remove(), 2000);
        }
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => App.init());

// Export for global access
window.App = App;

// Convenience function for toasts
function showToast(message, type = 'success') {
    App.toast(message, type);
}
