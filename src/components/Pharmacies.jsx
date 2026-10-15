
import { useCallback, useEffect, useMemo, useState } from 'react'
import {
  Building2,
  RefreshCw,
  Search,
  MapPin,
  Users,
  ShieldCheck,
  Clock,
  XCircle,
  AlertCircle,
  Eye,
X,
Mail,
Unlink,
} from 'lucide-react'

const statusStyles = {
  verified: 'bg-green-100 text-green-800',
  pending: 'bg-amber-100 text-amber-800',
  rejected: 'bg-red-100 text-red-800',
}

function Pharmacies() {
  const [pharmacies, setPharmacies] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [refreshKey, setRefreshKey] = useState(0)

  const [selectedPharmacy, setSelectedPharmacy] = useState(null)
const [pharmacyDetails, setPharmacyDetails] = useState(null)
const [detailsLoading, setDetailsLoading] = useState(false)
const [detailsError, setDetailsError] = useState('')
const [unlinkTarget, setUnlinkTarget] = useState(null)
const [unlinkReason, setUnlinkReason] = useState('')
const [unlinkLoading, setUnlinkLoading] = useState(false)
const [unlinkError, setUnlinkError] = useState('')

  const loadPharmacies = useCallback(async (signal) => {
    try {
      setLoading(true)
      setError('')

      const response = await fetch('/admin-api/get_pharmacies.php', {
        credentials: 'include',
        signal,
      })

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to load pharmacies')
      }

      setPharmacies(
        Array.isArray(data.pharmacies) ? data.pharmacies : []
      )
    } catch (err) {
      if (err.name !== 'AbortError') {
        setError(err.message || 'Unable to load pharmacies')
      }
    } finally {
      if (!signal.aborted) {
        setLoading(false)
      }
    }
  }, [])

  useEffect(() => {
    const controller = new AbortController()

    loadPharmacies(controller.signal)

    return () => controller.abort()
  }, [loadPharmacies, refreshKey])

  const stats = useMemo(() => ({
    total: pharmacies.length,
    verified: pharmacies.filter(
      (p) => p.verification === 'verified'
    ).length,
    pending: pharmacies.filter(
      (p) => p.verification === 'pending'
    ).length,
    rejected: pharmacies.filter(
      (p) => p.verification === 'rejected'
    ).length,
  }), [pharmacies])

  const filteredPharmacies = useMemo(() => {
    const term = search.trim().toLowerCase()

    return pharmacies.filter((pharmacy) => {
      const matchesSearch =
        String(pharmacy.name ?? '').toLowerCase().includes(term) ||
        String(pharmacy.location ?? '').toLowerCase().includes(term) ||
        String(pharmacy.id ?? '').includes(term)

      const matchesStatus =
        statusFilter === 'all' ||
        pharmacy.verification === statusFilter

      return matchesSearch && matchesStatus
    })
  }, [pharmacies, search, statusFilter])

  const viewPharmacyDetails = async (pharmacy) => {
  setSelectedPharmacy(pharmacy)
  setPharmacyDetails(null)
  setDetailsError('')
  setDetailsLoading(true)

  try {
    const response = await fetch(
      `/admin-api/get_pharmacy_details.php?id=${encodeURIComponent(pharmacy.id)}`,
      {
        credentials: 'include',
      }
    )

    const data = await response.json()

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Failed to load pharmacy details')
    }

    setPharmacyDetails(data)
  } catch (error) {
    setDetailsError(error.message || 'Unable to load pharmacy details')
  } finally {
    setDetailsLoading(false)
  }
}

const closeDetails = () => {
  setSelectedPharmacy(null)
  setPharmacyDetails(null)
  setDetailsError('')
}


const openUnlinkModal = (pharmacist) => {
  setUnlinkTarget(pharmacist)
  setUnlinkReason('')
  setUnlinkError('')
}

