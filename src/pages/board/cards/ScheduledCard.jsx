import { fmtDate, fmtDateYear, fmtClock, poBadge, scheduleBadge } from './helpers'
import CrewRow from './CrewRow'
import { useCrewDrop } from '../useCrewDrop'
import { useBoardT } from '../t'

export default function ScheduledCard({ job, onCrew }) {
  const T = useBoardT()
  const po = poBadge(T)[job.po_status] ?? poBadge(T).none
  const sched = scheduleBadge(T)[job.schedule_status] ?? scheduleBadge(T).confirmed
  const { dropProps, over, removeCrew } = useCrewDrop(job, onCrew)

  return (
    <article
      className={`ops-card ops-card--state-${job.schedule_state} ${over ? 'is-drop' : ''}`}
      {...dropProps}
    >
      <p className="ops-card__title">{job.title}</p>
      <p className="ops-card__sub">
        {job.client_name || T.clientTBD}
        {job.estimate_number ? ` · ${T.est} #${job.estimate_number}` : ''}
      </p>

      {job.address && <div className="ops-card__row">{job.address}</div>}

      <div className="ops-card__row">
        <span>
          {job.schedule_state === 'delayed' ? T.wasDue : T.scheduled}{' '}
          <b>{fmtDate(job.projected_start) ?? fmtDateYear(job.projected_end) ?? T.dateTBD}</b>
        </span>
        {job.scheduled_start_time && (
          <span>
            {T.start} <b>{fmtClock(job.scheduled_start_time)}</b>
          </span>
        )}
        {job.projected_end && (
          <span>
            {T.doneBy} <b>{fmtDateYear(job.projected_end)}</b>
          </span>
        )}
      </div>

      <div className="ops-card__badges">
        <span className={`ops-badge ${sched.cls}`}>{sched.text}</span>
        {!job.po_missing && <span className={`ops-badge ${po.cls}`}>{po.text}</span>}
        {job.schedule_state === 'delayed' && <span className="ops-badge ops-badge--red">{T.delayed}</span>}
        {job.schedule_state === 'today' && <span className="ops-badge ops-badge--green">{T.today}</span>}
      </div>

      {job.po_missing && <div className="ops-warn">{T.poMissing}</div>}

      <CrewRow job={job} over={over} onRemove={removeCrew} />

      {job.board_notes && <p className="ops-card__notes">{job.board_notes}</p>}
    </article>
  )
}
