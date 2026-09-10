import { useState, useRef, useLayoutEffect, useEffect, useCallback } from 'react'
import { useBoardT } from './t'

// A board column that never shrinks its text: it measures how many cards
// actually fit and, when there are more, rotates through "pages" on a timer
// instead of scaling anything down.
const ROTATE_MS = 12000

export default function BoardColumn({ variant, title, count, items, renderCard, emptyText }) {
  const T = useBoardT()
  const bodyRef = useRef(null)
  const measureRef = useRef(null)
  const [perPage, setPerPage] = useState(items.length || 1)
  const [page, setPage] = useState(0)

  const recompute = useCallback(() => {
    const body = bodyRef.current
    const meas = measureRef.current
    if (!body || !meas) return
    const avail = body.clientHeight
    const style = getComputedStyle(body)
    const gap = parseFloat(style.rowGap || style.gap) || 12
    let used = 0
    let n = 0
    for (const child of meas.children) {
      const h = child.getBoundingClientRect().height
      const next = used + (n > 0 ? gap : 0) + h
      if (next > avail && n > 0) break
      used = next
      n++
    }
    setPerPage(Math.max(1, n))
  }, [])

  useLayoutEffect(() => {
    recompute()
  }, [recompute, items])

  useEffect(() => {
    const body = bodyRef.current
    if (!body || typeof ResizeObserver === 'undefined') return
    const ro = new ResizeObserver(recompute)
    ro.observe(body)
    return () => ro.disconnect()
  }, [recompute])

  const pages = Math.max(1, Math.ceil(items.length / perPage))

  useEffect(() => {
    if (page > pages - 1) setPage(0)
  }, [page, pages])

  useEffect(() => {
    if (pages <= 1) return
    const id = setInterval(() => setPage((p) => (p + 1) % pages), ROTATE_MS)
    return () => clearInterval(id)
  }, [pages])

  const start = page * perPage
  const shown = items.slice(start, start + perPage)

  return (
    <section className="ops-col" aria-label={title}>
      <header className={`ops-col__head ops-col__head--${variant}`}>
        <span>{title}</span>
        <span className="ops-col__count">{count}</span>
      </header>

      <div className="ops-col__body" ref={bodyRef}>
        {items.length === 0 ? (
          <p className="ops-col__empty">{emptyText}</p>
        ) : (
          <div key={page} className="ops-page-enter" style={{ display: 'contents' }}>
            {shown.map((item) => renderCard(item))}
          </div>
        )}

        {/* hidden measuring pass — full list, same styles, never shown */}
        <div
          ref={measureRef}
          aria-hidden="true"
          style={{
            position: 'absolute',
            visibility: 'hidden',
            pointerEvents: 'none',
            left: -99999,
            width: bodyRef.current ? bodyRef.current.clientWidth : 320,
            display: 'flex',
            flexDirection: 'column',
            gap: 12,
          }}
        >
          {items.map((item) => renderCard(item))}
        </div>
      </div>

      <footer className="ops-col__foot">
        {pages > 1 ? (
          <>
            {Array.from({ length: pages }).map((_, i) => (
              <span key={i} className={`ops-col__dot ${i === page ? 'ops-col__dot--on' : ''}`} />
            ))}
            <span style={{ marginLeft: '0.5em' }}>
              {T.page} {page + 1} / {pages}
            </span>
          </>
        ) : (
          <span>{items.length} {T.shown}</span>
        )}
      </footer>
    </section>
  )
}
