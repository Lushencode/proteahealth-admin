
import { useEffect, useState } from 'react'
import DoctorVerificationHistory from './DoctorVerificationHistory'

import {
  Search,
  RefreshCw,
  Stethoscope,
  CheckCircle,
  Clock,
  XCircle,
  ChevronDown,
  ChevronUp,
  ShieldCheck,
} from 'lucide-react'

function DoctorVerification() {
  const [doctors, setDoctors] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [successMessage, setSuccessMessage] = useState('')

  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('all')
  const [expandedDoctor, setExpandedDoctor] = useState(null)
  const [refreshKey, setRefreshKey] = useState(0)

  // Verification form
  const [selectedDecision, setSelectedDecision] = useState(null)
  const [reason, setReason] = useState('')
  const [credentialReference, setCredentialReference] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [formError, setFormError] = useState('')
  const [confirmationOpen, setConfirmationOpen] = useState(false)

  // Load doctors from MySQL
  useEffect(() => {
    const controller = new AbortController()

    const fetchDoctors = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_doctors.php',
          {
            credentials: 'include',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Failed to load doctor applications'
          )
        }

        setDoctors(data.doctors)
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

    fetchDoctors()

    return () => controller.abort()
  }, [refreshKey])

  const refreshDoctors = () => {
    setRefreshKey((current) => current + 1)
  }

  // Open approval or rejection form
  const openDecisionForm = (doctorId, decision) => {
    setSelectedDecision({
      doctorId,
      decision,
    })

    setReason('')
    setCredentialReference('')
    setFormError('')
    setSuccessMessage('')
  }

  const closeDecisionForm = () => {
    if (submitting) return

    setSelectedDecision(null)
    setReason('')
    setCredentialReference('')
    setFormError('')
  }

  // Submit verification decision
    const submitDecision = (event) => {
  event.preventDefault()

  if (!selectedDecision || submitting) return

  setFormError('')

  if (reason.trim().length < 10 || reason.trim().length > 2000) {
    setFormError('Please provide a reason between 10 and 2000 characters.')
    return
  }

  if (
    selectedDecision.decision === 'verified' &&
    !credentialReference.trim()
  ) {
    setFormError('A professional registration verification reference is required.')
    return
  }

  setConfirmationOpen(true)
}

