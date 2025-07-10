// 🚀 MEDCAPSULE PWA SERVICE WORKER
// Version 1.0 - Ultimate Medical Education Experience

const CACHE_NAME = 'medcapsule-v1.0';
const OFFLINE_PAGE = '/offline.html';

// CRITICAL RESOURCES TO CACHE
const CRITICAL_RESOURCES = [
    '/',
    '/offline.html',
    '/css/app.css',
    '/js/app.js',
    '/favicon.ico',
    '/images/logo.png',
    '/courses',
    '/profile'
];

// DYNAMIC CACHE PATTERNS
const CACHE_STRATEGIES = {
    // Cache First: Static assets
    CACHE_FIRST: [
        /\.(?:js|css|woff2?|png|jpg|jpeg|gif|svg|ico)$/,
        /\/images\//,
        /\/fonts\//
    ],
    
    // Network First: API calls
    NETWORK_FIRST: [
        /\/api\//,
        /\/quiz\//,
        /\/notifications/,
        /\/payments/
    ],
    
    // Stale While Revalidate: Pages
    STALE_WHILE_REVALIDATE: [
        /\/courses/,
        /\/topics/,
        /\/profile/,
        /\/review/
    ]
};

// INSTALL EVENT - Cache Critical Resources
self.addEventListener('install', event => {
    console.log('🚀 MedCapsule SW: Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('📦 Caching critical resources');
                return cache.addAll(CRITICAL_RESOURCES);
            })
            .then(() => {
                console.log('✅ Critical resources cached');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('❌ Cache installation failed:', error);
            })
    );
});

// ACTIVATE EVENT - Clean Old Caches
self.addEventListener('activate', event => {
    console.log('🔄 MedCapsule SW: Activating...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('🗑️ Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('✅ Old caches cleaned');
                return self.clients.claim();
            })
    );
});

// FETCH EVENT - Intelligent Caching Strategy
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') return;
    
    // Skip Chrome extensions and non-HTTP requests
    if (!url.protocol.startsWith('http')) return;
    
    event.respondWith(handleRequest(request));
});

// INTELLIGENT REQUEST HANDLING
async function handleRequest(request) {
    const url = new URL(request.url);
    const pathname = url.pathname;
    
    try {
        // QUIZ DATA SPECIAL HANDLING
        if (pathname.includes('/quiz/')) {
            return await handleQuizRequest(request);
        }
        
        // API REQUESTS - Network First
        if (matchesPatterns(pathname, CACHE_STRATEGIES.NETWORK_FIRST)) {
            return await networkFirst(request);
        }
        
        // STATIC ASSETS - Cache First
        if (matchesPatterns(pathname, CACHE_STRATEGIES.CACHE_FIRST)) {
            return await cacheFirst(request);
        }
        
        // PAGES - Stale While Revalidate
        if (matchesPatterns(pathname, CACHE_STRATEGIES.STALE_WHILE_REVALIDATE)) {
            return await staleWhileRevalidate(request);
        }
        
        // DEFAULT - Network First with Offline Fallback
        return await networkFirstWithOfflineFallback(request);
        
    } catch (error) {
        console.error('🚨 Request handling failed:', error);
        return await getOfflinePage();
    }
}

// QUIZ-SPECIFIC HANDLING
async function handleQuizRequest(request) {
    try {
        // Always try network first for quiz data
        const response = await fetch(request);
        
        if (response.ok) {
            // Cache successful quiz responses temporarily
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request, response.clone());
            return response;
        }
        
        throw new Error('Network response not ok');
        
    } catch (error) {
        // Try to serve from cache if available
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            console.log('📱 Serving quiz from cache (offline mode)');
            return cachedResponse;
        }
        
        // Show offline quiz page
        return await getOfflineQuizPage();
    }
}

// CACHE FIRST STRATEGY
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.log('📱 Network failed, serving offline fallback');
        return await getOfflinePage();
    }
}

// NETWORK FIRST STRATEGY
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            // Cache successful API responses for 5 minutes
            const cache = await caches.open(CACHE_NAME);
            const clonedResponse = response.clone();
            
            // Add timestamp for cache expiry
            const headers = new Headers(clonedResponse.headers);
            headers.set('sw-cached-at', Date.now().toString());
            
            const modifiedResponse = new Response(clonedResponse.body, {
                status: clonedResponse.status,
                statusText: clonedResponse.statusText,
                headers: headers
            });
            
            await cache.put(request, modifiedResponse);
        }
        
        return response;
        
    } catch (error) {
        // Try cache as fallback
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Check if cache is fresh (5 minutes)
            const cachedAt = cachedResponse.headers.get('sw-cached-at');
            if (cachedAt && (Date.now() - parseInt(cachedAt)) < 300000) {
                console.log('📱 Serving API from cache');
                return cachedResponse;
            }
        }
        
        throw error;
    }
}

