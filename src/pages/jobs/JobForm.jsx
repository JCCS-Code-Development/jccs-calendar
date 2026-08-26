import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { getJob, createJob, updateJob, deleteJob, getClients, createClient, uploadJobPhoto, deleteJobPhoto } from '../../api/jobs'
import { getUsers } from '../../api/users'

const STATUS_OPTIONS = ['Active', 'On Hold', 'Completed', 'Cancelled']

export default function JobForm() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isEdit = Boolean(id)

  const [clients, setClients] = useState([])
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(isEdit)
  const [saving, setSaving] = useState(false)
  const [deleting, setDeleting] = useState(false)
  const [confirmDelete, setConfirmDelete] = useState(false)
  const [error, setError] = useState('')

  // New client inline creation
  const [newClientName, setNewClientName] = useState('')
  const [addingClient, setAddingClient] = useState(false)
  const [showNewClient, setShowNewClient] = useState(false)

  const [form, setForm] = useState({
    client_id:       '',
    title:           '',
    estimate_number: '',
    address:         '',
    scope:           '',
    projected_start: '',
    projected_end:   '',
    status:          'Active',
    worker_ids:      [],
    lead_time_days:  '',
  })

  // Carpentry Production Calendar: reference photo. A job must exist before
  // a photo can be attached (the upload endpoint needs a job_id), so a
  // photo picked while creating is uploaded right after the job is saved.
  const [photoFile, setPhotoFile] = useState(null)
  const [photoPreview, setPhotoPreview] = useState(null)
  const [photoUrl, setPhotoUrl] = useState(null)
  const [uploadingPhoto, setUploadingPhoto] = useState(false)

  useEffect(() => {
    Promise.all([getClients(), getUsers()]).then(([c, u]) => {
      setClients(c)
      setUsers(u)
    })
  }, [])

  useEffect(() => {
    if (!isEdit) return
    getJob(id).then((job) => {
      setForm({
        client_id:       job.client_id,
        title:           job.title,
        estimate_number: job.estimate_number ?? '',
        address:         job.address ?? '',
        scope:           job.scope ?? '',
        projected_start: job.projected_start?.slice(0, 10) ?? '',
        projected_end:   job.projected_end?.slice(0, 10) ?? '',
        status:          job.status,
        worker_ids:      job.workers.map((w) => w.id),
        lead_time_days:  job.lead_time_days ?? '',
      })
      setPhotoUrl(job.photo_url ?? null)
      setLoading(false)
    })
  }, [id, isEdit])

  const set = (field, value) => setForm((f) => ({ ...f, [field]: value }))

  const handlePhotoSelect = (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    setPhotoFile(file)
    setPhotoPreview(URL.createObjectURL(file))
  }

  const handlePhotoRemove = async () => {
    setPhotoFile(null)
    setPhotoPreview(null)
    if (isEdit && photoUrl) {
      try { await deleteJobPhoto(id) } catch { /* best effort */ }
      setPhotoUrl(null)
    }
  }

  const toggleWorker = (uid) => {
    setForm((f) => ({
      ...f,
      worker_ids: f.worker_ids.includes(uid)
        ? f.worker_ids.filter((i) => i !== uid)
        : [...f.worker_ids, uid],
    }))
  }

  const handleAddClient = async () => {
    if (!newClientName.trim()) return
    setAddingClient(true)
    try {
      const c = await createClient({ name: newClientName.trim() })
      setClients((prev) => [...prev, c])
      set('client_id', c.id)
      setNewClientName('')
      setShowNewClient(false)
    } catch { setError('Could not create client.') }
    setAddingClient(false)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!form.client_id) { setError('Select a client.'); return }
    if (!form.title.trim()) { setError('Enter a job title.'); return }
    if (!form.projected_start || !form.projected_end) { setError('Both dates are required.'); return }
    if (form.projected_end < form.projected_start) { setError('End date must be after start date.'); return }

    setSaving(true); setError('')
    try {
      const payload = {
        ...form,
        client_id: Number(form.client_id),
        lead_time_days: form.lead_time_days === '' ? null : Number(form.lead_time_days),
      }
      const saved = isEdit ? await updateJob(id, payload) : await createJob(payload)
      if (photoFile) {
        setUploadingPhoto(true)
        try { await uploadJobPhoto(saved.id, photoFile) } catch { /* job itself saved fine; photo can be retried from edit */ }
        setUploadingPhoto(false)
      }
      navigate('/jobs')
    } catch (err) {
      setError(err?.response?.data?.message ?? 'Could not save job.')
    } finally { setSaving(false) }
  }

  const handleDelete = async () => {
    setDeleting(true)
    try { await deleteJob(id); navigate('/jobs') }
    catch { setError('Could not delete job.') }
    setDeleting(false)
  }

  if (loading) return (
    <div className="flex items-center justify-center h-64 text-gray-400">Loading…</div>
  )

  return (
    <div className="max-w-2xl mx-auto">
      <div className="flex items-center gap-3 mb-6">
        <button onClick={() => navigate('/jobs')} className="p-2 rounded-xl hover:bg-gray-100 text-gray-500">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 className="text-xl font-extrabold text-gray-900">{isEdit ? 'Edit Job' : 'New Job'}</h1>
      </div>

      <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">

        {/* Client */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">Company / Client *</label>
          <div className="flex gap-2">
            <select
              value={form.client_id}
              onChange={(e) => set('client_id', e.target.value)}
              className="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            >
              <option value="">Select client…</option>
              {clients.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
            <button
              type="button"
              onClick={() => setShowNewClient((v) => !v)}
              className="px-3 py-2 text-xs font-semibold text-brand-600 border border-brand-200 rounded-xl hover:bg-brand-50 transition-colors whitespace-nowrap"
            >
              + New client
            </button>
          </div>
          {showNewClient && (
            <div className="flex gap-2 mt-2">
              <input
                type="text"
                placeholder="Company name"
                value={newClientName}
                onChange={(e) => setNewClientName(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), handleAddClient())}
                className="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
              />
              <button
                type="button"
                onClick={handleAddClient}
                disabled={addingClient}
                className="px-4 py-2 bg-brand-500 text-white text-sm font-semibold rounded-xl hover:bg-brand-600 disabled:opacity-50 transition-colors"
              >
                {addingClient ? '…' : 'Add'}
              </button>
            </div>
          )}
        </div>

        {/* Title */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">Job Title *</label>
          <input
            type="text"
            placeholder="e.g. Bathroom Renovation"
            value={form.title}
            onChange={(e) => set('title', e.target.value)}
            className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
        </div>

        {/* Estimate / Invoice # */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">Estimate / Invoice #</label>
          <input
            type="text"
            placeholder="e.g. EST-2024-001"
            value={form.estimate_number}
            onChange={(e) => set('estimate_number', e.target.value)}
            className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
        </div>

        {/* Address */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">Job Address</label>
          <input
            type="text"
            placeholder="123 Main St, Miami, FL"
            value={form.address}
            onChange={(e) => set('address', e.target.value)}
            className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
        </div>

        {/* Scope */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">Scope of Work</label>
          <textarea
            rows={3}
            placeholder="Brief description of work to be done…"
            value={form.scope}
            onChange={(e) => set('scope', e.target.value)}
            className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 resize-none"
          />
        </div>

        {/* Dates */}
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1.5">Projected Start *</label>
            <input
              type="date"
              value={form.projected_start}
              onChange={(e) => set('projected_start', e.target.value)}
              className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1.5">Projected End *</label>
            <input
              type="date"
              value={form.projected_end}
              min={form.projected_start}
              onChange={(e) => set('projected_end', e.target.value)}
              className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
            />
          </div>
        </div>

        {/* Carpentry production lead time — drives the recommended start date on the Production Calendar */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">
            Carpentry Production Lead Time <span className="font-normal text-gray-400">(days, optional)</span>
          </label>
          <input
            type="number"
            min="0"
            placeholder="e.g. 10"
            value={form.lead_time_days}
            onChange={(e) => set('lead_time_days', e.target.value)}
            className="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400"
          />
        </div>

        {/* Project photo — shown on the Production Calendar */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">
            Project Photo <span className="font-normal text-gray-400">(optional)</span>
          </label>
          {(photoPreview || photoUrl) ? (
            <div className="flex items-center gap-3">
              <img src={photoPreview ?? photoUrl} alt="" className="w-20 h-20 rounded-xl object-cover border border-gray-200" />
              <button type="button" onClick={handlePhotoRemove} className="text-xs text-red-500 hover:text-red-700 font-medium">
                Remove photo
              </button>
            </div>
          ) : (
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={handlePhotoSelect}
              className="w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-600 file:text-xs file:font-semibold hover:file:bg-brand-100"
            />
          )}
          {uploadingPhoto && <p className="text-xs text-gray-400 mt-1">Uploading photo…</p>}
        </div>

        {/* Status */}
        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
          <div className="flex flex-wrap gap-2">
            {STATUS_OPTIONS.map((s) => (
              <button
                key={s}
                type="button"
                onClick={() => set('status', s)}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors ${
                  form.status === s
                    ? 'bg-brand-500 border-brand-500 text-white'
                    : 'bg-white border-gray-200 text-gray-600 hover:border-brand-300'
                }`}
              >
                {s}
              </button>
            ))}
          </div>
        </div>

        {/* Workers */}
        {users.length > 0 && (
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-2">
              Workers <span className="font-normal text-gray-400">(optional)</span>
            </label>
            <div className="grid grid-cols-2 gap-1.5 max-h-48 overflow-y-auto pr-1">
              {users.map((u) => {
                const checked = form.worker_ids.includes(u.id)
                return (
                  <label
                    key={u.id}
                    className={`flex items-center gap-2.5 px-3 py-2 rounded-xl border cursor-pointer transition-colors text-sm ${
                      checked ? 'bg-brand-50 border-brand-300 text-brand-700' : 'border-gray-200 text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <input
                      type="checkbox"
                      className="accent-brand-500"
                      checked={checked}
                      onChange={() => toggleWorker(u.id)}
                    />
                    <span className="truncate">{u.name}</span>
                    {u.role && <span className="ml-auto text-[10px] text-gray-400 shrink-0">{u.role}</span>}
                  </label>
                )
              })}
            </div>
          </div>
        )}

        {error && <p className="text-sm text-red-500">{error}</p>}

        {/* Actions */}
        <div className="flex items-center gap-3 pt-2">
          {isEdit && !confirmDelete && (
            <button
              type="button"
              onClick={() => setConfirmDelete(true)}
              className="text-xs text-red-500 hover:text-red-700 font-medium"
            >
              Delete job
            </button>
          )}
          {confirmDelete && (
            <div className="flex items-center gap-2">
              <span className="text-xs text-red-600 font-medium">Sure?</span>
              <button type="button" onClick={handleDelete} disabled={deleting} className="text-xs text-red-600 font-bold hover:underline">
                {deleting ? '…' : 'Yes, delete'}
              </button>
              <button type="button" onClick={() => setConfirmDelete(false)} className="text-xs text-gray-500">Cancel</button>
            </div>
          )}
          <div className="flex gap-3 ml-auto">
            <button type="button" onClick={() => navigate('/jobs')} className="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">
              Cancel
            </button>
            <button
              type="submit"
              disabled={saving}
              className="px-5 py-2 bg-brand-500 text-white text-sm font-semibold rounded-xl hover:bg-brand-600 disabled:opacity-50 shadow-sm shadow-brand-500/30 transition-colors"
            >
              {saving ? 'Saving…' : isEdit ? 'Save Changes' : 'Create Job'}
            </button>
          </div>
        </div>
      </form>
    </div>
  )
}
