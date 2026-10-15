
import { useEffect, useMemo, useState } from 'react'
import {
  Stethoscope,
  RefreshCw,
  ShieldCheck,
  Clock,
  XCircle,
  Search,
  MapPin,
  Building2,
  Eye,
X,
} from 'lucide-react'

const statusStyles = {
  verified: 'bg-green-100 text-green-800',
  pending: 'bg-amber-100 text-amber-800',
  rejected: 'bg-red-100 text-red-800',
}

function Doctors() {
  const [doctors, setDoctors] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [detailsTarget, setDetailsTarget] = useState(null)

  // Retrieve doctors from MySQL
  useEffect(() => {
    const controller = new AbortController()

    const loadDoctors = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_all_doctors.php',
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
            data.message || 'Unable to retrieve doctors.'
          )
        }

        if (!controller.signal.aborted) {
          setDoctors(
            Array.isArray(data.doctors)
              ? data.doctors
              : []
          )
        }
      } catch (err) {
        if (!controller.signal.aborted) {
          setError(
            err.message || 'Failed to load doctor records.'
          )
          setDoctors([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadDoctors()

    return () => controller.abort()
  }, [refreshKey])

  // Calculate verification statistics
  const stats = useMemo(() => {
    const countStatus = (status) =>
      doctors.filter(
        (doctor) => doctor.verification === status
      ).length

    return {
      total: doctors.length,
      verified: countStatus('verified'),
      pending: countStatus('pending'),
      rejected: countStatus('rejected'),
    }
  }, [doctors])

  // Search and filter
  const filteredDoctors = useMemo(() => {
    const term = search.trim().toLowerCase()

    return doctors.filter((doctor) => {
      const searchableText = [
        doctor.id,
        doctor.name,
        doctor.surname,
        doctor.email,
        doctor.specialization,
        doctor.clinic_name,
        doctor.location,
      ]
        .filter((value) => value != null)
        .join(' ')
        .toLowerCase()

      const matchesSearch = searchableText.includes(term)

      const matchesStatus =
        statusFilter === 'all' ||
        doctor.verification === statusFilter

      return matchesSearch && matchesStatus
    })
  }, [doctors, search, statusFilter])

  const statisticCards = [
    {
      title: 'Total Doctors',
      value: stats.total,
      icon: Stethoscope,
      color: 'text-green-700',
    },
    {
      title: 'Verified Doctors',
      value: stats.verified,
      icon: ShieldCheck,
      color: 'text-green-600',
    },
    {
      title: 'Pending Verification',
      value: stats.pending,
      icon: Clock,
      color: 'text-amber-600',
    },
    {
      title: 'Rejected Doctors',
      value: stats.rejected,
      icon: XCircle,
      color: 'text-red-600',
    },
  ]

  const formatPrice = (price) => {
    if (price === null || price === undefined || price === '') {
      return 'Not provided'
    }

    const value = Number(price)

    if (!Number.isFinite(value)) {
      return 'Not provided'
    }

    return new Intl.NumberFormat('en-ZA', {
      style: 'currency',
      currency: 'ZAR',
    }).format(value)
  }

  return (
    <div className="space-y-6">

      {/* Page heading */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Doctor Management
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            View registered doctors, professional information,
            and verification statuses.
          </p>
        </div>

        <button
          type="button"
          onClick={() => setRefreshKey((key) => key + 1)}
          disabled={loading}
          className="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <RefreshCw
            size={17}
            className={loading ? 'animate-spin' : ''}
          />
          Refresh
        </button>
      </div>

      {/* Statistics */}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {statisticCards.map(
          ({ title, value, icon: Icon, color }) => (
            <div
              key={title}
              className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
              <div className="flex items-center justify-between">
                <p className="text-sm font-medium text-slate-500">
                  {title}
                </p>

                <Icon size={22} className={color} />
              </div>

              <p className="mt-4 text-3xl font-bold text-slate-800">
                {loading ? '...' : error ? '—' : value}
              </p>
            </div>
          )
        )}
      </div>

      {/* Doctor table */}
      <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        {/* Table heading and filters */}
        <div className="border-b border-slate-200 p-5">
          <h3 className="text-lg font-semibold text-slate-800">
            Registered Doctors
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Search and filter doctor records.
          </p>

          <div className="mt-5 flex flex-col gap-3 md:flex-row">

            {/* Search */}
            <div className="relative flex-1">
              <Search
                size={18}
                className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
              />

              <input
                type="search"
                value={search}
                onChange={(event) =>
                  setSearch(event.target.value)
                }
                placeholder="Search doctors..."
                className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-600"
              />
            </div>

            {/* Status filter */}
            <select
              value={statusFilter}
              onChange={(event) =>
                setStatusFilter(event.target.value)
              }
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-green-600"
            >
              <option value="all">All Statuses</option>
              <option value="verified">Verified</option>
              <option value="pending">Pending</option>
              <option value="rejected">Rejected</option>
            </select>

          </div>
        </div>

        {/* API error */}
        {error && (
          <div
            role="alert"
            className="m-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
          >
            {error}
          </div>
        )}

        {/* Table */}
        <div className="overflow-x-auto">
          <table className="w-full min-w-[1000px] text-left text-sm">

            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th className="px-5 py-4">Doctor</th>
                <th className="px-5 py-4">Specialization</th>
                <th className="px-5 py-4">Clinic / Location</th>
                <th className="px-5 py-4">Experience</th>
                <th className="px-5 py-4">Consultation Fee</th>
                <th className="px-5 py-4">Verification</th>
                <th className="px-5 py-4">Registered</th>
                <th className="px-5 py-4">Actions</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100">

              {/* Loading */}
              {loading && (
                <tr>
                  <td
                    colSpan={8}
                    className="px-5 py-12 text-center text-slate-500"
                  >
                    Loading doctors...
                  </td>
                </tr>
              )}

              {/* Doctor rows */}
              {!loading &&
                !error &&
                filteredDoctors.map((doctor) => (
                  <tr
                    key={doctor.id}
                    className="transition hover:bg-slate-50"
                  >

                    {/* Doctor */}
                    <td className="px-5 py-4">
                      <p className="font-semibold text-slate-800">
                        Dr. {doctor.name} {doctor.surname}
                      </p>

                      <p className="mt-1 text-xs text-slate-500">
                        {doctor.email || 'No email provided'}
                      </p>

                      <p className="mt-1 text-xs text-slate-400">
                        ID: {doctor.id}
                      </p>
                    </td>

                    {/* Specialization */}
                    <td className="px-5 py-4 text-slate-700">
                      {doctor.specialization || 'Not provided'}
                    </td>

                    {/* Clinic and location */}
                    <td className="px-5 py-4">
                      <div className="space-y-1">
                        <p className="flex items-center gap-2 font-medium text-slate-700">
                          <Building2
                            size={15}
                            className="shrink-0 text-green-700"
                          />
                          {doctor.clinic_name || 'Not provided'}
                        </p>

                        <p className="flex items-center gap-2 text-xs text-slate-500">
                          <MapPin
                            size={14}
                            className="shrink-0"
                          />
                          {doctor.location || 'Not provided'}
                        </p>
                      </div>
                    </td>

                    {/* Experience */}
                    <td className="px-5 py-4 text-slate-700">
                      {doctor.years_in_professional != null
                        ? `${doctor.years_in_professional} years`
                        : 'Not provided'}
                    </td>

                    {/* Consultation fee */}
                    <td className="px-5 py-4 font-medium text-slate-700">
                      {formatPrice(doctor.consultation_price)}
                    </td>

                    {/* Verification */}
                    <td className="px-5 py-4">
                      <span
                        className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                          statusStyles[doctor.verification] ||
                          'bg-slate-100 text-slate-700'
                        }`}
                      >
                        {doctor.verification || 'Unknown'}
                      </span>
                    </td>

                    {/* Registration date */}
                    <td className="px-5 py-4 text-slate-600">
                      {doctor.created_at || 'Not available'}
                    </td>

<td className="px-5 py-4">
  <button
    type="button"
    onClick={() => setDetailsTarget(doctor)}
    className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
  >
    <Eye size={15} />
    View Details
  </button>
</td>


                  </tr>
                ))}

              {/* No results */}
              {!loading &&
                !error &&
                filteredDoctors.length === 0 && (
                  <tr>
                    <td
                      colSpan={8}
                      className="px-5 py-12 text-center text-slate-500"
                    >
                      No doctors found.
                    </td>
                  </tr>
                )}

            </tbody>
          </table>
        </div>

        {/* Table footer */}
        {!loading && !error && (
          <div className="border-t border-slate-200 px-5 py-4">
            <p className="text-xs text-slate-500">
              Showing {filteredDoctors.length} of{' '}
              {doctors.length} doctors
            </p>
          </div>
        )}

      </section>


{/* Doctor Details Modal */}
{detailsTarget && (
  <div
    className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    onMouseDown={(event) => {
      if (event.target === event.currentTarget) {
        setDetailsTarget(null)
      }
    }}
  >
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="doctor-details-title"
      className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
    >
      {/* Header */}
      <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white p-5">
        <div>
          <h3
            id="doctor-details-title"
            className="text-xl font-bold text-slate-800"
          >
            Doctor Details
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Doctor ID: {detailsTarget.id}
          </p>
        </div>

        <button
          type="button"
          onClick={() => setDetailsTarget(null)}
          aria-label="Close doctor details"
          className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
        >
          <X size={22} />
        </button>
      </div>

      {/* Details */}
      <div className="space-y-6 p-6">

        {/* Personal Information */}
        <section>
          <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Personal Information
          </h4>

          <div className="grid gap-3 sm:grid-cols-2">
            {[
              ['First Name', detailsTarget.name],
              ['Surname', detailsTarget.surname],
              ['Email Address', detailsTarget.email],
              ['Registration Date', detailsTarget.created_at],
            ].map(([label, value]) => (
              <div
                key={label}
                className="rounded-lg bg-slate-50 p-4"
              >
                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                  {label}
                </p>

                <p className="mt-2 break-words text-sm font-semibold text-slate-800">
                  {value ?? 'Not provided'}
                </p>
              </div>
            ))}
          </div>
        </section>

        {/* Professional Information */}
        <section>
          <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Professional Information
          </h4>

          <div className="grid gap-3 sm:grid-cols-2">
            {[
              ['Specialization', detailsTarget.specialization],
              ['Years of Experience',
                detailsTarget.years_in_professional != null
                  ? `${detailsTarget.years_in_professional} years`
                  : null],
              ['Consultation Fee',
                formatPrice(detailsTarget.consultation_price)],
              ['Verification Status', detailsTarget.verification],
            ].map(([label, value]) => (
              <div
                key={label}
                className="rounded-lg bg-slate-50 p-4"
              >
                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                  {label}
                </p>

                <p className="mt-2 break-words text-sm font-semibold capitalize text-slate-800">
                  {value ?? 'Not provided'}
                </p>
              </div>
            ))}
          </div>
        </section>

        {/* Clinic Information */}
        <section>
          <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Clinic Information
          </h4>

          <div className="grid gap-3 sm:grid-cols-2">
            {[
              ['Clinic Name', detailsTarget.clinic_name],
              ['Practice Location', detailsTarget.location],
            ].map(([label, value]) => (
              <div
                key={label}
                className="rounded-lg bg-slate-50 p-4"
              >
                <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
                  {label}
                </p>

                <p className="mt-2 break-words text-sm font-semibold text-slate-800">
                  {value ?? 'Not provided'}
                </p>
              </div>
            ))}
          </div>
        </section>

      </div>

      {/* Footer */}
      <div className="sticky bottom-0 flex justify-end border-t border-slate-200 bg-white p-5">
        <button
          type="button"
          onClick={() => setDetailsTarget(null)}
          className="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-900"
        >
          Close
        </button>
      </div>
    </div>
  </div>
)}


      

    </div>
  )
}

export default Doctors
