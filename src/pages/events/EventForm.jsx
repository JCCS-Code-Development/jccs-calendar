import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Input from '../../components/ui/Input'
import Select from '../../components/ui/Select'
import Button from '../../components/ui/Button'
import { getEvent, createEvent, updateEvent, deleteEvent, getEventTypes } from '../../api/events'
import { getUsers } from '../../api/users'
import { useAuth } from '../../hooks/useAuth'

// Centered popup over the calendar with a dimmed backdrop. Click the
// backdrop or press Esc to close (→ back to wherever you came from).
function ModalShell({ onClose, children }) {
  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:p-8"
      onClick={onClose}
    >
      <div
        className="my-auto w-full max-w-2xl rounded-2xl bg-white shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        {children}
      </div>
    </div>
  )
}

const EVENT_CATEGORY_ORDER = [
  'Reminder', 'Site Visit', 'Meeting', 'Communication',
  'Supplies', 'Logistics', 'Estimate/Invoice', 'Payment',
]

const EVENT_SUBTYPES = {
  'reminder':        ['General', 'Personal'],
  'site visit':      ['Quote Walk-Through', 'Job Set-Up', 'Final Walk-Through'],
  'meeting':         ['Virtual Video Call', 'In-Person Meeting'],
  'communication':   ['Text', 'Email', 'Call'],
  'supplies':        ['Order', 'Pick Up', 'Return', 'Exchange', 'Buy In Store'],
  'logistics':       ['Coordinate Workers', 'Lead-Team Member Meeting', 'Office Meeting'],
  'estimate/invoice':['Send', 'Change Price', 'Change Details', 'Create an Add-On'],
  'payment':         ['Routine Payment', 'Payment Due', 'Cash Checks', 'Register Payment', 'Ask for Payment'],
}

const FIELD_MATRIX = {
  'site visit': {
    subtypes: ['quote walk-through', 'job set-up', 'final walk-through'],
    fields: ['engineer'],
  },
  'meeting': {
    subtypes: ['virtual video call', 'in-person meeting'],
    fields: ['participants'],
  },
  'communication': {
    subtypes: ['text', 'email', 'call'],
    fields: ['person'],
  },
  'supplies': {
    subtypes: ['order', 'pick up', 'return', 'exchange', 'buy in store'],
    fields: ['company', 'items'],
  },
  'logistics:coordinate workers': {
    subtypes: ['coordinate workers'],
    fields: ['workers', 'project', 'estimated_due_date', 'logistics_location'],
  },
  'logistics:lead-team member meeting': {
    subtypes: ['lead-team member meeting'],
    fields: ['team_workers'],
  },
  'estimate/invoice': {
    subtypes: ['send', 'change price', 'change details', 'create an add-on'],
    fields: ['invoice_or_estimate', 'number', 'name'],
  },
  'payment': {
    subtypes: ['routine payment', 'payment due', 'cash checks', 'register payment', 'ask for payment'],
    fields: ['payment_amount', 'payment_date', 'payment_document_number', 'payment_name', 'payment_method', 'payment_status'],
  },
}

const BLANK_ITEM = { name: '', quantity: '' }

function getDetailFields(typeName, subtype) {
  const t = (typeName ?? '').toLowerCase()
  const s = (subtype ?? '').toLowerCase()
  for (const [key, cfg] of Object.entries(FIELD_MATRIX)) {
    const [cat, sub] = key.split(':')
    if (t === cat && (!sub || s === sub) && cfg.subtypes.includes(s)) return cfg.fields
  }
  return []
}

function isReminderType(typeName) {
  return (typeName ?? '').toLowerCase() === 'reminder'
}

