/**
 * Smart Delivery Route Optimizer
 * Main JavaScript Application
 */

// Utility Functions
const Utils = {
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Format time from ISO string
     */
    formatTime(isoString) {
        if (!isoString) return '-';
        return new Date(isoString).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Format date from ISO string
     */
    formatDate(isoString) {
        if (!isoString) return '-';
        return new Date(isoString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    /**
     * Format distance in meters
     */
    formatDistance(meters) {
        if (meters >= 1000) {
            return (meters / 1000).toFixed(1) + ' km';
        }
        return Math.round(meters) + ' m';
    },

    /**
     * Format duration in seconds
     */
    formatDuration(seconds) {
        if (seconds < 60) {
            return seconds + ' sec';
        }

        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);

        if (hours > 0) {
            return hours + ' hr ' + minutes + ' min';
        }

        return minutes + ' min';
    },

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Earth radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
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
     * Show toast notification
     */
    showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-20 right-4 px-4 py-2 rounded-lg shadow-lg z-50 fade-in ${
            type === 'success' ? 'bg-green-600 text-white' :
            type === 'error' ? 'bg-red-600 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-gray-800 text-white'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
};

// API Client
const API = {
    baseUrl: '/api',

    /**
     * Make API request
     */
    async request(endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (options.body && typeof options.body === 'object') {
            config.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error?.message || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * Get customers
     */
    getCustomers(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return this.request('/customers.php?' + queryString);
    },

    /**
     * Create optimized route
     */
    createRoute(customerIds, options = {}) {
        return this.request('/get-route.php', {
            method: 'POST',
            body: {
                customer_ids: customerIds,
                ...options
            }
        });
    },

    /**
     * Recalculate route
     */
    recalculateRoute(routeId, currentLocation, remainingCustomerIds) {
        return this.request('/recalculate.php', {
            method: 'POST',
            body: {
                route_id: routeId,
                current_location: currentLocation,
                remaining_customer_ids: remainingCustomerIds
            }
        });
    },

    /**
     * Add customer
     */
    addCustomer(customerData) {
        return this.request('/add-customer.php', {
            method: 'POST',
            body: customerData
        });
    }
};

// Geolocation Service
const GeoService = {
    watchId: null,
    currentPosition: null,
    callbacks: [],

    /**
     * Check if geolocation is supported
     */
    isSupported() {
        return 'geolocation' in navigator;
    },

    /**
     * Get current position (one-time)
     */
    getCurrentPosition(options = {}) {
        return new Promise((resolve, reject) => {
            if (!this.isSupported()) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                position => {
                    this.currentPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        heading: position.coords.heading,
                        timestamp: position.timestamp
                    };
                    resolve(this.currentPosition);
                },
                reject,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 5000,
                    ...options
                }
            );
        });
    },

    /**
     * Start watching position
     */
    watchPosition(callback, options = {}) {
        if (!this.isSupported()) {
            throw new Error('Geolocation not supported');
        }

        this.callbacks.push(callback);

        if (this.watchId === null) {
            this.watchId = navigator.geolocation.watchPosition(
                position => {
                    this.currentPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        heading: position.coords.heading,
                        timestamp: position.timestamp
                    };

                    this.callbacks.forEach(cb => cb(this.currentPosition));
                },
                error => {
                    console.error('Geolocation error:', error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 5000,
                    ...options
                }
            );
        }

        return () => this.stopWatching(callback);
    },

    /**
     * Stop watching specific callback
     */
    stopWatching(callback) {
        this.callbacks = this.callbacks.filter(cb => cb !== callback);

        if (this.callbacks.length === 0 && this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
    },

    /**
     * Stop all watching
     */
    stopAll() {
        this.callbacks = [];
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
    }
};

// Storage Service
const Storage = {
    /**
     * Save route data
     */
    saveRoute(routeData) {
        sessionStorage.setItem('routeData', JSON.stringify(routeData));
    },

    /**
     * Get route data
     */
    getRoute() {
        const data = sessionStorage.getItem('routeData');
        return data ? JSON.parse(data) : null;
    },

    /**
     * Clear route data
     */
    clearRoute() {
        sessionStorage.removeItem('routeData');
    },

    /**
     * Save selection
     */
    saveSelection(customerIds) {
        localStorage.setItem('selectedCustomers', JSON.stringify(Array.from(customerIds)));
    },

    /**
     * Get selection
     */
    getSelection() {
        const data = localStorage.getItem('selectedCustomers');
        return data ? new Set(JSON.parse(data)) : new Set();
    },

    /**
     * Clear selection
     */
    clearSelection() {
        localStorage.removeItem('selectedCustomers');
    }
};

// Export for global use
window.Utils = Utils;
window.API = API;
window.GeoService = GeoService;
window.Storage = Storage;
