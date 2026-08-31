import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Input from '../../components/ui/Input'
import Button from '../../components/ui/Button'
import { login as fieldclockLogin } from '../../api/fieldclockAuth'
import { verify } from '../../api/auth'
import { useAuthStore } from '../../store/authStore'

function LangToggleLogin() {
  const { i18n } = useTranslation()
  const current = i18n.language
  const toggle = () => {
    const next = current === 'en' ? 'es' : 'en'
    i18n.changeLanguage(next)
    localStorage.setItem('jccs_lang', next)
  }
  return (
    <button onClick={toggle} className="absolute top-4 right-4 z-20 text-xs font-semibold text-brand-100/60 hover:text-white bg-brand-800/60 px-2.5 py-1 rounded-lg transition-colors">
      {current === 'en' ? 'ES' : 'EN'}
    </button>
  )
}

export default function Login() {
  const navigate = useNavigate()
  const { t } = useTranslation()
  const { login: storeLogin } = useAuthStore()
  const [identifier, setIdentifier] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!identifier.trim()) { setError(t('auth.identifierRequired')); return }
    setLoading(true)
    setError('')
    try {
      // Two steps, since Calendar has no login of its own: authenticate
      // against FieldClock, then resolve the resulting token to a
      // Calendar-specific role via /auth/verify. Stash the token first
      // (updateToken, not login) so verify()'s request can carry it —
      // storeLogin only finalizes the session once we know the role.
      const { token, refreshToken } = await fieldclockLogin(identifier.trim(), password)
      useAuthStore.getState().updateToken(token, refreshToken)
      const user = await verify()
      storeLogin(user, token, refreshToken)
      navigate('/', { replace: true })
    } catch (err) {
      useAuthStore.getState().logout()
      const status = err?.response?.status
      if (status === 403) {
        setError('Your account is not set up for Calendar yet. Contact your administrator.')
      } else {
        setError(err?.response?.data?.error ?? t('auth.invalidCreds'))
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="relative min-h-svh flex flex-col items-center justify-center bg-brand-900 px-6">
      <LangToggleLogin />

      <div className="relative z-10 w-full max-w-sm">
        <div className="text-center mb-8">
          <img
            src="/jccs-logo.jpg"
            alt="JCCS Services"
            className="h-14 mx-auto mb-4"
            style={{ filter: 'invert(1) brightness(10)' }}
          />
          <h1 className="text-2xl font-extrabold text-white tracking-tight mb-1">
            JCCS Calendar
          </h1>
          <p className="text-brand-100/60 text-sm">{t('auth.signInToContinue')}</p>
        </div>

        <form
          onSubmit={handleSubmit}
          className="bg-white/95 backdrop-blur-sm rounded-2xl p-7 shadow-2xl shadow-black/40 flex flex-col gap-4 border border-white/10"
        >
          <Input
            label={t('auth.identifier')}
            type="text"
            inputMode="email"
            placeholder={t('auth.identifierPlaceholder')}
            value={identifier}
            onChange={(e) => setIdentifier(e.target.value)}
            autoComplete="username"
          />
          <Input
            label={t('auth.password')}
            type="password"
            placeholder="••••••••"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            error={error}
            autoComplete="current-password"
          />
          <Button type="submit" fullWidth size="lg" loading={loading}>
            {loading ? t('auth.signingIn') : t('auth.signIn')}
          </Button>
        </form>
      </div>
    </div>
  )
}
