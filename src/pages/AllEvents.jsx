import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { getEvents, getEventTypes } from '../api/events'
import { getUsers } from '../api/users'
import { useAuth } from '../hooks/useAuth'
import EventCard from '../components/events/EventCard'
import EventFilters from '../components/events/EventFilters'
import Spinner from '../components/ui/Spinner'

function UserColorMap(events) {
  const colors = ['#2563eb','#16a34a','#f97316','#7c3aed','#dc2626','#0891b2','#db2777','#65a30d','#4f46e5','#e11d48']
  const map = {}
  let i = 0
  for (const e of events) {
    const name = e.assigned_user?.name ?? 'Unassigned'
    if (!map[name]) { map[name] = e.color ?? colors[i++ % colors.length] }
  }
  return map
}

export default function AllEvents() {
  const navigate = useNavigate()
  const { canManageEvents } = useAuth()
  const [events, setEvents] = useState([])
  const [eventTypes, setEventTypes] = useState([])
  const [users, setUsers] = useState([])
  const [filters, setFilters] = useState({ date_range: 'today_tomorrow' })
  const [loading, setLoading] = useState(true)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const [evts, types, usrs] = await Promise.all([
        getEvents(filters),
        getEventTypes(),
        getUsers().catch(() => []),
      ])
      setEvents(evts)
      setEventTypes(types)
      setUsers(usrs)
    } catch {}
    setLoading(false)
  }, [filters])

  useEffect(() => { load() }, [load])

  const colorMap = UserColorMap(events)

  // Group by user then by day label
  const grouped = {}
  for (const e of events) {
    const userName = e.assigned_user?.name ?? 'Unassigned'
    if (!grouped[userName]) grouped[userName] = []
    grouped[userName].push(e)
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">All Events</h1>
          <p className="text-sm text-gray-500 mt-0.5">{events.length} events</p>
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

      <EventFilters
        filters={filters}
        onChange={setFilters}
        eventTypes={eventTypes}
        users={users}
        showUserFilter
      />

      {loading ? (
        <div className="flex justify-center py-16">
          <Spinner size="lg" className="text-brand-500" />
        </div>
      ) : events.length === 0 ? (
        <div className="text-center py-16 text-gray-400">
          <p className="text-base">No events found for this range.</p>
        </div>
      ) : (
        <div className="space-y-8">
          {Object.entries(grouped).sort(([a], [b]) => a.localeCompare(b)).map(([userName, userEvents]) => (
            <div key={userName}>
              <div className="flex items-center gap-3 mb-3">
                <span
                  className="w-3 h-3 rounded-full shrink-0"
                  style={{ backgroundColor: colorMap[userName] ?? '#6b7280' }}
                />
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
    </div>
  )
}
