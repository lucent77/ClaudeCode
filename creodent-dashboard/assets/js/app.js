/**
 * Creodent Dashboard - Main Application JavaScript
 */

(function() {
    'use strict';

    // =========================================================================
    // Utility Functions
    // =========================================================================

    const Utils = {
        /**
         * Format currency
         */
        formatCurrency(value, symbol = '$') {
            return symbol + parseFloat(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        /**
         * Format number with commas
         */
        formatNumber(value) {
            return parseFloat(value).toLocaleString('en-US');
        },

        /**
         * Format percentage
         */
        formatPercent(value, decimals = 1) {
            return parseFloat(value).toFixed(decimals) + '%';
        },

        /**
         * Format date
         */
        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
        },

        /**
         * Debounce function
         */
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

        /**
         * Get URL parameters
         */
        getUrlParams() {
            const params = new URLSearchParams(window.location.search);
            return Object.fromEntries(params.entries());
        },

        /**
         * Update URL parameter
         */
        setUrlParam(key, value) {
            const url = new URL(window.location);
            url.searchParams.set(key, value);
            window.history.pushState({}, '', url);
        }
    };

    // =========================================================================
    // API Client
    // =========================================================================

    const API = {
        baseUrl: '../api/endpoint.php',

        /**
         * Make API request
         */
        async request(action, params = {}, method = 'GET') {
            const url = new URL(this.baseUrl, window.location.origin + window.location.pathname);
            url.searchParams.set('action', action);

            if (method === 'GET') {
                Object.entries(params).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        url.searchParams.set(key, value);
                    }
                });
            }

            const options = { method };

            if (method === 'POST') {
                options.headers = { 'Content-Type': 'application/json' };
                options.body = JSON.stringify(params);
            }

            try {
                const response = await fetch(url, options);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error?.message || 'Request failed');
                }

                return data.data;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        },

        // Convenience methods
        dashboard(params) { return this.request('dashboard', params); },
        customers(params) { return this.request('customers', params); },
        customerDetail(name, params) { return this.request('customer_detail', { name, ...params }); },
        products(params) { return this.request('products', params); },
        productDetail(name, params) { return this.request('product_detail', { name, ...params }); },
        remakes(params) { return this.request('remakes', params); },
        discounts(params) { return this.request('discounts', params); },
        riskInsights(params) { return this.request('risk_insights', params); }
    };

    // =========================================================================
    // Table Enhancements
    // =========================================================================

    const Tables = {
        /**
         * Initialize sortable tables
         */
        init() {
            document.querySelectorAll('table').forEach(table => {
                this.makeSortable(table);
            });
        },

        /**
         * Make table sortable
         */
        makeSortable(table) {
            const headers = table.querySelectorAll('th[data-sort]');

            headers.forEach(header => {
                header.addEventListener('click', () => {
                    const sortKey = header.dataset.sort;
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));

                    // Determine sort direction
                    const isAsc = header.classList.contains('sort-asc');
                    headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                    header.classList.add(isAsc ? 'sort-desc' : 'sort-asc');

                    // Sort rows
                    const colIndex = Array.from(header.parentNode.children).indexOf(header);
                    rows.sort((a, b) => {
                        const aVal = a.children[colIndex]?.textContent.trim() || '';
                        const bVal = b.children[colIndex]?.textContent.trim() || '';

                        // Try numeric sort first
                        const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                        const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));

                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return isAsc ? bNum - aNum : aNum - bNum;
                        }

                        // Fall back to string sort
                        return isAsc
                            ? bVal.localeCompare(aVal)
                            : aVal.localeCompare(bVal);
                    });

                    // Reorder DOM
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        }
    };

    // =========================================================================
    // Filter Handling
    // =========================================================================

    const Filters = {
        /**
         * Initialize filter form
         */
        init() {
            const form = document.getElementById('filter-form');
            if (!form) return;

            // Save filter preferences to localStorage
            form.addEventListener('submit', () => {
                const formData = new FormData(form);
                const filters = Object.fromEntries(formData.entries());
                localStorage.setItem('creodent_filters', JSON.stringify(filters));
            });

            // Restore filter preferences
            this.restore();
        },

        /**
         * Restore saved filters
         */
        restore() {
            const saved = localStorage.getItem('creodent_filters');
            if (!saved) return;

            try {
                const filters = JSON.parse(saved);
                // Only restore branch preference
                const branchSelect = document.querySelector('[name="branch"]');
                if (branchSelect && filters.branch && !new URLSearchParams(window.location.search).has('branch')) {
                    branchSelect.value = filters.branch;
                }
            } catch (e) {
                console.error('Failed to restore filters:', e);
            }
        }
    };

    // =========================================================================
    // Notifications
    // =========================================================================

    const Notifications = {
        container: null,

        /**
         * Initialize notification container
         */
        init() {
            this.container = document.createElement('div');
            this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(this.container);
        },

        /**
         * Show notification
         */
        show(message, type = 'info', duration = 5000) {
            const colors = {
                success: 'bg-emerald-500',
                error: 'bg-red-500',
                warning: 'bg-amber-500',
                info: 'bg-blue-500'
            };

            const notification = document.createElement('div');
            notification.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            notification.textContent = message;

            this.container.appendChild(notification);

            // Animate in
            requestAnimationFrame(() => {
                notification.classList.remove('translate-x-full');
            });

            // Auto dismiss
            if (duration > 0) {
                setTimeout(() => this.dismiss(notification), duration);
            }

            return notification;
        },

        /**
         * Dismiss notification
         */
        dismiss(notification) {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        },

        // Convenience methods
        success(msg) { return this.show(msg, 'success'); },
        error(msg) { return this.show(msg, 'error'); },
        warning(msg) { return this.show(msg, 'warning'); },
        info(msg) { return this.show(msg, 'info'); }
    };

    // =========================================================================
    // Loading States
    // =========================================================================

    const Loading = {
        /**
         * Show loading overlay
         */
        show(element) {
            const overlay = document.createElement('div');
            overlay.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10';
            overlay.innerHTML = `
                <div class="flex items-center gap-2 text-gray-500">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading...</span>
                </div>
            `;

            element.style.position = 'relative';
            element.appendChild(overlay);

            return overlay;
        },

        /**
         * Hide loading overlay
         */
        hide(overlay) {
            if (overlay && overlay.parentNode) {
                overlay.remove();
            }
        }
    };

    // =========================================================================
    // Initialize
    // =========================================================================

    document.addEventListener('DOMContentLoaded', () => {
        Tables.init();
        Filters.init();
        Notifications.init();
    });

    // Export to global scope
    window.Creodent = {
        Utils,
        API,
        Tables,
        Filters,
        Notifications,
        Loading
    };

})();
