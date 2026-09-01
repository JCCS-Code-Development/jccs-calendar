import { usePush } from '../hooks/usePush'
import Spinner from './ui/Spinner'
import { useTranslation } from 'react-i18next'

const BellIcon = ({ active }) => (
  <svg className="w-5 h-5" fill={active ? 'currentColor' : 'none'} viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
  </svg>
)

export default function NotificationToggle() {
  const { t } = useTranslation()
  const { subscribed, loading, supported, blocked, enable, disable } = usePush()

  if (!supported) return null

  if (blocked) {
    return (
      <div title={t('f.notificationsBlockedTitle')} className="flex items-center gap-1.5 text-xs text-brand-100/50 px-2 py-1">
        <BellIcon active={false} />
        <span className="hidden sm:inline">{t('f.blocked')}</span>
      </div>
    )
  }

  return (
    <button
      onClick={subscribed ? disable : enable}
      disabled={loading}
      title={subscribed ? t('f.disablePush') : t('f.enablePush')}
      className={`flex items-center gap-1.5 text-xs font-medium px-2 py-1 rounded-lg transition-colors disabled:opacity-50 ${
        subscribed
          ? 'text-brand-400 bg-brand-700 hover:bg-brand-700/80'
          : 'text-brand-100 hover:text-white hover:bg-brand-700'
      }`}
    >
      {loading ? <Spinner size="sm" /> : <BellIcon active={subscribed} />}
      <span className="hidden sm:inline">{subscribed ? t('notifications.enabled') : t('notifications.enable')}</span>
    </button>
  )
}
