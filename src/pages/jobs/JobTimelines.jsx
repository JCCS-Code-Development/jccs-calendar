import { useState, useEffect, useCallback, useRef } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  format, addMonths, subMonths, startOfMonth, endOfMonth,
  differenceInCalendarDays, parseISO, isToday, eachMonthOfInterval,
  getDaysInMonth, isSameMonth, addDays, isWeekend,
} from 'date-fns'
import { getJobs, syncToFieldclock } from '../../api/jobs'
import { useAuth } from '../../hooks/useAuth'
import { useTranslation } from 'react-i18next'
import Spinner from '../../components/ui/Spinner'

const DAY_PX     = 28   // pixels per day column
const LEFT_W     = 256  // left panel width in px
const ROW_H      = 64   // px per job row

// ── date helpers ──────────────────────────────────────────────────────────────

function rangeMonths(start, end) {
  return eachMonthOfInterval({ start, end })
}

function daysInRange(rangeStart, rangeEnd) {
  return differenceInCalendarDays(rangeEnd, rangeStart) + 1
}

function dayOffset(rangeStart, date) {
  return differenceInCalendarDays(date, rangeStart)
}

function parseDate(s) {
  if (!s) return null
  return parseISO(s.slice(0, 10))
}

// ── sub-components ────────────────────────────────────────────────────────────

function StatusBadge({ status }) {
  const colors = {
    Active:    'bg-green-100 text-green-700',
    'On Hold': 'bg-amber-100 text-amber-700',
    Completed: 'bg-blue-100 text-blue-700',
    Cancelled: 'bg-gray-100 text-gray-500',
  }
  return (
    <span className={`inline-block text-[10px] font-semibold px-1.5 py-0.5 rounded-md ${colors[status] ?? 'bg-gray-100 text-gray-500'}`}>
      {status}
    </span>
  )
}

function GanttBar({ job, rangeStart, totalDays, navigate, canManage }) {
  const start = parseDate(job.projected_start)
  const end   = parseDate(job.projected_end)
  if (!start || !end) return null

  const left  = Math.max(0, dayOffset(rangeStart, start)) * DAY_PX
  const right = Math.min(totalDays, dayOffset(rangeStart, end) + 1) * DAY_PX
  const width = right - left
  if (width <= 0) return null   // outside current view

  const clippedLeft  = dayOffset(rangeStart, start) < 0
  const clippedRight = dayOffset(rangeStart, end) + 1 > totalDays

  return (
    <button
      onClick={() => canManage && navigate(`/jobs/${job.id}/edit`)}
      title={`${job.title}  •  ${format(start, 'MMM d')} – ${format(end, 'MMM d, yyyy')}`}
      className={`absolute top-1/2 -translate-y-1/2 flex items-center px-2 text-white text-xs font-semibold transition-opacity hover:opacity-90 select-none overflow-hidden ${canManage ? 'cursor-pointer' : 'cursor-default'}`}
      style={{
        left,
        width,
        height: ROW_H - 18,
        backgroundColor: job.color,
        borderRadius: `${clippedLeft ? 0 : 8}px ${clippedRight ? 0 : 8}px ${clippedRight ? 0 : 8}px ${clippedLeft ? 0 : 8}px`,
        boxShadow: `0 2px 8px ${job.color}55`,
      }}
    >
      <span className="truncate">{job.title}</span>
    </button>
  )
}

// ── main component ────────────────────────────────────────────────────────────

const RANGE_OPTIONS = [
  { label: '3 mo', months: 3 },
  { label: '6 mo', months: 6 },
  { label: '1 yr', months: 12 },
]

