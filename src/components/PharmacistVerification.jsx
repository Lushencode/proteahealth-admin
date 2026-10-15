
import { useEffect, useMemo, useState } from 'react'
import PharmacistVerificationHistory from './PharmacistVerificationHistory'
import {
  Pill,
  Search,
  RefreshCw,
  CheckCircle,
  XCircle,
  Clock,
  ChevronDown,
  ChevronUp,
  Mail,
  Phone,
  MapPin,
  ShieldCheck,
  Briefcase,
  Building2,
} from 'lucide-react'

function PharmacistVerification() {
  const [pharmacists, setPharmacists] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('all')
  const [expandedPharmacist, setExpandedPharmacist] = useState(null)
  const [refreshKey, setRefreshKey] = useState(0)

const [selectedDecision, setSelectedDecision] = useState(null)
const [reason, setReason] = useState('')
const [credentialReference, setCredentialReference] = useState('')
const [pharmacyReference, setPharmacyReference] = useState('')
const [confirmationOpen, setConfirmationOpen] = useState(false)
const [submitting, setSubmitting] = useState(false)
const [formError, setFormError] = useState('')
const [successMessage, setSuccessMessage] = useState('')


  useEffect(() => {
    const controller = new AbortController()

    const loadPharmacists = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_pharmacists.php',
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Failed to load pharmacist applications'
          )
        }

        setPharmacists(
          Array.isArray(data.pharmacists) ? data.pharmacists : []
        )
      } catch (err) {
        if (err.name !== 'AbortError') {
          setError(err.message)
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadPharmacists()

    return () => controller.abort()
  }, [refreshKey])


const openDecisionModal = (pharmacist, decision) => {
  setSelectedDecision({
    pharmacistId: pharmacist.id,
    pharmacistName: `${pharmacist.name} ${pharmacist.surname}`,
    pharmacyId: pharmacist.pharmacy_id,
    pharmacyName: pharmacist.pharmacy_name,
    pharmacyVerification: pharmacist.pharmacy_verification,
    decision,
  })

  setReason('')
  setCredentialReference('')
  setPharmacyReference('')
  setFormError('')
  setConfirmationOpen(true)
}

const closeDecisionModal = () => {
  if (submitting) return

  setConfirmationOpen(false)
  setSelectedDecision(null)
  setReason('')
  setCredentialReference('')
  setPharmacyReference('')
  setFormError('')
}

