import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { getTodos, getEventTypes } from '../api/events'
import { getUsers } from '../api/users'
import { useAuth } from '../hooks/useAuth'
import EventCard from '../components/events/EventCard'
import EventFilters from '../components/events/EventFilters'
import Spinner from '../components/ui/Spinner'

export default function Todos() {
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
        getTodos(filters),
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

  const pending = events.filter((e) => e.status?.toLowerCase() !== 'completed')
  const done = events.filter((e) => e.status?.toLowerCase() === 'completed')

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">To-Do List</h1>
          <p className="text-sm text-gray-500 mt-0.5">{pending.length} pending · {done.length} completed</p>
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

      <EventFilters filters={filters} onChange={setFilters} eventTypes={eventTypes} users={users} showUserFilter />

      {loading ? (
        <div className="flex justify-center py-16">
          <Spinner size="lg" className="text-brand-500" />
        </div>
      ) : events.length === 0 ? (
        <div className="text-center py-16 text-gray-400">
          <p className="text-base">No events found for this range.</p>
        </div>
      ) : (
        <div className="space-y-6">
          {pending.length > 0 && (
            <div>
              <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Pending</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                {pending.map((e) => (
                  <EventCard key={e.id} event={e} color={e.color} onRefresh={load} />
                ))}
              </div>
            </div>
          )}
          {done.length > 0 && (
            <div>
              <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Completed</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                {done.map((e) => (
                  <EventCard key={e.id} event={e} color={e.color} onRefresh={load} />
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
