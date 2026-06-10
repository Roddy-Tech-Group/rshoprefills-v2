/**
 * RshopRefills service worker. Minimal + safe:
 *   - Runtime-caches static assets (network-first, cache fallback when offline).
 *   - Network-first for page navigations with an offline fallback to the cached
 *     dashboard shell.
 *   - Never caches POST/PUT, API responses, or Livewire updates, so authenticated
 *     and dynamic data always comes fresh from the network.
 *
 * Bump CACHE_VERSION to invalidate old caches on the next activate.
 */
const CACHE_VERSION = 'rshop-v3';
const OFFLINE_FALLBACK = '/dashboard';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k)));
            await self.clients.claim();
        })()
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    // Content-hashed Vite build assets (/build/...): cache-first for instant,
    // app-like loads. The filename changes on every deploy, so the cache can
    // never go stale — a new build is just a one-time cache miss that fetches
    // the new file. This is what stops the PWA re-downloading CSS/JS each visit.
    if (sameOrigin && url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(CACHE_VERSION).then((cache) => cache.put(request, clone));
                        }
                        return response;
                    })
            )
        );
        return;
    }

    // Other static assets: network-first, fall back to cache when offline.
    if (sameOrigin && ['style', 'script', 'image', 'font'].includes(request.destination)) {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_VERSION).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() => caches.match(request))
        );
        return;
    }

    // Page navigations: network-first, offline fallback to the cached shell.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(OFFLINE_FALLBACK, clone));
                    return response;
                })
                .catch(() => caches.match(OFFLINE_FALLBACK))
        );
    }
});