// STALE WHILE REVALIDATE STRATEGY
async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    // Fetch in background to update cache
    const fetchPromise = fetch(request).then(response => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => {
        console.log('📱 Background fetch failed');
    });
    
    // Return cached version immediately if available
    if (cachedResponse) {
        return cachedResponse;
    }
    
    // Wait for network if no cache
    return await fetchPromise;
}

// NETWORK FIRST WITH OFFLINE FALLBACK
async function networkFirstWithOfflineFallback(request) {
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request, response.clone());
        }
        
        return response;
        
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return await getOfflinePage();
    }
}

// OFFLINE PAGE HANDLER
async function getOfflinePage() {
    const cache = await caches.open(CACHE_NAME);
    const offlinePage = await cache.match(OFFLINE_PAGE);
    
    if (offlinePage) {
        return offlinePage;
    }
    
    // Fallback offline response
    return new Response(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>MedCapsule - Offline</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-align: center;
                }
                .offline-container {
                    max-width: 400px;
                    padding: 2rem;
                }
                .offline-icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                }
                h1 { margin: 0 0 1rem 0; }
                p { margin: 0 0 2rem 0; opacity: 0.9; }
                .retry-btn {
                    background: rgba(255,255,255,0.2);
                    border: 2px solid rgba(255,255,255,0.3);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    transition: all 0.3s ease;
                }
                .retry-btn:hover {
                    background: rgba(255,255,255,0.3);
                }
            </style>
        </head>
        <body>
            <div class="offline-container">
                <div class="offline-icon">📱</div>
                <h1>You're Offline</h1>
                <p>Don't worry! Your medical education continues even offline. Some features may be limited.</p>
                <button class="retry-btn" onclick="window.location.reload()">Try Again</button>
            </div>
        </body>
        </html>
    `, {
        status: 200,
        headers: { 'Content-Type': 'text/html' }
    });
}

// OFFLINE QUIZ PAGE
async function getOfflineQuizPage() {
    return new Response(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Quiz - Offline Mode</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    margin: 0;
                    padding: 2rem;
                    background: #f8f9fa;
                }
                .quiz-offline {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .offline-icon { font-size: 3rem; margin-bottom: 1rem; }
                .btn { 
                    background: #007bff; 
                    color: white; 
                    padding: 12px 24px; 
                    border: none; 
                    border-radius: 6px; 
                    margin: 0.5rem;
                    cursor: pointer;
                }
            </style>
        </head>
        <body>
            <div class="quiz-offline">
                <div class="offline-icon">🧠</div>
                <h2>Quiz Temporarily Unavailable</h2>
                <p>This quiz requires an internet connection. Please check your connection and try again.</p>
                <button class="btn" onclick="window.location.reload()">Retry</button>
                <button class="btn" onclick="window.history.back()">Go Back</button>
            </div>
        </body>
        </html>
    `, {
        status: 200,
        headers: { 'Content-Type': 'text/html' }
    });
}

// UTILITY: Pattern Matching
function matchesPatterns(pathname, patterns) {
    return patterns.some(pattern => pattern.test(pathname));
}

// BACKGROUND SYNC FOR QUIZ SUBMISSIONS
self.addEventListener('sync', event => {
    if (event.tag === 'quiz-submission') {
        event.waitUntil(handleQuizSubmissionSync());
    }
});

// Handle offline quiz submissions
async function handleQuizSubmissionSync() {
    console.log('🔄 Syncing offline quiz submissions...');
    
    try {
        // Get pending submissions from IndexedDB
        const submissions = await getPendingSubmissions();
        
        for (const submission of submissions) {
            try {
                const response = await fetch(submission.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': submission.token
                    },
                    body: JSON.stringify(submission.data)
                });
                
                if (response.ok) {
                    await removePendingSubmission(submission.id);
                    console.log('✅ Quiz submission synced successfully');
                } else {
                    console.log('❌ Quiz submission sync failed');
                }
            } catch (error) {
                console.log('❌ Sync attempt failed:', error);
            }
        }
    } catch (error) {
        console.error('🚨 Background sync failed:', error);
    }
}

// PUSH NOTIFICATIONS
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'New MedCapsule notification',
        icon: '/images/icon-192x192.png',
        badge: '/images/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View',
                icon: '/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/images/xmark.png'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification('MedCapsule', options)
    );
});

// NOTIFICATION CLICK HANDLER
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// MESSAGE HANDLER
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

console.log('🚀 MedCapsule Service Worker loaded successfully!');