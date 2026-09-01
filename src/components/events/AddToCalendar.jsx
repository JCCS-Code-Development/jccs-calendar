import { useState, useRef, useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { format, parseISO, addHours } from 'date-fns'

const CalIcon = () => (
  <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
    <rect x="3" y="4" width="18" height="18" rx="2" />
    <line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
  </svg>
)

function toIcalDate(dt) {
  const d = typeof dt === 'string' ? parseISO(dt.replace(' ', 'T')) : dt
  return format(d, "yyyyMMdd'T'HHmmss")
}

function googleCalUrl(event) {
  const start = toIcalDate(event.start_datetime)
  const rawEnd = event.end_datetime
    ? parseISO(event.end_datetime.replace(' ', 'T'))
    : addHours(parseISO(event.start_datetime.replace(' ', 'T')), 1)
  const end = toIcalDate(rawEnd)
  const params = new URLSearchParams({
    action: 'TEMPLATE',
    text: event.title,
    dates: `${start}/${end}`,
    details: [event.description, event.event_type && `Type: ${event.event_type}`, event.status && `Status: ${event.status}`].filter(Boolean).join('\n'),
    location: event.location ?? '',
  })
  return `https://calendar.google.com/calendar/render?${params}`
}

function outlookUrl(event) {
  const toISO = (dt) => parseISO((dt ?? '').replace(' ', 'T')).toISOString()
  const params = new URLSearchParams({
    path: '/calendar/action/compose',
    rru: 'addevent',
    subject: event.title,
    startdt: toISO(event.start_datetime),
    enddt: event.end_datetime ? toISO(event.end_datetime) : toISO(event.start_datetime),
    body: event.description ?? '',
    location: event.location ?? '',
  })
  return `https://outlook.live.com/calendar/0/deeplink/compose?${params}`
}

function icsDownloadUrl(event, apiBase, token) {
  // Trigger a download of the single-event .ics from the backend
  return `${apiBase}/events/${event.id}/export.ics?token=${token}`
}

export default function AddToCalendar({ event, apiBase, token }) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const ref = useRef(null)

  useEffect(() => {
    const handler = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false) }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [])

  const options = [
    {
      label: 'Google Calendar',
      icon: '🗓',
      href: googleCalUrl(event),
      external: true,
    },
    {
      label: 'Outlook / Office 365',
      icon: '📅',
      href: outlookUrl(event),
      external: true,
    },
    {
      label: 'Apple / iCal (.ics)',
      icon: '🍎',
      href: icsDownloadUrl(event, apiBase, token),
      external: false,
      download: true,
    },
  ]

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={(e) => { e.stopPropagation(); setOpen((o) => !o) }}
        className="flex items-center gap-1 text-xs text-gray-500 hover:text-brand-500 transition-colors px-2 py-1 rounded-lg hover:bg-brand-50"
        title={t('f.addToPersonalCalendar')}
      >
        <CalIcon />
        <span>{t('f.addToCalendar')}</span>
      </button>

      {open && (
        <div
          className="absolute bottom-full mb-1 right-0 z-50 bg-white rounded-xl shadow-xl border border-gray-100 min-w-[200px] py-1 overflow-hidden"
          onClick={(e) => e.stopPropagation()}
        >
          <p className="px-3 py-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">{t('f.addToCalendar')}</p>
          {options.map((opt) => (
            <a
              key={opt.label}
              href={opt.href}
              target={opt.external ? '_blank' : undefined}
              rel={opt.external ? 'noopener noreferrer' : undefined}
              download={opt.download ? `${event.title || 'event'}.ics` : undefined}
              onClick={() => setOpen(false)}
              className="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors"
            >
              <span>{opt.icon}</span>
              {opt.label}
            </a>
          ))}
          <div className="border-t border-gray-100 mt-1 pt-1">
            <a
              href={`${apiBase}/calendar.ics?token=${token}`}
              download="jccs-calendar.ics"
              onClick={() => setOpen(false)}
              className="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-600 transition-colors"
            >
              <span>📡</span>
              {t('f.subscribeFullFeed')}
            </a>
          </div>
        </div>
      )}
    </div>
  )
}
