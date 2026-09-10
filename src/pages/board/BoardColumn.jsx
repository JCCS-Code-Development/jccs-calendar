import { useState } from 'react'
import { useBoardT } from './t'

// Hasta MAX_FULL tarjetas se muestran completas. Si hay más, TODAS pasan a
// filas plegables: se ven en una línea y se expanden al tocarlas.
const MAX_FULL = 6

function ChevronIcon({ open }) {
  return (
    <svg
      viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.4}
      className="ops-collapse-row__chev" style={{ transform: open ? 'rotate(90deg)' : 'none' }}
      aria-hidden="true"
    >
      <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
    </svg>
  )
}

export default function BoardColumn({ variant, title, count, items, renderCard, emptyText }) {
  const T = useBoardT()
  const [openIds, setOpenIds] = useState(() => new Set())

  const collapsible = items.length > MAX_FULL
  const toggle = (id) =>
    setOpenIds((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })

  return (
    <section className="ops-col" aria-label={title}>
      <header className={`ops-col__head ops-col__head--${variant}`}>
        <span>{title}</span>
        <span className="ops-col__count">{count}</span>
      </header>

      <div className="ops-col__body ops-col__body--scroll">
        {items.length === 0 ? (
          <p className="ops-col__empty">{emptyText}</p>
        ) : collapsible ? (
          items.map((item) => {
            const open = openIds.has(item.id)
            return (
              <div key={item.id} className="ops-collapse">
                <button
                  type="button"
                  className="ops-collapse-row"
                  aria-expanded={open}
                  onClick={() => toggle(item.id)}
                >
                  <ChevronIcon open={open} />
                  <span className="ops-collapse-row__title">{item.title}</span>
                </button>
                {open && <div className="ops-collapse__body">{renderCard(item)}</div>}
              </div>
            )
          })
        ) : (
          items.map((item) => renderCard(item))
        )}
      </div>

      <footer className="ops-col__foot">
        {collapsible
          ? `${items.length} · ${T.tapToExpand}`
          : `${items.length} ${T.shown}`}
      </footer>
    </section>
  )
}
