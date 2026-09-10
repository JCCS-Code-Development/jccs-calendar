import { useState } from 'react'
import Modal from '../../../components/ui/Modal'
import Input from '../../../components/ui/Input'
import Select from '../../../components/ui/Select'
import Button from '../../../components/ui/Button'
import { createAppointment, updateAppointment, cancelAppointment, unpinAppointment } from '../../../api/board'
import { useBoardT } from '../t'

const FIELD = 'w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100'

// "2026-09-09 14:00:00" -> "2026-09-09T14:00" para <input type=datetime-local>
const toLocal = (s) => (s ? String(s).slice(0, 16).replace(' ', 'T') : '')
const toApi = (s) => (s ? `${s.replace('T', ' ')}:00`.slice(0, 19) : null)

function fromAppt(a) {
  return {
    title: a?.title ?? '',
    start_datetime: toLocal(a?.start_datetime),
    end_datetime: toLocal(a?.end_datetime),
    location: a?.location ?? '',
    event_type_id: a?.event_type_id ? String(a.event_type_id) : '',
    assigned_user_id: a?.assigned_user_id ? String(a.assigned_user_id) : '',
    related_job_id: a?.related_job_id ? String(a.related_job_id) : '',
    board_importance: a?.board_importance ?? 'normal',
    confirm_state: a?.confirm_state ?? 'tentative',
    description: a?.description ?? '',
  }
}

export default function AppointmentEditForm({ appt, refs, onSaved, onClose }) {
  const T = useBoardT()
  const isEdit = !!appt
  const [f, setF] = useState(() => fromAppt(appt))
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [conflict, setConflict] = useState(null)

  const set = (k) => (e) => setF((s) => ({ ...s, [k]: e.target.value }))

  const submit = async (e) => {
    e.preventDefault()
    if (!f.title.trim() || !f.start_datetime) { setError(T.afRequired); return }
    setBusy(true); setError(''); setConflict(null)

    const payload = {
      title: f.title.trim(),
      start_datetime: toApi(f.start_datetime),
      end_datetime: toApi(f.end_datetime),
      location: f.location || null,
      event_type_id: f.event_type_id ? Number(f.event_type_id) : null,
      assigned_user_id: f.assigned_user_id ? Number(f.assigned_user_id) : null,
      related_job_id: f.related_job_id ? Number(f.related_job_id) : null,
      board_importance: f.board_importance,
      confirm_state: f.confirm_state,
      description: f.description || null,
    }

    try {
      if (isEdit) {
        payload.expected_updated_at = appt.updated_at
        payload.repin = true
        await updateAppointment(appt.id, payload)
      } else {
        await createAppointment(payload)
      }
      onSaved()
    } catch (err) {
      if (err?.response?.status === 409) {
        setConflict(err.response.data.current)
        setError(T.afConflict)
      } else {
        setError(err?.response?.data?.error ?? T.jfSaveError)
      }
      setBusy(false)
    }
  }

  const loadLatest = () => {
    setF(fromAppt(conflict))
    appt.updated_at = conflict.updated_at
    setConflict(null); setError('')
  }

  const doCancel = async () => {
    if (!window.confirm(T.afCancelConfirm(appt.title))) return
    setBusy(true)
    try { await cancelAppointment(appt.id, appt.updated_at); onSaved() }
    catch (err) { setError(err?.response?.data?.error ?? T.afCancelError); setBusy(false) }
  }

  const doUnpin = async () => {
    if (!window.confirm(T.afRemoveConfirm(appt.title))) return
    setBusy(true)
    try { await unpinAppointment(appt.id); onSaved() }
    catch (err) { setError(err?.response?.data?.error ?? T.afRemoveError); setBusy(false) }
  }

  return (
    <Modal isOpen onClose={onClose} title={isEdit ? T.afEditTitle : T.afAddTitle} size="xl">
      <form onSubmit={submit} className="flex flex-col gap-4">
        {error && (
          <div className="rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 font-medium">
            {error}
            {conflict && (
              <button type="button" onClick={loadLatest} className="ml-2 underline font-semibold">{T.jfLoadLatest}</button>
            )}
          </div>
        )}

        <Input label={T.afTitle} value={f.title} onChange={set('title')} autoFocus />

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.afStart}</label>
            <input type="datetime-local" className={FIELD} value={f.start_datetime} onChange={set('start_datetime')} />
          </div>
          <div className="flex flex-col gap-1">
            <label className="text-sm font-medium text-gray-700">{T.afEnd}</label>
            <input type="datetime-local" className={FIELD} value={f.end_datetime} onChange={set('end_datetime')} />
          </div>
        </div>

        <Input label={T.afLocation} value={f.location} onChange={set('location')} />

        <div className="grid sm:grid-cols-2 gap-4">
          <Select label={T.afType} value={f.event_type_id} onChange={set('event_type_id')}>
            <option value="">{T.afMeetingDefault}</option>
            {refs.event_types.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
          </Select>
          <Select label={T.afResponsible} value={f.assigned_user_id} onChange={set('assigned_user_id')}>
            <option value="">{T.jfNone}</option>
            {refs.staff.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
          </Select>
          <Select label={T.afRelatedJob} value={f.related_job_id} onChange={set('related_job_id')}>
            <option value="">{T.jfNone}</option>
            {refs.jobs.filter((j) => !j.archived_at).map((j) => (
              <option key={j.id} value={j.id}>{j.title}</option>
            ))}
          </Select>
          <Select label={T.afImportance} value={f.board_importance} onChange={set('board_importance')}>
            <option value="normal">{T.afImpNormal}</option>
            <option value="important">{T.afImpImportant}</option>
            <option value="critical">{T.afImpCritical}</option>
          </Select>
          <Select label={T.afConfirmation} value={f.confirm_state} onChange={set('confirm_state')}>
            <option value="tentative">{T.afConfTentative}</option>
            <option value="confirmed">{T.afConfConfirmed}</option>
            <option value="completed">{T.afConfCompleted}</option>
            <option value="canceled">{T.afConfCanceled}</option>
          </Select>
        </div>

        <div className="flex flex-col gap-1">
          <label className="text-sm font-medium text-gray-700">{T.afNotes}</label>
          <textarea className={FIELD} rows={3} value={f.description} onChange={set('description')} />
        </div>

        <div className="flex items-center justify-between gap-2 pt-2">
          <div className="flex gap-2">
            {isEdit && <Button type="button" variant="danger" onClick={doCancel} disabled={busy}>{T.afCancelAppt}</Button>}
            {isEdit && <Button type="button" variant="secondary" onClick={doUnpin} disabled={busy}>{T.afRemoveBoard}</Button>}
          </div>
          <div className="flex gap-2">
            <Button type="button" variant="secondary" onClick={onClose}>{T.afClose}</Button>
            <Button type="submit" loading={busy}>{isEdit ? T.afSave : T.afAdd}</Button>
          </div>
        </div>
      </form>
    </Modal>
  )
}
