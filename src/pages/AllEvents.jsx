import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import {
  format, startOfWeek, endOfWeek, addWeeks, subWeeks,
} from 'date-fns'
import { getEvents, getEventTypes } from '../api/events'
import { getUsers } from '../api/users'
import { useAuth } from '../hooks/useAuth'
import EventCard from '../components/events/EventCard'
import EventFilters from '../components/events/EventFilters'
import WeekView from '../components/calendar/WeekView'
import Spinner from '../components/ui/Spinner'

const USER_COLORS = [
  '#2563eb','#16a34a','#f97316','#7c3aed','#dc2626',
  '#0891b2','#db2777','#65a30d','#4f46e5','#e11d48',
]

function buildColorMap(events) {
  const map = {}
  let i = 0
  for (const e of events) {
    const name = e.assigned_user?.name ?? 'Unassigned'
    if (!map[name]) map[name] = e.color ?? USER_COLORS[i++ % USER_COLORS.length]
  }
  return map
}

function ChevLeft() {
  return <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7"/></svg>
}
function ChevRight() {
  return <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7"/></svg>
}

export default function AllEvents({ embedded = false }) {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { canManageEvents } = useAuth()

  // Embedded inside the Calendar page's "List" tab — that page already owns
  // the week/month grids, so here we only ever show the grouped list.
  const [view,       setView]       = useState(embedded ? 'list' : 'week')
  const [anchor,     setAnchor]     = useState(new Date())
  const [events,     setEvents]     = useState([])
  const [eventTypes, setEventTypes] = useState([])
  const [users,      setUsers]      = useState([])
  const [filters,    setFilters]    = useState({ date_range: 'this_month' })
  const [loading,    setLoading]    = useState(true)

  // When in week view, override date_range with the current week window
  const fetchFilters = useCallback(() => {
    if (view === 'week') {
      const { date_range, ...rest } = filters
      return {
        ...rest,
        date_from: format(startOfWeek(anchor), 'yyyy-MM-dd'),
        date_to:   format(endOfWeek(anchor),   'yyyy-MM-dd'),
      }
    }
    return filters
  }, [view, anchor, filters])

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const [evts, types, usrs] = await Promise.all([
        getEvents(fetchFilters()),
        getEventTypes(),
        getUsers().catch(() => []),
      ])
      setEvents(evts)
      setEventTypes(types)
      setUsers(usrs)
    } catch {}
    setLoading(false)
  }, [fetchFilters])

  useEffect(() => { load() }, [load])

  const colorMap = buildColorMap(events)
  const getColor = (ev) => colorMap[ev.assigned_user?.name ?? 'Unassigned'] ?? '#6b7280'

  // List grouping by user
  const grouped = {}
  for (const e of events) {
    const name = e.assigned_user?.name ?? 'Unassigned'
    if (!grouped[name]) grouped[name] = []
    grouped[name].push(e)
  }

  const weekLabel = (() => {
    const s = startOfWeek(anchor)
    const e = endOfWeek(anchor)
    return `${format(s, 'MMM d')} – ${format(e, 'MMM d, yyyy')}`
  })()

  return (
    <div>
      {/* Page header — hidden when embedded in the Calendar page */}
      {!embedded && (
        <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
          <div>
            <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">{t('events.allEvents')}</h1>
            <p className="text-sm text-gray-500 mt-0.5">{events.length} {t('nav.allEvents').toLowerCase()}</p>
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            {/* View toggle */}
            <div className="flex items-center bg-gray-100 rounded-xl p-1 gap-1">
              {[['week', t('common.week')], ['list', t('common.list')]].map(([v, label]) => (
                <button
                  key={v}
                  onClick={() => setView(v)}
                  className={`px-3 py-1 text-xs font-semibold rounded-lg transition-colors ${view === v ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                >
                  {label}
                </button>
              ))}
            </div>
            {canManageEvents && (
              <button
                onClick={() => navigate('/events/create')}
                className="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-600 transition-colors shadow-sm shadow-brand-500/30"
              >
                {t('events.createEvent')}
              </button>
            )}
          </div>
        </div>
      )}

      {/* Calendar card (week view) */}
      {view === 'week' && (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-4">
          {/* Week nav toolbar */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <div className="flex items-center gap-1">
              <button onClick={() => setAnchor((a) => subWeeks(a, 1))} className="p-2 rounded-xl hover:bg-gray-100 text-gray-500"><ChevLeft /></button>
              <button onClick={() => setAnchor(new Date())} className="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">{t('common.today')}</button>
              <button onClick={() => setAnchor((a) => addWeeks(a, 1))} className="p-2 rounded-xl hover:bg-gray-100 text-gray-500"><ChevRight /></button>
              <span className="text-sm font-bold text-gray-800 ml-1">{weekLabel}</span>
            </div>
          </div>

          {loading ? (
            <div className="flex justify-center items-center h-64"><Spinner size="lg" className="text-brand-500" /></div>
          ) : (
            <WeekView anchor={anchor} events={events} navigate={navigate} getColor={getColor} />
          )}
        </div>
      )}

      {/* User color legend (week view) */}
      {view === 'week' && !loading && Object.keys(colorMap).length > 0 && (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{t('events.users')}</p>
          <div className="flex flex-wrap gap-4">
            {Object.entries(colorMap).map(([name, color]) => (
              <div key={name} className="flex items-center gap-1.5">
                <span className="w-3 h-3 rounded-full shrink-0" style={{ backgroundColor: color }} />
                <span className="text-xs text-gray-600 font-medium">{name}</span>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* List view */}
      {view === 'list' && (
        <>
          <EventFilters
            filters={filters}
            onChange={setFilters}
            eventTypes={eventTypes}
            users={users}
            showUserFilter
          />
          {loading ? (
            <div className="flex justify-center py-16"><Spinner size="lg" className="text-brand-500" /></div>
          ) : events.length === 0 ? (
            <div className="text-center py-16 text-gray-400">
              <p className="text-base">{t('events.noEvents')}</p>
            </div>
          ) : (
            <div className="space-y-8">
              {Object.entries(grouped).sort(([a], [b]) => a.localeCompare(b)).map(([userName, userEvents]) => (
                <div key={userName}>
                  <div className="flex items-center gap-3 mb-3">
                    <span className="w-3 h-3 rounded-full shrink-0" style={{ backgroundColor: colorMap[userName] ?? '#6b7280' }} />
                    <h3 className="text-sm font-bold text-gray-700 uppercase tracking-wider">{userName}</h3>
                    <span className="text-xs text-gray-400">({userEvents.length})</span>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    {userEvents.map((e) => (
                      <EventCard key={e.id} event={e} color={colorMap[userName]} onRefresh={load} />
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </>
      )}
    </div>
  )
}
