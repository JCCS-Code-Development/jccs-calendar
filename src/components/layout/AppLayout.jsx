import { useState } from 'react'
import { Outlet, NavLink, useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import OfflineBanner from '../OfflineBanner'
import NotificationToggle from '../NotificationToggle'
import { useAuth } from '../../hooks/useAuth'
import { logout as fieldclockLogout } from '../../api/fieldclockAuth'
import { useAuthStore } from '../../store/authStore'

const CalendarIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
    <line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
  </svg>
)
const MyEventsIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
  </svg>
)
const UsersIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" /><circle cx="9" cy="7" r="4" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
  </svg>
)
const ProductionIcon = () => (
  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
    <line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
    <path strokeLinecap="round" d="M8 14h2m4 0h2M8 17h2" />
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

function NavItem({ to, icon, label, badge, end = false, onClick }) {
  return (
    <NavLink
      to={to}
      end={end}
      onClick={onClick}
      className={({ isActive }) =>
        `flex items-center gap-3 px-5 py-2.5 text-sm font-medium transition-colors ${
          isActive
            ? 'bg-brand-500 text-white'
            : 'text-brand-100/80 hover:bg-brand-700 hover:text-white'
        }`
      }
    >
      {icon}
      {label}
      {badge && (
        <span className="ml-auto rounded-full bg-amber-400/90 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-950">
          {badge}
        </span>
      )}
    </NavLink>
  )
}

function SidebarLogo() {
  return (
    <div className="px-5 py-5 border-b border-brand-700/60 flex flex-col items-center text-center">
      <img
        src="/jccs-logo.jpg"
        alt="JCCS Services"
        className="h-12 w-auto"
        style={{ filter: 'invert(1) brightness(10)' }}
      />
      <p className="text-brand-300 text-xs font-bold mt-2 tracking-widest uppercase">Calendar</p>
    </div>
  )
}

function LangToggle() {
  const { i18n } = useTranslation()
  const current = i18n.language
  const toggle = () => {
    const next = current === 'en' ? 'es' : 'en'
    i18n.changeLanguage(next)
    localStorage.setItem('jccs_lang', next)
  }
  return (
    <button
      onClick={toggle}
      className="flex items-center gap-1.5 text-xs font-semibold px-2 py-1 rounded-lg text-brand-100/70 hover:text-white hover:bg-brand-700 transition-colors"
      title={current === 'en' ? 'Cambiar a Español' : 'Switch to English'}
    >
      <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
        <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
      </svg>
      {current === 'en' ? 'ES' : 'EN'}
    </button>
  )
}

export default function AppLayout() {
  const [drawerOpen, setDrawerOpen] = useState(false)
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { user, canManageUsers, canManageEvents } = useAuth()
  const { logout, refreshToken } = useAuthStore()

  const handleLogout = async () => {
    try { await fieldclockLogout(refreshToken) } catch {}
    logout()
    navigate('/login', { replace: true })
  }

  const close = () => setDrawerOpen(false)

  // Lead / Crew are read-only and scoped to their own events — the full
  // calendar and job planning were just noise, so they get one item.
  const isField = !canManageEvents
  const navItems = isField
    ? [
        { to: '/my-events', icon: <MyEventsIcon />,   label: t('nav.mySchedule'), end: true },
      ]
    : [
        { to: '/',          icon: <CalendarIcon />,   label: t('nav.calendar'), end: true },
        { to: '/jobs',      icon: <ProductionIcon />, label: t('nav.jobDeadlines'), badge: t('nav.soon') },
        { to: '/my-events', icon: <MyEventsIcon />,   label: t('nav.mySchedule') },
        ...(canManageUsers ? [{ to: '/users', icon: <UsersIcon />, label: t('nav.users') }] : []),
      ]

  const SidebarContent = ({ onNavClick }) => (
    <>
      <SidebarLogo />
      <div className="px-5 py-2.5 border-b border-brand-700/40">
        <p className="text-brand-100 text-sm font-semibold truncate">Welcome, {user?.name?.split(' ')[0]}!</p>
        <p className="text-brand-400/60 text-xs">{user?.role}</p>
      </div>
      <nav className="flex-1 py-3 overflow-y-auto">
        {navItems.map((item) => (
          <NavItem key={item.to} {...item} onClick={onNavClick} />
        ))}
      </nav>
      <div className="px-3 py-3 border-t border-brand-700/60 flex items-center justify-between gap-2">
        <NotificationToggle />
        <LangToggle />
      </div>
      <button
        onClick={handleLogout}
        className="flex items-center gap-3 px-5 py-4 text-sm text-brand-100/70 hover:text-white text-left border-t border-brand-700/60 transition-colors w-full"
      >
        <LogoutIcon />
        {t('nav.signOut')}
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
            <div className="flex items-center justify-between pr-4 border-b border-brand-700/60">
              <SidebarLogo />
              <button onClick={close} className="text-brand-100/60 hover:text-white p-1 text-xl ml-2">✕</button>
            </div>
            <div className="px-5 py-2.5 border-b border-brand-700/40">
              <p className="text-brand-100 text-sm font-semibold truncate">Welcome, {user?.name?.split(' ')[0]}!</p>
              <p className="text-brand-400/60 text-xs">{user?.role}</p>
            </div>
            <nav className="flex-1 py-3 overflow-y-auto">
              {navItems.map((item) => (
                <NavItem key={item.to} {...item} onClick={close} />
              ))}
            </nav>
            <div className="px-3 py-3 border-t border-brand-700/60">
              <NotificationToggle />
            </div>
            <button
              onClick={handleLogout}
              className="flex items-center gap-3 px-5 py-4 text-sm text-brand-100/70 hover:text-white text-left border-t border-brand-700/60 transition-colors w-full"
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
          <button onClick={() => setDrawerOpen(true)} className="p-1 text-brand-100/80">
            <MenuIcon />
          </button>
          <img
            src="/jccs-logo.jpg"
            alt="JCCS Services"
            className="h-7 w-auto"
            style={{ filter: 'invert(1) brightness(10)' }}
          />
          <div className="w-8" />
        </header>

        <main className="flex-1 overflow-y-auto p-4 lg:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