export default function EventForm() {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { id } = useParams()
  const isEdit = Boolean(id)
  const { canManageEvents } = useAuth()

  const [loading, setLoading] = useState(isEdit)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [error, setError] = useState('')
  const [eventTypes, setEventTypes] = useState([])
  const [users, setUsers] = useState([])

  const [form, setForm] = useState({
    title: '',
    event_type_id: '',
    event_subtype: '',
    assigned_user_id: '',
    start_datetime: '',
    end_datetime: '',
    status: 'Scheduled',
    priority: 'Normal',
    location: '',
    description: '',
    is_all_day_todo: false,
    all_day_date: '',
    details: {},
  })
  const [supplyItems, setSupplyItems] = useState([{ ...BLANK_ITEM }])

  const selectedTypeName = eventTypes.find((t) => String(t.id) === String(form.event_type_id))?.name ?? ''
  const subtypes = EVENT_SUBTYPES[(selectedTypeName ?? '').toLowerCase()] ?? []
  const isReminder = isReminderType(selectedTypeName)
  const isAllDay = isReminder || form.is_all_day_todo
  const detailFields = getDetailFields(selectedTypeName, form.event_subtype)

  useEffect(() => {
    Promise.all([
      getEventTypes(),
      getUsers().catch(() => []),
    ]).then(([types, usrs]) => {
      setEventTypes(types.filter((t) => EVENT_CATEGORY_ORDER.includes(t.name))
        .sort((a, b) => EVENT_CATEGORY_ORDER.indexOf(a.name) - EVENT_CATEGORY_ORDER.indexOf(b.name)))
      setUsers(usrs)
    })
  }, [])

  useEffect(() => {
    if (!isEdit) return
    getEvent(id).then((e) => {
      const details = e.details ?? {}
      setForm({
        title: e.title ?? '',
        event_type_id: String(e.event_type_id ?? ''),
        event_subtype: e.event_subtype ?? '',
        assigned_user_id: String(e.assigned_user_id ?? ''),
        start_datetime: (e.start_datetime ?? '').replace(' ', 'T').slice(0, 16),
        end_datetime: (e.end_datetime ?? '').replace(' ', 'T').slice(0, 16),
        status: e.status ?? 'Scheduled',
        priority: e.priority ?? 'Normal',
        location: e.location ?? '',
        description: e.description ?? '',
        is_all_day_todo: e.is_all_day ?? false,
        all_day_date: '',
        details,
      })
      if (details.items?.length) setSupplyItems(details.items)
      setLoading(false)
    }).catch(() => setLoading(false))
  }, [id, isEdit])

  const close = () => navigate(-1)

  // Modal behaviour: lock background scroll and close on Esc.
  useEffect(() => {
    const prev = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const onKey = (e) => { if (e.key === 'Escape') navigate(-1) }
    window.addEventListener('keydown', onKey)
    return () => {
      document.body.style.overflow = prev
      window.removeEventListener('keydown', onKey)
    }
  }, [navigate])

  const set = (key, val) => setForm((f) => ({ ...f, [key]: val }))
  const setDetail = (key, val) => setForm((f) => ({ ...f, details: { ...f.details, [key]: val } }))

  const handleTypeChange = (e) => {
    set('event_type_id', e.target.value)
    set('event_subtype', '')
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setError('')
    try {
      const payload = {
        title: form.title,
        event_type_id: form.event_type_id,
        event_subtype: form.event_subtype || null,
        assigned_user_id: form.assigned_user_id || null,
        start_datetime: isAllDay
          ? `${form.all_day_date ?? form.start_datetime?.slice(0, 10)} 00:00:00`
          : form.start_datetime,
        end_datetime: isAllDay ? null : form.end_datetime || null,
        is_all_day: isAllDay,
        status: form.status,
        priority: form.priority,
        location: form.location || null,
        description: form.description || null,
        details: buildDetails(),
      }

      if (isEdit) await updateEvent(id, payload)
      else await createEvent(payload)
      navigate('/events')
    } catch (err) {
      const msgs = err?.response?.data?.errors
      if (msgs) setError(Object.values(msgs).flat().join(' '))
      else setError(err?.response?.data?.message ?? t('f.saveFailed'))
    } finally {
      setSaving(false)
    }
  }

  const buildDetails = () => {
    const d = { ...form.details }
    if (detailFields.includes('items')) {
      d.items = supplyItems.filter((i) => i.name || i.quantity)
    }
    return d
  }

  const handleDelete = async () => {
    if (!window.confirm(t('f.deleteEventConfirm'))) return
    setDeleting(true)
    try { await deleteEvent(id); navigate('/events') }
    catch { setDeleting(false) }
  }

  if (loading) return (
    <ModalShell onClose={close}>
      <div className="flex justify-center py-16 text-gray-400">{t('f.loading')}</div>
    </ModalShell>
  )
  if (!canManageEvents) return (
    <ModalShell onClose={close}>
      <div className="text-center py-16 text-gray-400">{t('f.accessDenied')}</div>
    </ModalShell>
  )

  return (
    <ModalShell onClose={close}>
      <div className="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
        <h1 className="text-lg font-extrabold text-gray-900 tracking-tight">
          {isEdit ? t('f.editEventTitle') : t('f.createEventTitle')}
        </h1>
        <button onClick={close} aria-label={t('f.cancel')} className="text-gray-400 hover:text-gray-600 p-1 -mr-1">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form onSubmit={handleSubmit} className="max-h-[calc(100vh-11rem)] overflow-y-auto px-6 py-5 space-y-5">
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
            {error}
          </div>
        )}

        {/* Event Type */}
        <Select label={t('events.type')} value={form.event_type_id} onChange={handleTypeChange} required>
          <option value="">{t('f.selectEventType')}</option>
          {eventTypes.map((et) => (
            <option key={et.id} value={et.id}>{et.name}</option>
          ))}
        </Select>

        {/* Subtype */}
        {subtypes.length > 0 && (
          <Select label={t('f.eventSubType')} value={form.event_subtype} onChange={(e) => set('event_subtype', e.target.value)} required>
            <option value="">{t('f.selectSubType')}</option>
            {subtypes.map((s) => <option key={s} value={s}>{s}</option>)}
          </Select>
        )}

        {/* Title */}
        <Input label={t('events.title')} value={form.title} onChange={(e) => set('title', e.target.value)} required placeholder={t('f.eventTitlePlaceholder')} />

        {/* Dynamic Details */}
        {detailFields.length > 0 && (
          <div className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-4">
            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">{t('f.requiredDetails')}</p>

            {detailFields.includes('engineer') && (
              <Input label={t('f.engineer')} value={form.details.engineer ?? ''} onChange={(e) => setDetail('engineer', e.target.value)} required />
            )}
            {detailFields.includes('participants') && (
              <div className="flex flex-col gap-1">
                <label className="text-sm font-medium text-gray-700">{t('f.participants')}</label>
                <textarea
                  rows={3}
                  value={form.details.participants ?? ''}
                  onChange={(e) => setDetail('participants', e.target.value)}
                  placeholder={t('f.participantsPlaceholder')}
                  className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                  required
                />
              </div>
            )}
            {detailFields.includes('person') && (
              <Input label={t('f.person')} value={form.details.person ?? ''} onChange={(e) => setDetail('person', e.target.value)} required />
            )}
            {detailFields.includes('company') && (
              <Input label={t('f.company')} value={form.details.company ?? ''} onChange={(e) => setDetail('company', e.target.value)} required />
            )}
            {detailFields.includes('items') && (
              <div>
                <label className="text-sm font-medium text-gray-700 block mb-2">{t('f.itemsQuantities')}</label>
                <div className="space-y-2">
                  {supplyItems.map((item, i) => (
                    <div key={i} className="flex gap-2 items-center">
                      <input
                        className="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-brand-500"
                        placeholder={t('f.itemName')}
                        value={item.name}
                        onChange={(e) => {
                          const next = [...supplyItems]; next[i] = { ...next[i], name: e.target.value }; setSupplyItems(next)
                        }}
                        required={i === 0}
                      />
                      <input
                        className="w-20 rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-center outline-none focus:border-brand-500"
                        placeholder="#"
                        type="number"
                        min={1}
                        value={item.quantity}
                        onChange={(e) => {
                          const next = [...supplyItems]; next[i] = { ...next[i], quantity: e.target.value }; setSupplyItems(next)
                        }}
                      />
                      <button
                        type="button"
                        disabled={supplyItems.length <= 1}
                        onClick={() => setSupplyItems(supplyItems.filter((_, j) => j !== i))}
                        className="text-red-500 hover:text-red-700 p-1 disabled:opacity-30"
                      >
                        ✕
                      </button>
                    </div>
                  ))}
                </div>
                <button type="button" onClick={() => setSupplyItems([...supplyItems, { ...BLANK_ITEM }])}
                  className="mt-2 text-sm text-brand-500 font-medium hover:text-brand-400"
                >
                  {t('f.addItem')}
                </button>
              </div>
            )}
            {detailFields.includes('workers') && (
              <div className="flex flex-col gap-1">
                <label className="text-sm font-medium text-gray-700">{t('f.workersLabel')}</label>
                <textarea rows={3} value={form.details.workers ?? ''} onChange={(e) => setDetail('workers', e.target.value)}
                  placeholder={t('f.workerPerLine')} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100" required />
              </div>
            )}
            {detailFields.includes('team_workers') && (
              <div className="flex flex-col gap-1">
                <label className="text-sm font-medium text-gray-700">{t('f.workersLabel')}</label>
                <textarea rows={3} value={form.details.team_workers ?? ''} onChange={(e) => setDetail('team_workers', e.target.value)}
                  placeholder={t('f.workerPerLine')} className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100" required />
              </div>
            )}
            {detailFields.includes('project') && (
              <Input label={t('f.project')} value={form.details.project ?? ''} onChange={(e) => setDetail('project', e.target.value)} required />
            )}
            {detailFields.includes('estimated_due_date') && (
              <Input label={t('f.estimatedDueDate')} type="date" value={form.details.estimated_due_date ?? ''} onChange={(e) => setDetail('estimated_due_date', e.target.value)} />
            )}
            {detailFields.includes('logistics_location') && (
              <Input label={t('f.location')} value={form.details.logistics_location ?? ''} onChange={(e) => setDetail('logistics_location', e.target.value)} required />
            )}
            {detailFields.includes('invoice_or_estimate') && (
              <>
                <Select label={t('f.invoiceOrEstimate')} value={form.details.invoice_or_estimate ?? ''} onChange={(e) => setDetail('invoice_or_estimate', e.target.value)} required>
                  <option value="">{t('f.selectDash')}</option>
                  <option value="Invoice">{t('f.invoice')}</option>
                  <option value="Estimate">{t('f.estimateWord')}</option>
                </Select>
                <Input label={t('f.number')} value={form.details.number ?? ''} onChange={(e) => setDetail('number', e.target.value)} required />
                <Input label={t('f.name')} value={form.details.name ?? ''} onChange={(e) => setDetail('name', e.target.value)} required />
              </>
            )}
            {detailFields.includes('payment_amount') && (
              <>
                <div className="grid grid-cols-2 gap-4">
                  <Input label={t('f.paymentAmount')} type="number" min="0" step="0.01" placeholder="0.00"
                    value={form.details.payment_amount ?? ''} onChange={(e) => setDetail('payment_amount', e.target.value)} required />
                  <Input label={t('f.paymentDate')} type="date"
                    value={form.details.payment_date ?? ''} onChange={(e) => setDetail('payment_date', e.target.value)} required />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <Input label={t('f.invoiceEstimateNo')} value={form.details.payment_document_number ?? ''} onChange={(e) => setDetail('payment_document_number', e.target.value)} required />
                  <Input label={t('f.projectClientName')} value={form.details.payment_name ?? ''} onChange={(e) => setDetail('payment_name', e.target.value)} required />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <Select label={t('f.paymentMethod')} value={form.details.payment_method ?? ''} onChange={(e) => setDetail('payment_method', e.target.value)} required>
                    <option value="">{t('f.selectDash')}</option>
                    {['Cash','Check','Card','ACH / Bank Transfer','Other'].map((m) => <option key={m}>{m}</option>)}
                  </Select>
                  <Select label={t('f.paymentStatus')} value={form.details.payment_status ?? ''} onChange={(e) => setDetail('payment_status', e.target.value)} required>
                    <option value="">{t('f.selectDash')}</option>
                    {['Pending','Requested','Received','Deposited'].map((s) => <option key={s}>{s}</option>)}
                  </Select>
                </div>
              </>
            )}
          </div>
        )}

        {/* Assigned To */}
        <Select label={t('f.assignedTo')} value={form.assigned_user_id} onChange={(e) => set('assigned_user_id', e.target.value)}>
          <option value="">{t('f.unassignedDash')}</option>
          {users.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
        </Select>

        {/* All-day todo */}
        {!isReminder && (
          <label className="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl p-4 cursor-pointer">
            <input
              type="checkbox"
              checked={form.is_all_day_todo}
              onChange={(e) => set('is_all_day_todo', e.target.checked)}
              className="mt-0.5 rounded border-gray-300 accent-brand-500"
            />
            <span className="text-sm text-gray-800">
              <span className="font-medium">{t('f.allDayTodo')}</span>
              <span className="block text-gray-500 mt-0.5">{t('f.allDayTodoHint')}</span>
            </span>
          </label>
        )}

        {/* Date fields */}
        {isReminder ? (
          <Input label={t('f.reminderDate')} type="date" value={form.all_day_date} onChange={(e) => set('all_day_date', e.target.value)} required />
        ) : isAllDay ? (
          <Input label={t('f.todoDate')} type="date" value={form.all_day_date} onChange={(e) => set('all_day_date', e.target.value)} required />
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input label={t('f.startDateTime')} type="datetime-local" value={form.start_datetime} onChange={(e) => set('start_datetime', e.target.value)} required />
            <Input label={t('f.endDateTime')} type="datetime-local" value={form.end_datetime} onChange={(e) => set('end_datetime', e.target.value)} />
          </div>
        )}

        {/* Status & Priority */}
        <div className="grid grid-cols-2 gap-4">
          <Select label={t('f.status')} value={form.status} onChange={(e) => set('status', e.target.value)}>
            {['Scheduled','Pending','Confirmed','In Progress','Completed','Cancelled','Rescheduled'].map((s) => <option key={s}>{s}</option>)}
          </Select>
          <Select label={t('f.priority')} value={form.priority} onChange={(e) => set('priority', e.target.value)}>
            {['Normal','Low','High','Urgent'].map((p) => <option key={p}>{p}</option>)}
          </Select>
        </div>

        {/* Location */}
        <Input label={t('f.locationFacility')} value={form.location} onChange={(e) => set('location', e.target.value)} placeholder={t('f.optional')} />

        {/* Description */}
        <div className="flex flex-col gap-1">
          <label className="text-sm font-medium text-gray-700">{t('f.descriptionNotes')}</label>
          <textarea
            rows={4}
            value={form.description}
            onChange={(e) => set('description', e.target.value)}
            placeholder={t('f.optionalNotes')}
            className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          />
        </div>

        {/* Actions */}
        <div className="flex gap-3 pt-2">
          <Button type="submit" loading={saving} size="lg" className="flex-1">
            {isEdit ? t('f.saveChanges') : t('f.createEventBtn')}
          </Button>
          <Button variant="secondary" size="lg" onClick={close}>
            {t('f.cancel')}
          </Button>
          {isEdit && (
            <Button variant="danger" size="lg" loading={deleting} onClick={handleDelete}>
              {t('f.delete')}
            </Button>
          )}
        </div>
      </form>
    </ModalShell>
  )
}
