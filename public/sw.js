// LIGHTNING FAST SERVICE WORKER FOR IMAGE CACHING
const CACHE_NAME = 'medcapsule-images-v1';
const IMAGE_CACHE_NAME = 'medcapsule-images-cache';

// Install event - cache critical resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            // Only cache files that we know exist
            const filesToCache = [
                '/css/app.css'
                // Removed /js/app.js as it doesn't exist
            ];
            
            return cache.addAll(filesToCache).catch(error => {
                console.warn('Service Worker: Some files failed to cache:', error);
                // Continue with installation even if some files fail
                return Promise.resolve();
            });
        })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName !== IMAGE_CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - aggressive image caching
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);
    
    // Cache images aggressively
    if (event.request.destination === 'image' || 
        url.pathname.match(/\.(jpg|jpeg|png|gif|webp|svg)$/i) ||
        url.hostname.includes('unsplash.com') ||
        url.hostname.includes('picsum.photos') ||
        url.hostname.includes('placeholder.com')) {
        
        event.respondWith(
            caches.open(IMAGE_CACHE_NAME).then(cache => {
                return cache.match(event.request).then(response => {
                    if (response) {
                        return response;
                    }
                    
                    return fetch(event.request).then(fetchResponse => {
                        // Cache successful responses
                        if (fetchResponse.ok) {
                            cache.put(event.request, fetchResponse.clone());
                        }
                        return fetchResponse;
                    }).catch(() => {
                        // Return a fallback image if network fails
                        return new Response(
                            '<svg xmlns="http://www.w3.org/2000/svg" width="420" height="580" viewBox="0 0 420 580"><rect width="420" height="580" fill="#f8f9fa"/><text x="210" y="290" text-anchor="middle" font-family="Arial" font-size="24" fill="#6c757d">📚</text></svg>',
                            { headers: { 'Content-Type': 'image/svg+xml' } }
                        );
                    });
                });
            })
        );
    }
});