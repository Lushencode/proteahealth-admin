import { useEffect, useState } from 'react'
import DoctorVerification from './DoctorVerification'
import PharmacistVerification from './PharmacistVerification'
import Pharmacies from './Pharmacies'
import Pharmacists from './Pharmacists'
import Patients from './Patients'
import Doctors from './Doctors'
import AdminSettings from './AdminSettings'
import HealthTips from './HealthTips'
import Questions from './Questions'
import Answers from './Answers'

import {
  LayoutDashboard,
  Users,
  Stethoscope,
  Pill,
  Store,
  ShieldCheck,
  Settings,
  LogOut,
  Menu,
  X,
  Clock,
  BookOpen,
  MessageCircle,
  MessagesSquare,
  ChevronDown,
} from 'lucide-react'

// Sidebar navigation

const navigation = [
  { label: 'Dashboard', icon: LayoutDashboard },
  { label: 'Patients', icon: Users },
  { label: 'Doctors', icon: Stethoscope },
  { label: 'Pharmacists', icon: Pill },
  { label: 'Pharmacies', icon: Store },
  {
    label: 'Verifications',
    icon: ShieldCheck,
    children: [
      { label: 'Doctor Verification', icon: Stethoscope },
      { label: 'Pharmacist Verification', icon: Pill },
    ],
  },
  { label: 'Health Tips', icon: BookOpen },
  { label: 'Questions', icon: MessageCircle },
  { label: 'Answers', icon: MessagesSquare },
  { label: 'Settings', icon: Settings },
]


