import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import Input from '../../components/ui/Input'
import Button from '../../components/ui/Button'
import { login } from '../../api/auth'
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
          <stop offset="50%" stopColor="#60a5fa" stopOpacity="0.35" />
          <stop offset="100%" stopColor="#93c5fd" stopOpacity="0" />
        </linearGradient>
        <linearGradient id="s2" x1="100%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stopColor="#1d4ed8" stopOpacity="0" />
          <stop offset="50%" stopColor="#3b82f6" stopOpacity="0.28" />
          <stop offset="100%" stopColor="#60a5fa" stopOpacity="0" />
        </linearGradient>
        <linearGradient id="s3" x1="0%" y1="100%" x2="100%" y2="0%">
          <stop offset="0%" stopColor="#2563eb" stopOpacity="0" />
          <stop offset="40%" stopColor="#60a5fa" stopOpacity="0.22" />
          <stop offset="100%" stopColor="#bfdbfe" stopOpacity="0" />
        </linearGradient>
        <linearGradient id="s4" x1="50%" y1="0%" x2="50%" y2="100%">
          <stop offset="0%" stopColor="#3b82f6" stopOpacity="0.18" />
          <stop offset="100%" stopColor="#1d4ed8" stopOpacity="0" />
        </linearGradient>
      </defs>

      {/* Large background sweep — top-left */}
      <path
        d="M-80 -40 C 60 80, 300 20, 420 200 C 540 380, 200 460, 340 600 C 480 740, 700 580, 860 620"
        fill="none"
        stroke="url(#s1)"
        strokeWidth="120"
        strokeLinecap="round"
      />
      {/* Mid sweep — top-right descending */}
      <path
        d="M 900 -60 C 720 100, 580 -20, 460 160 C 340 340, 600 380, 500 520 C 400 660, 160 560, 80 680"
        fill="none"
        stroke="url(#s2)"
        strokeWidth="90"
        strokeLinecap="round"
      />
      {/* Thin accent — bottom-left rising */}
      <path
        d="M -60 620 C 100 500, 240 560, 360 420 C 480 280, 320 200, 500 100 C 680 0, 760 80, 860 -40"
        fill="none"
        stroke="url(#s3)"
        strokeWidth="55"
        strokeLinecap="round"
      />
      {/* Fine highlight — centre */}
      <path
        d="M 200 -20 C 260 140, 160 260, 320 340 C 480 420, 560 300, 620 460 C 680 620, 580 680, 700 740"
        fill="none"
        stroke="url(#s4)"
        strokeWidth="38"
        strokeLinecap="round"
      />
      {/* Very thin bright edge line */}
      <path
        d="M 0 300 C 120 260, 200 320, 340 280 C 480 240, 520 160, 680 200 C 840 240, 820 340, 950 300"
        fill="none"
        stroke="#60a5fa"
        strokeWidth="1.5"
        strokeOpacity="0.18"
        strokeLinecap="round"
      />
      <path
        d="M 0 380 C 140 340, 260 400, 400 360 C 540 320, 580 240, 740 260 C 900 280, 880 360, 960 340"
        fill="none"
        stroke="#93c5fd"
        strokeWidth="1"
        strokeOpacity="0.12"
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
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!email.trim()) { setError(t('auth.email') + ' required'); return }
    setLoading(true)
    setError('')
    try {
      const data = await login(email.trim(), password)
      storeLogin(data.user, data.token)
      navigate('/', { replace: true })
    } catch (err) {
      setError(err?.response?.data?.message ?? t('auth.invalidCreds'))
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
            className="h-14 mx-auto mb-5"
            style={{ filter: 'invert(1) brightness(10)' }}
          />
          <p className="text-brand-100/70 text-sm">{t('auth.signInToContinue')}</p>
        </div>

        <form
          onSubmit={handleSubmit}
          className="bg-white/95 backdrop-blur-sm rounded-2xl p-7 shadow-2xl shadow-black/40 flex flex-col gap-4 border border-white/10"
        >
          <Input
            label={t('auth.email')}
            type="email"
            inputMode="email"
            placeholder="you@example.com"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            autoComplete="email"
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
