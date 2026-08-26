import { format, parseISO } from 'date-fns'
import Badge from '../ui/Badge'
import { getRecommendedStart, getProductionStatus, PRODUCTION_STATUS_LABELS } from '../../utils/format'

// Sibling to EventCard.jsx — same card conventions, but for a job viewed as
// a Carpentry Production Calendar entry rather than an event.
export default function ProductionEntryCard({ job, onSelect, compact = false }) {
  const status = getProductionStatus(job)
  const recommendedStart = getRecommendedStart(job)

  return (
    <button
      onClick={() => onSelect(job)}
      className={`w-full text-left bg-white rounded-2xl shadow-sm border border-gray-100 hover:border-brand-300 hover:shadow-md transition-all overflow-hidden ${compact ? 'flex items-center gap-3 p-2.5' : 'p-4'}`}
    >
      <div className={compact ? 'w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-gray-100' : 'w-full h-28 mb-3 rounded-xl overflow-hidden bg-gray-100'}>
        {job.photo_url ? (
          <img src={job.photo_url} alt="" className="w-full h-full object-cover" />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-300">
            <svg className={compact ? 'w-5 h-5' : 'w-8 h-8'} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M3 16l5-5 4 4 5-6 4 5M3 5h18v14H3V5z" />
            </svg>
          </div>
        )}
      </div>

      <div className="flex-1 min-w-0">
        <div className="flex items-start justify-between gap-2 mb-1">
          <p className={`font-bold text-gray-900 truncate ${compact ? 'text-sm' : 'text-base'}`}>{job.title}</p>
          <Badge label={PRODUCTION_STATUS_LABELS[status]} />
        </div>
        {job.estimate_number && (
          <p className="text-xs text-gray-400 mb-0.5">#{job.estimate_number}{job.client_name ? ` · ${job.client_name}` : ''}</p>
        )}
        {!compact && job.scope && (
          <p className="text-xs text-gray-500 line-clamp-2 mb-2">{job.scope}</p>
        )}
        <div className="flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
          {job.projected_end && (
            <span>Due <strong className="text-gray-700">{format(parseISO(job.projected_end.slice(0, 10)), 'MMM d, yyyy')}</strong></span>
          )}
          {job.lead_time_days != null && <span>Lead time <strong className="text-gray-700">{job.lead_time_days}d</strong></span>}
          {recommendedStart && (
            <span>Start by <strong className="text-gray-700">{format(recommendedStart, 'MMM d')}</strong></span>
          )}
        </div>
      </div>
    </button>
  )
}
