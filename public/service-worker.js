/**
 * Ultra Performance Service Worker
 * Golden Standard Implementation for Offline Capabilities & Performance
 * 
 * Features:
 * - Intelligent caching strategies
 * - Network-first for dynamic content
 * - Cache-first for static assets
 * - Stale-while-revalidate for API calls
 * - Background sync
 * - Push notifications
 * - Performance optimization
 */

const CACHE_NAME = 'medcapsule-v1.0.0';
const DYNAMIC_CACHE_NAME = 'medcapsule-dynamic-v1.0.0';
const API_CACHE_NAME = 'medcapsule-api-v1.0.0';

// Critical resources to cache immediately
const CRITICAL_RESOURCES = [
    '/',
    '/courses',
    '/review',
    '/offline',
    '/css/critical.css',
    '/js/core/ultra-performance-optimized.js',
    '/manifest.json',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'
];

// API endpoints to cache
const API_ENDPOINTS = [
    '/api/courses/optimized',
    '/api/user/progress',
    '/api/notifications',
    '/api/quiz/preload'
];

// Cache strategies
const CACHE_STRATEGIES = {
    NETWORK_FIRST: 'network-first',
    CACHE_FIRST: 'cache-first',
    STALE_WHILE_REVALIDATE: 'stale-while-revalidate',
    NETWORK_ONLY: 'network-only',
    CACHE_ONLY: 'cache-only'
};

// Route patterns and their strategies
const ROUTE_STRATEGIES = [
    { pattern: /^\/api\//, strategy: CACHE_STRATEGIES.STALE_WHILE_REVALIDATE },
    { pattern: /\.(?:css|js|woff|woff2|ttf|otf)$/, strategy: CACHE_STRATEGIES.CACHE_FIRST },
    { pattern: /\.(?:png|jpg|jpeg|svg|gif|webp|avif)$/, strategy: CACHE_STRATEGIES.CACHE_FIRST },
    { pattern: /^\/courses/, strategy: CACHE_STRATEGIES.NETWORK_FIRST },
    { pattern: /^\/quiz/, strategy: CACHE_STRATEGIES.NETWORK_FIRST },
    { pattern: /^\/review/, strategy: CACHE_STRATEGIES.NETWORK_FIRST }
];

/**
 * Service Worker Installation
 */
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching critical resources...');
                return cache.addAll(CRITICAL_RESOURCES);
            })
            .then(() => {
                console.log('Critical resources cached successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Failed to cache critical resources:', error);
            })
    );
});

/**
 * Service Worker Activation
 */
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        Promise.all([
            // Clean up old caches
            cleanupOldCaches(),
            
            // Claim all clients
            self.clients.claim(),
            
            // Preload API data
            preloadAPIData()
        ])
    );
});

/**
 * Fetch Event Handler
 */
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip cross-origin requests (except fonts and CDN)
    if (url.origin !== location.origin && !isAllowedOrigin(url.origin)) {
        return;
    }
    
    // Determine cache strategy
    const strategy = determineStrategy(request);
    
    event.respondWith(
        handleRequest(request, strategy)
            .catch(error => {
                console.error('Fetch failed:', error);
                return handleFallback(request);
            })
    );
});

/**
 * Background Sync
 */
self.addEventListener('sync', event => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'background-sync') {
        event.waitUntil(performBackgroundSync());
    }
});

/**
 * Push Notification Handler
 */
self.addEventListener('push', event => {
    console.log('Push notification received:', event);
    
    if (event.data) {
        const data = event.data.json();
        event.waitUntil(
            self.registration.showNotification(data.title, {
                body: data.body,
                icon: data.icon || '/icons/icon-192.png',
                badge: '/icons/badge-72.png',
                tag: data.tag || 'default',
                data: data.data,
                actions: data.actions,
                requireInteraction: data.requireInteraction || false
            })
        );
    }
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    const url = event.notification.data?.url || '/';
    
    event.waitUntil(
        clients.openWindow(url)
    );
});

/**
 * Cache Strategies Implementation
 */
async function handleRequest(request, strategy) {
    switch (strategy) {
        case CACHE_STRATEGIES.NETWORK_FIRST:
            return networkFirst(request);
        
        case CACHE_STRATEGIES.CACHE_FIRST:
            return cacheFirst(request);
        
        case CACHE_STRATEGIES.STALE_WHILE_REVALIDATE:
            return staleWhileRevalidate(request);
        
        case CACHE_STRATEGIES.NETWORK_ONLY:
            return networkOnly(request);
        
        case CACHE_STRATEGIES.CACHE_ONLY:
            return cacheOnly(request);
        
        default:
            return networkFirst(request);
    }
}

/**
 * Network First Strategy
 */
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        console.log('Network failed, trying cache:', error);
        return caches.match(request);
    }
}

/**
 * Cache First Strategy
 */
async function cacheFirst(request) {
    const cached = await caches.match(request);
    
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        console.log('Cache and network failed:', error);
        return handleFallback(request);
    }
}

/**
 * Stale While Revalidate Strategy
 */
