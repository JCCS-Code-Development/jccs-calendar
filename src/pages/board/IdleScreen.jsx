import { useState, useEffect, useMemo } from 'react'
import { useBoardT } from './t'
import { boardLocale } from './lang'

const TZ = 'America/New_York'
const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1)

// Protector de pantalla: sólo el logo de JCCS y un reloj grande. Se cierra
// únicamente con el botón "Salir de reposo" (o el mismo botón del pie) — nada
// de "toca en cualquier lado", que en la TV lo prendía y apagaba solo. El
// tablero sigue actualizándose por detrás.
export default function IdleScreen({ onWake }) {
  const T = useBoardT()
  const [now, setNow] = useState(() => new Date())

  const { timeFmt, dateFmt } = useMemo(() => {
    const loc = boardLocale()
    return {
      timeFmt: new Intl.DateTimeFormat(loc, { timeZone: TZ, hour: 'numeric', minute: '2-digit', hour12: true }),
      dateFmt: new Intl.DateTimeFormat(loc, { timeZone: TZ, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }),
    }
  }, [T])

  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(id)
  }, [])

  return (
    <div className="ops-idle">
      <div className="ops-idle__drift">
        <img
          className="ops-idle__logo"
          src="/jccs-logo.jpg"
          alt="JCCS Services"
          style={{ filter: 'brightness(0) invert(1)' }}
        />
        <div className="ops-idle__time">{timeFmt.format(now)}</div>
        <div className="ops-idle__date">{cap(dateFmt.format(now))}</div>
      </div>
      <button type="button" className="ops-idle__wake" onClick={onWake}>
        {T.wakeBtn}
      </button>
    </div>
  )
}
