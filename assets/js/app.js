/**
 * Main Application JavaScript
 * Handles PWA registration, auth state, and utilities
 */

// Configuration
const API_BASE = '/api';

/**
 * Register Service Worker
 */
async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('Service Worker registered:', registration.scope);

            // Handle updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('Service Worker update found');

                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        // New service worker available
                        showUpdateNotification();
                    }
                });
            });

            return registration;
        } catch (error) {
            console.error('Service Worker registration failed:', error);
        }
    }
}

/**
 * Show update notification
 */
function showUpdateNotification() {
    const banner = document.createElement('div');
    banner.className = 'fixed top-0 left-0 right-0 bg-blue-600 text-white p-4 text-center z-50';
    banner.innerHTML = `
        <p class="mb-2">New version available!</p>
        <button onclick="window.location.reload()" class="bg-white text-blue-600 px-4 py-2 rounded font-semibold">
            Update Now
        </button>
    `;
    document.body.prepend(banner);
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    // Simple check - in production, verify with server
    return localStorage.getItem('user_id') !== null;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    return {
        id: localStorage.getItem('user_id'),
        email: localStorage.getItem('user_email')
    };
}

/**
 * Save user session
 */
function saveUserSession(userId, email) {
    localStorage.setItem('user_id', userId);
    localStorage.setItem('user_email', email);
}

/**
 * Clear user session
 */
function clearUserSession() {
    localStorage.removeItem('user_id');
    localStorage.removeItem('user_email');
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        window.location.href = '/pages/login.html';
        return false;
    }
    return true;
}

/**
 * Make authenticated API request
 */
async function apiRequest(endpoint, options = {}) {
    try {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            credentials: 'same-origin'
        };

        const response = await fetch(`${API_BASE}${endpoint}`, {
            ...defaultOptions,
            ...options
        });

        const data = await response.json();

        // Handle authentication errors
        if (response.status === 401) {
            clearUserSession();
            window.location.href = '/pages/login.html';
            throw new Error('Authentication required');
        }

        return {
            ok: response.ok,
            status: response.status,
            data: data
        };

    } catch (error) {
        console.error('API request failed:', error);
        throw error;
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };

    toast.className = `fixed bottom-4 right-4 ${bgColors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-slide-up`;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('opacity-0', 'transition-opacity');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Show loading spinner
 */
function showLoading(message = 'Loading...') {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    loading.innerHTML = `
        <div class="bg-white rounded-lg p-6 flex flex-col items-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
            <p class="text-gray-700 font-medium">${message}</p>
        </div>
    `;
    document.body.appendChild(loading);
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

/**
 * Format relative time (e.g., "2 hours ago")
 */
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;

    return formatDate(dateString);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize app
 */
async function initApp() {
    // Register service worker
    await registerServiceWorker();

    // Check for iOS standalone mode
    if (window.navigator.standalone) {
        document.body.classList.add('ios-standalone');
    }

    // Add install prompt listener
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        showInstallButton(deferredPrompt);
    });

    // Log install status
    window.addEventListener('appinstalled', () => {
        console.log('PWA installed successfully');
        showToast('App installed successfully!', 'success');
    });
}

/**
 * Show install button
 */
function showInstallButton(deferredPrompt) {
    const installButton = document.getElementById('install-button');
    if (installButton) {
        installButton.style.display = 'block';
        installButton.addEventListener('click', async () => {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`Install prompt outcome: ${outcome}`);
            deferredPrompt = null;
            installButton.style.display = 'none';
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

// Export utilities
window.App = {
    isAuthenticated,
    getCurrentUser,
    saveUserSession,
    clearUserSession,
    requireAuth,
    apiRequest,
    showToast,
    showLoading,
    hideLoading,
    formatCurrency,
    formatDate,
    formatRelativeTime,
    debounce
};
