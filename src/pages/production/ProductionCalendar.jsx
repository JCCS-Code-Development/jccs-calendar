import { useState, useEffect, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  format, startOfMonth, endOfMonth, startOfWeek, endOfWeek,
  addDays, addMonths, subMonths, addWeeks, subWeeks,
  isSameMonth, isToday, isSameDay, parseISO,
} from 'date-fns'
import { getJobs } from '../../api/jobs'
import { useAuth } from '../../hooks/useAuth'
import Spinner from '../../components/ui/Spinner'
import ProductionEntryCard from '../../components/production/ProductionEntryCard'
import ProductionDetailModal from '../../components/production/ProductionDetailModal'
import { getProductionStatus, PRODUCTION_STATUS_LABELS } from '../../utils/format'

const DAYS_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

function dueDate(job) {
  return job.projected_end ? parseISO(job.projected_end.slice(0, 10)) : null
}

function buildMonthDays(month) {
  const start = startOfWeek(startOfMonth(month))
  const end   = endOfWeek(endOfMonth(month))
  const days  = []
  let d = start
  while (d <= end) { days.push(d); d = addDays(d, 1) }
  return days
}

function buildWeekDays(anchor) {
  const start = startOfWeek(anchor)
  return Array.from({ length: 7 }, (_, i) => addDays(start, i))
}

function jobsOnDay(jobs, day) {
  return jobs.filter((j) => { const d = dueDate(j); return d && isSameDay(d, day) })
}

const STATUS_DOT = { completed: 'bg-green-500', overdue: 'bg-red-500', due_soon: 'bg-amber-500', upcoming: 'bg-blue-500' }

function ChevLeft() {
  return <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" /></svg>
}
function ChevRight() {
  return <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" /></svg>
}

// Month grid — same visual pattern as CalendarView's MonthView, but entries
// are jobs plotted on projected_end (the scheduled delivery date) instead
// of events on start_datetime.
function MonthGrid({ month, jobs, onSelect }) {
  const days = buildMonthDays(month)
  return (
    <div className="overflow-x-auto">
      <div className="min-w-[480px]">
        <div className="grid grid-cols-7 border-b border-gray-100">
          {DAYS_SHORT.map((d) => (
            <div key={d} className="py-2 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">{d}</div>
          ))}
        </div>
        <div className="grid grid-cols-7" style={{ minHeight: '60vh' }}>
          {days.map((day, i) => {
            const dayJobs = jobsOnDay(jobs, day)
            const inMonth = isSameMonth(day, month)
            const today   = isToday(day)
            return (
              <div key={i} className={`p-1.5 border-b border-r border-gray-100 flex flex-col ${!inMonth ? 'bg-gray-50/60' : ''} ${i % 7 === 6 ? 'border-r-0' : ''}`} style={{ minHeight: 96 }}>
                <div className={`w-7 h-7 flex items-center justify-center rounded-full text-xs font-semibold mb-1 shrink-0 ${today ? 'bg-brand-500 text-white' : inMonth ? 'text-gray-700' : 'text-gray-300'}`}>
                  {format(day, 'd')}
                </div>
                <div className="flex flex-col gap-1 flex-1 overflow-hidden">
                  {dayJobs.slice(0, 3).map((job) => {
                    const status = getProductionStatus(job)
                    return (
                      <button
                        key={job.id}
                        onClick={() => onSelect(job)}
                        className="flex items-center gap-1.5 text-left text-[10px] px-1.5 py-0.5 rounded-md truncate w-full font-medium bg-gray-50 hover:bg-gray-100 transition-colors"
                      >
                        <span className={`w-1.5 h-1.5 rounded-full shrink-0 ${STATUS_DOT[status]}`} />
                        <span className="truncate text-gray-700">{job.title}</span>
                      </button>
                    )
                  })}
                  {dayJobs.length > 3 && <span className="text-[10px] text-gray-400 pl-1">+{dayJobs.length - 3} more</span>}
                </div>
              </div>
            )
          })}
        </div>
      </div>
    </div>
  )
}

// Week strip — a production entry has no time-of-day, so this is a simple
// 7-day column layout (not WeekView's hour grid, which is built for
// time-slotted events and doesn't apply to date-only due dates).
function WeekStrip({ anchor, jobs, onSelect }) {
  const days = buildWeekDays(anchor)
  return (
    <div className="grid grid-cols-1 sm:grid-cols-7 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
      {days.map((day) => {
        const dayJobs = jobsOnDay(jobs, day)
        const today = isToday(day)
        return (
          <div key={day.toISOString()} className="p-3 min-h-[8rem]">
            <div className={`text-xs font-semibold mb-2 ${today ? 'text-brand-600' : 'text-gray-400'}`}>
              {format(day, 'EEE d')}
            </div>
            <div className="flex flex-col gap-2">
              {dayJobs.length === 0 && <span className="text-[11px] text-gray-300">—</span>}
              {dayJobs.map((job) => <ProductionEntryCard key={job.id} job={job} onSelect={onSelect} compact />)}
            </div>
          </div>
        )
      })}
    </div>
  )
}

