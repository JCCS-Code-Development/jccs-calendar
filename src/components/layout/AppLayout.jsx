import { useState } from 'react'
import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import OfflineBanner from '../OfflineBanner'
import { useAuth } from '../../hooks/useAuth'
import { logout as logoutAPI } from '../../api/auth'
import { useAuthStore } from '../../store/authStore'

const CalendarIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
    <line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
  </svg>
)
const ListIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" />
    <line x1="3" y1="6" x2="3.01" y2="6" /><line x1="3" y1="12" x2="3.01" y2="12" /><line x1="3" y1="18" x2="3.01" y2="18" />
  </svg>
)
const UserIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />
  </svg>
)
const CheckIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <polyline points="9 11 12 14 22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
  </svg>
)
const LogoutIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />
  </svg>
)
const MenuIcon = () => (
  <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
    <line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="18" x2="21" y2="18" />
  </svg>
)

function NavItem({ to, icon, label, end = false, onClick }) {
  return (
    <NavLink
      to={to}
      end={end}
      onClick={onClick}
      className={({ isActive }) =>
        `flex items-center gap-3 px-5 py-2.5 text-sm font-medium transition-colors rounded-none ${
          isActive ? 'bg-brand-700 text-white' : 'text-brand-100 hover:bg-brand-700 hover:text-white'
        }`
      }
    >
      {icon}
      {label}
    </NavLink>
  )
}

export default function AppLayout() {
  const [drawerOpen, setDrawerOpen] = useState(false)
  const navigate = useNavigate()
  const { user, canManageUsers, canViewAllEvents, canManageEvents } = useAuth()
  const { logout } = useAuthStore()

  const handleLogout = async () => {
    try { await logoutAPI() } catch {}
    logout()
    navigate('/login', { replace: true })
  }

  const close = () => setDrawerOpen(false)

  const navItems = [
    { to: '/', icon: <CalendarIcon />, label: 'Calendar', end: true, show: true },
    { to: '/events', icon: <ListIcon />, label: 'All Events', show: canViewAllEvents },
    { to: '/my-events', icon: <UserIcon />, label: 'My Events', show: true },
    { to: '/todos', icon: <CheckIcon />, label: 'To-Do List', show: canManageEvents },
    { to: '/users', icon: <UserIcon />, label: 'Users', show: canManageUsers },
  ].filter((i) => i.show)

  const SidebarContent = ({ onNavClick }) => (
    <>
      <div className="px-5 py-5 border-b border-brand-700">
        <p className="font-extrabold text-lg text-white leading-none tracking-tight">JCCS Calendar</p>
        <p className="text-brand-100 text-xs mt-0.5">{user?.name}</p>
      </div>
      <nav className="flex-1 py-3 overflow-y-auto">
        {navItems.map((item) => (
          <NavItem key={item.to} {...item} onClick={onNavClick} />
        ))}
      </nav>
      <button
        onClick={handleLogout}
        className="flex items-center gap-3 px-5 py-4 text-sm text-brand-100 hover:text-white text-left border-t border-brand-700 transition-colors w-full"
      >
        <LogoutIcon />
        Sign Out
      </button>
    </>
  )

  return (
    <div className="flex min-h-svh bg-gray-50">
      {/* Desktop sidebar */}
      <aside className="hidden lg:flex flex-col w-60 bg-brand-900 text-white shrink-0 fixed top-0 bottom-0 left-0 z-20">
        <SidebarContent onNavClick={undefined} />
      </aside>

      {/* Mobile drawer overlay */}
      {drawerOpen && (
        <div className="fixed inset-0 z-40 lg:hidden" onClick={close}>
          <div className="absolute inset-0 bg-black/50" />
          <aside className="absolute left-0 top-0 bottom-0 w-64 bg-brand-900 text-white flex flex-col" onClick={(e) => e.stopPropagation()}>
            <div className="px-5 py-5 border-b border-brand-700 flex items-center justify-between">
              <div>
                <p className="font-extrabold text-lg text-white tracking-tight">JCCS Calendar</p>
                <p className="text-brand-100 text-xs">{user?.name}</p>
              </div>
              <button onClick={close} className="text-brand-100 p-1 text-xl">✕</button>
            </div>
            <nav className="flex-1 py-3 overflow-y-auto">
              {navItems.map((item) => (
                <NavItem key={item.to} {...item} onClick={close} />
              ))}
            </nav>
            <button
              onClick={handleLogout}
              className="flex items-center gap-3 px-5 py-4 text-sm text-brand-100 hover:text-white text-left border-t border-brand-700 transition-colors w-full"
            >
              <LogoutIcon />
              Sign Out
            </button>
          </aside>
        </div>
      )}

      {/* Main content area */}
      <div className="flex-1 flex flex-col min-w-0 lg:ml-60">
        <OfflineBanner />

        {/* Mobile top bar */}
        <header className="lg:hidden bg-brand-900 text-white flex items-center justify-between px-4 py-3 sticky top-0 z-30">
          <button onClick={() => setDrawerOpen(true)} className="p-1">
            <MenuIcon />
          </button>
          <span className="font-extrabold tracking-tight">JCCS Calendar</span>
          <div className="w-8" />
        </header>

        <main className="flex-1 overflow-y-auto p-4 lg:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
