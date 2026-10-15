
import { useEffect, useMemo, useState } from 'react'
import {
  Pill,
  RefreshCw,
  Users,
  Search,
  Link2,
  Building2,
  ShieldCheck,
  Clock,
  XCircle,
  X,
  Eye,
} from 'lucide-react'

const statusStyles = {
  verified: 'bg-green-100 text-green-800',
  pending: 'bg-amber-100 text-amber-800',
  rejected: 'bg-red-100 text-red-800',
}

function Pharmacists() {
  const [pharmacists, setPharmacists] = useState([])
  const [pharmacies, setPharmacies] = useState([])

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')

  const [linkTarget, setLinkTarget] = useState(null)
  const [selectedPharmacyId, setSelectedPharmacyId] = useState('')
  const [linkReason, setLinkReason] = useState('')
  const [linkLoading, setLinkLoading] = useState(false)
  const [linkError, setLinkError] = useState('')
  const [successMessage, setSuccessMessage] = useState('')
  const [detailsTarget, setDetailsTarget] = useState(null)

  useEffect(() => {
    const controller = new AbortController()

    const loadData = async () => {
      setLoading(true)
      setError('')

      try {
        const [pharmacistsResponse, pharmaciesResponse] =
          await Promise.all([
            fetch('/admin-api/get_all_pharmacists.php', {
              credentials: 'include',
              signal: controller.signal,
            }),
            fetch('/admin-api/get_pharmacies.php', {
              credentials: 'include',
              signal: controller.signal,
            }),
          ])

        const pharmacistsData = await pharmacistsResponse.json()
        const pharmaciesData = await pharmaciesResponse.json()

        if (!pharmacistsResponse.ok || !pharmacistsData.success) {
          throw new Error(
            pharmacistsData.message || 'Unable to load pharmacists.'
          )
        }

        if (!pharmaciesResponse.ok || !pharmaciesData.success) {
          throw new Error(
            pharmaciesData.message || 'Unable to load pharmacies.'
          )
        }

        if (!controller.signal.aborted) {
          setPharmacists(
            Array.isArray(pharmacistsData.pharmacists)
              ? pharmacistsData.pharmacists
              : []
          )

          setPharmacies(
            Array.isArray(pharmaciesData.pharmacies)
              ? pharmaciesData.pharmacies
              : []
          )
        }
      } catch (err) {
        if (err.name !== 'AbortError') {
          setError(err.message || 'Failed to load data.')
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadData()

    return () => controller.abort()
  }, [refreshKey])

  // Only verified pharmacies can be selected
  const verifiedPharmacies = useMemo(
    () => pharmacies.filter(
      (pharmacy) => pharmacy.verification === 'verified'
    ),
    [pharmacies]
  )

  const stats = useMemo(() => ({
    total: pharmacists.length,
    verified: pharmacists.filter(
      (p) => p.verification === 'verified'
    ).length,
    pending: pharmacists.filter(
      (p) => p.verification === 'pending'
    ).length,
    unlinked: pharmacists.filter(
      (p) =>
        p.verification === 'verified' &&
        p.pharmacy_id == null
    ).length,
  }), [pharmacists])

  const filteredPharmacists = useMemo(() => {
    const term = search.trim().toLowerCase()

    return pharmacists.filter((pharmacist) => {
      const searchableText = [
        pharmacist.id,
        pharmacist.name,
        pharmacist.surname,
        pharmacist.email,
        pharmacist.pharmacy_name,
      ].join(' ').toLowerCase()

      return (
        searchableText.includes(term) &&
        (statusFilter === 'all' ||
          pharmacist.verification === statusFilter)
      )
    })
  }, [pharmacists, search, statusFilter])

  const openLinkModal = (pharmacist) => {
    if (
      pharmacist.verification !== 'verified' ||
      pharmacist.pharmacy_id != null
    ) {
      return
    }

    setLinkTarget(pharmacist)
    setSelectedPharmacyId('')
    setLinkReason('')
    setLinkError('')
    setSuccessMessage('')
  }

  const closeLinkModal = () => {
    if (linkLoading) return

    setLinkTarget(null)
    setSelectedPharmacyId('')
    setLinkReason('')
    setLinkError('')
  }

  const confirmLink = async () => {
    if (!linkTarget || linkLoading) return

    const reason = linkReason.trim()

    if (!selectedPharmacyId) {
      setLinkError('Please select a pharmacy.')
      return
    }

    if (reason.length < 10 || reason.length > 2000) {
      setLinkError('Reason must be between 10 and 2000 characters.')
      return
    }

    setLinkLoading(true)
    setLinkError('')

    try {
      // Retrieve CSRF token
      const csrfResponse = await fetch('/admin-api/csrf.php', {
        credentials: 'include',
      })

      const csrfData = await csrfResponse.json()

      if (!csrfResponse.ok || !csrfData.csrf_token) {
        throw new Error('Unable to retrieve security token.')
      }

      // Submit link request
      const response = await fetch('/admin-api/link_pharmacist.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfData.csrf_token,
        },
        body: JSON.stringify({
          pharmacist_id: Number(linkTarget.id),
          pharmacy_id: Number(selectedPharmacyId),
          reason,
        }),
      })

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || 'Unable to link pharmacist.'
        )
      }

      setSuccessMessage(data.message)
      setLinkTarget(null)
      setSelectedPharmacyId('')
      setLinkReason('')

      // Refresh pharmacist and pharmacy records
      setRefreshKey((previous) => previous + 1)

    } catch (err) {
      setLinkError(err.message || 'Unable to link pharmacist.')
    } finally {
      setLinkLoading(false)
    }
  }

  const cards = [
    {
      label: 'Total Pharmacists',
      value: stats.total,
      icon: Users,
    },
    {
      label: 'Verified',
      value: stats.verified,
      icon: ShieldCheck,
    },
    {
      label: 'Pending',
      value: stats.pending,
      icon: Clock,
    },
    {
      label: 'Verified & Unlinked',
      value: stats.unlinked,
      icon: Link2,
    },
  ]

  return (
    <div className="space-y-6">

      {/* Page heading */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Pharmacist Management
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            Manage registered pharmacists and pharmacy associations.
          </p>
        </div>

        <button
          type="button"
          onClick={() => setRefreshKey((key) => key + 1)}
          disabled={loading}
          className="inline-flex items-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-800 disabled:opacity-50"
        >
          <RefreshCw
            size={17}
            className={loading ? 'animate-spin' : ''}
          />
          Refresh
        </button>
      </div>

      {/* Success message */}
      {successMessage && (
        <div
          role="status"
          className="rounded-lg bg-green-50 p-4 text-sm text-green-800"
        >
          {successMessage}
        </div>
      )}

      {/* Statistics */}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {cards.map(({ label, value, icon: Icon }) => (
          <div
            key={label}
            className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-slate-500">
                {label}
              </p>
              <Icon size={21} className="text-green-700" />
            </div>

            <p className="mt-4 text-3xl font-bold text-slate-800">
              {loading ? '...' : error ? '—' : value}
            </p>
          </div>
        ))}
      </div>

      {/* Pharmacist table */}
      <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div className="flex flex-col gap-4 border-b border-slate-200 p-5 md:flex-row">
          <div className="relative flex-1">
            <Search
              size={18}
              className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
            />

            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search pharmacists..."
              className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-600"
            />
          </div>

          <select
            value={statusFilter}
            onChange={(event) => setStatusFilter(event.target.value)}
            className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"
          >
            <option value="all">All Statuses</option>
            <option value="verified">Verified</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        {error && (
          <div
            role="alert"
            className="m-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"
          >
            {error}
          </div>
        )}

        <div className="overflow-x-auto">
          <table className="w-full min-w-[850px] text-left text-sm">

            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-5 py-4">Pharmacist</th>
                <th className="px-5 py-4">Pharmacy</th>
                <th className="px-5 py-4">Verification</th>
                <th className="px-5 py-4">Actions</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100">

              {!loading && !error &&
                filteredPharmacists.map((pharmacist) => {
                  const canLink =
                    pharmacist.verification === 'verified' &&
                    pharmacist.pharmacy_id == null

                  return (
                    <tr
                      key={pharmacist.id}
                      className="hover:bg-slate-50"
                    >
                      <td className="px-5 py-4">
                        <p className="font-semibold text-slate-800">
                          {pharmacist.name} {pharmacist.surname}
                        </p>

                        <p className="mt-1 text-xs text-slate-500">
                          {pharmacist.email}
                        </p>

                        <p className="mt-1 text-xs text-slate-400">
                          ID: {pharmacist.id}
                        </p>
                      </td>

                      <td className="px-5 py-4">
                        {pharmacist.pharmacy_id != null ? (
                          <div>
                            <p className="font-medium text-slate-700">
                              {pharmacist.pharmacy_name || 'Unknown Pharmacy'}
                            </p>

                            <p className="mt-1 text-xs text-slate-500">
                              {pharmacist.pharmacy_verification || 'Unknown'}
                            </p>
                          </div>
                        ) : (
                          <span className="text-sm text-slate-400">
                            Not Linked
                          </span>
                        )}
                      </td>

                      <td className="px-5 py-4">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                            statusStyles[pharmacist.verification] ||
                            'bg-slate-100 text-slate-700'
                          }`}
                        >
                          {pharmacist.verification || 'Unknown'}
                        </span>
                      </td>

                      
<td className="px-5 py-4">
  <div className="flex flex-wrap items-center gap-2">
    <button
      type="button"
      onClick={() => setDetailsTarget(pharmacist)}
      className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
    >
      <Eye size={15} />
      View Details
    </button>

    {canLink && (
      <button
        type="button"
        onClick={() => openLinkModal(pharmacist)}
        className="inline-flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-100"
      >
        <Link2 size={15} />
        Link Pharmacy
      </button>
    )}
  </div>
</td>

                    </tr>
                  )
                })}

              {loading && (
                <tr>
                  <td colSpan={4} className="px-5 py-12 text-center text-slate-500">
                    Loading pharmacists...
                  </td>
                </tr>
              )}

              {!loading && !error &&
                filteredPharmacists.length === 0 && (
                  <tr>
                    <td colSpan={4} className="px-5 py-12 text-center text-slate-500">
                      No pharmacists found.
                    </td>
                  </tr>
                )}
            </tbody>
          </table>
        </div>

        {!loading && !error && (
          <div className="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
            Showing {filteredPharmacists.length} of {pharmacists.length} pharmacists
          </div>
        )}
      </section>

      {/* Link Pharmacy confirmation modal */}
      {linkTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="link-pharmacy-title"
            className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl"
          >
            <div className="flex items-center justify-between border-b border-slate-200 p-5">
              <div>
                <h3
                  id="link-pharmacy-title"
                  className="text-xl font-bold text-slate-800"
                >
                  Link Pharmacist
                </h3>

                <p className="mt-1 text-sm text-slate-500">
                  Assign a verified pharmacy
                </p>
              </div>

              <button
                type="button"
                onClick={closeLinkModal}
                disabled={linkLoading}
                aria-label="Close"
                className="rounded-lg p-2 hover:bg-slate-100 disabled:opacity-50"
              >
                <X size={21} />
              </button>
            </div>

            <div className="space-y-5 p-6">
              <div className="rounded-lg bg-slate-50 p-4">
                <p className="text-xs text-slate-500">
                  Selected Pharmacist
                </p>

                <p className="mt-1 font-semibold text-slate-800">
                  {linkTarget.name} {linkTarget.surname}
                </p>

                <p className="mt-1 text-sm text-slate-500">
                  {linkTarget.email}
                </p>
              </div>

              <div>
                <label
                  htmlFor="link-pharmacy-select"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Select Verified Pharmacy
                </label>

                <select
                  id="link-pharmacy-select"
                  value={selectedPharmacyId}
                  onChange={(event) =>
                    setSelectedPharmacyId(event.target.value)
                  }
                  disabled={linkLoading}
                  className="w-full rounded-lg border border-slate-300 bg-white p-3 text-sm outline-none focus:border-green-600"
                >
                  <option value="">
                    Choose a pharmacy
                  </option>

                  {verifiedPharmacies.map((pharmacy) => (
                    <option
                      key={pharmacy.id}
                      value={pharmacy.id}
                    >
                      {pharmacy.name} — {pharmacy.location}
                    </option>
                  ))}
                </select>

                {verifiedPharmacies.length === 0 && (
                  <p className="mt-2 text-sm text-amber-700">
                    No verified pharmacies are available.
                  </p>
                )}
              </div>

              <div>
                <label
                  htmlFor="link-reason"
                  className="mb-2 block text-sm font-semibold text-slate-700"
                >
                  Reason for Linking
                </label>

                <textarea
                  id="link-reason"
                  value={linkReason}
                  onChange={(event) =>
                    setLinkReason(event.target.value)
                  }
                  disabled={linkLoading}
                  maxLength={2000}
                  rows={4}
                  placeholder="Explain why this pharmacist is being linked..."
                  className="w-full resize-none rounded-lg border border-slate-300 p-3 text-sm outline-none focus:border-green-600"
                />

                <p className="mt-1 text-xs text-slate-500">
                  Minimum 10 characters. Saved in the audit log.
                </p>
              </div>

              {linkError && (
                <div
                  role="alert"
                  className="rounded-lg bg-red-50 p-3 text-sm text-red-700"
                >
                  {linkError}
                </div>
              )}
            </div>

            <div className="flex justify-end gap-3 border-t border-slate-200 p-5">
              <button
                type="button"
                onClick={closeLinkModal}
                disabled={linkLoading}
                className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
              >
                Cancel
              </button>

              <button
                type="button"
                onClick={confirmLink}
                disabled={
                  linkLoading ||
                  !selectedPharmacyId ||
                  linkReason.trim().length < 10 ||
                  linkReason.trim().length > 2000
                }
                className="inline-flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <Link2 size={16} />
                {linkLoading ? 'Linking...' : 'Confirm Link'}
              </button>
            </div>
          </div>
        </div>
      )}


{/* Pharmacist Details Modal */}
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
      aria-labelledby="pharmacist-details-title"
      className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
    >
      {/* Header */}
      <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white p-5">
        <div>
          <h3
            id="pharmacist-details-title"
            className="text-xl font-bold text-slate-800"
          >
            Pharmacist Details
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Pharmacist ID: {detailsTarget.id}
          </p>
        </div>

        <button
          type="button"
          onClick={() => setDetailsTarget(null)}
          aria-label="Close pharmacist details"
          className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
        >
          <X size={22} />
        </button>
      </div>

      {/* Details */}
      <div className="space-y-6 p-6">
        <div>
          <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Personal Information
          </h4>

          <div className="grid gap-4 sm:grid-cols-2">
            {[
              ['First Name', detailsTarget.name],
              ['Surname', detailsTarget.surname],
              ['Email', detailsTarget.email],
              ['Phone Number', detailsTarget.phone],
              ['Work Experience', detailsTarget.work_experience_years != null
                ? `${detailsTarget.work_experience_years} years`
                : null],
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
                  {value || 'Not provided'}
                </p>
              </div>
            ))}
          </div>
        </div>

        {/* Verification */}
        <div>
          <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Professional Verification
          </h4>

          <div className="rounded-lg border border-slate-200 p-4">
            <p className="mb-2 text-xs text-slate-500">
              Verification Status
            </p>

            <span
              className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                statusStyles[detailsTarget.verification] ||
                'bg-slate-100 text-slate-700'
              }`}
            >
              {detailsTarget.verification || 'Unknown'}
            </span>
          </div>
        </div>

        {/* Pharmacy Association */}
        <div>
          <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Pharmacy Association
          </h4>

          {detailsTarget.pharmacy_id != null ? (
            <div className="grid gap-4 sm:grid-cols-2">
              {[
                ['Pharmacy Name', detailsTarget.pharmacy_name],
                ['Pharmacy ID', detailsTarget.pharmacy_id],
                ['Pharmacy Location', detailsTarget.linked_pharmacy_location],
                ['Pharmacy Verification', detailsTarget.pharmacy_verification],
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
          ) : (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
              <p className="text-sm font-medium text-amber-800">
                This pharmacist is not currently linked to a pharmacy.
              </p>
            </div>
          )}
        </div>

        {/* Registered Location */}
        <div>
          <h4 className="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
            Registered Pharmacy Location
          </h4>

          <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-700">
            {detailsTarget.pharmacy_location || 'Not provided'}
          </p>
        </div>
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

export default Pharmacists
