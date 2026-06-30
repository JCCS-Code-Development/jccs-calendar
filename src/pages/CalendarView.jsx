import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { format, startOfMonth, endOfMonth, startOfWeek, endOfWeek, addDays, addMonths, subMonths, isSameMonth, isToday, parseISO } from 'date-fns'
import { getCalendarEvents } from '../api/events'
import { useAuth } from '../hooks/useAuth'
import Spinner from '../components/ui/Spinner'
import Badge from '../components/ui/Badge'

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

function buildCalendarDays(month) {
  const start = startOfWeek(startOfMonth(month))
  const end = endOfWeek(endOfMonth(month))
  const days = []
  let d = start
  while (d <= end) { days.push(d); d = addDays(d, 1) }
  return days
}

export default function CalendarView() {
  const navigate = useNavigate()
  const { canManageEvents } = useAuth()
  const [month, setMonth] = useState(new Date())
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const data = await getCalendarEvents()
      setEvents(data)
    } catch {}
    setLoading(false)
  }, [])

  useEffect(() => { load() }, [load])

  const days = buildCalendarDays(month)

  const eventsForDay = (day) =>
    events.filter((e) => {
      const start = parseISO(e.start.replace(' ', 'T'))
      const end = e.end ? parseISO(e.end.replace(' ', 'T')) : start
      return day >= new Date(start.getFullYear(), start.getMonth(), start.getDate()) &&
             day <= new Date(end.getFullYear(), end.getMonth(), end.getDate())
    })

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">Company Calendar</h1>
          <p className="text-sm text-gray-500 mt-0.5">Schedule overview</p>
        </div>
        {canManageEvents && (
          <button
            onClick={() => navigate('/events/create')}
            className="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-400 transition-colors"
          >
            + Create Event
          </button>
        )}
      </div>

      {/* Calendar card */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {/* Month nav */}
        <div className="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <button
            onClick={() => setMonth((m) => subMonths(m, 1))}
            className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-600"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h2 className="text-base font-bold text-gray-900">{format(month, 'MMMM yyyy')}</h2>
          <button
            onClick={() => setMonth((m) => addMonths(m, 1))}
            className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-600"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        {loading ? (
          <div className="flex justify-center items-center h-64">
            <Spinner size="lg" className="text-brand-500" />
          </div>
        ) : (
          <div className="overflow-x-auto">
            <div className="min-w-[640px]">
              {/* Day headers */}
              <div className="grid grid-cols-7 border-b border-gray-100">
                {DAYS.map((d) => (
                  <div key={d} className="py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    {d}
                  </div>
                ))}
              </div>
              {/* Calendar grid */}
              <div className="grid grid-cols-7">
                {days.map((day, i) => {
                  const dayEvents = eventsForDay(day)
                  const inMonth = isSameMonth(day, month)
                  const today = isToday(day)
                  return (
                    <div
                      key={i}
                      className={`min-h-[96px] p-1.5 border-b border-r border-gray-100 ${!inMonth ? 'bg-gray-50' : ''} ${i % 7 === 6 ? 'border-r-0' : ''}`}
                    >
                      <div className={`w-7 h-7 flex items-center justify-center rounded-full text-sm font-medium mb-1 ${today ? 'bg-brand-500 text-white' : inMonth ? 'text-gray-800' : 'text-gray-400'}`}>
                        {format(day, 'd')}
                      </div>
                      <div className="flex flex-col gap-0.5">
                        {dayEvents.slice(0, 3).map((ev) => (
                          <button
                            key={ev.id}
                            onClick={() => navigate(`/events/${ev.id}/edit`)}
                            className="text-left text-xs px-1.5 py-0.5 rounded-md text-white truncate w-full font-medium hover:opacity-90 transition-opacity"
                            style={{ backgroundColor: ev.color }}
                          >
                            {ev.title}
                          </button>
                        ))}
                        {dayEvents.length > 3 && (
                          <span className="text-xs text-gray-500 pl-1">+{dayEvents.length - 3} more</span>
                        )}
                      </div>
                    </div>
                  )
                })}
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Legend */}
      {!loading && events.length > 0 && (
        <div className="mt-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
          <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">User Legend</p>
          <div className="flex flex-wrap gap-3">
            {[...new Map(events.map((e) => [e.assigned_user, { name: e.assigned_user, color: e.color }])).values()].map((u) => (
              <div key={u.name} className="flex items-center gap-1.5">
                <span className="w-3 h-3 rounded-full shrink-0" style={{ backgroundColor: u.color }} />
                <span className="text-xs text-gray-600">{u.name}</span>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