const submitDecision = async (event) => {
  event.preventDefault()

  if (!selectedDecision || submitting) return

  const cleanReason = reason.trim()
  const cleanReference = credentialReference.trim()
  const cleanPharmacyReference = pharmacyReference.trim()

  if (cleanReason.length < 10 || cleanReason.length > 2000) {
    setFormError(
      'Please provide a reason between 10 and 2000 characters.'
    )
    return
  }

  if (
    selectedDecision.decision === 'verified' &&
    !cleanReference
  ) {
    setFormError(
      'A professional registration verification reference is required.'
    )
    return
  }

  if (cleanReference.length > 255) {
    setFormError('Verification reference is too long.')
    return
  }

  if (
  selectedDecision.decision === 'verified' &&
  selectedDecision.pharmacyVerification === 'pending' &&
  !cleanPharmacyReference
) {
  setFormError(
    'A pharmacy authorization verification reference is required.'
  )
  return
}

if (cleanPharmacyReference.length > 255) {
  setFormError('Pharmacy verification reference is too long.')
  return
}
  setSubmitting(true)
  setFormError('')
  setSuccessMessage('')

  try {
    // Retrieve CSRF token
    // Retrieve CSRF token
const csrfResponse = await fetch('/admin-api/csrf.php', {
  method: 'GET',
  credentials: 'include',
  cache: 'no-store',
})

const csrfData = await csrfResponse.json()

console.log('CSRF HTTP status:', csrfResponse.status)
console.log('CSRF success:', csrfData.success)
console.log('CSRF message:', csrfData.message)

if (!csrfResponse.ok || !csrfData.success || !csrfData.csrf_token) {
  throw new Error(
    csrfData.message || 'Unable to retrieve security token'
  )
}

    // Submit pharmacist verification decision
    const response = await fetch(
      '/admin-api/verify_pharmacist.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
  'Content-Type': 'application/json',
  'X-CSRF-Token': csrfData.csrf_token,
},
        body: JSON.stringify({
  pharmacist_id: selectedDecision.pharmacistId,
  decision: selectedDecision.decision,
  reason: cleanReason,
  credential_reference: cleanReference,
  pharmacy_verification_reference: cleanPharmacyReference,
}),
      }
    )

    const data = await response.json()

    if (!response.ok || !data.success) {
      throw new Error(
        data.message || 'Unable to process verification'
      )
    }

    setSuccessMessage(data.message)
    setConfirmationOpen(false)
    setSelectedDecision(null)
    setReason('')
    setCredentialReference('')
    setPharmacyReference('')

    // Reload applications from MySQL
    setRefreshKey((current) => current + 1)

  } catch (err) {
    setFormError(
      err.message || 'An unexpected error occurred'
    )
  } finally {
    setSubmitting(false)
  }
}


  const counts = useMemo(() => ({
    total: pharmacists.length,
    pending: pharmacists.filter(
      (p) => p.verification === 'pending'
    ).length,
    verified: pharmacists.filter(
      (p) => p.verification === 'verified'
    ).length,
    rejected: pharmacists.filter(
      (p) => p.verification === 'rejected'
    ).length,
  }), [pharmacists])

  const filteredPharmacists = useMemo(() => {
    const query = search.trim().toLowerCase()

    return pharmacists.filter((pharmacist) => {
      const matchesStatus =
        filter === 'all' || pharmacist.verification === filter

      const searchableText = [
        pharmacist.id,
        pharmacist.name,
        pharmacist.surname,
        pharmacist.email,
        pharmacist.pharmacy_location,
        pharmacist.registration_code,
      ]
        .filter((value) => value !== null && value !== undefined)
        .join(' ')
        .toLowerCase()

      return matchesStatus && searchableText.includes(query)
    })
  }, [pharmacists, search, filter])

  const statusStyles = {
    pending: 'bg-amber-100 text-amber-800',
    verified: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }

  const statusIcons = {
    pending: Clock,
    verified: CheckCircle,
    rejected: XCircle,
  }

  return (
    <section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      {/* Header */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div className="flex items-center gap-3">
          <div className="rounded-xl bg-green-100 p-3 text-green-700">
            <Pill size={25} />
          </div>

          <div>
            <h2 className="text-xl font-semibold text-slate-800">
              Pharmacist Verification
            </h2>

            <p className="mt-1 text-sm text-slate-500">
              Review pharmacist registration applications.
            </p>
          </div>
        </div>

        <button
          type="button"
          onClick={() => setRefreshKey((value) => value + 1)}
          disabled={loading}
          className="flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
        >
          <RefreshCw size={17} />
          Refresh
        </button>
      </div>

      {/* Statistics */}
      <div className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        {[
          { label: 'Total', value: counts.total, color: 'text-slate-800' },
          { label: 'Pending', value: counts.pending, color: 'text-amber-700' },
          { label: 'Verified', value: counts.verified, color: 'text-green-700' },
          { label: 'Rejected', value: counts.rejected, color: 'text-red-700' },
        ].map((item) => (
          <div
            key={item.label}
            className="rounded-xl border border-slate-200 bg-slate-50 p-4"
          >
            <p className="text-sm text-slate-500">{item.label}</p>

            <p className={`mt-2 text-2xl font-bold ${item.color}`}>
              {loading || error ? '—' : item.value}
            </p>
          </div>
        ))}
      </div>

      {/* Search and filter */}
      <div className="mt-6 flex flex-col gap-3 md:flex-row">
        <div className="relative flex-1">
          <Search
            size={18}
            className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
          />

          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Search name, email or registration code..."
            aria-label="Search pharmacists"
            className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-4 text-sm outline-none focus:border-green-600"
          />
        </div>

        <select
          value={filter}
          onChange={(event) => setFilter(event.target.value)}
          aria-label="Filter pharmacist status"
          className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700"
        >
          <option value="all">All Applications</option>
          <option value="pending">Pending</option>
          <option value="verified">Verified</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      {/* Loading */}
      {loading && (
        <p className="py-10 text-center text-sm text-slate-500">
          Loading pharmacist applications...
        </p>
      )}

      {/* Error */}
      {!loading && error && (
        <div
          role="alert"
          className="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"
        >
          {error}
        </div>
      )}

      {/* Empty state */}
      {!loading && !error && filteredPharmacists.length === 0 && (
        <div className="mt-6 rounded-xl border border-slate-200 p-8 text-center">
          <Pill size={32} className="mx-auto text-slate-400" />

          <p className="mt-3 font-medium text-slate-700">
            No pharmacist applications found
          </p>

          <p className="mt-2 text-sm text-slate-500">
            Try changing your search or status filter.
          </p>
        </div>
      )}

      {/* Applications */}
      {!loading && !error && filteredPharmacists.length > 0 && (
        <div className="mt-6 space-y-4">
          {filteredPharmacists.map((pharmacist) => {
            const expanded =
              expandedPharmacist === pharmacist.id

            const StatusIcon =
              statusIcons[pharmacist.verification] || Clock

            return (
              <div
                key={pharmacist.id}
                className="overflow-hidden rounded-xl border border-slate-200"
              >
                <div className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center">
                  <div>
                    <h3 className="font-semibold text-slate-800">
                      {pharmacist.name} {pharmacist.surname}
                    </h3>

                    <p className="mt-1 text-sm text-slate-500">
                      {pharmacist.email}
                    </p>

                    <p className="mt-1 text-xs text-slate-400">
                      Pharmacist ID: {pharmacist.id}
                    </p>
                  </div>

                  <div className="flex flex-wrap items-center gap-3">
                    <span
                      className={`flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ${
                        statusStyles[pharmacist.verification] ||
                        'bg-slate-100 text-slate-700'
                      }`}
                    >
                      <StatusIcon size={14} />
                      {pharmacist.verification}
                    </span>

                    <button
                      type="button"
                      onClick={() =>
                        setExpandedPharmacist(
                          expanded ? null : pharmacist.id
                        )
                      }
                      aria-expanded={expanded}
                      className="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    >
                      {expanded ? 'Hide Details' : 'View Details'}

                      {expanded ? (
                        <ChevronUp size={16} />
                      ) : (
                        <ChevronDown size={16} />
                      )}
                    </button>
                  </div>
                </div>

                {expanded && (
                  <div className="border-t border-slate-200 bg-slate-50 p-5">
                    <div className="grid gap-5 sm:grid-cols-2">
                      <Detail
                        icon={Mail}
                        label="Email Address"
                        value={pharmacist.email}
                      />

                      <Detail
                        icon={Phone}
                        label="Phone Number"
                        value={pharmacist.phone}
                      />

                      <Detail
                        icon={MapPin}
                        label="Pharmacy Location"
                        value={pharmacist.pharmacy_location}
                      />

                      <Detail
  icon={Building2}
  label="Pharmacy Name"
  value={pharmacist.pharmacy_name || 'No pharmacy linked'}
/>

                    <Detail
  icon={ShieldCheck}
  label="Pharmacy Verification Status"
  value={pharmacist.pharmacy_verification || 'Not available'}
/>

                      <Detail
                        icon={ShieldCheck}
                        label="Professional Registration Code"
                        value={pharmacist.registration_code}
                      />

                      <Detail
                        icon={Briefcase}
                        label="Years of Experience"
                        value={pharmacist.work_experience_years}
                      />

                      <Detail
                        icon={Clock}
                        label="Application Date"
                        value={pharmacist.created_at}
                      />
                    </div>

                    {pharmacist.verification === 'pending' && (
                      <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p className="text-sm font-medium text-amber-800">
                          Awaiting Administrator Review
                        </p>

                        <p className="mt-1 text-sm text-amber-700">
                          Verify the professional registration code
                          with the appropriate registration authority
                          before approving this application.
                        </p>
                      </div>
                    )}


{/* Pharmacist approval actions */}
{pharmacist.verification === 'pending' && (
  <div className="mt-5 flex flex-wrap gap-3">
    <button
      type="button"
      onClick={() =>
        openDecisionModal(pharmacist, 'verified')
      }
      className="flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-green-800"
    >
      <CheckCircle size={18} />
      Approve Pharmacist
    </button>

    <button
      type="button"
      onClick={() =>
        openDecisionModal(pharmacist, 'rejected')
      }
      className="flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-red-700"
    >
      <XCircle size={18} />
      Reject Pharmacist
    </button>
  </div>
)}

                  </div>
                )}
              </div>
            )
          })}
        </div>
      )}
    


{/* Success message */}
{successMessage && (
  <div
    role="status"
    className="mt-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800"
  >
    {successMessage}
  </div>
)}

{/* Verification confirmation modal */}
{confirmationOpen && selectedDecision && (
  <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="pharmacist-decision-title"
      className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl"
    >
      <h2
        id="pharmacist-decision-title"
        className="text-xl font-semibold text-slate-800"
      >
        {selectedDecision.decision === 'verified'
          ? 'Approve Pharmacist'
          : 'Reject Pharmacist'}
      </h2>

      <p className="mt-2 text-sm text-slate-500">
        {selectedDecision.decision === 'verified'
          ? 'Confirm that the professional registration has been independently verified before approving this application.'
          : 'Provide the reason for rejecting this pharmacist application.'}
      </p>

      <form onSubmit={submitDecision} className="mt-6 space-y-5">
        {/* Decision reason */}
        <div>
          <label
            htmlFor="pharmacist-decision-reason"
            className="mb-2 block text-sm font-medium text-slate-700"
          >
            Decision Reason *
          </label>

          <textarea
            id="pharmacist-decision-reason"
            value={reason}
            onChange={(event) => setReason(event.target.value)}
            rows={4}
            maxLength={2000}
            required
            placeholder="Explain the reason for this decision..."
            className="w-full rounded-lg border border-slate-300 p-3 text-sm outline-none focus:border-green-600"
          />

          <p className="mt-1 text-xs text-slate-400">
            Minimum 10 characters.
          </p>
        </div>

        {/* Registration verification reference */}
        {selectedDecision.decision === 'verified' && (
          <div>
            <label
              htmlFor="pharmacist-credential-reference"
              className="mb-2 block text-sm font-medium text-slate-700"
            >
              Professional Registration Verification Reference *
            </label>

            <input
              id="pharmacist-credential-reference"
              type="text"
              value={credentialReference}
              onChange={(event) =>
                setCredentialReference(event.target.value)
              }
              maxLength={255}
              required
              placeholder="Enter the independent verification reference"
              className="w-full rounded-lg border border-slate-300 p-3 text-sm outline-none focus:border-green-600"
            />

            <p className="mt-2 text-xs text-slate-500">
              Record the reference from your independent professional
              registration check.
            </p>
          </div>
        )}

        {/* Pharmacy authorization verification */}
{selectedDecision.decision === 'verified' &&
  selectedDecision.pharmacyVerification === 'pending' && (
    <div>
      <label
        htmlFor="pharmacy-verification-reference"
        className="mb-2 block text-sm font-medium text-slate-700"
      >
        Pharmacy Authorization Verification Reference *
      </label>

      <p className="mb-3 text-sm text-slate-500">
        Pharmacy: {selectedDecision.pharmacyName || 'Linked pharmacy'}
      </p>

      <input
        id="pharmacy-verification-reference"
        type="text"
        value={pharmacyReference}
        onChange={(event) => setPharmacyReference(event.target.value)}
        maxLength={255}
        required
        placeholder="Enter the independent pharmacy verification reference"
        className="w-full rounded-lg border border-slate-300 p-3 text-sm outline-none focus:border-green-600"
      />

      <p className="mt-2 text-xs text-slate-500">
        Record the reference from your independent pharmacy
        authorization check. Both records will be approved together.
      </p>
    </div>
  )}
        {/* Error */}
        {formError && (
          <div
            role="alert"
            className="rounded-lg bg-red-50 p-3 text-sm text-red-700"
          >
            {formError}
          </div>
        )}

        {/* Confirmation buttons */}
        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            onClick={closeDecisionModal}
            disabled={submitting}
            className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          >
            Cancel
          </button>

          <button
            type="submit"
            disabled={submitting}
            className={`rounded-lg px-5 py-2.5 text-sm font-medium text-white disabled:opacity-50 ${
              selectedDecision.decision === 'verified'
                ? 'bg-green-700 hover:bg-green-800'
                : 'bg-red-600 hover:bg-red-700'
            }`}
          >
            {submitting
              ? 'Processing...'
              : selectedDecision.decision === 'verified'
                ? 'Confirm Approval'
                : 'Confirm Rejection'}
          </button>
        </div>
      </form>
    </div>
  </div>
)}
<PharmacistVerificationHistory key={refreshKey} />
</section>

  )
}


function Detail({ icon: Icon, label, value }) {
  return (
    <div className="min-w-0">
      <p className="flex items-center gap-2 text-xs font-semibold uppercase text-slate-500">
        <Icon size={15} />
        {label}
      </p>

      <p className="mt-2 break-words text-sm text-slate-800">
        {value !== null && value !== undefined && value !== ''
          ? value
          : 'Not provided'}
      </p>
    </div>
  )
}

export default PharmacistVerification
