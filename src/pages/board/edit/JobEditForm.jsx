import { useState } from 'react'
import Modal from '../../../components/ui/Modal'
import Input from '../../../components/ui/Input'
import Select from '../../../components/ui/Select'
import Button from '../../../components/ui/Button'
import { createJob, updateJob, archiveJob } from '../../../api/board'
import { T } from '../t'

const FIELD = 'w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100'

function fromJob(job) {
  return {
    title: job?.title ?? '',
    client_id: job?.client_id ? String(job.client_id) : '',
    client_name: '',
    address: job?.address ?? '',
    estimate_number: job?.estimate_number ?? '',
    priority: job?.priority ?? 'normal',
    po_status: job?.po_status ?? 'none',
    po_number: job?.po_number ?? '',
    po_received_date: job?.po_received_date ?? '',
    schedule_status: job?.schedule_status ?? 'unscheduled',
    projected_start: job?.projected_start ?? '',
    scheduled_start_time: job?.scheduled_start_time ?? '',
    projected_end: job?.projected_end ?? '',
    next_action_by: job?.next_action_by ?? '',
    board_notes: job?.board_notes ?? '',
    status: job?.status ?? 'Active',
    worker_ids: job?.worker_ids ?? [],
  }
}

export default function JobEditForm({ job, refs, onSaved, onClose }) {
  const isEdit = !!job
  const [f, setF] = useState(() => fromJob(job))
  const [newClient, setNewClient] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [conflict, setConflict] = useState(null)

  const set = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.value }))
  const toggleWorker = (id) =>
    setF((s) => ({
      ...s,
      worker_ids: s.worker_ids.includes(id)
        ? s.worker_ids.filter((x) => x !== id)
        : [...s.worker_ids, id],
    }))

  const submit = async (e) => {
    e.preventDefault()
    if (!f.title.trim()) { setError(T.jfNameRequired); return }
    setBusy(true); setError(''); setConflict(null)

    const payload = {
      title: f.title.trim(),
      address: f.address || null,
      estimate_number: f.estimate_number || null,
      priority: f.priority,
      po_status: f.po_status,
      po_number: f.po_number || null,
      po_received_date: f.po_received_date || null,
      schedule_status: f.schedule_status,
      projected_start: f.projected_start || null,
      scheduled_start_time: f.scheduled_start_time || null,
      projected_end: f.projected_end || null,
      next_action_by: f.next_action_by || null,
      board_notes: f.board_notes || null,
      status: f.status,
      worker_ids: f.worker_ids,
    }
    if (newClient) payload.client_name = f.client_name.trim()
    else payload.client_id = f.client_id ? Number(f.client_id) : null

    try {
      if (isEdit) {
        payload.expected_updated_at = job.updated_at
        await updateJob(job.id, payload)
      } else {
        await createJob(payload)
      }
      onSaved()
    } catch (err) {
      if (err?.response?.status === 409) {
        setConflict(err.response.data.current)
        setError(T.jfConflict)
      } else {
        setError(err?.response?.data?.error ?? T.jfSaveError)
      }
      setBusy(false)
    }
  }

  const loadLatest = () => {
    setF(fromJob(conflict))
    setConflict(null)
    setError('')
    job.updated_at = conflict.updated_at
  }

  const doArchive = async () => {
    if (!window.confirm(T.jfArchiveConfirm(job.title))) return
    setBusy(true)
    try {
      await archiveJob(job.id, job.updated_at)
      onSaved()
    } catch (err) {
      setError(err?.response?.data?.error ?? T.jfArchiveError)
      setBusy(false)
    }
  }

  return (
    <Modal isOpen onClose={onClose} title={isEdit ? T.jfEditTitle : T.jfAddTitle} size="2xl">
      <form onSubmit={submit} className="flex flex-col gap-4">
        {error && (
          <div className="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 font-medium">
            {error}
            {conflict && (
              <button type="button" onClick={loadLatest} className="ml-2 underline font-semibold">
                {T.jfLoadLatest}
              </button>
            )}
          </div>
        )}

        <Input label={T.jfName} value={f.title} onChange={set('title')} autoFocus />

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.jfClient}</label>
            {newClient ? (
              <input className={FIELD} placeholder={T.jfNewClientName} value={f.client_name} onChange={set('client_name')} />
            ) : (
              <select className={FIELD} value={f.client_id} onChange={set('client_id')}>
                <option value="">{T.jfNone}</option>
                {refs.clients.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            )}
            <button
              type="button"
              className="text-xs text-brand-600 font-semibold self-start mt-0.5"
              onClick={() => setNewClient((v) => !v)}
            >
              {newClient ? T.jfPickExisting : T.jfNewClient}
            </button>
          </div>
          <Input label={T.jfLocation} value={f.address} onChange={set('address')} />
        </div>

        <div className="grid sm:grid-cols-3 gap-4">
          <Input label={T.jfEstimate} value={f.estimate_number} onChange={set('estimate_number')} />
          <Select label={T.jfPriority} value={f.priority} onChange={set('priority')}>
            <option value="low">{T.priority.low}</option>
            <option value="normal">{T.priority.normal}</option>
            <option value="high">{T.priority.high}</option>
            <option value="urgent">{T.priority.urgent}</option>
          </Select>
          <Select label={T.jfOverallStatus} value={f.status} onChange={set('status')}>
            <option value="Active">{T.jfStatusActive}</option>
            <option value="On Hold">{T.jfStatusOnHold}</option>
            <option value="Completed">{T.jfStatusCompleted}</option>
            <option value="Cancelled">{T.jfStatusCancelled}</option>
          </Select>
        </div>

        <fieldset className="border border-gray-200 rounded-xl p-4 grid sm:grid-cols-3 gap-4">
          <legend className="text-xs font-bold text-gray-500 uppercase tracking-wide px-1">{T.jfPO}</legend>
          <Select label={T.jfPOStatus} value={f.po_status} onChange={set('po_status')}>
            <option value="none">{T.jfPONone}</option>
            <option value="requested">{T.jfPORequested}</option>
            <option value="received">{T.jfPOReceived}</option>
            <option value="approved">{T.jfPOApproved}</option>
          </Select>
          <Input label={T.jfPONumber} value={f.po_number} onChange={set('po_number')} />
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.jfPOReceivedDate}</label>
            <input type="date" className={FIELD} value={f.po_received_date ?? ''} onChange={set('po_received_date')} />
          </div>
        </fieldset>

        <fieldset className="border border-gray-200 rounded-xl p-4 grid sm:grid-cols-2 gap-4">
          <legend className="text-xs font-bold text-gray-500 uppercase tracking-wide px-1">{T.jfSchedule}</legend>
          <Select label={T.jfScheduleStatus} value={f.schedule_status} onChange={set('schedule_status')}>
            <option value="unscheduled">{T.jfSchUnscheduled}</option>
            <option value="tentative">{T.jfSchTentative}</option>
            <option value="confirmed">{T.jfSchConfirmed}</option>
            <option value="in_progress">{T.jfSchInProgress}</option>
            <option value="completed">{T.jfSchCompleted}</option>
          </Select>
          <div />
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.jfSchedDate}</label>
            <input type="date" className={FIELD} value={f.projected_start ?? ''} onChange={set('projected_start')} />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.jfStartTime}</label>
            <input type="time" className={FIELD} value={f.scheduled_start_time ?? ''} onChange={set('scheduled_start_time')} />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.jfExpectedCompletion}</label>
            <input type="date" className={FIELD} value={f.projected_end ?? ''} onChange={set('projected_end')} />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.jfCrew}</label>
            <div className="flex flex-wrap gap-2">
              {refs.staff.map((s) => (
                <label key={s.id} className={`text-sm px-2.5 py-1 rounded-lg border cursor-pointer ${f.worker_ids.includes(s.id) ? 'bg-brand-50 border-brand-400 text-brand-700' : 'border-gray-300 text-gray-600'}`}>
                  <input type="checkbox" className="sr-only" checked={f.worker_ids.includes(s.id)} onChange={() => toggleWorker(s.id)} />
                  {s.name}
                </label>
              ))}
            </div>
          </div>
        </fieldset>

        <Input label={T.jfNextAction} value={f.next_action_by} onChange={set('next_action_by')} />
        <div className="flex flex-col gap-1">
          <label className="text-sm font-medium text-gray-700">{T.jfNotes}</label>
          <textarea className={FIELD} rows={3} value={f.board_notes} onChange={set('board_notes')} />
        </div>

        <div className="flex items-center justify-between gap-2 pt-2">
          {isEdit ? (
            <Button type="button" variant="danger" onClick={doArchive} disabled={busy}>{T.jfArchive}</Button>
          ) : <span />}
          <div className="flex gap-2">
            <Button type="button" variant="secondary" onClick={onClose}>{T.cancel}</Button>
            <Button type="submit" loading={busy}>{isEdit ? T.jfSave : T.jfAdd}</Button>
          </div>
        </div>
      </form>
    </Modal>
  )
}
