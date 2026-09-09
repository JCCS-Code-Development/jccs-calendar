import { useState, useEffect } from 'react'
import { T } from './t'

const TZ = 'America/New_York'
const timeFmt = new Intl.DateTimeFormat('es-US', {
  timeZone: TZ, hour: 'numeric', minute: '2-digit', hour12: true,
})
const dateFmt = new Intl.DateTimeFormat('es-US', {
  timeZone: TZ, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
})
const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1)

// Protector de pantalla: sólo el logo de JCCS y un reloj grande. Cualquier
// toque / tecla / movimiento lo cierra (lo maneja OpsBoard). El tablero sigue
// actualizándose por detrás, así que al despertar ya está al día.
export default function IdleScreen() {
  const [now, setNow] = useState(() => new Date())

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
          style={{ filter: 'invert(1) brightness(10)' }}
        />
        <div className="ops-idle__time">{timeFmt.format(now)}</div>
        <div className="ops-idle__date">{cap(dateFmt.format(now))}</div>
      </div>
      <p className="ops-idle__hint">{T.idleHint}</p>
    </div>
  )
}
