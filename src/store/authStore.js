import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export const useAuthStore = create(
  persist(
    (set) => ({
      user: null,          // { id, name, role: 'Admin' | 'Office' | 'Lead' | 'Crew' }
      token: null,          // FieldClock-issued JWT, validated locally by the Calendar API
      refreshToken: null,   // FieldClock refresh token
      isAuthenticated: false,

      login: (user, token, refreshToken) =>
        set({ user, token, refreshToken, isAuthenticated: true }),

      logout: () =>
        set({ user: null, token: null, refreshToken: null, isAuthenticated: false }),

      updateToken: (token, refreshToken) =>
        set(refreshToken ? { token, refreshToken } : { token }),
    }),
    { name: 'calendar-auth' }
  )
)
