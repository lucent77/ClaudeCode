/**
 * Web Push Notification Handler
 * Manages subscription and permission requests
 */

// VAPID public key (update this with your actual key from config.php)
const VAPID_PUBLIC_KEY = 'YOUR_VAPID_PUBLIC_KEY';

/**
 * URL Base64 to Uint8Array converter (for VAPID key)
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

/**
 * Check if push notifications are supported
 */
function isPushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window;
}

/**
 * Get current push subscription
 */
async function getCurrentSubscription() {
    try {
        const registration = await navigator.serviceWorker.ready;
        return await registration.pushManager.getSubscription();
    } catch (error) {
        console.error('Error getting subscription:', error);
        return null;
    }
}

/**
 * Request notification permission
 */
async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.warn('Notifications not supported');
        return false;
    }

    if (Notification.permission === 'granted') {
        return true;
    }

    if (Notification.permission === 'denied') {
        console.warn('Notification permission denied');
        return false;
    }

    const permission = await Notification.requestPermission();
    return permission === 'granted';
}

/**
 * Subscribe to push notifications
 */
async function subscribeToPush() {
    try {
        // Check support
        if (!isPushSupported()) {
            throw new Error('Push notifications not supported');
        }

        // Request permission
        const hasPermission = await requestNotificationPermission();
        if (!hasPermission) {
            throw new Error('Notification permission denied');
        }

        // Get service worker registration
        const registration = await navigator.serviceWorker.ready;

        // Check if already subscribed
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            // Create new subscription
            const applicationServerKey = urlBase64ToUint8Array(VAPID_PUBLIC_KEY);

            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: applicationServerKey
            });

            console.log('Push subscription created:', subscription);
        } else {
            console.log('Already subscribed to push');
        }

        // Send subscription to server
        await saveSubscriptionToServer(subscription);

        return subscription;

    } catch (error) {
        console.error('Failed to subscribe to push:', error);
        throw error;
    }
}

/**
 * Unsubscribe from push notifications
 */
async function unsubscribeFromPush() {
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await subscription.unsubscribe();
            console.log('Unsubscribed from push');

            // Optionally notify server to remove subscription
            // await removeSubscriptionFromServer(subscription);
        }

        return true;
    } catch (error) {
        console.error('Failed to unsubscribe:', error);
        return false;
    }
}

/**
 * Save subscription to server
 */
async function saveSubscriptionToServer(subscription) {
    try {
        const response = await fetch('/api/push_subscribe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                subscription: subscription.toJSON()
            })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Failed to save subscription');
        }

        console.log('Subscription saved to server');
        return true;

    } catch (error) {
        console.error('Failed to save subscription to server:', error);
        throw error;
    }
}

/**
 * Show test notification
 */
async function showTestNotification() {
    try {
        const hasPermission = await requestNotificationPermission();
        if (!hasPermission) {
            throw new Error('Notification permission not granted');
        }

        const registration = await navigator.serviceWorker.ready;

        await registration.showNotification('Price Tracker', {
            body: 'Push notifications are working! 🎉',
            icon: '/assets/icons/icon-192.png',
            badge: '/assets/icons/icon-192.png',
            vibrate: [200, 100, 200],
            data: {
                url: '/pages/dashboard.html'
            }
        });

        console.log('Test notification shown');
        return true;

    } catch (error) {
        console.error('Failed to show test notification:', error);
        return false;
    }
}

/**
 * Initialize push notifications
 * Call this after user logs in
 */
async function initPushNotifications() {
    try {
        if (!isPushSupported()) {
            console.warn('Push notifications not supported');
            return false;
        }

        // Wait for service worker to be ready
        await navigator.serviceWorker.ready;

        // Check current status
        const subscription = await getCurrentSubscription();

        if (subscription) {
            console.log('Already subscribed to push notifications');
            return true;
        }

        // Don't auto-subscribe, wait for user action
        console.log('Push notifications available - waiting for user to enable');
        return false;

    } catch (error) {
        console.error('Failed to initialize push notifications:', error);
        return false;
    }
}

// Export functions for use in other scripts
window.PushNotifications = {
    isSupported: isPushSupported,
    requestPermission: requestNotificationPermission,
    subscribe: subscribeToPush,
    unsubscribe: unsubscribeFromPush,
    showTest: showTestNotification,
    init: initPushNotifications,
    getCurrentSubscription: getCurrentSubscription
};
