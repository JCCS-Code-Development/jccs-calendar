import { fmtDateYear, poBadge, priorityBadge } from './helpers'
import CrewRow from './CrewRow'
import PhotoStrip from './PhotoStrip'
import ProjectMeta from './ProjectMeta'
import { useCrewDrop } from '../useCrewDrop'
import { useBoardT } from '../t'

const WAIT_ALERT_DAYS = 14

export default function AwaitingCard({ job, onCrew }) {
  const T = useBoardT()
  const po = poBadge(T)[job.po_status] ?? poBadge(T).none
  const prio = priorityBadge(T)[job.priority] ?? priorityBadge(T).normal
  const overdue = job.days_waiting >= WAIT_ALERT_DAYS
  const { dropProps, over, removeCrew } = useCrewDrop(job, onCrew)

  return (
    <article
      className={`ops-card ops-card--prio-${job.priority} ${over ? 'is-drop' : ''}`}
      {...dropProps}
    >
      <p className="ops-card__title">{job.title}</p>
      <p className="ops-card__sub">
        {job.client_name || T.clientTBD}
        {job.estimate_number ? ` · ${T.est} #${job.estimate_number}` : ''}
      </p>

      {job.address && <div className="ops-card__row">{job.address}</div>}

      <PhotoStrip photos={job.photos} />

      <div className="ops-card__badges">
        <span className={`ops-badge ${po.cls}`}>{po.text}</span>
        {(job.priority === 'urgent' || job.priority === 'high') && (
          <span className={`ops-badge ${prio.cls}`}>{prio.text}</span>
        )}
        {job.status === 'On Hold' && <span className="ops-badge ops-badge--gray">{T.onHold}</span>}
      </div>

      <div className="ops-card__row">
        <span>
          {T.received} <b>{fmtDateYear(job.date_received) ?? '—'}</b>
        </span>
        <span className={overdue ? 'ops-waiting-strong' : undefined}>
          {job.days_waiting} {job.days_waiting === 1 ? T.day : T.days} {T.waiting}
          {overdue ? ' ⚠' : ''}
        </span>
      </div>

      {job.next_action_by && (
        <div className="ops-card__row">
          {T.next}: <b>{job.next_action_by}</b>
        </div>
      )}

      <CrewRow job={job} over={over} onRemove={removeCrew} />

      {job.board_notes && <p className="ops-card__notes">{job.board_notes}</p>}

      <ProjectMeta job={job} />
    </article>
  )
}
