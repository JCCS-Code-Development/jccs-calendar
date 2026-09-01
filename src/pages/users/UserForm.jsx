import { useState, useEffect } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Input from '../../components/ui/Input'
import Select from '../../components/ui/Select'
import Button from '../../components/ui/Button'
import { getUser, createUser, updateUser, getRoles } from '../../api/users'
import { listEmployees } from '../../api/fieldclockAuth'
import { useAuth } from '../../hooks/useAuth'
import EmployeeCombobox from '../../components/users/EmployeeCombobox'

// There's no local signup — a Calendar "user" is an existing FieldClock
// account with a Calendar role attached (see api/users/index.php). On
// create, pick who from FieldClock's employee list; on edit, the identity
// (fieldclock_user_id) is fixed, only name/role can change.
export default function UserForm() {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { id } = useParams()
  const isEdit = Boolean(id)
  const { canManageUsers } = useAuth()

  const [loading, setLoading] = useState(isEdit)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [roles, setRoles] = useState([])
  const [employees, setEmployees] = useState([])
  const [employeesFailed, setEmployeesFailed] = useState(false)
  const [form, setForm] = useState({ fieldclock_user_id: '', name: '', role_id: '', outlook_ics_url: '' })

  useEffect(() => {
    getRoles().then(setRoles)
    if (!isEdit) {
      listEmployees().then((data) => setEmployees(data.employees ?? [])).catch(() => setEmployeesFailed(true))
    }
    if (isEdit) {
      getUser(id).then((u) => {
        setForm({
          fieldclock_user_id: String(u.id),
          name: u.name,
          role_id: String(u.role_id ?? ''),
          outlook_ics_url: u.outlook_ics_url ?? '',
        })
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
      const outlook = form.outlook_ics_url.trim()
      if (isEdit) await updateUser(id, { name: form.name, role_id: form.role_id, outlook_ics_url: outlook })
      else await createUser({ fieldclock_user_id: Number(form.fieldclock_user_id), name: form.name, role_id: form.role_id, outlook_ics_url: outlook })
      navigate('/users')
    } catch (err) {
      setError(err?.response?.data?.error ?? t('f.saveFailed'))
    } finally {
      setSaving(false)
    }
  }

  if (!canManageUsers) return <div className="text-center py-16 text-gray-400">{t('f.accessDenied')}</div>
  if (loading) return <div className="flex justify-center py-16 text-gray-400">{t('f.loading')}</div>

  return (
    <div className="max-w-lg mx-auto">
      <div className="flex items-center gap-3 mb-6">
        <button onClick={() => navigate(-1)} className="text-gray-400 hover:text-gray-600 p-1">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">{isEdit ? t('f.editUserTitle') : t('f.addUserTitle')}</h1>
      </div>

      <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">{error}</div>
        )}

        {!isEdit && !employeesFailed && (
          <EmployeeCombobox
            employees={employees}
            value={form.fieldclock_user_id}
            onSelect={(emp) =>
              setForm((f) => ({
                ...f,
                fieldclock_user_id: emp ? String(emp.id) : '',
                name: emp?.name ?? f.name,
              }))
            }
            required
          />
        )}
        {!isEdit && employeesFailed && (
          <Input
            label={t('f.fieldclockEmployeeId')}
            type="number"
            value={form.fieldclock_user_id}
            onChange={(e) => set('fieldclock_user_id', e.target.value)}
            required
            placeholder={t('f.fieldclockIdPlaceholder')}
          />
        )}
        {isEdit && (
          <Input label={t('f.fieldclockEmployeeId')} value={form.fieldclock_user_id} disabled />
        )}

        <Input label={t('f.fullName')} value={form.name} onChange={(e) => set('name', e.target.value)} required placeholder={t('f.fullNamePlaceholder')} />

        <Select label={t('users.role')} value={form.role_id} onChange={(e) => set('role_id', e.target.value)} required>
          <option value="">{t('f.selectRole')}</option>
          {roles.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
        </Select>

        <Input
          label={t('f.outlookIcsUrl')}
          type="url"
          value={form.outlook_ics_url}
          onChange={(e) => set('outlook_ics_url', e.target.value)}
          placeholder="https://outlook.office365.com/owa/calendar/…/calendar.ics"
          helperText={t('f.outlookIcsHelp')}
        />

        <div className="flex gap-3 pt-2">
          <Button type="submit" loading={saving} size="lg" className="flex-1">
            {isEdit ? t('f.saveChanges') : t('f.createUserBtn')}
          </Button>
          <Button variant="secondary" size="lg" onClick={() => navigate(-1)}>{t('f.cancel')}</Button>
        </div>
      </form>
    </div>
  )
}
