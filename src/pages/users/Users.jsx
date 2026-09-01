import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { getUsers, deleteUser } from '../../api/users'
import { useAuth } from '../../hooks/useAuth'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'

const ROLE_COLORS = {
  Admin:  'bg-brand-100 text-brand-700',
  Office: 'bg-purple-100 text-purple-700',
  Lead:   'bg-amber-100 text-amber-700',
  Crew:   'bg-green-100 text-green-700',
}

export default function Users() {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { user: me, canManageUsers } = useAuth()
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)

  const load = async () => {
    setLoading(true)
    try { setUsers(await getUsers()) } catch {}
    setLoading(false)
  }

  useEffect(() => { load() }, [])

  const handleDelete = async (u) => {
    if (!window.confirm(t('f.deleteUserConfirm', { name: u.name }))) return
    await deleteUser(u.id).catch(() => {})
    load()
  }

  if (!canManageUsers) return <div className="text-center py-16 text-gray-400">{t('f.accessDenied')}</div>

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">{t('f.usersTitle')}</h1>
          <p className="text-sm text-gray-500 mt-0.5">{t('f.accountsCount', { count: users.length })}</p>
        </div>
        <Button onClick={() => navigate('/users/create')}>{t('f.addUserBtn')}</Button>
      </div>

      {loading ? (
        <div className="flex justify-center py-16"><Spinner size="lg" className="text-brand-500" /></div>
      ) : (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{t('f.colName')}</th>
                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{t('f.colFieldclockId')}</th>
                <th className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{t('f.colRole')}</th>
                <th className="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{t('f.colActions')}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {users.map((u) => (
                <tr key={u.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-5 py-3.5 font-medium text-gray-900">
                    {u.name}
                    {u.id === me?.id && <span className="ml-2 text-xs text-brand-500">{t('f.you')}</span>}
                  </td>
                  <td className="px-5 py-3.5 text-gray-500">{u.id}</td>
                  <td className="px-5 py-3.5">
                    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${ROLE_COLORS[u.role] ?? 'bg-gray-100 text-gray-600'}`}>
                      {u.role}
                    </span>
                  </td>
                  <td className="px-5 py-3.5 text-right">
                    <div className="flex gap-2 justify-end">
                      <Button variant="ghost" size="sm" onClick={() => navigate(`/users/${u.id}/edit`)}>{t('f.edit')}</Button>
                      {u.id !== me?.id && (
                        <Button variant="danger" size="sm" onClick={() => handleDelete(u)}>{t('f.delete')}</Button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
