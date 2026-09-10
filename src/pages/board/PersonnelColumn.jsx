import { CREW_MIME } from './useCrewDrop'
import { useBoardT } from './t'

// Home-base sites, in display order. A clocked-in worker whose FieldClock job
// name contains one of these lands in that group; anyone else who's on the
// clock goes under "On site". Edit this list if the job names change.
const HOME_SITES = ['Oficina', 'Carpinteria Mauldin', 'Carpinteria Principal']

const matchesSite = (jobName, site) =>
  (jobName ?? '').toLowerCase().includes(site.toLowerCase())

function since(ts) {
  if (!ts) return null
  const start = new Date(String(ts).replace(' ', 'T'))
  const mins = Math.max(0, Math.round((Date.now() - start.getTime()) / 60000))
  if (Number.isNaN(mins)) return null
  if (mins < 60) return `${mins}m`
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return m ? `${h}h ${m}m` : `${h}h`
}

function Person({ w, T, statusMap }) {
  const s = statusMap[w.status_label]
  const canDrag = !!w.user_id
  return (
    <span
      className={`ops-pc__person ${w.off ? 'is-off' : ''} ${canDrag ? 'is-draggable' : ''}`}
      draggable={canDrag}
      onDragStart={(e) => {
        e.dataTransfer.setData(CREW_MIME, JSON.stringify({ id: w.user_id, name: w.name }))
        e.dataTransfer.effectAllowed = 'copy'
      }}
      title={canDrag && !w.off ? T.dragHint : undefined}
    >
      <b>{w.name}</b>
      {!w.off && s && <span className={`ops-pc__stat ${s.cls}`}>{s.label}</span>}
      {!w.off && w.since && <span className="ops-pc__dur">{since(w.since)}</span>}
    </span>
  )
}

export default function PersonnelColumn({ data }) {
  const T = useBoardT()
  const statusMap = {
    working: { label: T.statusWorking, cls: 'st-working' },
    lunch: { label: T.statusLunch, cls: 'st-lunch' },
    material_run: { label: T.statusMaterial, cls: 'st-material' },
    waiting: { label: T.statusWaiting, cls: 'st-waiting' },
  }
  const ok = data?.status === 'ok'
  const workers = ok ? (data.workers ?? []) : []
  const roster = ok ? (data.roster ?? []) : []

  // Build the groups.
  const used = new Set()
  const siteGroups = HOME_SITES.map((site) => {
    const people = workers.filter((w) => matchesSite(w.job_name, site))
    people.forEach((w) => used.add(w.user_id))
    return { label: site, people }
  })
  const onSite = workers.filter((w) => !used.has(w.user_id))
  const clockedIds = new Set(workers.map((w) => w.user_id))
  const off = roster.filter((r) => !clockedIds.has(r.user_id)).map((r) => ({ ...r, off: true }))

  const groups = [
    ...siteGroups,
    ...(onSite.length ? [{ label: T.grpEnObra, people: onSite }] : []),
  ]

  return (
    <section className="ops-col ops-pc" aria-label={T.personnelTitle}>
      <header className="ops-col__head ops-col__head--personnel">
        <span>{T.personnelTitle}</span>
        <span className="ops-col__count">{ok ? workers.length : '—'}</span>
      </header>

      <div className="ops-col__body ops-col__body--scroll">
        {!ok ? (
          <p className="ops-col__empty">
            {T.clockUnavailable}
            {data?.status && data.status !== 'disabled' && (
              <span style={{ opacity: 0.5 }}> ({data.status})</span>
            )}
          </p>
        ) : (
          <>
            {groups.map((g) => (
              <div key={g.label} className="ops-pc__group">
                <div className="ops-pc__grouphd">
                  <span>{g.label}</span>
                  <span className="ops-pc__gcount">{g.people.length}</span>
                </div>
                <div className="ops-pc__people">
                  {g.people.length === 0 ? (
                    <span className="ops-pc__none">—</span>
                  ) : (
                    g.people.map((w) => <Person key={w.user_id ?? w.name} w={w} T={T} statusMap={statusMap} />)
                  )}
                </div>
              </div>
            ))}

            {off.length > 0 && (
              <div className="ops-pc__group ops-pc__group--off">
                <div className="ops-pc__grouphd">
                  <span>{T.grpOffClock}</span>
                  <span className="ops-pc__gcount">{off.length}</span>
                </div>
                <div className="ops-pc__people">
                  {off.map((w) => <Person key={w.user_id} w={w} T={T} statusMap={statusMap} />)}
                </div>
              </div>
            )}
          </>
        )}
      </div>

      <footer className="ops-col__foot">
        {ok ? `${workers.length + off.length} ${T.shown}` : ''}
      </footer>
    </section>
  )
}