const closeUnlinkModal = () => {
  if (unlinkLoading) return

  setUnlinkTarget(null)
  setUnlinkReason('')
  setUnlinkError('')
}

const confirmUnlink = async () => {
  if (!unlinkTarget || !selectedPharmacy || unlinkLoading) return

  const reason = unlinkReason.trim()

  if (reason.length < 10 || reason.length > 2000) {
    setUnlinkError('Reason must be between 10 and 2000 characters.')
    return
  }

  setUnlinkLoading(true)
  setUnlinkError('')

  try {
    // Retrieve the administrator's CSRF token
    const csrfResponse = await fetch('/admin-api/csrf.php', {
      credentials: 'include',
    })

    const csrfData = await csrfResponse.json()

    if (!csrfResponse.ok || !csrfData.csrf_token) {
      throw new Error('Unable to retrieve security token.')
    }

    // Send the unlink request
    const response = await fetch('/admin-api/unlink_pharmacist.php', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfData.csrf_token,
      },
      body: JSON.stringify({
        pharmacist_id: unlinkTarget.id,
        pharmacy_id: selectedPharmacy.id,
        reason,
      }),
    })

    const data = await response.json()

    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Failed to unlink pharmacist.')
    }

    const pharmacy = selectedPharmacy

    // Close confirmation popup
    setUnlinkTarget(null)
    setUnlinkReason('')
    setUnlinkError('')

    // Refresh the pharmacy details
    await viewPharmacyDetails(pharmacy)

    // Refresh the main pharmacy table and statistics
    setRefreshKey((previous) => previous + 1)

  } catch (error) {
    setUnlinkError(error.message || 'Unable to unlink pharmacist.')
  } finally {
    setUnlinkLoading(false)
  }
}


  const cards = [
    { label: 'Total Pharmacies', value: stats.total, icon: Building2 },
    { label: 'Verified', value: stats.verified, icon: ShieldCheck },
    { label: 'Pending', value: stats.pending, icon: Clock },
    { label: 'Rejected', value: stats.rejected, icon: XCircle },
  ]

  return (
    <div className="space-y-6">
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Pharmacy Management
          </h2>
          <p className="mt-1 text-sm text-slate-500">
            View registered pharmacies and their verification status.
          </p>
        </div>

        <button
          type="button"
          onClick={() => setRefreshKey((key) => key + 1)}
          disabled={loading}
          className="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-green-800 disabled:opacity-50"
        >
          <RefreshCw size={17} className={loading ? 'animate-spin' : ''} />
          Refresh
        </button>
      </div>

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
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search by pharmacy, location or ID..."
              className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-600"
            />
          </div>

          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-green-600"
          >
            <option value="all">All Statuses</option>
            <option value="verified">Verified</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        {error && (
          <div role="alert" className="m-5 flex items-center gap-2 rounded-lg bg-red-50 p-4 text-sm text-red-700">
            <AlertCircle size={18} />
            {error}
          </div>
        )}

        <div className="overflow-x-auto">
          <table className="w-full min-w-[700px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th className="px-5 py-4">Pharmacy</th>
                <th className="px-5 py-4">Location</th>
                <th className="px-5 py-4">Pharmacists</th>
                <th className="px-5 py-4">Verification</th>
                <th className="px-5 py-4">Actions</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100">
              {!loading && !error && filteredPharmacies.map((pharmacy) => (
                <tr key={pharmacy.id} className="hover:bg-slate-50">
                  <td className="px-5 py-4">
                    <div className="font-semibold text-slate-800">
                      {pharmacy.name || 'Unnamed Pharmacy'}
                    </div>
                    <div className="mt-1 text-xs text-slate-400">
                      ID: {pharmacy.id}
                    </div>
                  </td>

                  <td className="px-5 py-4 text-slate-600">
                    <span className="inline-flex items-center gap-2">
                      <MapPin size={15} className="text-slate-400" />
                      {pharmacy.location || 'Not provided'}
                    </span>
                  </td>

                  <td className="px-5 py-4 text-slate-600">
                    <span className="inline-flex items-center gap-2">
                      <Users size={16} className="text-slate-400" />
                      {pharmacy.pharmacist_count ?? 0}
                    </span>
                  </td>

                  <td className="px-5 py-4">
                    <span
                      className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                        statusStyles[pharmacy.verification] ||
                        'bg-slate-100 text-slate-700'
                      }`}
                    >
                      {pharmacy.verification || 'Unknown'}
                    </span>
                  </td>
                  <td className="px-5 py-4">
  <button
    type="button"
    onClick={() => viewPharmacyDetails(pharmacy)}
    className="inline-flex items-center gap-2 rounded-lg border border-green-200 px-3 py-2 text-xs font-semibold text-green-700 transition hover:bg-green-50"
  >
    <Eye size={15} />
    View Details
  </button>
</td>
                </tr>
              ))}

              {loading && (
                <tr>
                  <td colSpan={5} className="px-5 py-12 text-center text-slate-500">
                    Loading pharmacies...
                  </td>
                </tr>
              )}

              {!loading && !error && filteredPharmacies.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-5 py-12 text-center text-slate-500">
                    No pharmacies match your search or filters.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        {!loading && !error && (
          <div className="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
            Showing {filteredPharmacies.length} of {pharmacies.length} pharmacies
          </div>
        )}
      </section>

{selectedPharmacy && (
  <div
    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    onMouseDown={(event) => {
      if (event.target === event.currentTarget) {
        closeDetails()
      }
    }}
  >
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="pharmacy-details-title"
      className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl"
    >
      <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white p-5">
        <div>
          <h3
            id="pharmacy-details-title"
            className="text-xl font-bold text-slate-800"
          >
            Pharmacy Details
          </h3>
          <p className="mt-1 text-sm text-slate-500">
            {selectedPharmacy.name}
          </p>
        </div>

        <button
          type="button"
          onClick={closeDetails}
          aria-label="Close pharmacy details"
          className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
        >
          <X size={22} />
        </button>
      </div>

      <div className="space-y-6 p-6">
        {detailsLoading && (
          <p className="py-8 text-center text-slate-500">
            Loading pharmacy details...
          </p>
        )}

        {detailsError && (
          <div role="alert" className="rounded-lg bg-red-50 p-4 text-sm text-red-700">
            {detailsError}
          </div>
        )}

        {!detailsLoading && pharmacyDetails && (
          <>
            <div className="grid gap-4 sm:grid-cols-2">
              {[
                ['Pharmacy Name', pharmacyDetails.pharmacy.name],
                ['Pharmacy ID', pharmacyDetails.pharmacy.id],
                ['Location', pharmacyDetails.pharmacy.location],
                ['Email', pharmacyDetails.pharmacy.email],
                ['Opening Time', pharmacyDetails.pharmacy.open_hour],
                ['Closing Time', pharmacyDetails.pharmacy.close_hours],
                ['Verification Status', pharmacyDetails.pharmacy.verification],
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

            <div>
              <h4 className="font-semibold text-slate-800">
                Pharmacy Description
              </h4>
              <p className="mt-2 whitespace-pre-wrap rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                {pharmacyDetails.pharmacy.details || 'No description provided.'}
              </p>
            </div>

            <div>
              <div className="mb-3 flex items-center justify-between">
                <h4 className="font-semibold text-slate-800">
                  Linked Pharmacists
                </h4>
                <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                  {pharmacyDetails.pharmacist_count} Total
                </span>
              </div>

              {pharmacyDetails.pharmacists.length === 0 ? (
                <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">
                  No pharmacists are linked to this pharmacy.
                </p>
              ) : (
                <div className="space-y-3">
                  {pharmacyDetails.pharmacists.map((pharmacist) => (
                    <div
                      key={pharmacist.id}
                      className="flex flex-col justify-between gap-3 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center"
                    >
                      <div>
                        <p className="font-semibold text-slate-800">
                          {pharmacist.name} {pharmacist.surname}
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                          {pharmacist.email}
                        </p>
                      </div>

                      
<div className="flex flex-wrap items-center gap-3">
  <span
    className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${
      statusStyles[pharmacist.verification] ||
      'bg-slate-100 text-slate-700'
    }`}
  >
    {pharmacist.verification || 'Unknown'}
  </span>

  <button
    type="button"
    onClick={() => openUnlinkModal(pharmacist)}
    className="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
  >
    <Unlink size={15} />
    Unlink
  </button>
</div>

                    </div>
                  ))}
                </div>
              )}
            </div>
          </>
        )}
      </div>

      <div className="sticky bottom-0 flex justify-end border-t border-slate-200 bg-white p-4">
        <button
          type="button"
          onClick={closeDetails}
          className="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-900"
        >
          Close
        </button>
      </div>
    </div>
  </div>
)}
    
