import { CREW_MIME } from './useCrewDrop'
import { useBoardT } from './t'

// Sitios base que JCCS opera a diario. Los trabajadores con entrada marcada
// en un trabajo de FieldClock cuyo nombre coincida aparecen en "En los talleres".
// Edita esta lista si cambian los nombres de los trabajos en FieldClock.
const HOME_SITES = ['Carpinteria Mauldin', 'Carpinteria Principal', 'Oficina']

function since(ts) {
  if (!ts) return null
  const start = new Date(String(ts).replace(' ', 'T'))
  const mins = Math.max(0, Math.round((Date.now() - start.getTime()) / 60000))
  if (Number.isNaN(mins)) return null
  if (mins < 60) return `${mins} min`
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return m ? `${h} h ${m} min` : `${h} h`
}

const matchesSite = (jobName, site) =>
  (jobName ?? '').toLowerCase().includes(site.toLowerCase())

export default function PersonnelBar({ data }) {
  const T = useBoardT()
  const STATUS = {
    working: { label: T.statusWorking, cls: 'ops-clock-chip--working' },
    lunch: { label: T.statusLunch, cls: 'ops-clock-chip--lunch' },
    material_run: { label: T.statusMaterial, cls: 'ops-clock-chip--material' },
    waiting: { label: T.statusWaiting, cls: 'ops-clock-chip--waiting' },
  }
  // `data` is the payload's `clocked_in`: { status, workers? }. Only render
  // people when the FieldClock call actually succeeded.
  const ok = data?.status === 'ok'
  const workers = ok ? (data.workers ?? []) : []

  return (
    <div className="ops-personnel">
      <div className="ops-clock">
        <span className="ops-clock__label">
          {T.onTheClock}
          {ok && <span className="ops-clock__count">{workers.length}</span>}
        </span>
        <div className="ops-clock__list">
          {!ok ? (
            <span className="ops-clock__muted">
              {T.clockUnavailable}
              {data?.status && data.status !== 'disabled' && (
                <span style={{ opacity: 0.5, marginLeft: '0.5em' }}>({data.status})</span>
              )}
            </span>
          ) : workers.length === 0 ? (
            <span className="ops-clock__muted">{T.nobodyClockedIn}</span>
          ) : (
            workers.map((w) => {
              const s = STATUS[w.status_label] ?? { label: T.statusOnClock, cls: '' }
              const dur = since(w.since)
              const canDrag = !!w.user_id
              return (
                <span
                  key={w.user_id ?? w.name}
                  className={`ops-clock-chip ${s.cls} ${canDrag ? 'is-draggable' : ''}`}
                  draggable={canDrag}
                  onDragStart={(e) => {
                    e.dataTransfer.setData(
                      CREW_MIME,
                      JSON.stringify({ id: w.user_id, name: w.name })
                    )
                    e.dataTransfer.effectAllowed = 'copy'
                  }}
                  title={canDrag ? T.dragHint : undefined}
                >
                  <b>{w.name}</b>
                  <span className="ops-clock-chip__status">{s.label}</span>
                  {w.job_name && <span className="ops-clock-chip__job">· {w.job_name}</span>}
                  {dur && <span className="ops-clock-chip__dur">· {dur}</span>}
                </span>
              )
            })
          )}
        </div>
      </div>

      <div className="ops-shops">
        <span className="ops-clock__label">{T.atTheShops}</span>
        <div className="ops-shops__grid">
          {HOME_SITES.map((site) => {
            const here = workers.filter((w) => matchesSite(w.job_name, site))
            return (
              <div key={site} className="ops-shop">
                <div className="ops-shop__head">
                  <span className="ops-shop__name">{site}</span>
                  <span className="ops-shop__count">{here.length}</span>
                </div>
                <div className="ops-shop__people">
                  {here.length === 0 ? (
                    <span className="ops-clock__muted">—</span>
                  ) : (
                    here.map((w) => (
                      <span key={w.user_id ?? w.name} className="ops-shop__person">
                        {w.name}
                        {w.status_label === 'lunch' ? T.lunchParen : ''}
                      </span>
                    ))
                  )}
                </div>
              </div>
            )
          })}
        </div>
      </div>
    </div>
  )
}
