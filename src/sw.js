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

// Push notification received
self.addEventListener('push', (event) => {
  if (!event.data) return
  let data = {}
  try { data = event.data.json() } catch {}

  const title   = data.title ?? 'JCCS Calendar'
  const options = {
    body:  data.body ?? '',
    icon:  '/favicon.svg',
    badge: '/favicon.svg',
    vibrate: [200, 100, 200],
    data:  { url: data.url ?? '/' },
    actions: [{ action: 'open', title: 'View Event' }],
  }

  event.waitUntil(self.registration.showNotification(title, options))
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