const confirmDecision = async () => {
  if (!selectedDecision || submitting) return

  setConfirmationOpen(false)
  setFormError('')
  setSuccessMessage('')
  setSubmitting(true)

    try {
      // Obtain CSRF token from authenticated PHP session
      const csrfResponse = await fetch(
        '/admin-api/csrf.php',
        {
          credentials: 'include',
          cache: 'no-store',
        }
      )

      const csrfData = await csrfResponse.json()

      if (!csrfResponse.ok || !csrfData.success) {
        throw new Error(
          csrfData.message || 'Unable to obtain security token'
        )
      }

      // Send administrator decision to PHP
      const response = await fetch(
        '/admin-api/verify_doctor.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfData.csrf_token,
          },
          body: JSON.stringify({
            doctor_id: Number(selectedDecision.doctorId),
            decision: selectedDecision.decision,
            reason: reason.trim(),
            credential_reference: credentialReference.trim(),
          }),
        }
      )

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || 'Unable to update doctor verification'
        )
      }

      setSuccessMessage(
        selectedDecision.decision === 'verified'
          ? 'Doctor application approved successfully.'
          : 'Doctor application rejected successfully.'
      )

      setSelectedDecision(null)
      setReason('')
      setCredentialReference('')
      setExpandedDoctor(null)

      // Reload current statuses from MySQL
      refreshDoctors()
    } catch (err) {
      setFormError(err.message)
    } finally {
      setSubmitting(false)
    }
  }

  // Statistics
  const pending = doctors.filter(
    (doctor) => doctor.verification === 'pending'
  ).length

  const verified = doctors.filter(
    (doctor) => doctor.verification === 'verified'
  ).length

  const rejected = doctors.filter(
    (doctor) => doctor.verification === 'rejected'
  ).length

  // Search and filter
  const filteredDoctors = doctors.filter((doctor) => {
    const searchableText = [
      doctor.name,
      doctor.surname,
      doctor.email,
      doctor.specialization,
      doctor.clinic_name,
      doctor.location,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    const matchesSearch = searchableText.includes(
      search.toLowerCase().trim()
    )

    const matchesFilter =
      filter === 'all' ||
      doctor.verification === filter

    return matchesSearch && matchesFilter
  })

  const statusStyles = {
    pending: 'bg-amber-100 text-amber-800',
    verified: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }

  return (
    <div className="space-y-6">

      {/* Heading */}
      <div>
        <h2 className="text-2xl font-bold text-slate-800">
          Doctor Verification
        </h2>

        <p className="mt-1 text-sm text-slate-500">
          Review professional credentials and manage doctor applications.
        </p>
      </div>

      {/* Success message */}
      {successMessage && (
        <div
          role="status"
          className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800"
        >
          {successMessage}
        </div>
      )}

      {/* Statistics */}
      <div className="grid gap-4 sm:grid-cols-3">
        {[
          {
            title: 'Pending',
            value: pending,
            icon: Clock,
            color: 'text-amber-600',
          },
          {
            title: 'Verified',
            value: verified,
            icon: CheckCircle,
            color: 'text-green-600',
          },
          {
            title: 'Rejected',
            value: rejected,
            icon: XCircle,
            color: 'text-red-600',
          },
        ].map(({ title, value, icon: Icon, color }) => (
          <div
            key={title}
            className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium text-slate-500">
                {title}
              </p>

              <Icon size={23} className={color} />
            </div>

            <p className="mt-3 text-3xl font-bold text-slate-800">
              {loading ? '...' : error ? '—' : value}
            </p>
          </div>
        ))}
      </div>

      {/* Search and filter */}
      <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col gap-4 md:flex-row">
          <div className="relative flex-1">
            <Search
              size={19}
              className="absolute left-3 top-3 text-slate-400"
            />

            <input
              type="text"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search doctors, clinics, specializations..."
              aria-label="Search doctors"
              className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-green-600"
            />
          </div>

          <select
            value={filter}
            onChange={(event) => setFilter(event.target.value)}
            aria-label="Filter by verification status"
            className="rounded-lg border border-slate-300 px-4 py-2.5"
          >
            <option value="all">All Applications</option>
            <option value="pending">Pending</option>
            <option value="verified">Verified</option>
            <option value="rejected">Rejected</option>
          </select>

          <button
            type="button"
            onClick={refreshDoctors}
            disabled={loading || submitting}
            className="flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2.5 text-white hover:bg-green-800 disabled:opacity-50"
          >
            <RefreshCw size={17} />
            Refresh
          </button>
        </div>
      </div>

      {/* API error */}
      {error && (
        <div
          role="alert"
          className="rounded-lg bg-red-50 p-4 text-red-700"
        >
          {error}
        </div>
      )}

      {loading && (
        <p className="text-center text-slate-500">
          Loading doctor applications...
        </p>
      )}

      {/* Doctor applications */}
      {!loading && !error && (
        <div className="space-y-4">
          {filteredDoctors.length === 0 ? (
            <div className="rounded-xl border bg-white p-8 text-center text-slate-500">
              No matching doctor applications found.
            </div>
          ) : (
            filteredDoctors.map((doctor) => (
              <div
                key={doctor.id}
                className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
              >
                {/* Doctor summary */}
                <div className="flex flex-col justify-between gap-4 p-5 md:flex-row md:items-center">
                  <div className="flex items-start gap-4">
                    <div className="rounded-xl bg-green-100 p-3 text-green-700">
                      <Stethoscope size={24} />
                    </div>

                    <div>
                      <h3 className="text-lg font-semibold text-slate-800">
                        {doctor.name} {doctor.surname}
                      </h3>

                      <p className="text-sm text-slate-600">
                        {doctor.specialization}
                      </p>

                      <p className="mt-1 text-sm text-slate-500">
                        {doctor.clinic_name}
                      </p>
                    </div>
                  </div>

                  <div className="flex flex-wrap items-center gap-3">
                    <span
                      className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                        statusStyles[doctor.verification] ||
                        'bg-slate-100 text-slate-700'
                      }`}
                    >
                      {doctor.verification}
                    </span>

                    <button
                      type="button"
                      onClick={() => {
                        setExpandedDoctor(
                          expandedDoctor === doctor.id
                            ? null
                            : doctor.id
                        )
                        closeDecisionForm()
                      }}
                      aria-expanded={expandedDoctor === doctor.id}
                      disabled={submitting}
                      className="flex items-center gap-2 rounded-lg border border-green-700 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50 disabled:opacity-50"
                    >
                      View Details

                      {expandedDoctor === doctor.id ? (
                        <ChevronUp size={16} />
                      ) : (
                        <ChevronDown size={16} />
                      )}
                    </button>
                  </div>
                </div>

                {/* Expanded details */}
                {expandedDoctor === doctor.id && (
                  <div className="border-t border-slate-200 bg-slate-50 p-5">
                    <div className="grid gap-5 sm:grid-cols-2">
                      {[
                        ['Doctor ID', doctor.id],
                        ['Email', doctor.email],
                        ['Specialization', doctor.specialization],
                        ['Clinic', doctor.clinic_name],
                        ['Location', doctor.location],
                        [
                          'Years of Experience',
                          doctor.years_in_professional,
                        ],
                        [
                          'Consultation Price',
                          doctor.consultation_price != null
                            ? `R ${Number(
                                doctor.consultation_price
                              ).toFixed(2)}`
                            : 'Not provided',
                        ],
                        ['Certification', doctor.certification],
                        ['Registration Date', doctor.created_at],
                        ['Verification Status', doctor.verification],
                      ].map(([label, value]) => (
                        <div key={label} className="min-w-0">
                          <p className="text-xs font-medium uppercase text-slate-500">
                            {label}
                          </p>

                          <p className="mt-1 break-words text-sm font-medium text-slate-800">
                            {value ?? 'Not provided'}
                          </p>
                        </div>
                      ))}
                    </div>

                    {/* Only pending doctors can be reviewed */}
                    {doctor.verification === 'pending' && (
                      <div className="mt-6 border-t border-slate-200 pt-6">
                        <div className="mb-4 flex items-center gap-2">
                          <ShieldCheck
                            size={21}
                            className="text-green-700"
                          />

                          <h4 className="font-semibold text-slate-800">
                            Verification Decision
                          </h4>
                        </div>

                        <p className="mb-4 text-sm text-slate-600">
                          Confirm the doctor's professional registration
                          independently before approving this application.
                          The submitted certification alone is not proof
                          of registration.
                        </p>

                        {/* Decision buttons */}
                        <div className="flex flex-wrap gap-3">
                          <button
                            type="button"
                            disabled={submitting}
                            onClick={() =>
                              openDecisionForm(doctor.id, 'verified')
                            }
                            className="flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-800 disabled:opacity-50"
                          >
                            <CheckCircle size={18} />
                            Approve
                          </button>

                          <button
                            type="button"
                            disabled={submitting}
                            onClick={() =>
                              openDecisionForm(doctor.id, 'rejected')
                            }
                            className="flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                          >
                            <XCircle size={18} />
                            Reject
                          </button>
                        </div>

                        {/* Decision form */}
                        {selectedDecision?.doctorId === doctor.id && (
                          <form
                            onSubmit={submitDecision}
                            className="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-5"
                          >
                            <h5 className="font-semibold text-slate-800">
                              {selectedDecision.decision === 'verified'
                                ? 'Approve Doctor Application'
                                : 'Reject Doctor Application'}
                            </h5>

                            {selectedDecision.decision === 'verified' && (
                              <div>
                                <label
                                  htmlFor="credentialReference"
                                  className="mb-2 block text-sm font-medium text-slate-700"
                                >
                                  Professional Registration Verification Reference
                                </label>

                                <input
                                  id="credentialReference"
                                  type="text"
                                  required
                                  maxLength={255}
                                  value={credentialReference}
                                  onChange={(event) =>
                                    setCredentialReference(event.target.value)
                                  }
                                  placeholder="Reference from independently checked registration"
                                  className="w-full rounded-lg border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600"
                                />
                              </div>
                            )}

                            <div>
                              <label
                                htmlFor="decisionReason"
                                className="mb-2 block text-sm font-medium text-slate-700"
                              >
                                Decision Reason
                              </label>

                              <textarea
                                id="decisionReason"
                                required
                                minLength={10}
                                maxLength={2000}
                                rows={4}
                                value={reason}
                                onChange={(event) =>
                                  setReason(event.target.value)
                                }
                                placeholder="Explain the reason for this verification decision..."
                                className="w-full rounded-lg border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600"
                              />
                            </div>

                            {formError && (
                              <p
                                role="alert"
                                className="rounded-lg bg-red-50 p-3 text-sm text-red-700"
                              >
                                {formError}
                              </p>
                            )}

                            <div className="flex flex-wrap gap-3">
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
                                  ? 'Saving Decision...'
                                  : selectedDecision.decision === 'verified'
                                    ? 'Confirm Approval'
                                    : 'Confirm Rejection'}
                              </button>

                              <button
                                type="button"
                                onClick={closeDecisionForm}
                                disabled={submitting}
                                className="rounded-lg border border-slate-300 px-5 py-2.5 text-sm text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                              >
                                Cancel
                              </button>
                            </div>
                          </form>
                        )}
                      </div>
                    )}
                  </div>
                )}
              </div>
            ))
          )}
        </div>
      )}

{/* Doctor verification audit history */}
<DoctorVerificationHistory />
{/* Custom confirmation modal */}
{confirmationOpen && selectedDecision && (
  <div
    className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
    role="presentation"
    onMouseDown={(event) => {
      if (event.target === event.currentTarget) {
        setConfirmationOpen(false)
      }
    }}
  >
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="verification-confirm-title"
      aria-describedby="verification-confirm-description"
      className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
    >
      <div className="flex flex-col items-center text-center">
        <div
          className={`mb-4 flex h-16 w-16 items-center justify-center rounded-full ${
            selectedDecision.decision === 'verified'
              ? 'bg-green-100'
              : 'bg-red-100'
          }`}
        >
          {selectedDecision.decision === 'verified' ? (
            <CheckCircle
              size={32}
              className="text-green-700"
            />
          ) : (
            <XCircle
              size={32}
              className="text-red-600"
            />
          )}
        </div>

        <h3
          id="verification-confirm-title"
          className="text-xl font-bold text-slate-800"
        >
          {selectedDecision.decision === 'verified'
            ? 'Confirm Doctor Approval'
            : 'Confirm Doctor Rejection'}
        </h3>

        <p
          id="verification-confirm-description"
          className="mt-3 text-sm leading-6 text-slate-600"
        >
          Are you sure you want to{' '}
          <strong>
            {selectedDecision.decision === 'verified'
              ? 'approve'
              : 'reject'}
          </strong>{' '}
          the application for{' '}
          <strong>
            {doctors.find(
              (doctor) => doctor.id === selectedDecision.doctorId
            )?.name}{' '}
            {doctors.find(
              (doctor) => doctor.id === selectedDecision.doctorId
            )?.surname}
          </strong>
          ?
        </p>

        <div className="mt-5 w-full rounded-lg bg-slate-50 p-4 text-left">
          <p className="text-xs font-semibold uppercase text-slate-500">
            Decision Reason
          </p>

          <p className="mt-2 whitespace-pre-wrap break-words text-sm text-slate-700">
            {reason}
          </p>

          {selectedDecision.decision === 'verified' && (
            <>
              <p className="mt-4 text-xs font-semibold uppercase text-slate-500">
                Verification Reference
              </p>

              <p className="mt-2 break-words text-sm text-slate-700">
                {credentialReference}
              </p>
            </>
          )}
        </div>

        <p className="mt-4 text-xs text-amber-700">
          This decision will be recorded in the verification audit log.
        </p>

        <div className="mt-6 flex w-full gap-3">
          <button
            type="button"
            onClick={() => setConfirmationOpen(false)}
            className="flex-1 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
          >
            Cancel
          </button>

          <button
            type="button"
            onClick={confirmDecision}
            disabled={submitting}
            className={`flex-1 rounded-lg px-4 py-3 text-sm font-semibold text-white transition disabled:opacity-50 ${
              selectedDecision.decision === 'verified'
                ? 'bg-green-700 hover:bg-green-800'
                : 'bg-red-600 hover:bg-red-700'
            }`}
          >
            {selectedDecision.decision === 'verified'
              ? 'Confirm Approval'
              : 'Confirm Rejection'}
          </button>
        </div>
      </div>
    </div>
  </div>
)}

    </div>
  )
}

export default DoctorVerification
