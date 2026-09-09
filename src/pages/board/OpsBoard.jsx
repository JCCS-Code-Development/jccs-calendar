import { useState, useEffect, useRef, useCallback } from 'react'
import './board.css'
import { getBoard } from '../../api/board'
import { useBoardStore } from '../../store/boardStore'
import BoardHeader from './BoardHeader'
import BoardFooter from './BoardFooter'
import BoardColumn from './BoardColumn'
import PersonnelBar from './PersonnelBar'
import AwaitingCard from './cards/AwaitingCard'
import ScheduledCard from './cards/ScheduledCard'
import AppointmentTimeline from './AppointmentTimeline'
import PinGate from './edit/PinGate'
import EditPanel from './edit/EditPanel'
import IdleScreen from './IdleScreen'
import { T } from './t'

const POLL_MS = 30000
// Minutos sin ninguna interacción antes de mostrar la pantalla de reposo.
const IDLE_MS = 4 * 60 * 1000

const syncFmt = new Intl.DateTimeFormat('es-US', {
  timeZone: 'America/New_York', hour: 'numeric', minute: '2-digit', hour12: true,
})

export default function OpsBoard() {
  const [data, setData] = useState(null)      // last SUCCESSFUL payload — never cleared on error
  const [lastSync, setLastSync] = useState(null)
  const [stale, setStale] = useState(false)
  const [firstError, setFirstError] = useState(false)
  const [online, setOnline] = useState(() => navigator.onLine)
  const [pinOpen, setPinOpen] = useState(false)
  const [idle, setIdle] = useState(false)

  const { editMode, enterEditMode, exitEditMode } = useBoardStore()
  const abortRef = useRef(null)

  const load = useCallback(async () => {
    abortRef.current?.abort()
    const ac = new AbortController()
    abortRef.current = ac
    try {
      const fresh = await getBoard(ac.signal)
      setData(fresh)
      setFirstError(false)
      // Guard against a stale response served by any cache layer: trust the
      // server's own clock, not the fact that the request resolved.
      const serverAge = Date.now() - new Date(fresh.server_time).getTime()
      if (Number.isFinite(serverAge) && serverAge > 90000) {
        setStale(true)
        setLastSync(new Date(fresh.server_time))
      } else {
        setStale(false)
        setLastSync(new Date())
      }
    } catch (err) {
      if (ac.signal.aborted || err?.name === 'CanceledError') return
      // Keep whatever we last had on screen. Just flag it as stale.
      setStale(true)
      if (!data) setFirstError(true)
    }
  }, [data])

  useEffect(() => {
    load()
    const id = setInterval(load, POLL_MS)
    const onVis = () => { if (document.visibilityState === 'visible') load() }
    document.addEventListener('visibilitychange', onVis)
    return () => { clearInterval(id); document.removeEventListener('visibilitychange', onVis) }
  }, [load])

  useEffect(() => {
    const up = () => { setOnline(true); load() }
    const down = () => { setOnline(false); setStale(true) }
    window.addEventListener('online', up)
    window.addEventListener('offline', down)
    return () => { window.removeEventListener('online', up); window.removeEventListener('offline', down) }
  }, [load])

  // Pantalla de reposo: aparece tras IDLE_MS sin interacción; cualquier
  // toque/tecla/movimiento la cierra. Se desactiva en Modo Edición.
  useEffect(() => {
    let timer
    const arm = () => {
      clearTimeout(timer)
      if (!editMode) timer = setTimeout(() => setIdle(true), IDLE_MS)
    }
    const wake = () => {
      setIdle((was) => {
        if (was) load()          // al despertar, refresca de inmediato
        return false
      })
      arm()
    }
    const events = ['pointerdown', 'pointermove', 'keydown', 'wheel', 'touchstart']
    events.forEach((e) => window.addEventListener(e, wake, { passive: true }))
    arm()
    return () => {
      clearTimeout(timer)
      events.forEach((e) => window.removeEventListener(e, wake))
    }
  }, [editMode, load])

  const showIdle = idle && !editMode && !pinOpen

  const awaiting = data?.awaiting ?? []
  const scheduled = data?.scheduled ?? []
  const appointments = data?.appointments ?? []

  return (
    <div className="ops-board">
      <BoardHeader />

      {(stale || !online) && data && (
        <div className="ops-stale-banner">
          {online
            ? T.staleOnline(lastSync ? syncFmt.format(lastSync) : T.earlier)
            : T.staleOffline(lastSync ? syncFmt.format(lastSync) : T.earlier)}
        </div>
      )}

      <PersonnelBar data={data?.clocked_in ?? null} />

      <div className="ops-cols">
        <BoardColumn
          variant="awaiting"
          title={T.colAwaiting}
          count={awaiting.length}
          items={awaiting}
          renderCard={(job) => <AwaitingCard key={job.id} job={job} onCrew={load} />}
          emptyText={firstError ? T.cantConnect : T.emptyAwaiting}
        />
        <BoardColumn
          variant="scheduled"
          title={T.colScheduled}
          count={scheduled.length}
          items={scheduled}
          renderCard={(job) => <ScheduledCard key={job.id} job={job} onCrew={load} />}
          emptyText={firstError ? T.cantConnect : T.emptyScheduled}
        />

        <section className="ops-col" aria-label={T.colAppointments}>
          <header className="ops-col__head ops-col__head--appts">
            <span>{T.colAppointments}</span>
            <span className="ops-col__count">{appointments.length}</span>
          </header>
          {firstError && appointments.length === 0 ? (
            <div className="ops-col__body"><p className="ops-col__empty">{T.cantConnect}</p></div>
          ) : appointments.length === 0 ? (
            <div className="ops-col__body"><p className="ops-col__empty">{T.emptyAppointments}</p></div>
          ) : (
            <AppointmentTimeline appts={appointments} />
          )}
        </section>
      </div>

      <BoardFooter
        lastSync={lastSync}
        online={online}
        editMode={editMode}
        onEnterEdit={() => setPinOpen(true)}
        onExitEdit={exitEditMode}
        onRest={() => requestAnimationFrame(() => setIdle(true))}
      />

      {pinOpen && (
        <PinGate
          onClose={() => setPinOpen(false)}
          onUnlocked={(pin) => { enterEditMode(pin); setPinOpen(false) }}
        />
      )}

      {editMode && <EditPanel onChanged={load} onClose={exitEditMode} />}

      {showIdle && <IdleScreen />}
    </div>
  )
}
