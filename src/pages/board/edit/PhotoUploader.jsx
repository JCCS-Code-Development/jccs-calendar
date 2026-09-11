import { useState, useRef, useEffect } from 'react'
import { addJobPhoto, removeJobPhoto } from '../../../api/board'
import { useBoardT } from '../t'

const FIELD =
  'w-full rounded-xl border border-gray-300 px-4 py-3 text-base outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100'
const LEGEND = 'text-xs font-bold text-gray-500 uppercase tracking-wide px-1'

// Job-site photos, managed from Edit Mode. Multi-file picker + paste from the
// clipboard (Ctrl+V anywhere in this box). Each upload is its own request;
// the shared caption applies to everything added in one go.
export default function PhotoUploader({ jobId, photos = [] }) {
  const T = useBoardT()
  const [items, setItems] = useState(photos)
  const [caption, setCaption] = useState('')
  const [busy, setBusy] = useState(false)
  const [err, setErr] = useState('')
  const fileRef = useRef(null)

  useEffect(() => { setItems(photos) }, [photos])

  if (!jobId) {
    return (
      <fieldset className="border border-gray-200 rounded-xl p-4">
        <legend className={LEGEND}>{T.photos}</legend>
        <p className="text-sm text-gray-500">{T.photoSaveFirst}</p>
      </fieldset>
    )
  }

  const upload = async (files) => {
    const list = Array.from(files).filter((f) => f.type.startsWith('image/'))
    if (!list.length) return
    setBusy(true)
    setErr('')
    try {
      let latest = items
      for (const f of list) {
        const job = await addJobPhoto(jobId, f, caption)
        latest = job.photos ?? latest
        setItems(latest)
      }
      setCaption('')
    } catch (e) {
      setErr(e?.response?.data?.error ?? T.photoError)
    }
    setBusy(false)
    if (fileRef.current) fileRef.current.value = ''
  }

  const remove = async (photoId) => {
    setBusy(true)
    setErr('')
    try {
      const job = await removeJobPhoto(jobId, photoId)
      setItems(job.photos ?? [])
    } catch (e) {
      setErr(e?.response?.data?.error ?? T.photoError)
    }
    setBusy(false)
  }

  const onPaste = (e) => {
    const imgs = Array.from(e.clipboardData?.items ?? [])
      .filter((it) => it.type.startsWith('image/'))
      .map((it) => it.getAsFile())
      .filter(Boolean)
    if (imgs.length) {
      e.preventDefault()
      upload(imgs)
    }
  }

  return (
    <fieldset className="border border-gray-200 rounded-xl p-4 flex flex-col gap-3" onPaste={onPaste}>
      <legend className={LEGEND}>
        {T.photos}
        {items.length ? ` (${items.length})` : ''}
      </legend>

      {items.length > 0 && (
        <div className="grid grid-cols-3 sm:grid-cols-4 gap-2">
          {items.map((p) => (
            <div key={p.id} className="min-w-0">
              <div className="relative">
                <img
                  src={p.url}
                  alt={p.caption || ''}
                  className="w-full h-24 object-cover rounded-lg border border-gray-200 bg-gray-100"
                />
                <button
                  type="button"
                  onClick={() => remove(p.id)}
                  disabled={busy}
                  title={T.photoRemove}
                  aria-label={T.photoRemove}
                  className="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white text-sm leading-none disabled:opacity-50"
                >
                  ×
                </button>
              </div>
              {p.caption && <p className="text-[11px] text-gray-500 mt-0.5 line-clamp-2">{p.caption}</p>}
            </div>
          ))}
        </div>
      )}

      <input
        type="text"
        className={FIELD}
        placeholder={T.photoCaption}
        value={caption}
        onChange={(e) => setCaption(e.target.value)}
      />

      <div className="flex items-center gap-3 flex-wrap">
        <input
          ref={fileRef}
          type="file"
          accept="image/*"
          multiple
          onChange={(e) => upload(e.target.files)}
          className="text-sm"
        />
        {busy && <span className="text-sm text-brand-600 font-semibold">{T.photoUploading}</span>}
      </div>

      <p className="text-xs text-gray-400">{T.photoPasteHint}</p>
      {err && <p className="text-sm text-red-600 font-medium">{err}</p>}
    </fieldset>
  )
}