{unlinkTarget && (
  <div
    className="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4"
    onMouseDown={(event) => {
      if (event.target === event.currentTarget) {
        closeUnlinkModal()
      }
    }}
  >
    <div
      role="alertdialog"
      aria-modal="true"
      aria-labelledby="unlink-modal-title"
      aria-describedby="unlink-modal-description"
      className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"
    >
      <div className="mb-5 flex items-center gap-3">
        <div className="rounded-xl bg-red-100 p-3 text-red-700">
          <Unlink size={24} />
        </div>

        <div>
          <h3
            id="unlink-modal-title"
            className="text-xl font-bold text-slate-800"
          >
            Unlink Pharmacist
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Administrator confirmation required
          </p>
        </div>
      </div>

      <div
        id="unlink-modal-description"
        className="rounded-lg border border-red-200 bg-red-50 p-4"
      >
        <p className="text-sm text-red-800">
          Are you sure you want to unlink
          {' '}
          <strong>
            {unlinkTarget.name} {unlinkTarget.surname}
          </strong>
          {' '}
          from
          {' '}
          <strong>{selectedPharmacy?.name}</strong>?
        </p>

        <p className="mt-2 text-xs text-red-700">
          The pharmacist account will not be deleted.
          Their pharmacy association will be removed.
        </p>
      </div>

      <div className="mt-5">
        <label
          htmlFor="unlink-reason"
          className="mb-2 block text-sm font-semibold text-slate-700"
        >
          Reason for Unlinking
        </label>

        <textarea
          id="unlink-reason"
          value={unlinkReason}
          onChange={(event) => setUnlinkReason(event.target.value)}
          disabled={unlinkLoading}
          maxLength={2000}
          rows={4}
          placeholder="Explain why this pharmacist is being unlinked..."
          className="w-full resize-none rounded-lg border border-slate-300 p-3 text-sm outline-none focus:border-red-500 disabled:opacity-60"
        />

        <p className="mt-1 text-xs text-slate-500">
          Minimum 10 characters. This reason will be saved
          in the administrator audit log.
        </p>
      </div>

      {unlinkError && (
        <div
          role="alert"
          className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700"
        >
          {unlinkError}
        </div>
      )}

      <div className="mt-6 flex justify-end gap-3">
        <button
          type="button"
          onClick={closeUnlinkModal}
          disabled={unlinkLoading}
          className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
        >
          Cancel
        </button>

        <button
          type="button"
          onClick={confirmUnlink}
          disabled={
            unlinkLoading ||
            unlinkReason.trim().length < 10 ||
            unlinkReason.trim().length > 2000
          }
          className="inline-flex items-center gap-2 rounded-lg bg-red-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <Unlink size={16} />
          {unlinkLoading ? 'Unlinking...' : 'Confirm Unlink'}
        </button>
      </div>
    </div>
  </div>
)}

    </div>
  )
}

export default Pharmacies
