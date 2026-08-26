import { format, parseISO } from 'date-fns'
import { useNavigate } from 'react-router-dom'
import Modal from '../ui/Modal'
import Badge from '../ui/Badge'
import Button from '../ui/Button'
import { getRecommendedStart, getProductionStatus, PRODUCTION_STATUS_LABELS } from '../../utils/format'
import { useAuth } from '../../hooks/useAuth'

export default function ProductionDetailModal({ job, onClose }) {
  const navigate = useNavigate()
  const { canManageEvents } = useAuth()
  if (!job) return null

  const status = getProductionStatus(job)
  const recommendedStart = getRecommendedStart(job)

  return (
    <Modal isOpen={Boolean(job)} onClose={onClose} title={job.title} size="lg">
      <div className="space-y-4">
        {job.photo_url && (
          <img src={job.photo_url} alt="" className="w-full h-48 object-cover rounded-xl border border-gray-100" />
        )}

        <div className="flex items-center gap-2">
          <Badge label={PRODUCTION_STATUS_LABELS[status]} />
          <Badge label={job.status} />
        </div>

        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
          {job.estimate_number && (
            <div><dt className="text-xs text-gray-400 uppercase tracking-wide">Estimate #</dt><dd className="text-gray-800 font-medium">{job.estimate_number}</dd></div>
          )}
          {job.client_name && (
            <div><dt className="text-xs text-gray-400 uppercase tracking-wide">Client</dt><dd className="text-gray-800 font-medium">{job.client_name}</dd></div>
          )}
          {job.projected_end && (
            <div><dt className="text-xs text-gray-400 uppercase tracking-wide">Scheduled Completion</dt><dd className="text-gray-800 font-medium">{format(parseISO(job.projected_end.slice(0, 10)), 'MMMM d, yyyy')}</dd></div>
          )}
          {job.lead_time_days != null && (
            <div><dt className="text-xs text-gray-400 uppercase tracking-wide">Production Lead Time</dt><dd className="text-gray-800 font-medium">{job.lead_time_days} days</dd></div>
          )}
          {recommendedStart && (
            <div className="col-span-2"><dt className="text-xs text-gray-400 uppercase tracking-wide">Recommended Production Start</dt><dd className="text-gray-800 font-medium">{format(recommendedStart, 'MMMM d, yyyy')}</dd></div>
          )}
          {job.address && (
            <div className="col-span-2"><dt className="text-xs text-gray-400 uppercase tracking-wide">Address</dt><dd className="text-gray-800 font-medium">{job.address}</dd></div>
          )}
          {job.scope && (
            <div className="col-span-2"><dt className="text-xs text-gray-400 uppercase tracking-wide">Scope</dt><dd className="text-gray-700 whitespace-pre-wrap">{job.scope}</dd></div>
          )}
          {job.workers?.length > 0 && (
            <div className="col-span-2"><dt className="text-xs text-gray-400 uppercase tracking-wide">Workers</dt><dd className="text-gray-800 font-medium">{job.workers.map((w) => w.name).join(', ')}</dd></div>
          )}
        </dl>

        <div className="flex gap-3 pt-2">
          {canManageEvents && (
            <Button onClick={() => navigate(`/jobs/${job.id}/edit`)} className="flex-1">Edit Project</Button>
          )}
          <Button variant="secondary" onClick={onClose} className={canManageEvents ? '' : 'flex-1'}>Close</Button>
        </div>
      </div>
    </Modal>
  )
}
