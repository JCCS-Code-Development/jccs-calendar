import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Input from '../../components/ui/Input'
import Button from '../../components/ui/Button'
import { login as fieldclockLogin } from '../../api/fieldclockAuth'
import { verify } from '../../api/auth'
import { useAuthStore } from '../../store/authStore'

function SwirlBackground() {
  return (
    <svg
      className="absolute inset-0 w-full h-full"
      viewBox="0 0 800 600"
      preserveAspectRatio="xMidYMid slice"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
    >
      <defs>
        <linearGradient id="s1" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stopColor="#3b82f6" stopOpacity="0" />
          <stop offset="50%" stopColor="#60a5fa" stopOpacity="0.32" />
          <stop offset="100%" stopColor="#93c5fd" stopOpacity="0" />
        </linearGradient>
        <linearGradient id="s2" x1="100%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stopColor="#1d4ed8" stopOpacity="0" />
          <stop offset="50%" stopColor="#3b82f6" stopOpacity="0.26" />
          <stop offset="100%" stopColor="#60a5fa" stopOpacity="0" />
        </linearGradient>
        <linearGradient id="s3" x1="0%" y1="100%" x2="100%" y2="0%">
          <stop offset="0%" stopColor="#2563eb" stopOpacity="0" />
          <stop offset="40%" stopColor="#60a5fa" stopOpacity="0.20" />
          <stop offset="100%" stopColor="#bfdbfe" stopOpacity="0" />
        </linearGradient>
        <linearGradient id="s4" x1="50%" y1="0%" x2="50%" y2="100%">
          <stop offset="0%" stopColor="#3b82f6" stopOpacity="0.16" />
          <stop offset="100%" stopColor="#1d4ed8" stopOpacity="0" />
        </linearGradient>
      </defs>

      {/* Sweep 1 — top-left diagonal, wide ribbon */}
      <path
        d="M-100 -60 C 80 60, 320 -10, 440 180 C 560 370, 210 440, 360 590 C 510 740, 720 570, 880 610"
        fill="none"
        stroke="url(#s1)"
        strokeWidth="46"
        strokeLinecap="round"
      />
      {/* Sweep 2 — top-right descending, offset well away from sweep 1 */}
      <path
        d="M 920 -80 C 740 80, 600 -40, 480 150 C 360 340, 620 370, 520 510 C 420 650, 180 550, 100 670"
        fill="none"
        stroke="url(#s2)"
        strokeWidth="34"
        strokeLinecap="round"
      />
      {/* Sweep 3 — bottom-left rising, tighter ribbon */}
      <path
        d="M -80 640 C 80 520, 220 580, 350 440 C 480 300, 330 210, 510 110 C 690 10, 770 90, 880 -60"
        fill="none"
        stroke="url(#s3)"
        strokeWidth="22"
        strokeLinecap="round"
      />
      {/* Sweep 4 — centre diagonal, fine accent */}
      <path
        d="M 180 -30 C 250 130, 155 250, 310 340 C 465 430, 555 310, 615 470 C 675 630, 570 690, 690 750"
        fill="none"
        stroke="url(#s4)"
        strokeWidth="14"
        strokeLinecap="round"
      />
      {/* Hair-line edge accents */}
      <path
        d="M 0 290 C 130 255, 210 315, 360 272 C 510 229, 540 148, 700 192 C 860 236, 840 332, 960 292"
        fill="none"
        stroke="#60a5fa"
        strokeWidth="1.5"
        strokeOpacity="0.15"
        strokeLinecap="round"
      />
      <path
        d="M 0 390 C 150 348, 270 408, 420 366 C 570 324, 600 242, 760 262 C 920 282, 900 368, 970 348"
        fill="none"
        stroke="#93c5fd"
        strokeWidth="1"
        strokeOpacity="0.10"
        strokeLinecap="round"
      />
    </svg>
  )
}

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
    <div className="relative min-h-svh flex flex-col items-center justify-center bg-brand-900 px-6 overflow-hidden">
      <SwirlBackground />
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
