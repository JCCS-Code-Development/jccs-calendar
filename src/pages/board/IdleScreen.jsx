import { useState, useEffect, useMemo } from 'react'
import { useBoardT } from './t'
import { boardLocale } from './lang'

const TZ = 'America/New_York'
const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1)

// Protector de pantalla: sólo el logo de JCCS y un reloj grande. Cualquier
// toque / tecla / movimiento lo cierra (lo maneja OpsBoard). El tablero sigue
// actualizándose por detrás, así que al despertar ya está al día.
export default function IdleScreen() {
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
    <div className="ops-idle" role="presentation">
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
      <p className="ops-idle__hint">{T.idleHint}</p>
    </div>
  )
}
