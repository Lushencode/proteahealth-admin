
import { useEffect, useState } from 'react'

import {
  History,
  RefreshCw,
  ChevronDown,
  ChevronUp,
  CheckCircle,
  XCircle,
  UserRound,
  CalendarDays,
} from 'lucide-react'

function DoctorVerificationHistory() {
  const [history, setHistory] = useState([])
  const [isOpen, setIsOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)
  const [expandedRecord, setExpandedRecord] = useState(null)

  useEffect(() => {
    if (!isOpen) return

    const controller = new AbortController()

    const fetchHistory = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_doctor_verification_history.php',
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Unable to load verification history'
          )
        }

        setHistory(data.history)
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

    fetchHistory()

    return () => controller.abort()
  }, [isOpen, refreshKey])

  const refreshHistory = () => {
    setRefreshKey((current) => current + 1)
  }

  return (
    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

      {/* Section header */}
      <div className="flex flex-col justify-between gap-4 p-6 sm:flex-row sm:items-center">
        <div className="flex items-center gap-3">
          <div className="rounded-xl bg-green-100 p-3 text-green-700">
            <History size={24} />
          </div>

          <div>
            <h3 className="text-lg font-semibold text-slate-800">
              Doctor Verification History
            </h3>

            <p className="mt-1 text-sm text-slate-500">
              Review previous administrator decisions.
            </p>
          </div>
        </div>

        <button
          type="button"
          onClick={() => setIsOpen((current) => !current)}
          aria-expanded={isOpen}
          className="flex items-center justify-center gap-2 rounded-lg border border-green-700 px-4 py-2.5 text-sm font-medium text-green-700 hover:bg-green-50"
        >
          {isOpen ? (
            <>
              Hide History
              <ChevronUp size={17} />
            </>
          ) : (
            <>
              View History
              <ChevronDown size={17} />
            </>
          )}
        </button>
      </div>

      {/* History content */}
      {isOpen && (
        <div className="border-t border-slate-200 bg-slate-50 p-5">

          {/* Refresh */}
          <div className="mb-5 flex items-center justify-between">
            <p className="text-sm text-slate-500">
              {loading
                ? 'Loading history...'
                : error
                  ? 'History unavailable'
                  : `${history.length} recent decision(s)`}
            </p>

            <button
              type="button"
              onClick={refreshHistory}
              disabled={loading}
              className="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 disabled:opacity-50"
            >
              <RefreshCw size={16} />
              Refresh
            </button>
          </div>

          {/* Error */}
          {error && (
            <div
              role="alert"
              className="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700"
            >
              {error}
            </div>
          )}

          {/* Loading */}
          {loading && (
            <p className="py-6 text-center text-sm text-slate-500">
              Retrieving verification history...
            </p>
          )}

          {/* No records */}
          {!loading && !error && history.length === 0 && (
            <div className="rounded-lg border border-slate-200 bg-white p-8 text-center">
              <History
                size={32}
                className="mx-auto mb-3 text-slate-400"
              />

              <p className="font-medium text-slate-700">
                No verification history yet
              </p>

              <p className="mt-2 text-sm text-slate-500">
                Administrator decisions will appear here after
                a doctor application is reviewed.
              </p>
            </div>
          )}

          {/* History records */}
          {!loading && !error && history.length > 0 && (
            <div className="space-y-3">
              {history.map((record) => {
                const approved = record.new_status === 'verified'
                const expanded = expandedRecord === record.id

                return (
                  <div
                    key={record.id}
                    className="overflow-hidden rounded-xl border border-slate-200 bg-white"
                  >
                    <div className="flex flex-col justify-between gap-4 p-5 md:flex-row md:items-center">
                      <div className="flex items-start gap-3">
                        <div
                          className={`rounded-full p-2 ${
                            approved
                              ? 'bg-green-100 text-green-700'
                              : 'bg-red-100 text-red-600'
                          }`}
                        >
                          {approved ? (
                            <CheckCircle size={21} />
                          ) : (
                            <XCircle size={21} />
                          )}
                        </div>

                        <div>
                          <p className="font-semibold text-slate-800">
                            {record.doctor_name || 'Unknown'}{' '}
                            {record.doctor_surname || 'Doctor'}
                          </p>

                          <p className="mt-1 flex items-center gap-2 text-sm text-slate-500">
                            <UserRound size={15} />
                            Reviewed by:{' '}
                            {record.admin_name || 'Unknown'}{' '}
                            {record.admin_surname || 'Administrator'}
                          </p>

                          <p className="mt-1 flex items-center gap-2 text-sm text-slate-500">
                            <CalendarDays size={15} />
                            {record.created_at || 'Date unavailable'}
                          </p>
                        </div>
                      </div>

                      <div className="flex flex-wrap items-center gap-3">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-semibold ${
                            approved
                              ? 'bg-green-100 text-green-800'
                              : 'bg-red-100 text-red-800'
                          }`}
                        >
                          {approved ? 'Approved' : 'Rejected'}
                        </span>

                        <button
                          type="button"
                          onClick={() =>
                            setExpandedRecord(
                              expanded ? null : record.id
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

                    {/* Expanded audit details */}
                    {expanded && (
                      <div className="border-t border-slate-200 bg-slate-50 p-5">
                        <div className="grid gap-5 sm:grid-cols-2">
                          <div>
                            <p className="text-xs font-semibold uppercase text-slate-500">
                              Doctor ID
                            </p>
                            <p className="mt-1 text-sm text-slate-800">
                              {record.doctor_id}
                            </p>
                          </div>

                          <div>
                            <p className="text-xs font-semibold uppercase text-slate-500">
                              Administrator ID
                            </p>
                            <p className="mt-1 text-sm text-slate-800">
                              {record.admin_id}
                            </p>
                          </div>

                          <div>
                            <p className="text-xs font-semibold uppercase text-slate-500">
                              Previous Status
                            </p>
                            <p className="mt-1 text-sm capitalize text-slate-800">
                              {record.previous_status}
                            </p>
                          </div>

                          <div>
                            <p className="text-xs font-semibold uppercase text-slate-500">
                              New Status
                            </p>
                            <p className="mt-1 text-sm capitalize text-slate-800">
                              {record.new_status}
                            </p>
                          </div>

                          <div className="sm:col-span-2">
                            <p className="text-xs font-semibold uppercase text-slate-500">
                              Decision Reason
                            </p>
                            <p className="mt-2 whitespace-pre-wrap break-words text-sm text-slate-800">
                              {record.decision_reason}
                            </p>
                          </div>

                          <div className="sm:col-span-2">
                            <p className="text-xs font-semibold uppercase text-slate-500">
                              Professional Registration Reference
                            </p>
                            <p className="mt-2 break-words text-sm text-slate-800">
                              {record.credential_reference || 'Not provided'}
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                )
              })}
            </div>
          )}
        </div>
      )}
    </section>
  )
}

export default DoctorVerificationHistory
