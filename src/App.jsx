
import { useEffect, useState } from 'react'
import AdminDashboard from './components/AdminDashboard'

function App() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [admin, setAdmin] = useState(null)
  const [checkingSession, setCheckingSession] = useState(true)

  // Check if the administrator is already logged in
  useEffect(() => {
    let active = true

    const checkSession = async () => {
      try {
        const response = await fetch('/admin-api/check_session.php', {
          credentials: 'include',
        })

        if (response.ok) {
          const data = await response.json()

          if (active && data.success) {
            setAdmin(data.admin)
          }
        }
      } catch (error) {
        console.error('Session check failed:', error)
      } finally {
        if (active) {
          setCheckingSession(false)
        }
      }
    }

    checkSession()

    return () => {
      active = false
    }
  }, [])

  // Administrator login
  const handleLogin = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)

    try {
      const response = await fetch('/admin-api/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({ email, password }),
      })

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Login failed')
      }

      setAdmin(data.admin)
      setPassword('')
      setError('')
    } catch (err) {
      setError(err.message || 'Unable to connect to server')
    } finally {
      setLoading(false)
    }
  }

  // Administrator logout
  const handleLogout = async () => {
    setError('')

    try {
      const response = await fetch('/admin-api/logout.php', {
        method: 'POST',
        credentials: 'include',
      })

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error('Logout failed')
      }

      setAdmin(null)
      setEmail('')
      setPassword('')
    } catch (error) {
      setError('Unable to log out. Please try again.')
    }
  }

  // Loading screen while checking session
  if (checkingSession) {
    return (
      <img
  src="/proteahealth-logo.png"
  alt="ProteaHealth Logo"
  className="mx-auto mb-4 h-40 w-auto object-contain"
/>
    )
  }

  // Dashboard shown after successful authentication
  if (admin) {
  return (
    <AdminDashboard
      admin={admin}
      onLogout={handleLogout}
    />
  )
}

  // Administrator login page
  return (
    <div className="min-h-screen bg-slate-100 flex items-center justify-center px-4">
      <div className="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

        <div className="text-center mb-8">
          <img
  src="/proteahealth-logo.png"
  alt="ProteaHealth Logo"
  className="mx-auto mb-4 h-40 w-auto object-contain"
/>

          <h1 className="text-3xl font-bold text-green-800">
            Administrator Portal
          </h1>

          
        </div>

        <form onSubmit={handleLogin} className="space-y-5">

          <div>
            <label
              htmlFor="email"
              className="block text-sm font-medium text-slate-700 mb-2"
            >
              Email Address
            </label>

            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="admin@proteahealth.com"
              autoComplete="username"
              required
              className="w-full rounded-lg border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600"
            />
          </div>

          <div>
            <label
              htmlFor="password"
              className="block text-sm font-medium text-slate-700 mb-2"
            >
              Password
            </label>

            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter your password"
              autoComplete="current-password"
              required
              className="w-full rounded-lg border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600"
            />
          </div>

          {error && (
            <p role="alert" className="text-sm text-red-600">
              {error}
            </p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-lg bg-green-700 px-4 py-3 font-semibold text-white hover:bg-green-800 disabled:opacity-50"
          >
            {loading ? 'Signing in...' : 'Sign In'}
          </button>

        </form>

        <p className="mt-8 text-center text-xs text-slate-400">
          ProteaHealth Administration System
        </p>

      </div>
    </div>
  )
}

export default App
