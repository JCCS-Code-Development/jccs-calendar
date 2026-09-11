import { useState, useEffect, useCallback } from 'react'
import { useBoardT } from '../t'

// Job-site photo thumbnails on a TV card. Shows the first few inline; tap any
// one to open a full-screen lightbox (big enough to read from across the
// office) with the caption and prev/next.
const INLINE = 4

export default function PhotoStrip({ photos }) {
  const T = useBoardT()
  const list = photos ?? []
  const [open, setOpen] = useState(-1)

  const close = useCallback(() => setOpen(-1), [])
  const step = useCallback(
    (d) => setOpen((i) => (i < 0 ? i : (i + d + list.length) % list.length)),
    [list.length],
  )

  useEffect(() => {
    if (open < 0) return
    const onKey = (e) => {
      if (e.key === 'Escape') close()
      else if (e.key === 'ArrowRight') step(1)
      else if (e.key === 'ArrowLeft') step(-1)
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, close, step])

  if (!list.length) return null
  const shown = list.slice(0, INLINE)
  const cur = open >= 0 ? list[open] : null

  return (
    <>
      <div className="ops-photos">
        {shown.map((p, i) => (
          <button
            key={p.id}
            type="button"
            className="ops-photo"
            onClick={() => setOpen(i)}
            aria-label={p.caption || T.photoOf(i + 1, list.length)}
          >
            <img src={p.url} alt={p.caption || ''} loading="lazy" />
            {i === INLINE - 1 && list.length > INLINE && (
              <span className="ops-photo__more">+{list.length - INLINE}</span>
            )}
          </button>
        ))}
      </div>

      {cur && (
        <div className="ops-lightbox" role="dialog" aria-modal="true" onClick={close}>
          <img
            className="ops-lightbox__img"
            src={cur.url}
            alt={cur.caption || ''}
            onClick={(e) => e.stopPropagation()}
          />
          {cur.caption && <p className="ops-lightbox__cap">{cur.caption}</p>}
          <div className="ops-lightbox__bar" onClick={(e) => e.stopPropagation()}>
            {list.length > 1 && (
              <button type="button" className="ops-lightbox__nav" onClick={() => step(-1)} aria-label="‹">
                ‹
              </button>
            )}
            <span className="ops-lightbox__count">{T.photoOf(open + 1, list.length)}</span>
            {list.length > 1 && (
              <button type="button" className="ops-lightbox__nav" onClick={() => step(1)} aria-label="›">
                ›
              </button>
            )}
            <button type="button" className="ops-lightbox__close" onClick={close}>
              {T.photoClose}
            </button>
          </div>
        </div>
      )}
    </>
  )
}
