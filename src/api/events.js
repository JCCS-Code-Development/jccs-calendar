import client from './client'

export const getEvents = (params) =>
  client.get('/events', { params }).then((r) => r.data)

export const getMyEvents = (params) =>
  client.get('/my-events', { params }).then((r) => r.data)

export const getTodos = (params) =>
  client.get('/todos', { params }).then((r) => r.data)

export const getCalendarEvents = (params) =>
  client.get('/calendar-events', { params }).then((r) => r.data)

export const getEvent = (id) =>
  client.get(`/events/${id}`).then((r) => r.data)

export const createEvent = (data) =>
  client.post('/events', data).then((r) => r.data)

export const updateEvent = (id, data) =>
  client.put(`/events/${id}`, data).then((r) => r.data)

export const deleteEvent = (id) =>
  client.delete(`/events/${id}`).then((r) => r.data)

export const markDone = (id) =>
  client.patch(`/events/${id}/mark-done`).then((r) => r.data)

export const getEventTypes = () =>
  client.get('/event-types').then((r) => r.data)