export default function JobTimelines() {
  const navigate    = useNavigate()
  const { t } = useTranslation()
  const { canManageEvents } = useAuth()

  const [jobs,      setJobs]      = useState([])
  const [loading,   setLoading]   = useState(true)
  const [syncing,   setSyncing]   = useState(false)
  const [syncMsg,   setSyncMsg]   = useState('')
  const [filter,    setFilter]    = useState('all')
  const [rangeMonthCount, setRangeMonthCount] = useState(3)
  const [viewStart, setViewStart] = useState(() => startOfMonth(new Date()))

  const scrollRef = useRef(null)

  const load = useCallback(async () => {
    setLoading(true)
    try { setJobs(await getJobs()) } catch {}
    setLoading(false)
  }, [])

  const handleSync = async () => {
    setSyncing(true)
    setSyncMsg('')
    try {
      const res = await syncToFieldclock()
      setSyncMsg(res.message ?? t('common.syncing'))
    } catch {
      setSyncMsg(t('jobs.syncFail'))
    }
    setSyncing(false)
    setTimeout(() => setSyncMsg(''), 5000)
  }

  useEffect(() => { load() }, [load])

  // Scroll to today on mount / range change
  useEffect(() => {
    if (!scrollRef.current) return
    const todayOff = dayOffset(viewStart, new Date())
    if (todayOff >= 0) {
      scrollRef.current.scrollLeft = Math.max(0, todayOff * DAY_PX - 120)
    }
  }, [viewStart, rangeMonthCount, loading])

  const viewEnd    = endOfMonth(addMonths(viewStart, rangeMonthCount - 1))
  const totalDays  = daysInRange(viewStart, viewEnd)
  const totalWidth = totalDays * DAY_PX
  const months     = rangeMonths(viewStart, viewEnd)

  const todayOff   = dayOffset(viewStart, new Date())
  const showToday  = todayOff >= 0 && todayOff < totalDays

  const filtered = jobs.filter((j) => filter === 'all' || j.status === filter)

  // Group by client for section headers
  const grouped = filtered.reduce((acc, job) => {
    const key = job.client_id
    if (!acc[key]) acc[key] = { client_name: job.client_name, jobs: [] }
    acc[key].jobs.push(job)
    return acc
  }, {})

  // Flat rows: client header + job rows
  const rows = []
  for (const group of Object.values(grouped)) {
    rows.push({ type: 'client', label: group.client_name })
    for (const job of group.jobs) rows.push({ type: 'job', job })
  }

  return (
    <div className="flex flex-col gap-4">
      {/* Page header */}
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">{t('jobs.title')}</h1>
          <p className="text-sm text-gray-500 mt-0.5">{t('jobs.subtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {/* Status filter */}
          <div className="flex items-center bg-gray-100 rounded-xl p-1 gap-0.5">
            {['all', 'Active', 'On Hold', 'Completed', 'Cancelled'].map((s) => (
              <button
                key={s}
                onClick={() => setFilter(s)}
                className={`px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors ${filter === s ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
              >
                {s === 'all' ? 'All' : s}
              </button>
            ))}
          </div>

          {canManageEvents && (
            <>
              <button
                onClick={handleSync}
                disabled={syncing}
                title="Push any un-synced calendar jobs into FieldClock"
                className="flex items-center gap-2 border border-brand-300 text-brand-600 px-3 py-2 rounded-xl text-sm font-semibold hover:bg-brand-50 transition-colors disabled:opacity-50"
              >
                <svg className={`w-4 h-4 ${syncing ? 'animate-spin' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M4 4v5h.582M20 20v-5h-.581M4.582 9A8 8 0 0119.418 15M19.419 9A8 8 0 014.583 15" />
                </svg>
                {syncing ? t('common.syncing') : t('common.syncFieldclock')}
              </button>
              <button
                onClick={() => navigate('/jobs/create')}
                className="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-600 transition-colors shadow-sm shadow-brand-500/30"
              >
                {t('jobs.newJob')}
              </button>
            </>
          )}
        </div>
      </div>

      {syncMsg && (
        <div className="px-4 py-2.5 rounded-xl bg-green-50 border border-green-200 text-sm text-green-700 font-medium">
          {syncMsg}
        </div>
      )}

      {/* Timeline card */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {/* Toolbar */}
        <div className="flex items-center gap-3 px-4 py-3 border-b border-gray-100 flex-wrap">
          {/* Nav */}
          <div className="flex items-center gap-1">
            <button onClick={() => setViewStart((d) => subMonths(d, 1))} className="p-2 rounded-xl hover:bg-gray-100 text-gray-500">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button onClick={() => setViewStart(startOfMonth(new Date()))} className="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">
              Today
            </button>
            <button onClick={() => setViewStart((d) => addMonths(d, 1))} className="p-2 rounded-xl hover:bg-gray-100 text-gray-500">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </button>
            <span className="text-sm font-bold text-gray-800 ml-1">
              {format(viewStart, 'MMM yyyy')} – {format(viewEnd, 'MMM yyyy')}
            </span>
          </div>

          {/* Range toggle */}
          <div className="flex items-center bg-gray-100 rounded-xl p-1 gap-1 ml-auto">
            {RANGE_OPTIONS.map(({ label, months }) => (
              <button
                key={months}
                onClick={() => setRangeMonthCount(months)}
                className={`px-3 py-1 text-xs font-semibold rounded-lg transition-colors ${rangeMonthCount === months ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
              >
                {label}
              </button>
            ))}
          </div>
        </div>

        {loading ? (
          <div className="flex items-center justify-center h-64">
            <Spinner size="lg" className="text-brand-500" />
          </div>
        ) : rows.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-64 gap-3 text-gray-400">
            <svg className="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="8" y1="2" x2="8" y2="6"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <p className="text-sm font-medium">{t('jobs.noJobs')}</p>
            {canManageEvents && (
              <button onClick={() => navigate('/jobs/create')} className="text-sm text-brand-500 font-semibold hover:underline">
                {t('jobs.newJob')}
              </button>
            )}
          </div>
        ) : (
          /* Gantt container */
          <div ref={scrollRef} className="overflow-x-auto select-none" style={{ maxHeight: '70vh', overflowY: 'auto' }}>
            <div style={{ minWidth: LEFT_W + totalWidth }}>

              {/* ── Sticky header ──────────────────────────────────── */}
              <div className="sticky top-0 z-20 bg-white border-b border-gray-100 flex">
                {/* Left corner */}
                <div className="sticky left-0 z-30 bg-white border-r border-gray-100 flex items-end pb-1 px-3"
                     style={{ width: LEFT_W, minWidth: LEFT_W }}>
                  <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Project</span>
                </div>

                {/* Month labels */}
                <div className="relative" style={{ width: totalWidth }}>
                  <div className="flex border-b border-gray-100">
                    {months.map((m) => {
                      const mStart   = isSameMonth(m, viewStart) ? viewStart : startOfMonth(m)
                      const mEnd     = isSameMonth(m, viewEnd)   ? viewEnd   : endOfMonth(m)
                      const mDays    = daysInRange(mStart, mEnd)
                      const mLeft    = dayOffset(viewStart, mStart) * DAY_PX
                      return (
                        <div
                          key={m.toISOString()}
                          className="flex-none border-r border-gray-100 px-2 py-1"
                          style={{ width: mDays * DAY_PX }}
                        >
                          <span className="text-xs font-bold text-gray-700">{format(m, 'MMM yyyy')}</span>
                        </div>
                      )
                    })}
                  </div>

                  {/* Day numbers (every 7 days) */}
                  <div className="relative h-6">
                    {Array.from({ length: totalDays }, (_, i) => addDays(viewStart, i))
                      .filter((d) => d.getDay() === 1)   // Mondays only
                      .map((d) => {
                        const off = dayOffset(viewStart, d)
                        return (
                          <div
                            key={d.toISOString()}
                            className="absolute top-0 flex items-center justify-start"
                            style={{ left: off * DAY_PX, width: 7 * DAY_PX }}
                          >
                            <span className={`text-[10px] pl-1 ${isToday(d) ? 'text-brand-500 font-bold' : 'text-gray-400'}`}>
                              {format(d, 'd')}
                            </span>
                          </div>
                        )
                      })}
                    {/* Today marker on header */}
                    {showToday && (
                      <div
                        className="absolute top-0 bottom-0 w-px bg-brand-500 opacity-70"
                        style={{ left: todayOff * DAY_PX + DAY_PX / 2 }}
                      />
                    )}
                  </div>
                </div>
              </div>

              {/* ── Rows ───────────────────────────────────────────── */}
              {rows.map((row, i) => {
                if (row.type === 'client') {
                  return (
                    <div key={`client-${i}`} className="flex sticky z-10 bg-gray-50 border-b border-gray-100" style={{ top: 72 }}>
                      <div className="sticky left-0 z-20 bg-gray-50 border-r border-gray-100 px-3 py-2 flex items-center gap-2" style={{ width: LEFT_W, minWidth: LEFT_W }}>
                        <span className="text-xs font-bold text-gray-600 uppercase tracking-wider truncate">{row.label}</span>
                      </div>
                      <div className="relative" style={{ width: totalWidth, height: 28 }}>
                        {/* Weekend shading */}
                        {Array.from({ length: totalDays }, (_, i) => addDays(viewStart, i))
                          .filter(isWeekend)
                          .map((d) => (
                            <div key={d.toISOString()} className="absolute top-0 bottom-0 bg-gray-100/40"
                                 style={{ left: dayOffset(viewStart, d) * DAY_PX, width: DAY_PX }} />
                          ))}
                        {/* Today line */}
                        {showToday && (
                          <div className="absolute top-0 bottom-0 w-px bg-brand-400 opacity-40"
                               style={{ left: todayOff * DAY_PX + DAY_PX / 2 }} />
                        )}
                      </div>
                    </div>
                  )
                }

                const { job } = row
                return (
                  <div key={`job-${job.id}`} className="flex border-b border-gray-100 hover:bg-gray-50/50 group">
                    {/* Left info panel */}
                    <div
                      className="sticky left-0 z-10 bg-white group-hover:bg-gray-50/50 border-r border-gray-100 px-3 flex flex-col justify-center gap-0.5"
                      style={{ width: LEFT_W, minWidth: LEFT_W, height: ROW_H }}
                    >
                      <div className="flex items-center gap-2">
                        <span
                          className="w-2.5 h-2.5 rounded-full shrink-0"
                          style={{ backgroundColor: job.color }}
                        />
                        <span className="text-xs font-bold text-gray-800 truncate">{job.title}</span>
                        <StatusBadge status={job.status} />
                      </div>
                      {job.estimate_number && (
                        <p className="text-[10px] text-gray-400 pl-4.5 truncate">#{job.estimate_number}</p>
                      )}
                      {job.address && (
                        <p className="text-[10px] text-gray-400 pl-4.5 truncate">{job.address}</p>
                      )}
                      {job.workers?.length > 0 && (
                        <p className="text-[10px] text-gray-400 pl-4.5 truncate">
                          {job.workers.map((w) => w.name).join(', ')}
                        </p>
                      )}
                    </div>

                    {/* Timeline lane */}
                    <div className="relative" style={{ width: totalWidth, height: ROW_H }}>
                      {/* Weekend shading */}
                      {Array.from({ length: totalDays }, (_, i) => addDays(viewStart, i))
                        .filter(isWeekend)
                        .map((d) => (
                          <div key={d.toISOString()} className="absolute top-0 bottom-0 bg-gray-50"
                               style={{ left: dayOffset(viewStart, d) * DAY_PX, width: DAY_PX }} />
                        ))}
                      {/* Today line */}
                      {showToday && (
                        <div className="absolute top-0 bottom-0 w-px bg-brand-400 opacity-30"
                             style={{ left: todayOff * DAY_PX + DAY_PX / 2 }} />
                      )}
                      {/* Bar */}
                      <GanttBar
                        job={job}
                        rangeStart={viewStart}
                        totalDays={totalDays}
                        navigate={navigate}
                        canManage={canManageEvents}
                      />
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        )}
      </div>

      {/* Color legend */}
      {!loading && jobs.length > 0 && (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
          <p className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Clients</p>
          <div className="flex flex-wrap gap-4">
            {[...new Map(jobs.map((j) => [j.client_id, { name: j.client_name, jobs: jobs.filter((x) => x.client_id === j.client_id) }])).values()]
              .map((c) => (
                <div key={c.name}>
                  <p className="text-xs font-semibold text-gray-700 mb-1">{c.name}</p>
                  <div className="flex gap-1.5 flex-wrap">
                    {c.jobs.map((j) => (
                      <div key={j.id} className="flex items-center gap-1">
                        <span className="w-3 h-3 rounded-sm shrink-0" style={{ backgroundColor: j.color }} />
                        <span className="text-[10px] text-gray-500">{j.title}</span>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
          </div>
        </div>
      )}
    </div>
  )
}
