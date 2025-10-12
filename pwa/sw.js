const CACHE_NAME = 'slide-mobile-v2';
const API_CACHE_NAME = 'slide-api-cache-v2';
const API_CACHE_TIMEOUT = 5 * 60 * 1000; // 5 minutes in milliseconds

const ASSETS_TO_CACHE = [
    '/mobile/',
    '/mobile/index.php',
    '/mobile/devices.php',
    '/mobile/agents.php',
    '/mobile/snapshots.php',
    '/mobile/restores.php',
    '/mobile/alerts.php',
    '/mobile/more.php',
    '/mobile/accounts.php',
    '/mobile/users.php',
    '/mobile/clients.php',
    '/mobile/audits.php',
    '/mobile/backups.php',
    '/mobile/css/style.css',
    '/mobile/css/bootstrap.min.css',
    '/mobile/css/bootstrap-icons.css',
    '/mobile/js/app.js',
    '/mobile/js/bootstrap.bundle.min.js',
    '/mobile/fonts/bootstrap-icons/bootstrap-icons.woff2',
    '/mobile/fonts/bootstrap-icons/bootstrap-icons.woff',
    '/mobile/pwa/manifest.json',
    '/fonts/dattoDin/dattoDin.css'
];

// Critical API endpoints to cache for offline access
const CRITICAL_API_ACTIONS = [
    'getDevices',
    'getAgents',
    'getAlerts'
];

// Install event - cache assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME).then((cache) => {
                return cache.addAll(ASSETS_TO_CACHE);
            }),
            caches.open(API_CACHE_NAME)
        ])
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && cacheName !== API_CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Check if cached response is still fresh
function isCacheFresh(cachedResponse) {
    const cachedDate = cachedResponse.headers.get('sw-cached-date');
    if (!cachedDate) return false;
    
    const cacheAge = Date.now() - parseInt(cachedDate);
    return cacheAge < API_CACHE_TIMEOUT;
}

// Add timestamp header to cached response
async function addTimestampToResponse(response) {
    const blob = await response.blob();
    const headers = new Headers(response.headers);
    headers.append('sw-cached-date', Date.now().toString());
    
    return new Response(blob, {
        status: response.status,
        statusText: response.statusText,
        headers: headers
    });
}

// Network-first strategy for API requests
async function networkFirstStrategy(request) {
    const cache = await caches.open(API_CACHE_NAME);
    
    try {
        // Try network first
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            // Cache the response with timestamp
            const responseToCache = await addTimestampToResponse(networkResponse.clone());
            cache.put(request, responseToCache);
            return networkResponse;
        }
        
        return networkResponse;
    } catch (error) {
        // Network failed, try cache
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            // Return cached response even if stale (better than nothing offline)
            return cachedResponse;
        }
        
        // No cache available, return error
        return new Response(JSON.stringify({
            success: false,
            message: 'You are offline and no cached data is available',
            offline: true
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Cache-first strategy for static assets
async function cacheFirstStrategy(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Fetch event - route requests to appropriate strategy
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Skip non-GET requests for caching
    if (event.request.method !== 'GET') {
        return;
    }
    
    // API requests - use network-first strategy
    if (url.pathname.includes('mobileSlideApi.php')) {
        event.respondWith(networkFirstStrategy(event.request));
        return;
    }
    
    // Static assets - use cache-first strategy
    event.respondWith(cacheFirstStrategy(event.request));
});

// Listen for messages from the app
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
}); 