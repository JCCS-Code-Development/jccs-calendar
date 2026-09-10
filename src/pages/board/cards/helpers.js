import { format, parseISO } from 'date-fns'
import { boardT } from '../t'

// Las fechas de la API vienen como "YYYY-MM-DD HH:MM:SS", ya en America/New_York.
// El locale de date-fns lo fija i18n.js según el idioma de la app, así que
// no se pasa explícitamente aquí.
export function fmtDate(s) {
  if (!s) return null
  try {
    return format(parseISO(String(s).slice(0, 10)), 'EEE, MMM d')
  } catch {
    return null
  }
}

export function fmtDateYear(s) {
  if (!s) return null
  try {
    return format(parseISO(String(s).slice(0, 10)), 'MMM d, yyyy')
  } catch {
    return null
  }
}

// "2026-09-09 14:30:00" -> "2:30 PM" / "2:30 p. m." según el idioma.
export function fmtTime(s) {
  if (!s) return null
  const t = String(s).split(/[ T]/)[1]
  if (!t) return null
  const [h, m] = t.split(':').map(Number)
  if (Number.isNaN(h)) return null
  return fmtClock(`${h}:${String(m || 0).padStart(2, '0')}`)
}

// "07:00" -> "7:00 AM" / "7:00 a. m."
export function fmtClock(hhmm) {
  if (!hhmm) return null
  const [h, m] = hhmm.split(':').map(Number)
  const ap = boardT().amPm
  const ampm = h >= 12 ? ap.pm : ap.am
  const h12 = h % 12 || 12
  return `${h12}:${String(m).padStart(2, '0')} ${ampm}`
}

// ── Insignias — se construyen con el diccionario activo (T de useBoardT()) ──
export const poBadge = (T) => ({
  none: { text: T.po.none, cls: 'ops-badge--amber' },
  requested: { text: T.po.requested, cls: 'ops-badge--orange' },
  received: { text: T.po.received, cls: 'ops-badge--blue' },
  approved: { text: T.po.approved, cls: 'ops-badge--green' },
})

export const priorityBadge = (T) => ({
  urgent: { text: T.priority.urgent, cls: 'ops-badge--red' },
  high: { text: T.priority.high, cls: 'ops-badge--orange' },
  normal: { text: T.priority.normal, cls: 'ops-badge--slate' },
  low: { text: T.priority.low, cls: 'ops-badge--gray' },
})

export const scheduleBadge = (T) => ({
  unscheduled: { text: T.schedule.unscheduled, cls: 'ops-badge--gray' },
  tentative: { text: T.schedule.tentative, cls: 'ops-badge--amber' },
  confirmed: { text: T.schedule.confirmed, cls: 'ops-badge--blue' },
  in_progress: { text: T.schedule.in_progress, cls: 'ops-badge--green' },
  completed: { text: T.schedule.completed, cls: 'ops-badge--gray' },
})

export const confirmBadge = (T) => ({
  tentative: { text: T.confirm.tentative, cls: 'ops-badge--amber' },
  confirmed: { text: T.confirm.confirmed, cls: 'ops-badge--green' },
  completed: { text: T.confirm.completed, cls: 'ops-badge--gray' },
  canceled: { text: T.confirm.canceled, cls: 'ops-badge--red' },
})

export const importanceBadge = (T) => ({
  critical: { text: T.importance.critical, cls: 'ops-badge--red' },
  important: { text: T.importance.important, cls: 'ops-badge--orange' },
  normal: null,
})
