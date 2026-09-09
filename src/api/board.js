import axios from 'axios'
import { useBoardStore } from '../store/boardStore'

// The Operations Board deliberately does NOT use the shared `client.js`
// (which carries the FieldClock JWT and force-redirects to /login on 401).
// The board is public; only writes are gated, by a shared PIN sent in the
// X-Board-Pin header.
const board = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: { 'Content-Type': 'application/json' },
})

board.interceptors.request.use((config) => {
  const pin = useBoardStore.getState().pin
  if (pin) config.headers['X-Board-Pin'] = pin
  return config
})

// ── Read (public) ─────────────────────────────────────────────────────────
export const getBoard = (signal) =>
  board.get('/ops-board', { signal }).then((r) => r.data)

// ── PIN ───────────────────────────────────────────────────────────────────
// Sends the candidate PIN explicitly so it can be checked before it's stored.
export const verifyPin = (pin) =>
  board
    .post('/ops-board/verify-pin', {}, { headers: { 'X-Board-Pin': pin } })
    .then((r) => r.data)

export const getRefs = () => board.get('/ops-board/refs').then((r) => r.data)

// ── Jobs ─────────────────────────────────────────────────────────────────
export const createJob = (data) =>
  board.post('/ops-board/jobs', data).then((r) => r.data)

export const updateJob = (id, data) =>
  board.put(`/ops-board/jobs/${id}`, data).then((r) => r.data)

export const archiveJob = (id, expected_updated_at) =>
  board
    .delete(`/ops-board/jobs/${id}`, { data: { expected_updated_at } })
    .then((r) => r.data)

// Crew drag-and-drop on the main board (no PIN — see api/ops-board/job_crew.php).
export const addJobCrew = (jobId, worker_id, worker_name) =>
  board.post(`/ops-board/jobs/${jobId}/crew`, { worker_id, worker_name }).then((r) => r.data)

export const removeJobCrew = (jobId, worker_id) =>
  board.delete(`/ops-board/jobs/${jobId}/crew?worker_id=${worker_id}`).then((r) => r.data)

// ── Appointments ─────────────────────────────────────────────────────────
export const createAppointment = (data) =>
  board.post('/ops-board/appointments', data).then((r) => r.data)

export const updateAppointment = (id, data) =>
  board.put(`/ops-board/appointments/${id}`, data).then((r) => r.data)

export const cancelAppointment = (id, expected_updated_at) =>
  board
    .delete(`/ops-board/appointments/${id}`, { data: { expected_updated_at } })
    .then((r) => r.data)

export const unpinAppointment = (id) =>
  board.delete(`/ops-board/appointments/${id}?hard=1`).then((r) => r.data)
