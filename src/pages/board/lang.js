import i18n from '../../i18n'

// El idioma activo de la app (el que fija el botón EN/ES del calendario).
// 'es' | 'en'.
export function boardLang() {
  return (i18n.language || 'en').toLowerCase().startsWith('es') ? 'es' : 'en'
}

// Locale para Intl.DateTimeFormat según el idioma de la app.
export function boardLocale() {
  return boardLang() === 'es' ? 'es-US' : 'en-US'
}
