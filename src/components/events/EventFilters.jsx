import { useTranslation } from 'react-i18next'
import Select from '../ui/Select'
import { DATE_RANGES } from '../../utils/format'

export default function EventFilters({ filters, onChange, eventTypes, users, showUserFilter = false }) {
  const { t } = useTranslation()
  const set = (key, val) => onChange({ ...filters, [key]: val })

  return (
    <div className="flex flex-wrap gap-2 mb-4">
      <Select
        value={filters.date_range ?? 'today_tomorrow'}
        onChange={(e) => set('date_range', e.target.value)}
        className="w-44"
      >
        {DATE_RANGES.map((r) => (
          <option key={r.value} value={r.value}>{r.label}</option>
        ))}
      </Select>

      <Select
        value={filters.event_type_id ?? ''}
        onChange={(e) => set('event_type_id', e.target.value || undefined)}
        className="w-40"
      >
        <option value="">{t('f.allTypes')}</option>
        {eventTypes.map((t) => (
          <option key={t.id} value={t.id}>{t.name}</option>
        ))}
      </Select>

      <Select
        value={filters.status ?? ''}
        onChange={(e) => set('status', e.target.value || undefined)}
        className="w-36"
      >
        <option value="">{t('f.allStatuses')}</option>
        {['Scheduled', 'Completed', 'Cancelled', 'In Progress'].map((s) => (
          <option key={s} value={s}>{s}</option>
        ))}
      </Select>

      {showUserFilter && users?.length > 0 && (
        <Select
          value={filters.assigned_user_id ?? ''}
          onChange={(e) => set('assigned_user_id', e.target.value || undefined)}
          className="w-40"
        >
          <option value="">{t('f.allUsers')}</option>
          {users.map((u) => (
            <option key={u.id} value={u.id}>{u.name}</option>
          ))}
        </Select>
      )}
    </div>
  )
}