export default function ProductionCalendar() {
  const navigate = useNavigate()
  const { canManageEvents } = useAuth()

  const [view, setView] = useState('month') // 'week' | 'month'
  const [anchor, setAnchor] = useState(new Date())
  const [jobs, setJobs] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [selected, setSelected] = useState(null)
  const [statusFilter, setStatusFilter] = useState('all')

  const load = useCallback(async () => {
    setLoading(true)
    setError('')
    try { setJobs(await getJobs()) }
    catch { setError('Could not load projects. Try again in a moment.') }
    setLoading(false)
  }, [])

  useEffect(() => { load() }, [load])

  const scheduled = jobs.filter((j) => j.projected_end)
  const filtered = statusFilter === 'all' ? scheduled : scheduled.filter((j) => getProductionStatus(j) === statusFilter)

  const isWeek = view === 'week'
  const prev  = () => isWeek ? setAnchor((a) => subWeeks(a, 1)) : setAnchor((a) => subMonths(a, 1))
  const next  = () => isWeek ? setAnchor((a) => addWeeks(a, 1)) : setAnchor((a) => addMonths(a, 1))
  const today = () => setAnchor(new Date())

  const headLabel = isWeek
    ? (() => { const s = startOfWeek(anchor); const e = endOfWeek(anchor); return `${format(s, 'MMM d')} – ${format(e, 'MMM d, yyyy')}` })()
    : format(anchor, 'MMMM yyyy')

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">Carpentry Production Calendar</h1>
          <p className="text-sm text-gray-500 mt-0.5">Scheduled completions and recommended production start dates</p>
        </div>
        {canManageEvents && (
          <button
            onClick={() => navigate('/jobs/create')}
            className="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-600 transition-colors shadow-sm shadow-brand-500/30"
          >
            + New Project
          </button>
        )}
      </div>

      {/* Status filter */}
      <div className="flex items-center bg-gray-100 rounded-xl p-1 gap-0.5 mb-4 w-fit">
        {['all', 'overdue', 'due_soon', 'upcoming', 'completed'].map((s) => (
          <button
            key={s}
            onClick={() => setStatusFilter(s)}
            className={`px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors ${statusFilter === s ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
          >
            {s === 'all' ? 'All' : PRODUCTION_STATUS_LABELS[s]}
          </button>
        ))}
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100 gap-3 flex-wrap">
          <div className="flex items-center gap-1">
            <button onClick={prev} className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500"><ChevLeft /></button>
            <button onClick={today} className="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Today</button>
            <button onClick={next} className="p-2 rounded-xl hover:bg-gray-100 transition-colors text-gray-500"><ChevRight /></button>
            <h2 className="text-sm font-bold text-gray-900 ml-1 whitespace-nowrap">{headLabel}</h2>
          </div>
          <div className="flex items-center bg-gray-100 rounded-xl p-1 gap-1">
            {[['week', 'Week'], ['month', 'Month']].map(([v, label]) => (
              <button
                key={v}
                onClick={() => setView(v)}
                className={`px-3 py-1 text-xs font-semibold rounded-lg transition-colors ${view === v ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
              >
                {label}
              </button>
            ))}
          </div>
        </div>

        {loading ? (
          <div className="flex justify-center items-center h-64"><Spinner size="lg" className="text-brand-500" /></div>
        ) : error ? (
          <div className="flex flex-col items-center justify-center h-64 gap-2 text-red-400">
            <p className="text-sm font-medium">{error}</p>
            <button onClick={load} className="text-sm text-brand-500 font-semibold hover:underline">Retry</button>
          </div>
        ) : filtered.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-64 gap-3 text-gray-400">
            <svg className="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <rect x="3" y="4" width="18" height="18" rx="2" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="16" y1="2" x2="16" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <p className="text-sm font-medium">No projects scheduled for this range.</p>
          </div>
        ) : view === 'week' ? (
          <WeekStrip anchor={anchor} jobs={filtered} onSelect={setSelected} />
        ) : (
          <MonthGrid month={anchor} jobs={filtered} onSelect={setSelected} />
        )}
      </div>

      <ProductionDetailModal job={selected} onClose={() => setSelected(null)} />
    </div>
  )
}
