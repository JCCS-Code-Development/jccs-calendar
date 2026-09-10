import { useState, useEffect, useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { useBoardT } from './t'
import { boardLocale } from './lang'

const TZ = 'America/New_York'

export default function BoardFooter({ lastSync, online, editMode, onEnterEdit, onExitEdit, onRest, onExit }) {
  const T = useBoardT()
  const { i18n } = useTranslation()
  const isEs = (i18n.language || 'en').startsWith('es')
  const toggleLang = () => {
    const next = isEs ? 'en' : 'es'
    i18n.changeLanguage(next)
    try { localStorage.setItem('jccs_lang', next) } catch { /* private mode */ }
  }
  const [isFs, setIsFs] = useState(() => !!document.fullscreenElement)
  const syncFmt = useMemo(
    () => new Intl.DateTimeFormat(boardLocale(), { timeZone: TZ, hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true }),
    [T]
  )

  useEffect(() => {
    const on = () => setIsFs(!!document.fullscreenElement)
    document.addEventListener('fullscreenchange', on)
    return () => document.removeEventListener('fullscreenchange', on)
  }, [])

  const toggleFs = () => {
    if (document.fullscreenElement) document.exitFullscreen()
    else document.documentElement.requestFullscreen?.()
  }

  return (
    <footer className="ops-footer">
      <div className="ops-footer__group">
        {onExit && (
          <button className="ops-btn" onClick={onExit} title={`${T.exitToApp} (Esc)`}>
            ← {T.exitToApp}
          </button>
        )}
        <span>
          {T.lastUpdated}{' '}
          <b>{lastSync ? syncFmt.format(lastSync) : '—'}</b>
        </span>
        <span className="ops-status">
          <span className={`ops-status__dot ops-status__dot--${online ? 'online' : 'offline'}`} />
          {online ? T.online : T.offline}
        </span>
      </div>

      <div className="ops-footer__group">
        <button className="ops-btn" onClick={toggleLang} title={isEs ? 'Switch to English' : 'Cambiar a Español'}>
          {isEs ? 'EN' : 'ES'}
        </button>
        {onRest && (
          <button className="ops-btn" onClick={onRest}>
            {T.restNow}
          </button>
        )}
        <button className="ops-btn" onClick={toggleFs}>
          {isFs ? T.exitFullscreen : T.fullscreen}
        </button>
        {editMode ? (
          <button className="ops-btn ops-btn--edit" onClick={onExitEdit}>
            {T.exitEditMode}
          </button>
        ) : (
          <button className="ops-btn ops-btn--edit" onClick={onEnterEdit}>
            {T.editMode}
          </button>
        )}
      </div>
    </footer>
  )
}
