import { useState, useEffect, useRef, useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import './board.css'
import { getBoard } from '../../api/board'
import { useBoardStore } from '../../store/boardStore'
import { useAuthStore } from '../../store/authStore'
import BoardHeader from './BoardHeader'
import BoardFooter from './BoardFooter'
import BoardColumn from './BoardColumn'
import PersonnelColumn from './PersonnelColumn'
import AwaitingCard from './cards/AwaitingCard'
import ScheduledCard from './cards/ScheduledCard'
import AppointmentTimeline from './AppointmentTimeline'
import PinGate from './edit/PinGate'
import EditPanel from './edit/EditPanel'
import IdleScreen from './IdleScreen'
import { useBoardT } from './t'
import { boardLocale } from './lang'

const POLL_MS = 30000
// Minutos sin ninguna interacción antes de mostrar la pantalla de reposo.
const IDLE_MS = 4 * 60 * 1000

export default function OpsBoard() {
  const T = useBoardT()
  const syncFmt = new Intl.DateTimeFormat(boardLocale(), {
    timeZone: 'America/New_York', hour: 'numeric', minute: '2-digit', hour12: true,
  })
  const [data, setData] = useState(null)      // last SUCCESSFUL payload — never cleared on error
  const [lastSync, setLastSync] = useState(null)
  const [stale, setStale] = useState(false)
  const [firstError, setFirstError] = useState(false)
  const [online, setOnline] = useState(() => navigator.onLine)
  const [pinOpen, setPinOpen] = useState(false)
  const [idle, setIdle] = useState(false)

  const { editMode, enterEditMode, exitEditMode } = useBoardStore()
  // Only shown/available when the board was opened from inside the app (i.e.
  // there's a logged-in session). On the unattended TV there's no session,
  // so there's no visible way off the board.
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated)
  const navigate = useNavigate()
  const exitBoard = useCallback(() => navigate('/'), [navigate])
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

  // Refs so the idle effects don't re-subscribe on every 30s poll (which
  // would keep resetting the countdown and it'd never fire).
  const idleRef = useRef(idle)
  idleRef.current = idle
  const editModeRef = useRef(editMode)
  editModeRef.current = editMode
  const loadRef = useRef(load)
  loadRef.current = load

  // Count down to the idle screen. Any input (incl. cursor movement) while the
  // board is showing resets the timer. Runs once — never re-subscribes.
  useEffect(() => {
    let timer
    const reset = () => {
      clearTimeout(timer)
      timer = setTimeout(() => {
        if (!editModeRef.current) setIdle(true)
      }, IDLE_MS)
    }
    const onActivity = () => { if (!idleRef.current) reset() }
    const evs = ['pointermove', 'pointerdown', 'keydown', 'wheel', 'touchstart']
    evs.forEach((e) => window.addEventListener(e, onActivity, { passive: true }))
    reset()
    return () => {
      clearTimeout(timer)
      evs.forEach((e) => window.removeEventListener(e, onActivity))
    }
  }, [])

  // Dismiss the idle screen — only on a deliberate action. Cursor drift
  // (pointermove) is deliberately NOT here, so a jittery TV pointer can't
  // flicker it away. A grace window swallows the trailing click/pointer event
  // that some TV browsers fire right after the "Sleep" button press, which
  // would otherwise dismiss the screen the instant it appears.
  useEffect(() => {
    if (!idle) return
    const shownAt = Date.now()
    const dismiss = () => {
      if (Date.now() - shownAt < 600) return
      setIdle(false)
      loadRef.current()   // refresh immediately on wake
    }
    const evs = ['pointerdown', 'keydown', 'wheel', 'touchstart', 'click']
    evs.forEach((e) => window.addEventListener(e, dismiss, { passive: true }))
    return () => evs.forEach((e) => window.removeEventListener(e, dismiss))
  }, [idle])

  const showIdle = idle && !editMode && !pinOpen

  // Esc leaves the board — only for an in-app session, and not while a modal
  // (Edit Mode / PIN) is handling its own Esc.
  useEffect(() => {
    if (!isAuthenticated) return
    const onKey = (e) => {
      if (e.key === 'Escape' && !editMode && !pinOpen) exitBoard()
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [isAuthenticated, editMode, pinOpen, exitBoard])

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

      <div className="ops-cols ops-cols--4">
        <PersonnelColumn data={data?.clocked_in ?? null} />

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
        onRest={() => setIdle(true)}
        onExit={isAuthenticated ? exitBoard : null}
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
