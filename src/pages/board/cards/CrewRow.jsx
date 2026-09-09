import { T } from '../t'

// Fichas de cuadrilla en una tarjeta de trabajo. Cada una se puede quitar (✕).
// Al arrastrar una persona sobre la tarjeta, muestra la pista "Suelta para asignar".
export default function CrewRow({ job, over, onRemove }) {
  const workers = job.workers ?? []
  return (
    <div className={`ops-crew ${over ? 'is-drop' : ''}`}>
      <span className="ops-crew__label">{T.crew}</span>
      {workers.length === 0 && !over && <span className="ops-crew__none">{T.crewEmpty}</span>}
      {over && <span className="ops-crew__hint">{T.dropToAssign}</span>}
      {workers.map((w) => (
        <span key={w.id} className="ops-crew__chip">
          {w.name || `#${w.id}`}
          <button
            type="button"
            className="ops-crew__x"
            aria-label={T.removeFrom(w.name || 'trabajador', job.title)}
            onClick={() => onRemove(w.id)}
          >
            ✕
          </button>
        </span>
      ))}
    </div>
  )
}