async function staleWhileRevalidate(request) {
    const cached = await caches.match(request);
    
    // Start network request in background
    const networkPromise = fetch(request)
        .then(response => {
            if (response.ok) {
                const cache = caches.open(API_CACHE_NAME);
                cache.then(c => c.put(request, response.clone()));
            }
            return response;
        })
        .catch(error => {
            console.log('Network request failed:', error);
            return cached;
        });
    
    // Return cached version immediately if available
    if (cached) {
        return cached;
    }
    
    // Return network response if no cache
    return networkPromise;
}

/**
 * Network Only Strategy
 */
async function networkOnly(request) {
    return fetch(request);
}

/**
 * Cache Only Strategy
 */
async function cacheOnly(request) {
    return caches.match(request);
}

/**
 * Determine Cache Strategy
 */
function determineStrategy(request) {
    const url = new URL(request.url);
    
    for (const route of ROUTE_STRATEGIES) {
        if (route.pattern.test(url.pathname)) {
            return route.strategy;
        }
    }
    
    return CACHE_STRATEGIES.NETWORK_FIRST;
}

/**
 * Handle Fallback Responses
 */
async function handleFallback(request) {
    const url = new URL(request.url);
    
    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
        return caches.match('/offline') || new Response('Offline', {
            status: 200,
            headers: { 'Content-Type': 'text/html' }
        });
    }
    
    // Return empty response for images
    if (request.destination === 'image') {
        return new Response('', {
            status: 200,
            headers: { 'Content-Type': 'image/svg+xml' }
        });
    }
    
    // Return empty response for other requests
    return new Response('', {
        status: 200,
        headers: { 'Content-Type': 'text/plain' }
    });
}

/**
 * Clean Up Old Caches
 */
async function cleanupOldCaches() {
    const cacheNames = await caches.keys();
    const validCacheNames = [CACHE_NAME, DYNAMIC_CACHE_NAME, API_CACHE_NAME];
    
    return Promise.all(
        cacheNames
            .filter(name => !validCacheNames.includes(name))
            .map(name => {
                console.log('Deleting old cache:', name);
                return caches.delete(name);
            })
    );
}

/**
 * Preload API Data
 */
async function preloadAPIData() {
    const cache = await caches.open(API_CACHE_NAME);
    
    return Promise.all(
        API_ENDPOINTS.map(endpoint => {
            return fetch(endpoint)
                .then(response => {
                    if (response.ok) {
                        return cache.put(endpoint, response.clone());
                    }
                })
                .catch(error => {
                    console.log('Failed to preload API data:', endpoint, error);
                });
        })
    );
}

/**
 * Background Sync Handler
 */
async function performBackgroundSync() {
    console.log('Performing background sync...');
    
    try {
        // Sync offline data
        await syncOfflineData();
        
        // Update cached resources
        await updateCachedResources();
        
        // Clean up old data
        await cleanupOldData();
        
        console.log('Background sync completed successfully');
    } catch (error) {
        console.error('Background sync failed:', error);
        throw error;
    }
}

/**
 * Sync Offline Data
 */
async function syncOfflineData() {
    // Implementation for syncing offline data
    console.log('Syncing offline data...');
}

/**
 * Update Cached Resources
 */
async function updateCachedResources() {
    console.log('Updating cached resources...');
    
    const cache = await caches.open(CACHE_NAME);
    
    return Promise.all(
        CRITICAL_RESOURCES.map(resource => {
            return fetch(resource)
                .then(response => {
                    if (response.ok) {
                        return cache.put(resource, response.clone());
                    }
                })
                .catch(error => {
                    console.log('Failed to update cached resource:', resource, error);
                });
        })
    );
}

/**
 * Clean Up Old Data
 */
async function cleanupOldData() {
    console.log('Cleaning up old data...');
    
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    const keys = await cache.keys();
    
    // Remove old entries (older than 1 week)
    const oneWeekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
    
    return Promise.all(
        keys.map(async request => {
            const response = await cache.match(request);
            const dateHeader = response.headers.get('date');
            
            if (dateHeader) {
                const responseDate = new Date(dateHeader).getTime();
                if (responseDate < oneWeekAgo) {
                    console.log('Removing old cache entry:', request.url);
                    return cache.delete(request);
                }
            }
        })
    );
}

/**
 * Check if Origin is Allowed
 */
function isAllowedOrigin(origin) {
    const allowedOrigins = [
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com'
    ];
    
    return allowedOrigins.includes(origin);
}

/**
 * Performance Optimization: Preload on Idle
 */
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'PRELOAD_RESOURCES') {
        event.waitUntil(
            preloadResources(event.data.resources)
        );
    }
});

/**
 * Preload Resources on Idle
 */
async function preloadResources(resources) {
    if (!resources || !Array.isArray(resources)) {
        return;
    }
    
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    
    return Promise.all(
        resources.map(resource => {
            return fetch(resource)
                .then(response => {
                    if (response.ok) {
                        return cache.put(resource, response.clone());
                    }
                })
                .catch(error => {
                    console.log('Failed to preload resource:', resource, error);
                });
        })
    );
}

console.log('Service Worker loaded successfully');