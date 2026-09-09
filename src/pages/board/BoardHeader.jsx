import { useState, useEffect } from 'react'
import { T } from './t'

const TZ = 'America/New_York'
const timeFmt = new Intl.DateTimeFormat('es-US', {
  timeZone: TZ, hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
})
const dateFmt = new Intl.DateTimeFormat('es-US', {
  timeZone: TZ, weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
})
const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1)

export default function BoardHeader() {
  const [now, setNow] = useState(() => new Date())

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
        style={{ filter: 'invert(1) brightness(10)' }}
      />
      <h1 className="ops-header__title">{T.boardTitle}</h1>
      <div className="ops-header__clock">
        <div className="ops-header__time">{timeFmt.format(now)}</div>
        <div className="ops-header__date">{cap(dateFmt.format(now))} · {T.tz}</div>
      </div>
    </header>
  )
}
