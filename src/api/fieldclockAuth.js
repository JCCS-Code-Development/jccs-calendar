import axios from 'axios'
import { useAuthStore } from '../store/authStore'

// A separate client pointed at FieldClock's live API — Calendar has no
// login of its own. It authenticates users through FieldClock's existing
// identifier+password login and reuses the JWT that comes back. Mirrors
// jccs-inventory's src/api/fieldclockAuth.js.
const fieldclockClient = axios.create({
  baseURL: import.meta.env.VITE_FIELDCLOCK_API_BASE_URL,
  headers: { 'Content-Type': 'application/json' },
})

// Same token used against Calendar's own API works here too — it's the
// exact JWT FieldClock issued at login, and this is FieldClock's own API.
fieldclockClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

export const login = (identifier, password) =>
  fieldclockClient.post('/auth/login.php', { identifier, password }).then((r) => r.data)

export const refresh = (refreshToken) =>
  fieldclockClient.post('/auth/refresh.php', { refreshToken }).then((r) => r.data)

export const logout = (refreshToken) =>
  fieldclockClient.post('/auth/logout.php', { refreshToken }).then((r) => r.data)

// Backs the FieldClock-employee picker on the Users page — search instead
// of typing a raw id. Gated to FieldClock admins on their side; falls back
// to manual id entry if the signed-in Calendar admin isn't also one.
export const listEmployees = () => fieldclockClient.get('/employees/index.php?active=1').then((r) => r.data)