function AdminDashboard({ admin, onLogout }) {
  // Navigation state
  const [activePage, setActivePage] = useState('Dashboard')
  const [sidebarOpen, setSidebarOpen] = useState(false)

  // Dashboard statistics state
  const [dashboardStats, setDashboardStats] = useState(null)
  const [statsLoading, setStatsLoading] = useState(true)
  const [statsError, setStatsError] = useState('')
  const [verificationsOpen, setVerificationsOpen] = useState(false)
  

  // Retrieve dashboard statistics from MySQL via PHP
  useEffect(() => {
    const controller = new AbortController()

    const fetchDashboardStats = async () => {
      try {
        setStatsLoading(true)
        setStatsError('')

        const response = await fetch(
          '/admin-api/dashboard_stats.php',
          {
            credentials: 'include',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Failed to load dashboard statistics'
          )
        }

        setDashboardStats(data.stats)
      } catch (error) {
        if (error.name !== 'AbortError') {
          setStatsError(error.message)
        }
      } finally {
        if (!controller.signal.aborted) {
          setStatsLoading(false)
        }
      }
    }

    fetchDashboardStats()

    return () => controller.abort()
  }, [activePage])

  // Statistics cards
  const stats = [
    {
      title: 'Total Patients',
      icon: Users,
      value: dashboardStats?.patients,
    },
    {
      title: 'Total Doctors',
      icon: Stethoscope,
      value: dashboardStats?.doctors,
    },
    {
      title: 'Total Pharmacists',
      icon: Pill,
      value: dashboardStats?.pharmacists,
    },
    {
      title: 'Total Pharmacies',
      icon: Store,
      value: dashboardStats?.pharmacies,
    },
  ]

  // Handle sidebar navigation
  const navigateTo = (page) => {
    setActivePage(page)
    setSidebarOpen(false)
  }

  // Dashboard overview
  const renderDashboard = () => (
    <div className="space-y-8">
      <div>
        <h3 className="text-xl font-semibold text-slate-800">
          Dashboard Overview
        </h3>

        <p className="mt-1 text-sm text-slate-500">
          Welcome back, {admin.name}.
        </p>
      </div>

      {/* Statistics errors */}
      {statsError && (
        <div
          role="alert"
          className="rounded-lg bg-red-50 p-4 text-sm text-red-700"
        >
          {statsError}
        </div>
      )}

      {/* Statistics cards */}
      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        {stats.map(({ title, icon: Icon, value }) => (
          <div
            key={title}
            className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
          >
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-slate-500">
                {title}
              </p>

              <Icon size={22} className="text-green-700" />
            </div>

            <p className="mt-5 text-3xl font-bold text-slate-800">
              {statsLoading ? '...' : value ?? '—'}
            </p>

            <p className="mt-2 text-xs text-slate-400">
              {statsLoading
                ? 'Loading statistics...'
                : statsError
                  ? 'Unable to load data'
                  : 'Registered in ProteaHealth'}
            </p>
          </div>
        ))}
      </div>

      {/* Verification overview */}
      
{/* Pending verification overview */}
<section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <div className="flex items-center gap-3">
    <Clock size={24} className="text-amber-600" />

    <div>
      <h3 className="text-lg font-semibold text-slate-800">
        Pending Verifications
      </h3>

      <p className="mt-1 text-sm text-slate-500">
        Professional registrations awaiting administrator review.
      </p>
    </div>
  </div>

  <div className="mt-6 grid gap-4 sm:grid-cols-3">
    {/* Pending doctors */}
    <div className="rounded-xl border border-slate-200 bg-slate-50 p-5">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-slate-600">
          Pending Doctors
        </p>

        <Stethoscope size={21} className="text-green-700" />
      </div>

      <p className="mt-3 text-3xl font-bold text-slate-800">
        {statsLoading || statsError
          ? '—'
          : dashboardStats?.pending_doctors ?? '—'}
      </p>
    </div>

    {/* Pending pharmacists */}
    <div className="rounded-xl border border-slate-200 bg-slate-50 p-5">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-slate-600">
          Pending Pharmacists
        </p>

        <Pill size={21} className="text-green-700" />
      </div>

      <p className="mt-3 text-3xl font-bold text-slate-800">
        {statsLoading || statsError
          ? '—'
          : dashboardStats?.pending_pharmacists ?? '—'}
      </p>
    </div>

    {/* Total pending */}
    <div className="rounded-xl border border-amber-200 bg-amber-50 p-5">
      <div className="flex items-center justify-between">
        <p className="text-sm font-medium text-amber-800">
          Total Pending
        </p>

        <Clock size={21} className="text-amber-600" />
      </div>

      <p className="mt-3 text-3xl font-bold text-amber-900">
        {statsLoading || statsError
          ? '—'
          : dashboardStats?.pending_total ?? '—'}
      </p>
    </div>
  </div>

  <button
    type="button"
    onClick={() => navigateTo('Verifications')}
    className="mt-6 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-green-800"
  >
    View Verifications
  </button>
</section>

    </div>
  )

  // Placeholder for sections not yet implemented
  const renderPlaceholder = () => (
    <section className="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
      <h3 className="text-xl font-semibold text-slate-800">
        {activePage} Management
      </h3>

      <p className="mt-3 text-slate-500">
        This section is ready for its database and API integration.
      </p>
    </section>
  )


const renderVerificationsHome = () => {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-slate-800">
          Verification Management
        </h2>
        <p className="mt-2 text-sm text-slate-500">
          Select the professional category you want to review.
        </p>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {/* Doctor verification */}
        <button
          type="button"
          onClick={() => navigateTo('Doctor Verification')}
          className="group rounded-xl border border-slate-200 bg-white p-6 text-left shadow-sm transition hover:border-green-500 hover:shadow-md"
        >
          <div className="mb-5 inline-flex rounded-xl bg-green-100 p-4 text-green-700">
            <Stethoscope size={30} />
          </div>

          <h3 className="text-lg font-semibold text-slate-800">
            Doctor Verification
          </h3>

          <p className="mt-2 text-sm text-slate-500">
            Review doctor applications, verify professional
            credentials, and manage approval decisions.
          </p>

          <div className="mt-6 flex items-center gap-2 font-medium text-green-700">
            View Doctor Applications
            <span aria-hidden="true">→</span>
          </div>
        </button>

        {/* Pharmacist verification */}
        <button
          type="button"
          onClick={() => navigateTo('Pharmacist Verification')}
          className="group rounded-xl border border-slate-200 bg-white p-6 text-left shadow-sm transition hover:border-green-500 hover:shadow-md"
        >
          <div className="mb-5 inline-flex rounded-xl bg-green-100 p-4 text-green-700">
            <Pill size={30} />
          </div>

          <h3 className="text-lg font-semibold text-slate-800">
            Pharmacist Verification
          </h3>

          <p className="mt-2 text-sm text-slate-500">
            Review pharmacist applications, check registration
            details, and manage professional verification.
          </p>

          <div className="mt-6 flex items-center gap-2 font-medium text-green-700">
            View Pharmacist Applications
            <span aria-hidden="true">→</span>
          </div>
        </button>
      </div>
    </div>
  )
}

 
// Render the selected page
  const renderPage = () => {
  switch (activePage) {
    case 'Dashboard':
      return renderDashboard()

      case 'Answers':
  return <Answers />

      case 'Questions':
  return <Questions />

      case 'Health Tips':
  return <HealthTips />

      case 'Settings':
  return <AdminSettings />

      case 'Doctors':
  return <Doctors />

      case 'Patients':
  return <Patients />

    case 'Pharmacists':
  return <Pharmacists />

    case 'Pharmacies':
      return <Pharmacies />

    case 'Verifications':
      return renderVerificationsHome()

    case 'Doctor Verification':
      return <DoctorVerification />

    case 'Pharmacist Verification':
      return <PharmacistVerification />

    default:
      return renderPlaceholder()
  }
}




  return (
    <div className="min-h-screen bg-slate-50 md:flex">

      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <button
          type="button"
          aria-label="Close sidebar"
          onClick={() => setSidebarOpen(false)}
          className="fixed inset-0 z-30 bg-black/40 md:hidden"
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-40 w-64 bg-green-900 text-white transition-transform md:sticky md:top-0 md:h-screen md:translate-x-0 ${
          sidebarOpen
            ? 'translate-x-0'
            : '-translate-x-full'
        }`}
      >
        {/* Branding */}
        <div className="flex items-center justify-between border-b border-green-800 p-6">
          <div className="flex items-center gap-3">
            <img
              src="/proteahealth-icon.png"
              alt="ProteaHealth"
              className="h-12 w-12 rounded-xl bg-white object-contain p-1"
            />

            <div>
              <h1 className="text-lg font-bold">
                ProteaHealth
              </h1>

              <p className="text-xs text-green-200">
                Admin Portal
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={() => setSidebarOpen(false)}
            className="md:hidden"
            aria-label="Close menu"
          >
            <X size={22} />
          </button>
        </div>

        {/* Navigation */}
        
{/* Navigation */}
<nav className="space-y-1 p-4">
  {navigation.map(({ label, icon: Icon, children }) => {
    const isChildActive = children?.some(
      (child) => child.label === activePage
    )

    const isActive = activePage === label || isChildActive

    return (
      <div key={label}>
        {/* Main navigation button */}
        <button
          type="button"
          onClick={() => {
            if (children) {
              setVerificationsOpen((previous) => !previous)
            } else {
              navigateTo(label)
            }
          }}
          aria-expanded={children ? verificationsOpen || isChildActive : undefined}
          className={`flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-sm transition ${
            isActive
              ? 'bg-green-700 text-white'
              : 'text-green-100 hover:bg-green-800'
          }`}
        >
          <Icon size={19} />

          <span className="flex-1">{label}</span>

          {children && (
            <ChevronDown
              size={17}
              className={`transition-transform ${
                verificationsOpen || isChildActive
                  ? 'rotate-180'
                  : ''
              }`}
            />
          )}
        </button>

        {/* Expandable submenu */}
        {children && (verificationsOpen || isChildActive) && (
          <div className="ml-5 mt-1 space-y-1 border-l border-green-700 pl-3">
            {children.map(({ label: childLabel, icon: ChildIcon }) => (
              <button
                key={childLabel}
                type="button"
                onClick={() => navigateTo(childLabel)}
                className={`flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition ${
                  activePage === childLabel
                    ? 'bg-green-600 font-medium text-white'
                    : 'text-green-100 hover:bg-green-800'
                }`}
              >
                <ChildIcon size={17} />
                <span>{childLabel}</span>
              </button>
            ))}
          </div>
        )}
      </div>
    )
  })}
</nav>


        {/* Logout */}
        <div className="p-4 md:absolute md:bottom-0 md:w-full">
          <button
            type="button"
            onClick={onLogout}
            className="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-sm text-red-200 transition hover:bg-green-800"
          >
            <LogOut size={19} />
            Logout
          </button>
        </div>
      </aside>

      {/* Main content */}
      <main className="min-w-0 flex-1">

        {/* Header */}
        <header className="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-5 md:px-8">
          <div className="flex items-center gap-4">
            <button
              type="button"
              className="md:hidden"
              onClick={() => setSidebarOpen(true)}
              aria-label="Open menu"
            >
              <Menu size={24} />
            </button>

            <div>
              <h2 className="text-2xl font-bold text-slate-800">
                {activePage}
              </h2>

              <p className="text-sm text-slate-500">
                ProteaHealth Administration
              </p>
            </div>
          </div>

          {/* Logged-in administrator */}
          <div className="text-right">
            <p className="text-sm font-semibold text-slate-800">
              {admin.name} {admin.surname}
            </p>

            <p className="text-xs text-slate-500">
              Administrator
            </p>
          </div>
        </header>

        {/* Selected page */}
        <div className="p-5 md:p-8">
          {renderPage()}
        </div>

      </main>
    </div>
  )
}

export default AdminDashboard
