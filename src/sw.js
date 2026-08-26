import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching'
import { clientsClaim } from 'workbox-core'
import { registerRoute } from 'workbox-routing'
import { NetworkFirst } from 'workbox-strategies'

self.skipWaiting()
clientsClaim()

precacheAndRoute(self.__WB_MANIFEST)
cleanupOutdatedCaches()

// API read caching
registerRoute(
  ({ url }) => /\/api\/(events|calendar-events|my-events|todos|event-types|users|roles)/.test(url.pathname),
  new NetworkFirst({
    cacheName: 'api-reads-v1',
    networkTimeoutSeconds: 5,
    plugins: [{ cacheWillUpdate: async ({ response }) => (response.status === 200 ? response : null) }],
  })
)

// Push notification received. The backend sends every push with an empty
// payload (api/push/push-helper.php — no RFC 8291 encryption implemented
// server-side), so there's never event-specific data to read here; this
// shows one fixed notification and always deep-links to My Events rather
// than a specific event. See push-helper.php's docblock for the trade-off.
self.addEventListener('push', (event) => {
  const options = {
    body:  'You have a calendar update.',
    icon:  '/favicon.svg',
    badge: '/favicon.svg',
    vibrate: [200, 100, 200],
    data:  { url: '/my-events' },
    actions: [{ action: 'open', title: 'View Events' }],
  }

  event.waitUntil(self.registration.showNotification('JCCS Calendar', options))
})

// Notification click — focus existing window or open new tab
self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const url = event.notification.data?.url ?? '/'

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (const client of windowClients) {
        if ('focus' in client) { client.focus(); return }
      }
      return clients.openWindow(url)
    })
  )
})
