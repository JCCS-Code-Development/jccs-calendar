import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import Input from '../../components/ui/Input'
import Select from '../../components/ui/Select'
import Button from '../../components/ui/Button'
import { getUser, createUser, updateUser, getRoles } from '../../api/users'
import { useAuth } from '../../hooks/useAuth'

export default function UserForm() {
  const navigate = useNavigate()
  const { id } = useParams()
  const isEdit = Boolean(id)
  const { canManageUsers } = useAuth()

  const [loading, setLoading] = useState(isEdit)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [roles, setRoles] = useState([])
  const [form, setForm] = useState({ name: '', email: '', role_id: '', password: '' })

  useEffect(() => {
    getRoles().then(setRoles)
    if (isEdit) {
      getUser(id).then((u) => {
        setForm({ name: u.name, email: u.email, role_id: String(u.role_id ?? ''), password: '' })
        setLoading(false)
      }).catch(() => setLoading(false))
    }
  }, [id, isEdit])

  const set = (key, val) => setForm((f) => ({ ...f, [key]: val }))

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setError('')
    try {
      const payload = { ...form }
      if (isEdit && !payload.password) delete payload.password
      if (isEdit) await updateUser(id, payload)
      else await createUser(payload)
      navigate('/users')
    } catch (err) {
      const msgs = err?.response?.data?.errors
      if (msgs) setError(Object.values(msgs).flat().join(' '))
      else setError(err?.response?.data?.message ?? 'Failed to save.')
    } finally {
      setSaving(false)
    }
  }

  if (!canManageUsers) return <div className="text-center py-16 text-gray-400">Access denied.</div>
  if (loading) return <div className="flex justify-center py-16 text-gray-400">Loading...</div>

  return (
    <div className="max-w-lg mx-auto">
      <div className="flex items-center gap-3 mb-6">
        <button onClick={() => navigate(-1)} className="text-gray-400 hover:text-gray-600 p-1">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">{isEdit ? 'Edit User' : 'Add User'}</h1>
      </div>

      <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{error}</div>
        )}

        <Input label="Full Name" value={form.name} onChange={(e) => set('name', e.target.value)} required placeholder="John Doe" />
        <Input label="Email Address" type="email" value={form.email} onChange={(e) => set('email', e.target.value)} required placeholder="john@example.com" />

        <Select label="Role" value={form.role_id} onChange={(e) => set('role_id', e.target.value)} required>
          <option value="">-- Select Role --</option>
          {roles.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
        </Select>

        <Input
          label={isEdit ? 'New Password (leave blank to keep)' : 'Password'}
          type="password"
          value={form.password}
          onChange={(e) => set('password', e.target.value)}
          required={!isEdit}
          placeholder={isEdit ? 'Leave blank to keep current' : 'Min. 8 characters'}
          autoComplete="new-password"
        />

        <div className="flex gap-3 pt-2">
          <Button type="submit" loading={saving} size="lg" className="flex-1">
            {isEdit ? 'Save Changes' : 'Create User'}
          </Button>
          <Button variant="secondary" size="lg" onClick={() => navigate(-1)}>Cancel</Button>
        </div>
      </form>
    </div>
  )
}
