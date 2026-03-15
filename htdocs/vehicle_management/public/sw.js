/**
 * Service Worker — Vehicle Management System
 * Network-first strategy with safe caching that handles Vary: * responses.
 */

const CACHE_NAME = 'vm-cache-v1';
const STATIC_ASSETS = [
    './css/theme.css',
    './js/app.js',
    './logo/shjmunlogo.png'
];

/* Install: pre-cache static assets */
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(STATIC_ASSETS).catch(function() {
                // Ignore pre-cache failures (e.g., files not found)
            });
        })
    );
    self.skipWaiting();
});

/* Activate: clean old caches */
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(names) {
            return Promise.all(
                names.filter(function(n) { return n !== CACHE_NAME; })
                     .map(function(n) { return caches.delete(n); })
            );
        })
    );
    self.clients.claim();
});

/* Fetch: network-first, skip caching responses with Vary: * */
self.addEventListener('fetch', function(event) {
    var request = event.request;

    // Only handle GET requests
    if (request.method !== 'GET') return;

    // Skip API calls and PHP pages (dynamic content)
    var url = new URL(request.url);
    if (url.pathname.match(/\.(php|json)$/) || url.pathname.indexOf('/api/') !== -1) {
        return;
    }

    event.respondWith(
        fetch(request).then(function(response) {
            // Don't cache responses with Vary: * (breaks Cache API)
            var vary = response.headers.get('Vary');
            if (vary && vary.indexOf('*') !== -1) {
                return response;
            }

            // Don't cache error responses
            if (!response.ok) {
                return response;
            }

            // Cache a clone of the response
            var clone = response.clone();
            caches.open(CACHE_NAME).then(function(cache) {
                cache.put(request, clone).catch(function() {
                    // Silently ignore cache put failures
                });
            });
            return response;
        }).catch(function() {
            // Network failed — try cache
            return caches.match(request).then(function(cached) {
                return cached || new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
            });
        })
    );
});
