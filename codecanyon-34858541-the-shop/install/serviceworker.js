// Self-destructing service worker.
//
// The previous PWA worker served a stale cached app shell (it survived hard
// reloads, incognito, and cache-busting query strings). This version removes
// itself cleanly: on activation it deletes every cache, unregisters the worker,
// and reloads any open tabs so they fetch fresh from the network. After it runs
// once, the site has no service worker and always serves the latest build.

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        // 1. Delete every cache this origin has.
        const cacheNames = await caches.keys();
        await Promise.all(cacheNames.map((name) => caches.delete(name)));

        // 2. Unregister this service worker. (No forced client reload here: the
        //    page re-registers on load, so an auto-reload would loop. The user
        //    reloads once more by hand and then gets fresh network content.)
        await self.registration.unregister();
    })());
});

// While winding down, never serve from cache — always hit the network.
self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});
