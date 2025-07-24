// Advanced service worker to block source map requests
self.addEventListener('install', function(event) {
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  event.waitUntil(self.clients.claim());
});

// Create empty source map response
const emptySourceMap = JSON.stringify({
  version: 3,
  sources: [],
  names: [],
  mappings: ''
});

self.addEventListener('fetch', function(event) {
  const url = event.request.url;
  
  // Block source map requests
  if (url.includes('.map') || 
      url.includes('bootstrap.min.css.map') || 
      url.includes('bootstrap.bundle.min.js.map') ||
      url.includes('f14685ec89fb3572e3f00624425a45d857caae8b82bfcf9039bd3d781c53a477')) {
    
    // Return empty source map
    event.respondWith(new Response(emptySourceMap, {
      status: 200,
      headers: {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      }
    }));
    return;
  }
  
  // Let other requests pass through
  event.respondWith(fetch(event.request));
});