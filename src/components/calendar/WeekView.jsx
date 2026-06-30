import { useEffect, useRef } from 'react'
import {
  format, startOfWeek, addDays, isSameDay, isToday,
  parseISO, getHours, getMinutes,
} from 'date-fns'

const DAYS_SHORT = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const HOUR_START = 6
const HOUR_END   = 22
const HOUR_PX    = 56

function parseDate(raw) {
  if (!raw) return null
  return parseISO(String(raw).replace(' ', 'T'))
}

function minutesFromMidnight(d) {
  return getHours(d) * 60 + getMinutes(d)
}

function isAllDay(event) {
  const raw = event.start_datetime ?? event.start ?? ''
  return !String(raw).includes('T') && !String(raw).includes(':')
}

export default function WeekView({ anchor, events, navigate, getColor }) {
  const scrollRef = useRef(null)
  const days      = Array.from({ length: 7 }, (_, i) => addDays(startOfWeek(anchor), i))
  const hours     = Array.from({ length: HOUR_END - HOUR_START }, (_, i) => HOUR_START + i)
  const totalH    = hours.length * HOUR_PX

  const colorOf = (ev) => {
    if (getColor) return getColor(ev)
    if (ev.source === 'outlook') return '#0078d4'
    return ev.color ?? ev.event_type_color ?? '#6b7280'
  }

  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollTop = (8 - HOUR_START) * HOUR_PX - 16
    }
  }, [anchor])

  const allDayEvents = events.filter(isAllDay)
  const timedEvents  = events.filter((e) => !isAllDay(e))

  return (
    <div className="flex flex-col">
      {/* Day headers */}
      <div className="grid border-b border-gray-100" style={{ gridTemplateColumns: '48px repeat(7, 1fr)' }}>
        <div className="border-r border-gray-100" />
        {days.map((day, i) => {
          const today = isToday(day)
          return (
            <div key={i} className={`py-2 text-center border-r border-gray-100 last:border-r-0 ${today ? 'bg-brand-50' : ''}`}>
              <p className="text-[10px] font-medium text-gray-400 uppercase">{DAYS_SHORT[i]}</p>
              <div className={`w-7 h-7 flex items-center justify-center rounded-full text-sm font-bold mx-auto mt-0.5 ${today ? 'bg-brand-500 text-white' : 'text-gray-700'}`}>
                {format(day, 'd')}
              </div>
            </div>
          )
        })}
      </div>

      {/* All-day strip */}
      {allDayEvents.length > 0 && (
        <div className="grid border-b border-gray-100 bg-gray-50/50" style={{ gridTemplateColumns: '48px repeat(7, 1fr)' }}>
          <div className="border-r border-gray-100 flex items-center justify-center">
            <span className="text-[9px] text-gray-400">all-day</span>
          </div>
          {days.map((day, i) => {
            const dayAll = allDayEvents.filter((e) => {
              const s = parseDate(e.start_datetime ?? e.start)
              return s && isSameDay(s, day)
            })
            return (
              <div key={i} className="p-0.5 min-h-[24px] border-r border-gray-100 last:border-r-0 flex flex-col gap-0.5">
                {dayAll.map((ev) => (
                  <div
                    key={ev.id}
                    onClick={() => navigate(`/events/${ev.id}/edit`)}
                    className="text-[10px] px-1 py-0.5 rounded text-white truncate font-medium cursor-pointer hover:opacity-90"
                    style={{ backgroundColor: colorOf(ev) }}
                  >
                    {ev.title}
                  </div>
                ))}
              </div>
            )
          })}
        </div>
      )}

      {/* Time grid */}
      <div ref={scrollRef} className="overflow-y-auto" style={{ maxHeight: '60vh' }}>
        <div className="relative grid" style={{ gridTemplateColumns: '48px repeat(7, 1fr)', height: totalH }}>
          {/* Hour labels */}
          <div className="relative border-r border-gray-100">
            {hours.map((h) => (
              <div key={h} className="absolute w-full flex items-start justify-end pr-2" style={{ top: (h - HOUR_START) * HOUR_PX - 8 }}>
                <span className="text-[10px] text-gray-400 font-medium">
                  {h === 12 ? '12pm' : h < 12 ? `${h}am` : `${h - 12}pm`}
                </span>
              </div>
            ))}
          </div>

          {/* Day columns */}
          {days.map((day, di) => {
            const today    = isToday(day)
            const dayTimed = timedEvents.filter((e) => {
              const s = parseDate(e.start_datetime ?? e.start)
              return s && isSameDay(s, day)
            })
            const nowMin = today ? minutesFromMidnight(new Date()) : null

            return (
              <div key={di} className={`relative border-r border-gray-100 last:border-r-0 ${today ? 'bg-brand-50/30' : ''}`}>
                {/* Hour lines */}
                {hours.map((h) => (
                  <div key={h} className="absolute w-full border-t border-gray-100" style={{ top: (h - HOUR_START) * HOUR_PX }} />
                ))}

                {/* Now indicator */}
                {nowMin !== null && nowMin >= HOUR_START * 60 && nowMin < HOUR_END * 60 && (
                  <div className="absolute left-0 right-0 z-10 flex items-center pointer-events-none"
                       style={{ top: (nowMin - HOUR_START * 60) / 60 * HOUR_PX }}>
                    <div className="w-2 h-2 rounded-full bg-brand-500 -ml-1 shrink-0" />
                    <div className="flex-1 h-[1.5px] bg-brand-500" />
                  </div>
                )}

                {/* Events */}
                {dayTimed.map((ev) => {
                  const start    = parseDate(ev.start_datetime ?? ev.start)
                  const end      = (ev.end_datetime ?? ev.end) ? parseDate(ev.end_datetime ?? ev.end) : null
                  const startMin = minutesFromMidnight(start)
                  const endMin   = end ? minutesFromMidnight(end) : startMin + 60
                  const top      = Math.max(0, (startMin - HOUR_START * 60) / 60 * HOUR_PX)
                  const height   = Math.max(22, (endMin - startMin) / 60 * HOUR_PX - 2)
                  const color    = colorOf(ev)

                  return (
                    <button
                      key={ev.id}
                      onClick={() => navigate(`/events/${ev.id}/edit`)}
                      className="absolute left-0.5 right-0.5 rounded-md px-1.5 py-0.5 text-left overflow-hidden z-[5] hover:opacity-90 transition-opacity"
                      style={{ top, height, backgroundColor: color, cursor: 'pointer' }}
                    >
                      <p className="text-[10px] font-semibold text-white leading-tight truncate">{ev.title}</p>
                      {height > 30 && (
                        <p className="text-[9px] text-white/75 leading-tight">{format(start, 'h:mm a')}</p>
                      )}
                      {height > 46 && ev.assigned_user?.name && (
                        <p className="text-[9px] text-white/60 leading-tight truncate">{ev.assigned_user.name}</p>
                      )}
                    </button>
                  )
                })}
              </div>
            )
          })}
        </div>
      </div>
    </div>
  )
}
