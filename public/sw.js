// MedCapsule Service Worker - Golden Standard Implementation
// Version 1.0 - Optimized for AAAA performance

const CACHE_NAME = 'medcapsule-v1.0';
const STATIC_CACHE = 'medcapsule-static-v1.0';
const DYNAMIC_CACHE = 'medcapsule-dynamic-v1.0';
const API_CACHE = 'medcapsule-api-v1.0';

// PERFORMANCE: Critical resources to cache immediately
const CRITICAL_RESOURCES = [
    '/',
    '/css/home-optimized.css',
    '/js/app.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap',
    '/offline.html'
];

// PERFORMANCE: Routes to cache with different strategies
const CACHE_STRATEGIES = {
    // Static assets - Cache First
    static: [
        /\.(?:js|css|woff2?|ttf|eot|ico|png|jpg|jpeg|gif|svg)$/,
        /^https:\/\/fonts\.googleapis\.com/,
        /^https:\/\/fonts\.gstatic\.com/,
        /^https:\/\/cdn\.jsdelivr\.net/,
        /^https:\/\/cdnjs\.cloudflare\.com/
    ],
    
    // API calls - Network First with fallback
    api: [
        /^\/api\//,
        /^\/notifications/,
        /^\/courses/,
        /^\/quiz/
    ],
    
    // Pages - Stale While Revalidate
    pages: [
        /^\/$/,
        /^\/courses/,
        /^\/profile/,
        /^\/login/,
        /^\/register/
    ]
};

// PERFORMANCE: Install event - Cache critical resources
self.addEventListener('install', event => {
    console.log('SW: Installing Service Worker v1.0');
    
    event.waitUntil(
        Promise.all([
            // Cache critical resources
            caches.open(STATIC_CACHE).then(cache => {
                console.log('SW: Caching critical resources');
                return cache.addAll(CRITICAL_RESOURCES);
            }),
            
            // Skip waiting to activate immediately
            self.skipWaiting()
        ])
    );
});

// PERFORMANCE: Activate event - Clean old caches
self.addEventListener('activate', event => {
    console.log('SW: Activating Service Worker v1.0');
    
    event.waitUntil(
        Promise.all([
            // Clean old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE && 
                            cacheName !== DYNAMIC_CACHE && 
                            cacheName !== API_CACHE) {
                            console.log('SW: Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            
            // Take control of all clients
            self.clients.claim()
        ])
    );
});

// PERFORMANCE: Fetch event - Implement caching strategies
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip Chrome extension requests
    if (url.protocol === 'chrome-extension:') {
        return;
    }
    
    // Determine caching strategy
    const strategy = getCachingStrategy(request);
    
    switch (strategy) {
        case 'static':
            event.respondWith(cacheFirst(request));
            break;
        case 'api':
            event.respondWith(networkFirst(request));
            break;
        case 'pages':
            event.respondWith(staleWhileRevalidate(request));
            break;
        default:
            event.respondWith(networkFirst(request));
    }
});

// PERFORMANCE: Cache First Strategy - For static assets
async function cacheFirst(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.status === 200) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('SW: Cache first failed:', error);
        
        // Return offline fallback for pages
        if (request.destination === 'document') {
            return caches.match('/offline.html');
        }
        
        throw error;
    }
}

// PERFORMANCE: Network First Strategy - For API calls
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        // Cache successful API responses
        if (networkResponse.status === 200 && isApiRequest(request)) {
            const cache = await caches.open(API_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('SW: Network first failed, trying cache:', error);
        
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline fallback for pages
        if (request.destination === 'document') {
            return caches.match('/offline.html');
        }
        
        throw error;
    }
}

// PERFORMANCE: Stale While Revalidate Strategy - For pages
async function staleWhileRevalidate(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    // Fetch fresh version in background
    const fetchPromise = fetch(request).then(networkResponse => {
        if (networkResponse.status === 200) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch(error => {
        console.log('SW: Background fetch failed:', error);
    });
    
    // Return cached version immediately if available
    if (cachedResponse) {
        // Don't await the fetch promise to return cached response immediately
        fetchPromise;
        return cachedResponse;
    }
    
    // If no cached version, wait for network
    try {
        return await fetchPromise;
    } catch (error) {
        console.log('SW: Stale while revalidate failed:', error);
        return caches.match('/offline.html');
    }
}

// PERFORMANCE: Determine caching strategy based on request
function getCachingStrategy(request) {
    const url = request.url;
    
    // Check static assets
    for (const pattern of CACHE_STRATEGIES.static) {
        if (pattern.test(url)) {
            return 'static';
        }
    }
    
    // Check API calls
    for (const pattern of CACHE_STRATEGIES.api) {
        if (pattern.test(url)) {
            return 'api';
        }
    }
    
    // Check pages
    for (const pattern of CACHE_STRATEGIES.pages) {
        if (pattern.test(url)) {
            return 'pages';
        }
    }
    
    return 'default';
}

// PERFORMANCE: Check if request is an API call
function isApiRequest(request) {
    return request.url.includes('/api/') || 
           request.url.includes('/notifications') ||
           request.headers.get('X-Requested-With') === 'XMLHttpRequest';
}

// PERFORMANCE: Background sync for offline actions
self.addEventListener('sync', event => {
    console.log('SW: Background sync:', event.tag);
    
    switch (event.tag) {
        case 'quiz-submission':
            event.waitUntil(syncQuizSubmissions());
            break;
        case 'progress-update':
            event.waitUntil(syncProgressUpdates());
            break;
        case 'notification-read':
            event.waitUntil(syncNotificationReads());
            break;
    }
});

// PERFORMANCE: Sync quiz submissions when back online
async function syncQuizSubmissions() {
    try {
        const submissions = await getStoredSubmissions();
        
        for (const submission of submissions) {
            try {
                const response = await fetch(submission.url, {
                    method: 'POST',
                    headers: submission.headers,
                    body: submission.data
                });
                
                if (response.ok) {
                    await removeStoredSubmission(submission.id);
                    console.log('SW: Quiz submission synced successfully');
                }
            } catch (error) {
                console.log('SW: Failed to sync quiz submission:', error);
            }
        }
    } catch (error) {
        console.log('SW: Background sync failed:', error);
    }
}

// PERFORMANCE: Sync progress updates
async function syncProgressUpdates() {
    try {
        const updates = await getStoredProgressUpdates();
        
        for (const update of updates) {
            try {
                const response = await fetch('/api/progress/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': update.csrfToken
                    },
                    body: JSON.stringify(update.data)
                });
                
                if (response.ok) {
                    await removeStoredProgressUpdate(update.id);
                    console.log('SW: Progress update synced successfully');
                }
            } catch (error) {
                console.log('SW: Failed to sync progress update:', error);
            }
        }
    } catch (error) {
        console.log('SW: Progress sync failed:', error);
    }
}

