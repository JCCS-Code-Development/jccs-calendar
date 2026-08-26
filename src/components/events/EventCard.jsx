import { useNavigate } from 'react-router-dom'
import Badge from '../ui/Badge'
import AddToCalendar from './AddToCalendar'
import { formatDateTime, formatDate, formatTime } from '../../utils/format'
import { useAuth } from '../../hooks/useAuth'
import { useAuthStore } from '../../store/authStore'
import { markDone } from '../../api/events'

const PRIORITY_DOT = { High: 'bg-red-500', Normal: 'bg-gray-400', Low: 'bg-blue-400' }

const API_BASE = import.meta.env.VITE_API_BASE_URL

export default function EventCard({ event, onRefresh, color }) {
  const navigate = useNavigate()
  const { canManageEvents } = useAuth()
  const token = useAuthStore((s) => s.token)

  const handleMarkDone = async (e) => {
    e.stopPropagation()
    try { await markDone(event.id); onRefresh?.() } catch {}
  }

  const borderColor = color ?? '#6366f1'
  const isDone = event.status?.toLowerCase() === 'completed'

  return (
    <div
      className={`bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden cursor-pointer hover:shadow-md transition-shadow ${isDone ? 'opacity-70' : ''}`}
      style={{ borderLeftWidth: 4, borderLeftColor: borderColor, borderLeftStyle: 'solid' }}
      onClick={() => navigate(`/events/${event.id}/edit`)}
    >
      <div className="px-4 py-3">
        <div className="flex items-start justify-between gap-2">
          <div className="flex-1 min-w-0">
            <p className={`text-sm font-semibold text-gray-900 truncate ${isDone ? 'line-through' : ''}`}>
              {event.title}
            </p>
            <p className="text-xs text-gray-500 mt-0.5">
              {event.is_all_day ? (
                `${formatDate(event.start_datetime)} · Any time`
              ) : (
                <>
                  {formatDateTime(event.start_datetime)}
                  {event.end_datetime && ` – ${formatTime(event.end_datetime)}`}
                </>
              )}
            </p>
          </div>
          <div className="flex items-center gap-1.5 shrink-0">
            {event.priority && event.priority !== 'Normal' && (
              <span className={`w-2 h-2 rounded-full ${PRIORITY_DOT[event.priority] ?? 'bg-gray-400'}`} title={event.priority} />
            )}
            <Badge label={event.status} />
          </div>
        </div>

        {(event.event_type || event.location || event.assigned_user?.name) && (
          <div className="mt-2 flex flex-wrap gap-1.5 text-xs text-gray-500">
            {event.event_type && (
              <span className="bg-brand-100 text-brand-700 px-2 py-0.5 rounded-full font-medium">{event.event_type}</span>
            )}
            {event.event_subtype && (
              <span className="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{event.event_subtype}</span>
            )}
            {event.location && (
              <span className="flex items-center gap-0.5">
                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {event.location}
              </span>
            )}
          </div>
        )}
      </div>

      <div className="px-4 py-2 border-t border-gray-50 flex items-center justify-between gap-2" onClick={(e) => e.stopPropagation()}>
        <AddToCalendar event={event} apiBase={API_BASE} token={token} />

        {canManageEvents && (
          <button
            onClick={handleMarkDone}
            className={`text-xs font-medium px-3 py-1 rounded-lg transition-colors ${isDone ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-green-50 text-green-700 hover:bg-green-100'}`}
          >
            {isDone ? 'Mark Scheduled' : '✓ Mark Done'}
          </button>
        )}
      </div>
    </div>
  )
}
