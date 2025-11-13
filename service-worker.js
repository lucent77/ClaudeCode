/**
 * Service Worker for Price Tracker PWA
 * Handles caching, offline functionality, and push notifications
 */

const CACHE_NAME = 'price-tracker-v1';
const RUNTIME_CACHE = 'price-tracker-runtime-v1';

// Files to cache on install
const STATIC_CACHE_URLS = [
    '/',
    '/index.html',
    '/pages/login.html',
    '/pages/register.html',
    '/pages/dashboard.html',
    '/pages/add_product.html',
    '/pages/product_details.html',
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/js/push.js',
    '/assets/icons/icon-192.png',
    '/assets/icons/icon-512.png',
    '/manifest.json'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .then(() => {
                console.log('[SW] Static assets cached');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[SW] Cache installation failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => {
                            return name !== CACHE_NAME && name !== RUNTIME_CACHE;
                        })
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[SW] Service worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip chrome extensions
    if (url.protocol === 'chrome-extension:') {
        return;
    }

    // API requests - network first, cache fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Clone and cache the response
                    const responseClone = response.clone();
                    caches.open(RUNTIME_CACHE)
                        .then((cache) => {
                            cache.put(request, responseClone);
                        });
                    return response;
                })
                .catch(() => {
                    // Network failed, try cache
                    return caches.match(request);
                })
        );
        return;
    }

    // Static assets - cache first, network fallback
    event.respondWith(
        caches.match(request)
            .then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Not in cache, fetch from network
                return fetch(request)
                    .then((response) => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type === 'error') {
                            return response;
                        }

                        // Clone and cache
                        const responseClone = response.clone();
                        caches.open(RUNTIME_CACHE)
                            .then((cache) => {
                                cache.put(request, responseClone);
                            });

                        return response;
                    });
            })
            .catch(() => {
                // Return offline page if available
                if (request.destination === 'document') {
                    return caches.match('/index.html');
                }
            })
    );
});

// Push notification event
self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');

    let data = {
        title: 'Price Tracker',
        body: 'A product price has changed!',
        icon: '/assets/icons/icon-192.png',
        badge: '/assets/icons/icon-192.png',
        url: '/'
    };

    // Parse push data if available
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            console.error('[SW] Failed to parse push data:', e);
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/assets/icons/icon-192.png',
        badge: data.badge || '/assets/icons/icon-192.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/',
            timestamp: data.timestamp || Date.now()
        },
        actions: [
            {
                action: 'open',
                title: 'View Product'
            },
            {
                action: 'close',
                title: 'Dismiss'
            }
        ],
        requireInteraction: false,
        tag: 'price-alert',
        renotify: true
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event.action);

    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    // Open the app
    const urlToOpen = event.notification.data.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if app is already open
                for (let client of clientList) {
                    if (client.url === urlToOpen && 'focus' in client) {
                        return client.focus();
                    }
                }

                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Background sync event (for future use)
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'sync-products') {
        event.waitUntil(
            // Sync logic here
            Promise.resolve()
        );
    }
});

// Message event - communicate with main app
self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);

    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }

    if (event.data.action === 'clearCache') {
        event.waitUntil(
            caches.keys()
                .then((cacheNames) => {
                    return Promise.all(
                        cacheNames.map((name) => caches.delete(name))
                    );
                })
                .then(() => {
                    event.ports[0].postMessage({ success: true });
                })
        );
    }
});
