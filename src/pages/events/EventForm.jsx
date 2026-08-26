import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import Input from '../../components/ui/Input'
import Select from '../../components/ui/Select'
import Button from '../../components/ui/Button'
import { getEvent, createEvent, updateEvent, deleteEvent, getEventTypes } from '../../api/events'
import { getUsers } from '../../api/users'
import { useAuth } from '../../hooks/useAuth'

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
      else setError(err?.response?.data?.message ?? 'Failed to save. Please try again.')
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
    if (!window.confirm('Delete this event?')) return
    setDeleting(true)
    try { await deleteEvent(id); navigate('/events') }
    catch { setDeleting(false) }
  }

  if (loading) return <div className="flex justify-center py-16 text-gray-400">Loading...</div>
  if (!canManageEvents) return <div className="text-center py-16 text-gray-400">Access denied.</div>

  return (
    <div className="max-w-2xl mx-auto">
      <div className="flex items-center gap-3 mb-6">
        <button onClick={() => navigate(-1)} className="text-gray-400 hover:text-gray-600 p-1">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">
          {isEdit ? 'Edit Event' : 'Create Event'}
        </h1>
      </div>

      <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
            {error}
          </div>
        )}

        {/* Event Type */}
        <Select label="Event Type" value={form.event_type_id} onChange={handleTypeChange} required>
          <option value="">-- Select Event Type --</option>
          {eventTypes.map((t) => (
            <option key={t.id} value={t.id}>{t.name}</option>
          ))}
        </Select>

        {/* Subtype */}
        {subtypes.length > 0 && (
          <Select label="Event Sub-Type" value={form.event_subtype} onChange={(e) => set('event_subtype', e.target.value)} required>
            <option value="">-- Select Sub-Type --</option>
            {subtypes.map((s) => <option key={s} value={s}>{s}</option>)}
          </Select>
        )}

        {/* Title */}
        <Input label="Title" value={form.title} onChange={(e) => set('title', e.target.value)} required placeholder="Event title" />

        {/* Dynamic Details */}
        {detailFields.length > 0 && (
          <div className="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-4">
            <p className="text-xs font-bold text-gray-500 uppercase tracking-wider">Required Details</p>

            {detailFields.includes('engineer') && (
              <Input label="Engineer" value={form.details.engineer ?? ''} onChange={(e) => setDetail('engineer', e.target.value)} required />
            )}
            {detailFields.includes('participants') && (
              <div className="flex flex-col gap-1">
                <label className="text-sm font-medium text-gray-700">Participants</label>
                <textarea
                  rows={3}
                  value={form.details.participants ?? ''}
                  onChange={(e) => setDetail('participants', e.target.value)}
                  placeholder="One participant per line"
                  className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                  required
                />
              </div>
            )}
            {detailFields.includes('person') && (
              <Input label="Person" value={form.details.person ?? ''} onChange={(e) => setDetail('person', e.target.value)} required />
            )}
            {detailFields.includes('company') && (
              <Input label="Company" value={form.details.company ?? ''} onChange={(e) => setDetail('company', e.target.value)} required />
            )}
            {detailFields.includes('items') && (
              <div>
                <label className="text-sm font-medium text-gray-700 block mb-2">Items &amp; Quantities</label>
                <div className="space-y-2">
                  {supplyItems.map((item, i) => (
                    <div key={i} className="flex gap-2 items-center">
                      <input
                        className="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-brand-500"
                        placeholder="Item name"
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
                  + Add item
                </button>
              </div>
            )}
            {detailFields.includes('workers') && (
              <div className="flex flex-col gap-1">
                <label className="text-sm font-medium text-gray-700">Worker(s)</label>
                <textarea rows={3} value={form.details.workers ?? ''} onChange={(e) => setDetail('workers', e.target.value)}
                  placeholder="One worker per line" className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100" required />
              </div>
            )}
            {detailFields.includes('team_workers') && (
              <div className="flex flex-col gap-1">
                <label className="text-sm font-medium text-gray-700">Worker(s)</label>
                <textarea rows={3} value={form.details.team_workers ?? ''} onChange={(e) => setDetail('team_workers', e.target.value)}
                  placeholder="One worker per line" className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100" required />
              </div>
            )}
            {detailFields.includes('project') && (
              <Input label="Project" value={form.details.project ?? ''} onChange={(e) => setDetail('project', e.target.value)} required />
            )}
            {detailFields.includes('estimated_due_date') && (
              <Input label="Estimated Due Date" type="date" value={form.details.estimated_due_date ?? ''} onChange={(e) => setDetail('estimated_due_date', e.target.value)} />
            )}
            {detailFields.includes('logistics_location') && (
              <Input label="Location" value={form.details.logistics_location ?? ''} onChange={(e) => setDetail('logistics_location', e.target.value)} required />
            )}
            {detailFields.includes('invoice_or_estimate') && (
              <>
                <Select label="Invoice or Estimate" value={form.details.invoice_or_estimate ?? ''} onChange={(e) => setDetail('invoice_or_estimate', e.target.value)} required>
                  <option value="">-- Select --</option>
                  <option>Invoice</option>
                  <option>Estimate</option>
                </Select>
                <Input label="Number" value={form.details.number ?? ''} onChange={(e) => setDetail('number', e.target.value)} required />
                <Input label="Name" value={form.details.name ?? ''} onChange={(e) => setDetail('name', e.target.value)} required />
              </>
            )}
            {detailFields.includes('payment_amount') && (
              <>
                <div className="grid grid-cols-2 gap-4">
                  <Input label="Payment Amount" type="number" min="0" step="0.01" placeholder="0.00"
                    value={form.details.payment_amount ?? ''} onChange={(e) => setDetail('payment_amount', e.target.value)} required />
                  <Input label="Payment Date / Due Date" type="date"
                    value={form.details.payment_date ?? ''} onChange={(e) => setDetail('payment_date', e.target.value)} required />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <Input label="Invoice / Estimate #" value={form.details.payment_document_number ?? ''} onChange={(e) => setDetail('payment_document_number', e.target.value)} required />
                  <Input label="Project / Client Name" value={form.details.payment_name ?? ''} onChange={(e) => setDetail('payment_name', e.target.value)} required />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <Select label="Payment Method" value={form.details.payment_method ?? ''} onChange={(e) => setDetail('payment_method', e.target.value)} required>
                    <option value="">-- Select --</option>
                    {['Cash','Check','Card','ACH / Bank Transfer','Other'].map((m) => <option key={m}>{m}</option>)}
                  </Select>
                  <Select label="Payment Status" value={form.details.payment_status ?? ''} onChange={(e) => setDetail('payment_status', e.target.value)} required>
                    <option value="">-- Select --</option>
                    {['Pending','Requested','Received','Deposited'].map((s) => <option key={s}>{s}</option>)}
                  </Select>
                </div>
              </>
            )}
          </div>
        )}

        {/* Assigned To */}
        <Select label="Assigned To" value={form.assigned_user_id} onChange={(e) => set('assigned_user_id', e.target.value)}>
          <option value="">-- Unassigned --</option>
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
              <span className="font-medium">Any time during the day / To-do item</span>
              <span className="block text-gray-500 mt-0.5">Use when the item doesn't need an exact time. It will appear in the To-Do List.</span>
            </span>
          </label>
        )}

        {/* Date fields */}
        {isReminder ? (
          <Input label="Reminder Date" type="date" value={form.all_day_date} onChange={(e) => set('all_day_date', e.target.value)} required />
        ) : isAllDay ? (
          <Input label="To-Do Date" type="date" value={form.all_day_date} onChange={(e) => set('all_day_date', e.target.value)} required />
        ) : (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input label="Start Date/Time" type="datetime-local" value={form.start_datetime} onChange={(e) => set('start_datetime', e.target.value)} required />
            <Input label="End Date/Time" type="datetime-local" value={form.end_datetime} onChange={(e) => set('end_datetime', e.target.value)} />
          </div>
        )}

        {/* Status & Priority */}
        <div className="grid grid-cols-2 gap-4">
          <Select label="Status" value={form.status} onChange={(e) => set('status', e.target.value)}>
            {['Scheduled','Pending','Confirmed','In Progress','Completed','Cancelled','Rescheduled'].map((s) => <option key={s}>{s}</option>)}
          </Select>
          <Select label="Priority" value={form.priority} onChange={(e) => set('priority', e.target.value)}>
            {['Normal','Low','High','Urgent'].map((p) => <option key={p}>{p}</option>)}
          </Select>
        </div>

        {/* Location */}
        <Input label="Location / Facility" value={form.location} onChange={(e) => set('location', e.target.value)} placeholder="Optional" />

        {/* Description */}
        <div className="flex flex-col gap-1">
          <label className="text-sm font-medium text-gray-700">Description / Internal Notes</label>
          <textarea
            rows={4}
            value={form.description}
            onChange={(e) => set('description', e.target.value)}
            placeholder="Optional notes..."
            className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          />
        </div>

        {/* Actions */}
        <div className="flex gap-3 pt-2">
          <Button type="submit" loading={saving} size="lg" className="flex-1">
            {isEdit ? 'Save Changes' : 'Create Event'}
          </Button>
          <Button variant="secondary" size="lg" onClick={() => navigate(-1)}>
            Cancel
          </Button>
          {isEdit && (
            <Button variant="danger" size="lg" loading={deleting} onClick={handleDelete}>
              Delete
            </Button>
          )}
        </div>
      </form>
    </div>
  )
}
