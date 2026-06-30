import { useAuthStore } from '../store/authStore'

export function useAuth() {
  const { user, token, isAuthenticated, login, logout } = useAuthStore()
  return {
    user,
    token,
    isAuthenticated,
    isAdmin: user?.role === 'Admin',
    isOffice: user?.role === 'Office',
    canManageEvents: user?.role === 'Admin' || user?.role === 'Office',
    canViewAllEvents: user?.role === 'Admin' || user?.role === 'Office',
    canManageUsers: user?.role === 'Admin',
    login,
    logout,
  }
}
