import client from './client'

export const getVapidKey = () =>
  client.get('/push/key').then((r) => r.data.publicKey)

export const subscribePush = (subscription) =>
  client.post('/push/subscribe', {
    endpoint: subscription.endpoint,
    keys: {
      p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
      auth:   btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth')))),
    },
  }).then((r) => r.data)

export const unsubscribePush = (endpoint) =>
  client.post('/push/unsubscribe', { endpoint }).then((r) => r.data)
