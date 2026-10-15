
import { useEffect, useState } from 'react'
import {
  UserRound,
  Mail,
  ShieldCheck,
  LockKeyhole,
  RefreshCw,
  AlertCircle,
  Pencil,
  Save,
  X,
  CheckCircle2,
  Eye,
  EyeOff,
} from 'lucide-react'

const emptyPasswords = {
  current_password: '',
  new_password: '',
  confirm_password: '',
}

function AdminSettings() {
  const [admin, setAdmin] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  // Profile editing
  const [isEditing, setIsEditing] = useState(false)
  const [saving, setSaving] = useState(false)
  const [form, setForm] = useState({
    name: '',
    surname: '',
    email: '',
  })

  // Password management
  const [changingPassword, setChangingPassword] = useState(false)
  const [passwordForm, setPasswordForm] = useState(emptyPasswords)
  const [passwordError, setPasswordError] = useState('')
  const [passwordSuccess, setPasswordSuccess] = useState('')
  const [passwordSaving, setPasswordSaving] = useState(false)

  const [showPasswords, setShowPasswords] = useState({
    current_password: false,
    new_password: false,
    confirm_password: false,
  })

  // Load administrator profile
  useEffect(() => {
    const controller = new AbortController()

    const loadProfile = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_admin_profile.php',
          {
            method: 'GET',
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Unable to retrieve admin profile.'
          )
        }

        if (!controller.signal.aborted) {
          setAdmin(data.admin)
        }
      } catch (err) {
        if (!controller.signal.aborted) {
          setError(
            err.message || 'Failed to load admin profile.'
          )
          setAdmin(null)
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadProfile()

    return () => controller.abort()
  }, [refreshKey])

  // Retrieve CSRF token
  const getCsrfToken = async () => {
    const response = await fetch('/admin-api/csrf.php', {
      method: 'GET',
      credentials: 'include',
      cache: 'no-store',
    })

    if (!response.ok) {
      throw new Error(
        'Unable to retrieve security token. Please log in again.'
      )
    }

    const data = await response.json()

    if (!data.csrf_token) {
      throw new Error('Security token is missing.')
    }

    return data.csrf_token
  }

  // Edit profile
  const handleEdit = () => {
    if (!admin) return

    setForm({
      name: admin.name || '',
      surname: admin.surname || '',
      email: admin.email || '',
    })

    setError('')
    setSuccess('')
    setIsEditing(true)
  }

  const handleCancel = () => {
    if (saving) return

    setIsEditing(false)
    setError('')
  }

  const handleChange = (event) => {
    const { name, value } = event.target

    setForm((previous) => ({
      ...previous,
      [name]: value,
    }))
  }

  // Save profile changes
  const handleSave = async (event) => {
    event.preventDefault()

    if (saving) return

    setError('')
    setSuccess('')

    const updatedProfile = {
      name: form.name.trim(),
      surname: form.surname.trim(),
      email: form.email.trim(),
    }

    if (
      !updatedProfile.name ||
      !updatedProfile.surname ||
      !updatedProfile.email
    ) {
      setError('All profile fields are required.')
      return
    }

    if (
      [...updatedProfile.name].length > 100 ||
      [...updatedProfile.surname].length > 100
    ) {
      setError('Name and surname cannot exceed 100 characters.')
      return
    }

    if (
      updatedProfile.email.length > 150 ||
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
        updatedProfile.email
      )
    ) {
      setError('Please enter a valid email address.')
      return
    }

    setSaving(true)

    try {
      const csrfToken = await getCsrfToken()

      const response = await fetch(
        '/admin-api/update_admin_profile.php',
        {
          method: 'POST',
          credentials: 'include',
          cache: 'no-store',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
          },
          body: JSON.stringify(updatedProfile),
        }
      )

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || 'Unable to update profile.'
        )
      }

      setAdmin(data.admin)
      setIsEditing(false)
      setSuccess(
        data.message || 'Profile updated successfully.'
      )
    } catch (err) {
      setError(err.message || 'Failed to update profile.')
    } finally {
      setSaving(false)
    }
  }

  // Password input changes
  const handlePasswordChange = (event) => {
    const { name, value } = event.target

    setPasswordForm((previous) => ({
      ...previous,
      [name]: value,
    }))

    setPasswordError('')
  }

  // Toggle password visibility
  const togglePasswordVisibility = (field) => {
    setShowPasswords((previous) => ({
      ...previous,
      [field]: !previous[field],
    }))
  }

  // Reset password form
  const resetPasswordForm = () => {
    setPasswordForm({ ...emptyPasswords })

    setShowPasswords({
      current_password: false,
      new_password: false,
      confirm_password: false,
    })
  }

  // Cancel password change
  const cancelPasswordChange = () => {
    if (passwordSaving) return

    resetPasswordForm()
    setPasswordError('')
    setChangingPassword(false)
  }

  // Submit password change
  const handlePasswordSubmit = async (event) => {
    event.preventDefault()

    if (passwordSaving) return

    setPasswordError('')
    setPasswordSuccess('')

    const {
      current_password,
      new_password,
      confirm_password,
    } = passwordForm

    if (
      !current_password ||
      !new_password ||
      !confirm_password
    ) {
      setPasswordError('All password fields are required.')
      return
    }

    const passwordBytes =
      new TextEncoder().encode(new_password).length

    if (passwordBytes < 12 || passwordBytes > 128) {
      setPasswordError(
        'New password must be over 12 characters.'
      )
      return
    }

    if (new_password !== confirm_password) {
      setPasswordError('New passwords do not match.')
      return
    }

    if (current_password === new_password) {
      setPasswordError(
        'Your new password must differ from your current password.'
      )
      return
    }

    setPasswordSaving(true)

    try {
      const csrfToken = await getCsrfToken()

      const response = await fetch(
        '/admin-api/change_admin_password.php',
        {
          method: 'POST',
          credentials: 'include',
          cache: 'no-store',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
          },
          body: JSON.stringify({
            current_password,
            new_password,
          }),
        }
      )

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || 'Unable to change password.'
        )
      }

      resetPasswordForm()
      setChangingPassword(false)

      setPasswordSuccess(
        data.message || 'Password changed successfully.'
      )
    } catch (err) {
      setPasswordError(
        err.message || 'Failed to change password.'
      )
    } finally {
      setPasswordSaving(false)
    }
  }

  const profileFields = admin
    ? [
        { label: 'First Name', value: admin.name },
        { label: 'Surname', value: admin.surname },
        { label: 'Email Address', value: admin.email },
        { label: 'Administrator ID', value: admin.id },
      ]
    : []

  const passwordFields = [
    {
      name: 'current_password',
      label: 'Current Password',
      autoComplete: 'current-password',
    },
    {
      name: 'new_password',
      label: 'New Password',
      autoComplete: 'new-password',
    },
    {
      name: 'confirm_password',
      label: 'Confirm New Password',
      autoComplete: 'new-password',
    },
  ]

  return (
    <div className="space-y-6">

      {/* Heading */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Admin Settings
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            Manage your administrator profile and account security.
          </p>
        </div>

        <button
          type="button"
          onClick={() => {
            setSuccess('')
            setRefreshKey((key) => key + 1)
          }}
          disabled={
            loading ||
            saving ||
            passwordSaving ||
            isEditing
          }
          className="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <RefreshCw
            size={17}
            className={loading ? 'animate-spin' : ''}
          />
          Refresh
        </button>
      </div>

      {/* Profile messages */}
      {success && (
        <div
          role="status"
          className="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800"
        >
          <CheckCircle2 size={20} />
          {success}
        </div>
      )}

      {error && (
        <div
          role="alert"
          className="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
          <AlertCircle size={20} />
          {error}
        </div>
      )}

      {/* Admin Profile */}
      <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div className="flex flex-col justify-between gap-4 border-b border-slate-200 p-6 sm:flex-row sm:items-center">
          <div className="flex items-center gap-4">
            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700">
              <UserRound size={28} />
            </div>

            <div>
              <h3 className="text-lg font-bold text-slate-800">
                {loading
                  ? 'Loading profile...'
                  : admin
                    ? `${admin.name} ${admin.surname}`
                    : 'Administrator Profile'}
              </h3>

              <p className="mt-1 flex items-center gap-2 text-sm text-slate-500">
                <ShieldCheck size={16} />
                Administrator
              </p>
            </div>
          </div>

          {!isEditing && admin && (
            <button
              type="button"
              onClick={handleEdit}
              disabled={loading || saving || passwordSaving}
              className="inline-flex items-center justify-center gap-2 rounded-lg border border-green-700 px-4 py-2.5 text-sm font-semibold text-green-700 hover:bg-green-50 disabled:opacity-50"
            >
              <Pencil size={16} />
              Edit Profile
            </button>
          )}
        </div>

        <div className="p-6">
          <h4 className="mb-5 font-semibold text-slate-800">
            Personal Information
          </h4>

          {loading ? (
            <p className="text-sm text-slate-500">
              Retrieving administrator information...
            </p>
          ) : isEditing ? (

            <form onSubmit={handleSave} className="space-y-5">
              <div className="grid gap-5 sm:grid-cols-2">

                <div>
                  <label
                    htmlFor="admin-name"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    First Name
                  </label>

                  <input
                    id="admin-name"
                    type="text"
                    name="name"
                    value={form.name}
                    onChange={handleChange}
                    maxLength={100}
                    required
                    disabled={saving}
                    autoComplete="given-name"
                    className="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-green-600"
                  />
                </div>

                <div>
                  <label
                    htmlFor="admin-surname"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Surname
                  </label>

                  <input
                    id="admin-surname"
                    type="text"
                    name="surname"
                    value={form.surname}
                    onChange={handleChange}
                    maxLength={100}
                    required
                    disabled={saving}
                    autoComplete="family-name"
                    className="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-green-600"
                  />
                </div>

                <div className="sm:col-span-2">
                  <label
                    htmlFor="admin-email"
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    Email Address
                  </label>

                  <input
                    id="admin-email"
                    type="email"
                    name="email"
                    value={form.email}
                    onChange={handleChange}
                    maxLength={150}
                    required
                    disabled={saving}
                    autoComplete="email"
                    className="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-green-600"
                  />
                </div>

              </div>

              <div className="rounded-lg bg-slate-50 p-4">
                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                  Administrator ID
                </p>

                <p className="mt-2 text-sm font-semibold text-slate-800">
                  {admin?.id}
                </p>
              </div>

              <div className="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
                <button
                  type="button"
                  onClick={handleCancel}
                  disabled={saving}
                  className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                >
                  <X size={16} />
                  Cancel
                </button>

                <button
                  type="submit"
                  disabled={saving}
                  className="inline-flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-800 disabled:opacity-50"
                >
                  {saving ? (
                    <RefreshCw size={16} className="animate-spin" />
                  ) : (
                    <Save size={16} />
                  )}

                  {saving ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
            </form>

          ) : admin ? (

            <div className="grid gap-4 sm:grid-cols-2">
              {profileFields.map((field) => (
                <div
                  key={field.label}
                  className="rounded-lg bg-slate-50 p-4"
                >
                  <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                    {field.label}
                  </p>

                  <p className="mt-2 break-words text-sm font-semibold text-slate-800">
                    {field.value ?? 'Not provided'}
                  </p>
                </div>
              ))}
            </div>

          ) : (
            <p className="text-sm text-slate-500">
              Administrator profile unavailable.
            </p>
          )}
        </div>
      </section>

      {/* Account Security */}
      <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

        <div className="flex items-start gap-3">
          <div className="rounded-lg bg-green-100 p-3 text-green-700">
            <LockKeyhole size={22} />
          </div>

          <div>
            <h3 className="text-lg font-semibold text-slate-800">
              Account Security
            </h3>

            <p className="mt-1 text-sm text-slate-500">
              Manage your administrator account security.
            </p>
          </div>
        </div>

        {/* Account Email */}
        <div className="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <div className="flex items-center gap-3">
            <Mail size={18} className="shrink-0 text-slate-500" />

            <div>
              <p className="text-sm font-semibold text-slate-800">
                Account Email
              </p>

              <p className="break-all text-sm text-slate-500">
                {admin?.email || 'Not available'}
              </p>
            </div>
          </div>
        </div>

        {/* Password Section */}
        <div className="mt-4 rounded-lg border border-slate-200 p-5">

          <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div className="flex items-center gap-3">
              <LockKeyhole
                size={20}
                className="shrink-0 text-green-700"
              />

              <div>
                <p className="text-sm font-semibold text-slate-800">
                  Password
                </p>

                <p className="mt-1 text-sm text-slate-500">
                  Update your administrator account password.
                </p>
              </div>
            </div>

            {!changingPassword && (
              <button
                type="button"
                onClick={() => {
                  setPasswordError('')
                  setPasswordSuccess('')
                  setChangingPassword(true)
                }}
                disabled={!admin || loading || saving}
                className="rounded-lg border border-green-700 px-4 py-2.5 text-sm font-semibold text-green-700 hover:bg-green-50 disabled:opacity-50"
              >
                Change Password
              </button>
            )}
          </div>

          {/* Password success */}
          {passwordSuccess && (
            <div
              role="status"
              className="mt-5 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800"
            >
              <CheckCircle2 size={19} className="shrink-0" />
              {passwordSuccess}
            </div>
          )}

          {/* Password error */}
          {passwordError && (
            <div
              role="alert"
              className="mt-5 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
            >
              <AlertCircle size={19} className="shrink-0" />
              {passwordError}
            </div>
          )}

          {/* Change Password Form */}
          {changingPassword && (
            <form
              onSubmit={handlePasswordSubmit}
              className="mt-6 space-y-5 border-t border-slate-200 pt-5"
            >
              {passwordFields.map((field) => (
                <div key={field.name}>
                  <label
                    htmlFor={field.name}
                    className="mb-2 block text-sm font-medium text-slate-700"
                  >
                    {field.label}
                  </label>

                  <div className="relative">
                    <input
                      id={field.name}
                      name={field.name}
                      type={
                        showPasswords[field.name]
                          ? 'text'
                          : 'password'
                      }
                      value={passwordForm[field.name]}
                      onChange={handlePasswordChange}
                      autoComplete={field.autoComplete}
                      required
                      disabled={passwordSaving}
                      className="w-full rounded-lg border border-slate-300 px-4 py-2.5 pr-12 text-sm outline-none focus:border-green-600 disabled:bg-slate-100"
                    />

                    <button
                      type="button"
                      onClick={() =>
                        togglePasswordVisibility(field.name)
                      }
                      disabled={passwordSaving}
                      aria-label={
                        showPasswords[field.name]
                          ? `Hide ${field.label}`
                          : `Show ${field.label}`
                      }
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700 disabled:opacity-50"
                    >
                      {showPasswords[field.name] ? (
                        <EyeOff size={19} />
                      ) : (
                        <Eye size={19} />
                      )}
                    </button>
                  </div>
                </div>
              ))}

              <p className="text-xs text-slate-500">
                Choose a unique password containing at least
                12 characters. Longer passphrases are encouraged.
                Your password must not match your current password.
              </p>

              <div className="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5">
                <button
                  type="button"
                  onClick={cancelPasswordChange}
                  disabled={passwordSaving}
                  className="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                >
                  <X size={16} />
                  Cancel
                </button>

                <button
                  type="submit"
                  disabled={passwordSaving}
                  className="inline-flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-800 disabled:opacity-50"
                >
                  {passwordSaving ? (
                    <RefreshCw
                      size={16}
                      className="animate-spin"
                    />
                  ) : (
                    <LockKeyhole size={16} />
                  )}

                  {passwordSaving
                    ? 'Updating Password...'
                    : 'Update Password'}
                </button>
              </div>
            </form>
          )}
        </div>
      </section>

    </div>
  )
}

export default AdminSettings
