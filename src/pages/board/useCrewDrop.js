import { useState, useCallback } from 'react'
import { addJobCrew, removeJobCrew } from '../../api/board'

export const CREW_MIME = 'application/x-jccs-crew'

// Shared drag-and-drop wiring for dropping an "on the clock" person onto a
// job card. Returns props to spread on the card + handlers for the crew
// chips already on it.
export function useCrewDrop(job, onCrew) {
  const [over, setOver] = useState(false)
  const [busy, setBusy] = useState(false)

  const onDragOver = useCallback((e) => {
    if (e.dataTransfer.types.includes(CREW_MIME)) {
      e.preventDefault()
      e.dataTransfer.dropEffect = 'copy'
      setOver(true)
    }
  }, [])

  const onDragLeave = useCallback(() => setOver(false), [])

  const onDrop = useCallback(
    async (e) => {
      setOver(false)
      const raw = e.dataTransfer.getData(CREW_MIME)
      if (!raw) return
      e.preventDefault()
      let payload
      try { payload = JSON.parse(raw) } catch { return }
      if (!payload?.id) return
      if (job.worker_ids?.includes(payload.id)) return
      setBusy(true)
      try {
        await addJobCrew(job.id, payload.id, payload.name)
        onCrew?.()
      } catch { /* board keeps polling; nothing else to do */ }
      setBusy(false)
    },
    [job.id, job.worker_ids, onCrew]
  )

  const removeCrew = useCallback(
    async (workerId) => {
      setBusy(true)
      try {
        await removeJobCrew(job.id, workerId)
        onCrew?.()
      } catch { /* noop */ }
      setBusy(false)
    },
    [job.id, onCrew]
  )

  return {
    dropProps: { onDragOver, onDragLeave, onDrop },
    over,
    busy,
    removeCrew,
  }
}
