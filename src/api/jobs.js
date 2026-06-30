import client from './client'

// Clients
export const getClients = () => client.get('/clients').then((r) => r.data)
export const createClient = (data) => client.post('/clients', data).then((r) => r.data)
export const updateClient = (id, data) => client.put(`/clients/${id}`, data).then((r) => r.data)
export const deleteClient = (id) => client.delete(`/clients/${id}`).then((r) => r.data)

// Jobs
export const getJobs = () => client.get('/jobs').then((r) => r.data)
export const getJob = (id) => client.get(`/jobs/${id}`).then((r) => r.data)
export const createJob = (data) => client.post('/jobs', data).then((r) => r.data)
export const updateJob = (id, data) => client.put(`/jobs/${id}`, data).then((r) => r.data)
export const deleteJob = (id) => client.delete(`/jobs/${id}`).then((r) => r.data)

// Sync existing jobs to FieldClock (admin only)
export const syncToFieldclock = () => client.post('/jobs/sync-fieldclock').then((r) => r.data)
