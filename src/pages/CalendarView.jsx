import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import {
  format, startOfMonth, endOfMonth, startOfWeek, endOfWeek,
  addDays, addMonths, subMonths, addWeeks, subWeeks,
  isSameMonth, isToday, parseISO, startOfDay,
} from 'date-fns'
import { getCalendarEvents, getExternalCalendar, getLinkedOutlookEvents } from '../api/events'
import { useAuth } from '../hooks/useAuth'
import { useAuthStore } from '../store/authStore'
import Spinner from '../components/ui/Spinner'
import WeekView from '../components/calendar/WeekView'
import AllEvents from './AllEvents'

const API_BASE = import.meta.env.VITE_API_BASE_URL
const DAYS_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const DAYS_FULL  = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
const OUTLOOK_URL_KEY = 'jccs_outlook_ical_url'

// ── helpers ──────────────────────────────────────────────────────────────────

function parseDate(raw) {
  if (!raw) return null
  return parseISO(raw.replace(' ', 'T'))
}

function buildMonthDays(month) {
  const start = startOfWeek(startOfMonth(month))
  const end   = endOfWeek(endOfMonth(month))
  const days  = []
  let d = start
  while (d <= end) { days.push(d); d = addDays(d, 1) }
  return days
}

function buildWeekDays(anchor) {
  const start = startOfWeek(anchor)
  return Array.from({ length: 7 }, (_, i) => addDays(start, i))
}

function eventsOnDay(events, day) {
  return events.filter((e) => {
    const start = parseDate(e.start_datetime ?? e.start)
    const end   = e.end_datetime ?? e.end ? parseDate(e.end_datetime ?? e.end) : start
    const dayStart = startOfDay(day)
    const dayEnd   = addDays(dayStart, 1)
    return start < dayEnd && end >= dayStart
  })
}

// ── sub-components ────────────────────────────────────────────────────────────

function ChevLeft() {
  return (
    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
  )
}
function ChevRight() {
  return (
    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
    </svg>
  )
}

// Monthly grid
function MonthView({ month, events, navigate }) {
  const days  = buildMonthDays(month)
  const weeks = Math.ceil(days.length / 7)       // 4, 5, or 6
  const rowH  = Math.floor(600 / weeks)           // distribute 60vh across rows

  return (
    <div className="overflow-x-auto">
      <div className="min-w-[480px]">
        {/* Day name headers */}
        <div className="grid grid-cols-7 border-b border-gray-100">
          {DAYS_SHORT.map((d) => (
            <div key={d} className="py-2 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">
              {d}
            </div>
          ))}
        </div>

        {/* Date grid — rows stretch to fill ~60vh */}
        <div className="grid grid-cols-7" style={{ minHeight: '60vh' }}>
          {days.map((day, i) => {
            const dayEvents = eventsOnDay(events, day)
            const inMonth   = isSameMonth(day, month)
            const today     = isToday(day)
            const maxVisible = Math.max(3, Math.floor((rowH - 36) / 20))  // fit as many pills as the row allows
            return (
              <div
                key={i}
                className={`p-1.5 border-b border-r border-gray-100 flex flex-col ${!inMonth ? 'bg-gray-50/60' : ''} ${i % 7 === 6 ? 'border-r-0' : ''}`}
                style={{ minHeight: rowH }}
              >
                <div className={`w-7 h-7 flex items-center justify-center rounded-full text-xs font-semibold mb-1 shrink-0 ${today ? 'bg-brand-500 text-white' : inMonth ? 'text-gray-700' : 'text-gray-300'}`}>
                  {format(day, 'd')}
                </div>
                <div className="flex flex-col gap-0.5 flex-1 overflow-hidden">
                  {dayEvents.slice(0, maxVisible).map((ev) => (
                    <button
                      key={ev.id}
                      onClick={() => ev.source !== 'outlook' && navigate(`/events/${ev.id}/edit`)}
                      className={`text-left text-[10px] px-1.5 py-0.5 rounded-md truncate w-full font-medium transition-opacity ${ev.source === 'outlook' ? 'opacity-80 cursor-default' : 'hover:opacity-90 cursor-pointer'}`}
                      style={{
                        backgroundColor: ev.source === 'outlook' ? '#0078d4' : (ev.color ?? ev.event_type_color ?? '#6b7280'),
                        color: '#fff',
                      }}
                    >
                      {ev.source === 'outlook' && '📅 '}{ev.title}
                    </button>
                  ))}
                  {dayEvents.length > maxVisible && (
                    <span className="text-[10px] text-gray-400 pl-1">+{dayEvents.length - maxVisible} more</span>
                  )}
                </div>
              </div>
            )
          })}
        </div>
      </div>
    </div>
  )
}


