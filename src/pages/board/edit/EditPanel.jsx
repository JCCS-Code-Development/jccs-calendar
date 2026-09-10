import { useState, useEffect, useCallback } from 'react'
import Button from '../../../components/ui/Button'
import Spinner from '../../../components/ui/Spinner'
import { getRefs, updateJob } from '../../../api/board'
import { fmtDateYear } from '../cards/helpers'
import { useBoardT } from '../t'
import JobEditForm from './JobEditForm'
import AppointmentEditForm from './AppointmentEditForm'

// Fecha de hoy en America/New_York, formato YYYY-MM-DD.
const etToday = () =>
  new Date().toLocaleDateString('en-CA', { timeZone: 'America/New_York' })

const FILTERS = [
  ['all', 'filterAll'],
  ['awaiting', 'filterAwaiting'],
  ['scheduled', 'filterScheduled'],
  ['completed', 'filterCompleted'],
  ['archived', 'filterArchived'],
]

function jobBucket(j) {
  if (j.archived_at) return 'archived'
  if (j.status === 'Completed' || j.schedule_status === 'completed') return 'completed'
  if (['confirmed', 'in_progress'].includes(j.schedule_status)) return 'scheduled'
  return 'awaiting'
}

export default function EditPanel({ onChanged, onClose }) {
  const T = useBoardT()
  const [refs, setRefs] = useState(null)
  const [err, setErr] = useState('')
  const [tab, setTab] = useState('jobs')
  const [filter, setFilter] = useState('all')
  const [q, setQ] = useState('')
  const [busyId, setBusyId] = useState(null)
  const [editingJob, setEditingJob] = useState(null)      // job object | 'new' | null
  const [editingAppt, setEditingAppt] = useState(null)

  const reload = useCallback(async () => {
    try {
      setRefs(await getRefs())
      setErr('')
    } catch {
      setErr(T.panelLoadError)
    }
  }, [T])

  useEffect(() => { reload() }, [reload])

  const afterSave = async () => {
    setEditingJob(null)
    setEditingAppt(null)
    await reload()
    onChanged?.()
  }

  // One-tap transition on a job — no full form.
  const quickAction = async (job, patch) => {
    setBusyId(job.id)
    setErr('')
    try {
      await updateJob(job.id, { ...patch, expected_updated_at: job.updated_at })
      await reload()
      onChanged?.()
    } catch {
      setErr(T.qaFailed)
    }
    setBusyId(null)
  }

  const term = q.trim().toLowerCase()
  const jobs = (refs?.jobs ?? [])
    .filter((j) => filter === 'all' || jobBucket(j) === filter)
    .filter((j) =>
      !term ||
      j.title.toLowerCase().includes(term) ||
      (j.client_name ?? '').toLowerCase().includes(term) ||
      (j.estimate_number ?? '').toLowerCase().includes(term)
    )
  const appts = (refs?.appointments ?? []).filter((a) =>
    !term ||
    a.title.toLowerCase().includes(term) ||
    (a.related_job ?? '').toLowerCase().includes(term)
  )

  return (
    <div className="fixed inset-0 z-50 bg-gray-50 overflow-y-auto">
      <div className="sticky top-0 z-10 bg-brand-900 text-white px-4 py-3 flex items-center justify-between gap-3">
        <div>
          <p className="text-base font-extrabold">{T.panelTitle}</p>
          <p className="text-xs text-brand-100/70">{T.panelHint}</p>
        </div>
        <Button variant="secondary" onClick={onClose}>{T.exitEditMode}</Button>
      </div>

      <div className="max-w-5xl mx-auto p-4">
        {err && (
          <div className="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 font-medium">
            {err}
          </div>
        )}

        <div className="flex flex-wrap items-center gap-3 mb-3">
          <div className="flex items-center bg-gray-200 rounded-xl p-1 gap-1">
            {[['jobs', T.tabJobs], ['appointments', T.tabAppointments]].map(([k, lbl]) => (
              <button
                key={k}
                onClick={() => setTab(k)}
                className={`px-4 py-1.5 text-sm font-semibold rounded-lg ${
                  tab === k ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-600'
                }`}
              >
                {lbl}
              </button>
            ))}
          </div>
          <input
            className="flex-1 min-w-[180px] rounded-xl border border-gray-300 px-4 py-2 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
            placeholder={T.searchPlaceholder}
            value={q}
            onChange={(e) => setQ(e.target.value)}
          />
          {tab === 'jobs' ? (
            <Button onClick={() => setEditingJob('new')}>{T.addJob}</Button>
          ) : (
            <Button onClick={() => setEditingAppt('new')}>{T.addAppointment}</Button>
          )}
        </div>

        {tab === 'jobs' && (
          <div className="flex flex-wrap gap-1.5 mb-4">
            {FILTERS.map(([k, lbl]) => (
              <button
                key={k}
                onClick={() => setFilter(k)}
                className={`px-3 py-1 text-xs font-semibold rounded-lg border ${
                  filter === k ? 'bg-brand-500 text-white border-brand-500' : 'bg-white text-gray-600 border-gray-300'
                }`}
              >
                {T[lbl]}
              </button>
            ))}
          </div>
        )}

        {!refs ? (
          <div className="flex justify-center py-20"><Spinner size="lg" className="text-brand-500" /></div>
        ) : tab === 'jobs' ? (
          <ul className="flex flex-col gap-2">
            {jobs.length === 0 && (
              <li className="text-sm text-gray-500 py-8 text-center">
                {T.noJobsMatch}{' '}
                <button className="text-brand-600 font-semibold" onClick={() => setEditingJob('new')}>{T.addFirstJob}</button>
              </li>
            )}
            {jobs.map((j) => {
              const busy = busyId === j.id
              const done = j.status === 'Completed' || j.schedule_status === 'completed'
              const poDone = ['received', 'approved'].includes(j.po_status)
              const isScheduled = ['confirmed', 'in_progress'].includes(j.schedule_status)
              return (
                <li
                  key={j.id}
                  className={`bg-white rounded-xl border border-gray-200 px-4 py-3 ${j.archived_at ? 'opacity-60' : ''}`}
                >
                  <div className="flex items-center justify-between gap-3">
                    <div className="min-w-0">
                      <p className="font-bold text-gray-900 truncate">
                        {j.title}
                        {j.archived_at && <span className="ml-2 text-xs font-semibold text-gray-500">{T.archived}</span>}
                      </p>
                      <p className="text-xs text-gray-500 truncate">
                        {j.client_name || T.noClient}
                        {j.estimate_number ? ` · ${T.est} #${j.estimate_number}` : ''}
                        {` · ${T.poShort}: ${T.po[j.po_status] ?? j.po_status}`}
                        {` · ${T.schedule[j.schedule_status] ?? j.schedule_status}`}
                        {j.projected_start ? ` · ${fmtDateYear(j.projected_start)}` : ''}
                      </p>
                    </div>
                    <Button variant="secondary" size="sm" onClick={() => setEditingJob(j)}>{T.editBtn}</Button>
                  </div>

                  {!j.archived_at && (
                    <div className="flex flex-wrap gap-1.5 mt-2">
                      {!poDone && (
                        <QuickBtn busy={busy} onClick={() => quickAction(j, { po_status: 'received', po_received_date: etToday() })}>
                          {T.qaPoReceived}
                        </QuickBtn>
                      )}
                      {!isScheduled && !done && (
                        <QuickBtn busy={busy} onClick={() => quickAction(j, { schedule_status: 'confirmed', projected_start: j.projected_start || etToday() })}>
                          {T.qaScheduleToday}
                        </QuickBtn>
                      )}
                      {!done && (
                        <QuickBtn busy={busy} onClick={() => quickAction(j, { status: 'Completed', schedule_status: 'completed' })}>
                          {T.qaComplete}
                        </QuickBtn>
                      )}
                      {j.priority !== 'urgent' && !done && (
                        <QuickBtn busy={busy} onClick={() => quickAction(j, { priority: 'urgent' })}>
                          {T.qaBumpUrgent}
                        </QuickBtn>
                      )}
                    </div>
                  )}
                  {j.archived_at && (
                    <div className="mt-2">
                      <QuickBtn busy={busy} onClick={() => quickAction(j, { archived_at: '' })}>{T.qaRestore}</QuickBtn>
                    </div>
                  )}
                </li>
              )
            })}
          </ul>
        ) : (
          <ul className="flex flex-col gap-2">
            {appts.length === 0 && (
              <li className="text-sm text-gray-500 py-8 text-center">
                {T.noApptsMatch}{' '}
                <button className="text-brand-600 font-semibold" onClick={() => setEditingAppt('new')}>{T.addFirstAppt}</button>
              </li>
            )}
            {appts.map((a) => (
              <li
                key={a.id}
                className={`bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center justify-between gap-3 ${a.confirm_state === 'canceled' ? 'opacity-60' : ''}`}
              >
                <div className="min-w-0">
                  <p className="font-bold text-gray-900 truncate">{a.title}</p>
                  <p className="text-xs text-gray-500 truncate">
                    {fmtDateYear(a.start_datetime)}
                    {` · ${T.confirm[a.confirm_state] ?? a.confirm_state}`}
                    {a.board_importance !== 'normal' ? ` · ${T.importance[a.board_importance] ?? a.board_importance}` : ''}
                    {a.related_job ? ` · ${a.related_job}` : ''}
                  </p>
                </div>
                <Button variant="secondary" size="sm" onClick={() => setEditingAppt(a)}>{T.editBtn}</Button>
              </li>
            ))}
          </ul>
        )}
      </div>

      {editingJob && (
        <JobEditForm
          job={editingJob === 'new' ? null : editingJob}
          refs={refs}
          onSaved={afterSave}
          onClose={() => setEditingJob(null)}
        />
      )}
      {editingAppt && (
        <AppointmentEditForm
          appt={editingAppt === 'new' ? null : editingAppt}
          refs={refs}
          onSaved={afterSave}
          onClose={() => setEditingAppt(null)}
        />
      )}
    </div>
  )
}

function QuickBtn({ busy, onClick, children }) {
  return (
    <button
      type="button"
      disabled={busy}
      onClick={onClick}
      className="px-2.5 py-1 text-xs font-semibold rounded-lg border border-brand-300 text-brand-700 bg-brand-50 hover:bg-brand-100 disabled:opacity-50"
    >
      {children}
    </button>
  )
}
