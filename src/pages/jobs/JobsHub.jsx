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

// Flip to true when the rebuilt Job Deadlines experience is ready. While
// false the menu item stays visible but the page shows a coming-soon card.
const ENABLED = false

function ComingSoon() {
  const { t } = useTranslation()
  return (
    <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white py-20 px-6 text-center">
      <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-500">
        <svg className="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
          <circle cx="12" cy="12" r="9" />
          <path strokeLinecap="round" d="M12 7v5l3.5 2" />
        </svg>
      </div>
      <p className="text-lg font-bold text-gray-800">{t('jobs.comingSoon')}</p>
      <p className="mt-1 max-w-sm text-sm text-gray-500">{t('jobs.comingSoonBody')}</p>
    </div>
  )
}

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
          <h1 className="text-xl font-extrabold text-gray-900 tracking-tight">
            {t('jobs.hubTitle')}
            {!ENABLED && (
              <span className="ml-2 align-middle rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                {t('nav.soon')}
              </span>
            )}
          </h1>
          <p className="text-sm text-gray-500 mt-0.5">{t('jobs.hubSubtitle')}</p>
        </div>
        {ENABLED && (
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
        )}
      </div>

      {!ENABLED ? (
        <ComingSoon />
      ) : tab === 'deadlines' ? (
        <ProductionCalendar embedded />
      ) : (
        <JobTimelines embedded />
      )}
    </div>
  )
}
