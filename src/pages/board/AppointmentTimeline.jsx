import { useState, useEffect, useRef, useMemo } from 'react'
import { fmtDate, fmtTime, CONFIRM_BADGE, IMPORTANCE_BADGE } from './cards/helpers'
import { T } from './t'

const START_HOUR = 6
const END_HOUR = 20
const HOUR_PX = 58

const TZ = 'America/New_York'
const etParts = new Intl.DateTimeFormat('en-US', {
  timeZone: TZ, hour: 'numeric', minute: 'numeric', hour12: false,
})
function etHourDecimal(d) {
  const p = Object.fromEntries(etParts.formatToParts(d).map((x) => [x.type, x.value]))
  let h = Number(p.hour)
  if (h === 24) h = 0
  return h + Number(p.minute) / 60
}

// "2026-09-09 14:30:00" -> 14.5  (wall clock, no timezone math)
function wallHour(s) {
  if (!s) return null
  const t = String(s).split(' ')[1]
  if (!t) return null
  const [h, m] = t.split(':').map(Number)
  return h + (m || 0) / 60
}

// Simple lane packing so overlapping blocks sit side by side.
function assignLanes(items) {
  const laneEnds = []
  return items.map((it) => {
    let lane = laneEnds.findIndex((end) => end <= it._start + 0.001)
    if (lane === -1) { lane = laneEnds.length; laneEnds.push(0) }
    laneEnds[lane] = it._end
    return { ...it, _lane: lane }
  }).map((it, _, arr) => ({ ...it, _lanes: Math.max(...arr.map((x) => x._lane)) + 1 }))
}

function Block({ a }) {
  const confirm = CONFIRM_BADGE[a.confirm_state] ?? CONFIRM_BADGE.tentative
  const importance = IMPORTANCE_BADGE[a.board_importance]
  const canceled = a.confirm_state === 'canceled'
  const top = (a._start - START_HOUR) * HOUR_PX
  const height = Math.max(34, (a._end - a._start) * HOUR_PX - 4)
  const widthPct = 100 / a._lanes
  return (
    <div
      className={`ops-tl-block imp-${a.board_importance} ${canceled ? 'is-canceled' : ''}`}
      style={{ top, height, left: `calc(${a._lane * widthPct}% + 2px)`, width: `calc(${widthPct}% - 4px)` }}
      title={`${a.title} — ${fmtTime(a.start_datetime)}${a.end_datetime ? ` to ${fmtTime(a.end_datetime)}` : ''}`}
    >
      <div className="ops-tl-block__time">
        {fmtTime(a.start_datetime)}{a.end_datetime ? `–${fmtTime(a.end_datetime)}` : ''}
      </div>
      <div className="ops-tl-block__title">{a.title}</div>
      <div className="ops-tl-block__meta">
        {importance && <span className={`ops-badge ${importance.cls}`}>{importance.text}</span>}
        <span className={`ops-badge ${confirm.cls}`}>{confirm.text}</span>
        {a.location && <span className="ops-tl-block__loc">{a.location}</span>}
      </div>
    </div>
  )
}

export default function AppointmentTimeline({ appts }) {
  const [now, setNow] = useState(() => new Date())
  const scrollRef = useRef(null)

  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 30000)
    return () => clearInterval(id)
  }, [])

  const { timed, allDay, upcoming } = useMemo(() => {
    const timed = []
    const allDay = []
    const upcoming = []
    for (const a of appts) {
      if (!a.is_today) { upcoming.push(a); continue }
      if (a.is_all_day) { allDay.push(a); continue }
      const s = wallHour(a.start_datetime)
      if (s == null) { allDay.push(a); continue }
      const e = wallHour(a.end_datetime) ?? s + 1
      timed.push({ ...a, _start: Math.max(START_HOUR, Math.min(s, END_HOUR - 0.25)), _end: Math.min(END_HOUR, Math.max(e, s + 0.5)) })
    }
    timed.sort((x, y) => x._start - y._start)
    return { timed: assignLanes(timed), allDay, upcoming }
  }, [appts])

  const nowH = etHourDecimal(now)
  const showNow = nowH >= START_HOUR && nowH <= END_HOUR
  const nowTop = (nowH - START_HOUR) * HOUR_PX
  const nowHourBucket = Math.floor(nowH)

  useEffect(() => {
    if (!scrollRef.current || !showNow) return
    scrollRef.current.scrollTo({ top: Math.max(0, (nowHourBucket - START_HOUR) * HOUR_PX - 100), behavior: 'smooth' })
  }, [showNow, nowHourBucket]) // re-center each hour

  const hours = []
  for (let h = START_HOUR; h <= END_HOUR; h++) hours.push(h)
  const label = (h) => {
    const ap = h >= 12 ? 'p. m.' : 'a. m.'
    const h12 = h % 12 || 12
    return `${h12} ${ap}`
  }

  return (
    <div className="ops-tl">
      {allDay.length > 0 && (
        <div className="ops-tl-allday">
          {allDay.map((a) => (
            <span key={a.id} className={`ops-badge ${a.confirm_state === 'canceled' ? 'ops-badge--red' : 'ops-badge--slate'}`}>
              {a.title}
            </span>
          ))}
        </div>
      )}

      <div className="ops-tl-grid" ref={scrollRef}>
        <div className="ops-tl-inner" style={{ height: (END_HOUR - START_HOUR) * HOUR_PX + 8 }}>
          {hours.map((h) => (
            <div key={h} className="ops-tl-hour" style={{ top: (h - START_HOUR) * HOUR_PX }}>
              <span className="ops-tl-hour__label">{label(h)}</span>
            </div>
          ))}

          <div className="ops-tl-lane">
            {timed.length === 0 && (
              <p className="ops-tl-empty">{T.tlEmpty}</p>
            )}
            {timed.map((a) => <Block key={a.id} a={a} />)}

            {showNow && (
              <div className="ops-tl-now" style={{ top: nowTop }}>
                <span className="ops-tl-now__dot" />
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="ops-tl-upcoming">
        <p className="ops-tl-upcoming__head">{T.tlUpcoming}</p>
        {upcoming.length === 0 ? (
          <p className="ops-col__empty" style={{ padding: '0.5em' }}>{T.tlNothingElse}</p>
        ) : (
          <ul>
            {upcoming.map((a) => {
              const imp = IMPORTANCE_BADGE[a.board_importance]
              return (
                <li key={a.id} className={a.confirm_state === 'canceled' ? 'is-canceled' : ''}>
                  <span className="ops-tl-upcoming__when">
                    <b>{fmtDate(a.start_datetime)}</b> {a.is_all_day ? '' : fmtTime(a.start_datetime)}
                  </span>
                  <span className="ops-tl-upcoming__title">{a.title}</span>
                  {imp && <span className={`ops-badge ${imp.cls}`}>{imp.text}</span>}
                  {a.confirm_state === 'canceled' && <span className="ops-badge ops-badge--red">{T.tlCanceled}</span>}
                </li>
              )
            })}
          </ul>
        )}
      </div>
    </div>
  )
}
