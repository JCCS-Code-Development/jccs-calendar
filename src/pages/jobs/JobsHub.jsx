import { useNavigate, useSearchParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useAuth } from '../../hooks/useAuth'
import ProductionCalendar from '../production/ProductionCalendar'
import JobTimelines from './JobTimelines'

// One screen for job scheduling, two lenses on the same `jobs` data:
//   Deadlines — each job on its projected completion date (calendar)
//   Timeline  — Gantt bars across the schedule
// Replaces the separate /production and /jobs pages in the menu.
const TABS = [
  { key: 'deadlines', labelKey: 'jobs.tabDeadlines' },
  { key: 'timeline',  labelKey: 'jobs.tabTimeline' },
]

export default function JobsHub() {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { canManageEvents } = useAuth()
  const [params, setParams] = useSearchParams()

  const tab = params.get('view') === 'timeline' ? 'timeline' : 'deadlines'
  const setTab = (key) =>
    setParams(key === 'deadlines' ? {} : { view: key }, { replace: true })

  return (
    <div>
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">{t('jobs.hubTitle')}</h1>
          <p className="text-sm text-gray-500 mt-0.5">{t('jobs.hubSubtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <div className="flex items-center bg-gray-100 rounded-xl p-1 gap-1">
            {TABS.map(({ key, labelKey }) => (
              <button
                key={key}
                onClick={() => setTab(key)}
                className={`px-3.5 py-1.5 text-sm font-semibold rounded-lg transition-colors ${
                  tab === key ? 'bg-white text-brand-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'
                }`}
              >
                {t(labelKey)}
              </button>
            ))}
          </div>
          {canManageEvents && (
            <button
              onClick={() => navigate('/jobs/create')}
              className="bg-brand-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-brand-600 transition-colors shadow-sm shadow-brand-500/30"
            >
              {t('jobs.newJob')}
            </button>
          )}
        </div>
      </div>

      {tab === 'deadlines' ? <ProductionCalendar embedded /> : <JobTimelines embedded />}
    </div>
  )
}
