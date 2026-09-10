import { useState, useEffect, useMemo } from 'react'
import { useBoardT } from './t'
import { boardLocale } from './lang'

const TZ = 'America/New_York'
const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1)

export default function BoardHeader() {
  const T = useBoardT()
  const [now, setNow] = useState(() => new Date())

  const { timeFmt, dateFmt } = useMemo(() => {
    const loc = boardLocale()
    return {
      timeFmt: new Intl.DateTimeFormat(loc, { timeZone: TZ, hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true }),
      dateFmt: new Intl.DateTimeFormat(loc, { timeZone: TZ, weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }),
    }
  }, [T])

  useEffect(() => {
    // Avanza cada segundo; sin red, sin re-render del resto del tablero.
    const id = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(id)
  }, [])

  return (
    <header className="ops-header">
      <img
        className="ops-header__logo"
        src="/jccs-logo.jpg"
        alt="JCCS Services"
        style={{ filter: 'brightness(0) invert(1)' }}
      />
      <h1 className="ops-header__title">{T.boardTitle}</h1>
      <div className="ops-header__clock">
        <div className="ops-header__time">{timeFmt.format(now)}</div>
        <div className="ops-header__date">{cap(dateFmt.format(now))} · {T.tz}</div>
      </div>
    </header>
  )
}
