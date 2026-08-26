import { format, parseISO, isToday, isTomorrow, isThisWeek, isThisMonth } from 'date-fns'

export function formatDateTime(dt) {
  if (!dt) return '—'
  return format(parseISO(dt.replace(' ', 'T')), 'MMM d, yyyy h:mm a')
}

export function formatDate(dt) {
  if (!dt) return '—'
  return format(parseISO(dt.replace(' ', 'T')), 'MMM d, yyyy')
}

export function formatTime(dt) {
  if (!dt) return ''
  return format(parseISO(dt.replace(' ', 'T')), 'h:mm a')
}

export function groupEventsByUserAndDate(events) {
  const groups = {}
  for (const event of events) {
    const userName = event.assigned_user?.name ?? 'Unassigned'
    if (!groups[userName]) groups[userName] = {}

    const d = parseISO((event.start_datetime ?? '').replace(' ', 'T'))
    let section
    if (isToday(d)) section = 'Today'
    else if (isTomorrow(d)) section = 'Tomorrow'
    else if (isThisWeek(d)) section = 'This Week'
    else if (isThisMonth(d)) section = 'This Month'
    else section = 'Upcoming'

    if (!groups[userName][section]) groups[userName][section] = {}

    const dayLabel = format(d, 'EEEE, MMM d, yyyy')
    if (!groups[userName][section][dayLabel]) groups[userName][section][dayLabel] = []
    groups[userName][section][dayLabel].push(event)
  }
  return groups
}

// ── Carpentry Production Calendar ───────────────────────────────────────────

// Recommended production start = scheduled due date minus the carpentry
// production lead time. Always derived, never stored (see api/schema.sql's
// comment on jobs.lead_time_days) — recompute whenever projected_end or
// lead_time_days changes rather than persisting a start date that could drift.
export function getRecommendedStart(job) {
  if (!job.projected_end || !job.lead_time_days) return null
  const due = parseISO(job.projected_end.slice(0, 10))
  const start = new Date(due)
  start.setDate(start.getDate() - job.lead_time_days)
  return start
}

// 'completed' | 'overdue' | 'due_soon' | 'upcoming' — drives ProductionEntryCard's badge.
export function getProductionStatus(job) {
  if (job.status === 'Completed') return 'completed'
  if (!job.projected_end) return 'upcoming'

  const due = parseISO(job.projected_end.slice(0, 10))
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  due.setHours(0, 0, 0, 0)

  if (due < today) return 'overdue'

  // "Due soon" once inside the recommended production window, so crews see
  // it as soon as production should be starting — falls back to a flat
  // 3-day threshold when no lead time is set.
  const soonThreshold = job.lead_time_days
    ? new Date(due).setDate(due.getDate() - job.lead_time_days)
    : new Date(due).setDate(due.getDate() - 3)
  if (today.getTime() >= soonThreshold) return 'due_soon'

  return 'upcoming'
}

export const PRODUCTION_STATUS_LABELS = {
  completed: 'Completed',
  overdue:   'Overdue',
  due_soon:  'Due Soon',
  upcoming:  'Upcoming',
}

export const DATE_RANGES = [
  { value: 'today_tomorrow', label: 'Today & Tomorrow' },
  { value: 'today', label: 'Today' },
  { value: 'tomorrow', label: 'Tomorrow' },
  { value: 'this_week', label: 'This Week' },
  { value: 'this_month', label: 'This Month' },
  { value: 'rest', label: 'Future' },
  { value: 'past', label: 'Past' },
  { value: 'today_forward', label: 'Current Week Forward' },
]