// PERFORMANCE: Sync notification reads
async function syncNotificationReads() {
    try {
        const reads = await getStoredNotificationReads();
        
        for (const read of reads) {
            try {
                const response = await fetch('/notifications/mark-as-read', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': read.csrfToken
                    },
                    body: JSON.stringify({ notification_ids: read.ids })
                });
                
                if (response.ok) {
                    await removeStoredNotificationRead(read.id);
                    console.log('SW: Notification read synced successfully');
                }
            } catch (error) {
                console.log('SW: Failed to sync notification read:', error);
            }
        }
    } catch (error) {
        console.log('SW: Notification sync failed:', error);
    }
}

// PERFORMANCE: IndexedDB helpers for offline storage
async function getStoredSubmissions() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MedCapsuleOffline', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['submissions'], 'readonly');
            const store = transaction.objectStore('submissions');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('submissions')) {
                db.createObjectStore('submissions', { keyPath: 'id', autoIncrement: true });
            }
            if (!db.objectStoreNames.contains('progress')) {
                db.createObjectStore('progress', { keyPath: 'id', autoIncrement: true });
            }
            if (!db.objectStoreNames.contains('notifications')) {
                db.createObjectStore('notifications', { keyPath: 'id', autoIncrement: true });
            }
        };
    });
}

async function removeStoredSubmission(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MedCapsuleOffline', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['submissions'], 'readwrite');
            const store = transaction.objectStore('submissions');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

async function getStoredProgressUpdates() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MedCapsuleOffline', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['progress'], 'readonly');
            const store = transaction.objectStore('progress');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
    });
}

async function removeStoredProgressUpdate(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MedCapsuleOffline', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['progress'], 'readwrite');
            const store = transaction.objectStore('progress');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

async function getStoredNotificationReads() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MedCapsuleOffline', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['notifications'], 'readonly');
            const store = transaction.objectStore('notifications');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
    });
}

async function removeStoredNotificationRead(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('MedCapsuleOffline', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['notifications'], 'readwrite');
            const store = transaction.objectStore('notifications');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

// PERFORMANCE: Push notification handling
self.addEventListener('push', event => {
    console.log('SW: Push notification received');
    
    if (!event.data) {
        return;
    }
    
    try {
        const data = event.data.json();
        
        const options = {
            body: data.body,
            icon: '/icon-192x192.png',
            badge: '/badge-72x72.png',
            tag: data.tag || 'medcapsule-notification',
            requireInteraction: data.requireInteraction || false,
            actions: data.actions || [],
            data: data.data || {}
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    } catch (error) {
        console.log('SW: Failed to show notification:', error);
    }
});

// PERFORMANCE: Notification click handling
self.addEventListener('notificationclick', event => {
    console.log('SW: Notification clicked');
    
    event.notification.close();
    
    const data = event.notification.data;
    const action = event.action;
    
    let url = '/';
    
    if (action) {
        switch (action) {
            case 'view':
                url = data.url || '/';
                break;
            case 'dismiss':
                return;
        }
    } else if (data.url) {
        url = data.url;
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            // Check if there's already a window open
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            
            // Open new window
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// PERFORMANCE: Message handling for communication with main thread
self.addEventListener('message', event => {
    console.log('SW: Message received:', event.data);
    
    switch (event.data.type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
        case 'GET_VERSION':
            event.ports[0].postMessage({ version: CACHE_NAME });
            break;
        case 'CLEAR_CACHE':
            clearAllCaches().then(() => {
                event.ports[0].postMessage({ success: true });
            });
            break;
    }
});

// PERFORMANCE: Clear all caches
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    return Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
}

console.log('SW: Service Worker v1.0 loaded successfully');