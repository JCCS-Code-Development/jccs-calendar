import { fmtDateYear } from './helpers'
import { useBoardT } from '../t'

// Cross-app info for a job that carries a 4-digit Estimate #:
//  - job.project         — canonical name/client/status from Inventory
//  - job.project_summary  — live status from the Projects app
//  - job.links            — deep links into Inventory / Projects
export default function ProjectMeta({ job }) {
  const T = useBoardT()
  const p = job.project
  const s = job.project_summary
  const links = job.links
  if (!p && !s && !links) return null

  return (
    <div className="ops-pm">
      {p && (
        <div className="ops-pm__line">
          📁 <b>{p.name}</b>
          {p.client_name ? ` · ${p.client_name}` : ''}
          {p.is_active === false && <span className="ops-pm__muted"> {T.pmInactive}</span>}
        </div>
      )}

      {s && s.in_projects && (
        <div className="ops-pm__chips">
          <span className="ops-pm__chip">
            {T.pmLastLog}:{' '}
            <b>{s.last_daily_log ? fmtDateYear(s.last_daily_log) : T.pmNoLogs}</b>
          </span>
          {s.open_punch_items > 0 && (
            <span className="ops-pm__chip ops-pm__chip--warn">🔧 {T.pmOpenPunch(s.open_punch_items)}</span>
          )}
          {s.current_phase && (
            <span className="ops-pm__chip">{T.pmPhase}: <b>{s.current_phase}</b></span>
          )}
        </div>
      )}
      {s && !s.in_projects && p && (
        <div className="ops-pm__muted ops-pm__chips">{T.pmNotInProjects}</div>
      )}

      {links && (
        <div className="ops-pm__links">
          {links.projects && (
            <a className="ops-pm__link" href={links.projects} target="_blank" rel="noopener noreferrer">
              {T.openInProjects}
            </a>
          )}
          {links.inventory && (
            <a className="ops-pm__link" href={links.inventory} target="_blank" rel="noopener noreferrer">
              {T.openInInventory}
            </a>
          )}
        </div>
      )}
    </div>
  )
}