// Outlook connect modal
function OutlookModal({ onClose, onSave, current }) {
  const [url, setUrl] = useState(current ?? '')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const handleSave = async () => {
    if (!url.trim()) { onSave(''); return }
    if (!url.startsWith('http')) { setError('Paste a valid https:// URL from Outlook.'); return }
    setLoading(true)
    setError('')
    try {
      const { getExternalCalendar } = await import('../api/events.js')
      await getExternalCalendar(url.trim())
      onSave(url.trim())
    } catch {
      setError('Could not load that calendar. Check the URL and try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" onClick={onClose}>
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" onClick={(e) => e.stopPropagation()}>
        <h2 className="text-base font-bold text-gray-900 mb-1">Connect Outlook Calendar</h2>
        <p className="text-sm text-gray-500 mb-4">
          Paste your Outlook calendar's ICS URL to display those events here alongside your JCCS events.
        </p>

        <div className="bg-blue-50 border border-blue-100 rounded-xl p-3 mb-4 text-xs text-blue-700 space-y-1">
          <p className="font-semibold">How to get your Outlook ICS URL:</p>
          <ol className="list-decimal list-inside space-y-0.5">
            <li>Go to <strong>outlook.com</strong> or <strong>Outlook app</strong></li>
            <li>Open <strong>Calendar</strong> → click the gear/settings on your calendar</li>
            <li>Choose <strong>"Share calendar"</strong> → <strong>"Get a link"</strong></li>
            <li>Select <strong>ICS format</strong> and copy the URL</li>
          </ol>
        </div>

        <label className="block text-xs font-semibold text-gray-600 mb-1">ICS Calendar URL</label>
        <input
          type="url"
          className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 mb-1"
          placeholder="https://outlook.live.com/owa/calendar/…/calendar.ics"
          value={url}
          onChange={(e) => setUrl(e.target.value)}
        />
        {error && <p className="text-xs text-red-500 mb-2">{error}</p>}

        <div className="flex gap-2 mt-4">
          {current && (
            <button
              onClick={() => onSave('')}
              className="flex-1 border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium py-2 rounded-xl transition-colors"
            >
              Disconnect
            </button>
          )}
          <button onClick={onClose} className="flex-1 border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium py-2 rounded-xl transition-colors">
            Cancel
          </button>
          <button
            onClick={handleSave}
            disabled={loading}
            className="flex-1 bg-brand-500 text-white text-sm font-semibold py-2 rounded-xl hover:bg-brand-600 transition-colors disabled:opacity-50"
          >
            {loading ? 'Checking…' : 'Save'}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── main component ────────────────────────────────────────────────────────────

export default function CalendarView() {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { canManageEvents } = useAuth()
  const token = useAuthStore((s) => s.token)

  const [view,   setView]   = useState('week')   // 'week' | 'month' | 'list'
  const [anchor, setAnchor] = useState(new Date())
  const [events, setEvents] = useState([])
  const [outlookEvents, setOutlookEvents] = useState([])       // per-browser "Connect Outlook" overlay
  const [linkedOutlook, setLinkedOutlook] = useState([])        // per-person team feeds (server-side)
  const [hidden, setHidden] = useState(() => new Set())  // legend ids toggled off
  const [loading, setLoading] = useState(true)
  const [showOutlookModal, setShowOutlookModal] = useState(false)
  const [outlookUrl, setOutlookUrl] = useState(() => localStorage.getItem(OUTLOOK_URL_KEY) ?? '')

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const data = await getCalendarEvents()
      setEvents(data)
    } catch {}
    setLoading(false)
    // Linked Outlook feeds can be slow (N remote fetches) — load them after
    // the grid is already up rather than blocking it.
    getLinkedOutlookEvents().then(setLinkedOutlook).catch(() => setLinkedOutlook([]))
  }, [])

  const loadOutlook = useCallback(async (url) => {
    if (!url) { setOutlookEvents([]); return }
    try {
      const data = await getExternalCalendar(url)
      setOutlookEvents(data)
    } catch {
      setOutlookEvents([])
    }
  }, [])

  useEffect(() => { load() }, [load])
  useEffect(() => { loadOutlook(outlookUrl) }, [outlookUrl, loadOutlook])

  const handleOutlookSave = (url) => {
    if (url) localStorage.setItem(OUTLOOK_URL_KEY, url)
    else localStorage.removeItem(OUTLOOK_URL_KEY)
    setOutlookUrl(url)
    setShowOutlookModal(false)
  }

  const allEvents = [...events, ...linkedOutlook, ...outlookEvents]

  // Legend / per-person filter, built from whoever actually has events in the
  // loaded range. Each JCCS event already carries a per-user `color` from the
  // API (userColor(assigned_user_id)); reuse it so dots match the calendar.
  const legend = (() => {
    const byId = new Map()
    let hasUnassigned = false
    for (const e of [...events, ...linkedOutlook]) {
      if (e.assigned_user?.id != null) {
        if (!byId.has(e.assigned_user.id)) {
          byId.set(e.assigned_user.id, {
            id: e.assigned_user.id,
            name: e.assigned_user.name ?? `#${e.assigned_user.id}`,
            color: e.color ?? '#64748b',
          })
        }
      } else if (e.source !== 'outlook') {
        hasUnassigned = true
      }
    }
    const list = [...byId.values()].sort((a, b) => a.name.localeCompare(b.name))
    if (hasUnassigned) list.push({ id: 'unassigned', name: t('events.unassigned'), color: '#94a3b8' })
    if (outlookEvents.length > 0) list.push({ id: 'outlook', name: 'Outlook', color: '#0078d4' })
    return list
  })()

  const toggleLegend = (id) => setHidden((h) => {
    const next = new Set(h)
    next.has(id) ? next.delete(id) : next.add(id)
    return next
  })
  const showAllLegend = () => setHidden(new Set())
  const hideAllLegend = () => setHidden(new Set(legend.map((l) => l.id)))

  const visibleEvents = allEvents.filter((ev) => {
    if (ev.assigned_user?.id != null) return !hidden.has(ev.assigned_user.id)
    if (ev.source === 'outlook') return !hidden.has('outlook')  // legacy per-browser overlay
    return !hidden.has('unassigned')
  })

  const isWeek  = view === 'week'
  const prev    = () => isWeek ? setAnchor((a) => subWeeks(a, 1))  : setAnchor((a) => subMonths(a, 1))
  const next    = () => isWeek ? setAnchor((a) => addWeeks(a, 1))  : setAnchor((a) => addMonths(a, 1))
  const today   = () => setAnchor(new Date())

  const headLabel = isWeek
    ? (() => { const s = startOfWeek(anchor); const e = endOfWeek(anchor); return `${format(s, 'MMM d')} – ${isSameMonth(s, e) ? format(e, 'd, yyyy') : format(e, 'MMM d, yyyy')}` })()
    : format(anchor, 'MMMM yyyy')

  return (
    <div>
      {/* Page header */}
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">{t('calendar.apptTitle')}</h1>
          <p className="text-sm text-gray-500 mt-0.5">{t('calendar.apptSubtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {/* Outlook connect */}
          <button
            onClick={() => setShowOutlookModal(true)}
            className={`flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium border transition-colors ${
              outlookUrl
                ? 'bg-blue-50 border-blue-200 text-blue-700 hover:bg-blue-100'
                : 'border-gray-200 text-gray-600 hover:text-brand-500 hover:border-brand-300'
            }`}
          >
            <svg className="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
              <path d="M7 6C7 4.34315 8.34315 3 10 3H18C19.6569 3 21 4.34315 21 6V18C21 19.6569 19.6569 21 18 21H10C8.34315 21 7 19.6569 7 18V15H9V18C9 18.5523 9.44772 19 10 19H18C18.5523 19 19 18.5523 19 18V6C19 5.44772 18.5523 5 18 5H10C9.44772 5 9 5.44772 9 6V9H7V6Z"/><path d="M3 12L7 8V11H15V13H7V16L3 12Z"/>
            </svg>
            {outlookUrl ? 'Outlook connected' : 'Connect Outlook'}
          </button>

          {/* Subscribe iCal */}
          <a
            href={`webcal://${new URL(API_BASE).host}/api/calendar.ics?token=${token}`}
            title="Subscribe in Apple Calendar / Outlook"
            className="flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:text-brand-500 hover:border-brand-300 px-3 py-2 rounded-xl text-sm font-medium transition-colors"
          >
            <svg className="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
            Subscribe
          </a>

          {canManageEvents && (
            <button
              onClick={() => navigate('/events/create')}
              className="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-600 transition-colors shadow-sm shadow-brand-500/30"
            >
              + Create Event
            </button>
          )}
        </div>
      </div>

      {/* Calendar card */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {/* Toolbar */}
        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100 gap-3 flex-wrap">
          {/* Nav — only meaningful for the calendar grids */}
          {view === 'list' ? <div /> : (
            <div className="flex items-center gap-1">
              <button onClick={prev} className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
                <ChevLeft />
              </button>
              <button onClick={today} className="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                {t('common.today')}
              </button>
              <button onClick={next} className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500">
                <ChevRight />
              </button>
              <h2 className="text-sm font-bold text-gray-900 ml-1 whitespace-nowrap">{headLabel}</h2>
            </div>
          )}

          {/* View toggle */}
          <div className="flex items-center bg-gray-100 rounded-xl p-1 gap-1">
            {[['week', t('common.week')], ['month', t('common.month')], ['list', t('common.list')]].map(([v, label]) => (
              <button
                key={v}
                onClick={() => setView(v)}
                className={`px-3 py-1 text-xs font-semibold rounded-lg transition-colors ${
                  view === v ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                {label}
              </button>
            ))}
          </div>
        </div>

        {loading ? (
          <div className="flex justify-center items-center h-64">
            <Spinner size="lg" className="text-brand-500" />
          </div>
        ) : view === 'week' ? (
          <WeekView anchor={anchor} events={visibleEvents} navigate={navigate} />
        ) : view === 'month' ? (
          <MonthView month={anchor} events={visibleEvents} navigate={navigate} />
        ) : (
          <div className="p-4">
            <AllEvents embedded />
          </div>
        )}
      </div>

      {/* Legend / per-person filter */}
      {!loading && view !== 'list' && legend.length > 0 && (
        <div className="mt-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
          <div className="flex items-center justify-between mb-3">
            <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider">{t('calendar.filterByPerson')}</p>
            <div className="flex items-center gap-2 text-xs font-semibold">
              <button onClick={showAllLegend} className="text-brand-500 hover:text-brand-600">{t('common.all')}</button>
              <span className="text-gray-300">·</span>
              <button onClick={hideAllLegend} className="text-gray-400 hover:text-gray-600">{t('calendar.none')}</button>
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            {legend.map((u) => {
              const off = hidden.has(u.id)
              return (
                <button
                  key={u.id}
                  onClick={() => toggleLegend(u.id)}
                  className={`flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors ${
                    off ? 'border-gray-200 bg-gray-50 text-gray-400' : 'border-gray-200 text-gray-700 hover:bg-gray-50'
                  }`}
                >
                  <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ backgroundColor: off ? '#cbd5e1' : u.color }} />
                  <span className={off ? 'line-through' : ''}>{u.name}</span>
                </button>
              )
            })}
          </div>
        </div>
      )}

      {showOutlookModal && (
        <OutlookModal
          current={outlookUrl}
          onClose={() => setShowOutlookModal(false)}
          onSave={handleOutlookSave}
        />
      )}
    </div>
  )
}
