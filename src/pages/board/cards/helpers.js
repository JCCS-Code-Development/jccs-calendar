import { format, parseISO } from 'date-fns'
import { es } from 'date-fns/locale'
import { T } from '../t'

const L = { locale: es }

// Las fechas de la API vienen como "YYYY-MM-DD HH:MM:SS", ya en America/New_York.
export function fmtDate(s) {
  if (!s) return null
  try {
    return format(parseISO(String(s).slice(0, 10)), "EEE d 'de' MMM", L)
  } catch {
    return null
  }
}

export function fmtDateYear(s) {
  if (!s) return null
  try {
    return format(parseISO(String(s).slice(0, 10)), "d 'de' MMM yyyy", L)
  } catch {
    return null
  }
}

// "2026-09-09 14:30:00" -> "2:30 p. m."  (formato español, sin depender del locale de date-fns)
export function fmtTime(s) {
  if (!s) return null
  const t = String(s).split(/[ T]/)[1]
  if (!t) return null
  const [h, m] = t.split(':').map(Number)
  if (Number.isNaN(h)) return null
  return fmtClock(`${h}:${String(m || 0).padStart(2, '0')}`)
}

// "07:00" -> "7:00 a. m."
export function fmtClock(hhmm) {
  if (!hhmm) return null
  const [h, m] = hhmm.split(':').map(Number)
  const ampm = h >= 12 ? 'p. m.' : 'a. m.'
  const h12 = h % 12 || 12
  return `${h12}:${String(m).padStart(2, '0')} ${ampm}`
}

export const PO_BADGE = {
  none: { text: T.po.none, cls: 'ops-badge--amber' },
  requested: { text: T.po.requested, cls: 'ops-badge--orange' },
  received: { text: T.po.received, cls: 'ops-badge--blue' },
  approved: { text: T.po.approved, cls: 'ops-badge--green' },
}

export const PRIORITY_BADGE = {
  urgent: { text: T.priority.urgent, cls: 'ops-badge--red' },
  high: { text: T.priority.high, cls: 'ops-badge--orange' },
  normal: { text: T.priority.normal, cls: 'ops-badge--slate' },
  low: { text: T.priority.low, cls: 'ops-badge--gray' },
}

export const SCHEDULE_BADGE = {
  unscheduled: { text: T.schedule.unscheduled, cls: 'ops-badge--gray' },
  tentative: { text: T.schedule.tentative, cls: 'ops-badge--amber' },
  confirmed: { text: T.schedule.confirmed, cls: 'ops-badge--blue' },
  in_progress: { text: T.schedule.in_progress, cls: 'ops-badge--green' },
  completed: { text: T.schedule.completed, cls: 'ops-badge--gray' },
}

export const CONFIRM_BADGE = {
  tentative: { text: T.confirm.tentative, cls: 'ops-badge--amber' },
  confirmed: { text: T.confirm.confirmed, cls: 'ops-badge--green' },
  completed: { text: T.confirm.completed, cls: 'ops-badge--gray' },
  canceled: { text: T.confirm.canceled, cls: 'ops-badge--red' },
}

export const IMPORTANCE_BADGE = {
  critical: { text: T.importance.critical, cls: 'ops-badge--red' },
  important: { text: T.importance.important, cls: 'ops-badge--orange' },
  normal: null,
}
