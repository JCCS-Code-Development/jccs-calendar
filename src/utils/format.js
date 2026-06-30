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
