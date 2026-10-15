
import { useEffect, useMemo, useState } from 'react'
import {
  History,
  Search,
  RefreshCw,
  CheckCircle,
  XCircle,
  Clock,
  ChevronDown,
  ChevronUp,
  Building2,
  ShieldCheck,
} from 'lucide-react'

export default function PharmacistVerificationHistory() {
  const [history, setHistory] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('all')
  const [expandedId, setExpandedId] = useState(null)
  const [refreshKey, setRefreshKey] = useState(0)

  useEffect(() => {
    const controller = new AbortController()

    const loadHistory = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_pharmacist_verification_history.php',
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Failed to load verification history'
          )
        }

        setHistory(data.history || [])
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

    loadHistory()

    return () => controller.abort()
  }, [refreshKey])

  const filteredHistory = useMemo(() => {
    return history.filter((record) => {
      const searchText = [
        record.pharmacist_name,
        record.pharmacy_name,
        record.admin_name,
        record.registration_code,
        record.credential_reference,
        record.decision_reason,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()

      const matchesSearch = searchText.includes(
        search.toLowerCase().trim()
      )

      const matchesFilter =
        filter === 'all' || record.new_status === filter

      return matchesSearch && matchesFilter
    })
  }, [history, search, filter])

  const approvedCount = history.filter(
    (record) => record.new_status === 'verified'
  ).length

  const rejectedCount = history.filter(
    (record) => record.new_status === 'rejected'
  ).length

  const formatDate = (date) => {
    if (!date) return 'Not available'

    const parsed = new Date(date.replace(' ', 'T'))

    if (Number.isNaN(parsed.getTime())) {
      return date
    }

    return parsed.toLocaleString('en-ZA', {
      dateStyle: 'medium',
      timeStyle: 'short',
    })
  }

  return (
    <section className="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

      {/* Header */}
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-3">
            <History className="h-6 w-6 text-blue-600" />
            <h2 className="text-xl font-bold text-slate-900">
              Pharmacist Verification History
            </h2>
          </div>

          <p className="mt-2 text-sm text-slate-500">
            Audit records of pharmacist approval and rejection decisions.
          </p>
        </div>

        <button
          type="button"
          onClick={() => setRefreshKey((current) => current + 1)}
          disabled={loading}
          className="flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
        >
          <RefreshCw
            className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`}
          />
          Refresh
        </button>
      </div>

      {/* Statistics */}
      <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div className="rounded-lg bg-slate-50 p-4">
          <div className="flex items-center gap-2 text-slate-600">
            <Clock className="h-5 w-5" />
            <span className="text-sm">Total Decisions</span>
          </div>
          <p className="mt-2 text-2xl font-bold">
            {history.length}
          </p>
        </div>

        <div className="rounded-lg bg-green-50 p-4">
          <div className="flex items-center gap-2 text-green-700">
            <CheckCircle className="h-5 w-5" />
            <span className="text-sm">Approved</span>
          </div>
          <p className="mt-2 text-2xl font-bold text-green-700">
            {approvedCount}
          </p>
        </div>

        <div className="rounded-lg bg-red-50 p-4">
          <div className="flex items-center gap-2 text-red-700">
            <XCircle className="h-5 w-5" />
            <span className="text-sm">Rejected</span>
          </div>
          <p className="mt-2 text-2xl font-bold text-red-700">
            {rejectedCount}
          </p>
        </div>
      </div>

      {/* Search and Filter */}
      <div className="mb-6 flex flex-col gap-3 sm:flex-row">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-3 h-4 w-4 text-slate-400" />

          <input
            type="text"
            placeholder="Search pharmacist and pharmacy..."
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            className="w-full rounded-lg border border-slate-200 py-2.5 pl-10 pr-4 text-sm outline-none focus:border-blue-500"
          />
        </div>

        <select
          value={filter}
          onChange={(event) => setFilter(event.target.value)}
          className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm"
        >
          <option value="all">All Decisions</option>
          <option value="verified">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      {/* Errors */}
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
        <p className="py-8 text-center text-slate-500">
          Loading verification history...
        </p>
      )}

      {/* Empty State */}
      {!loading && !error && filteredHistory.length === 0 && (
        <div className="py-10 text-center text-slate-500">
          <History className="mx-auto mb-3 h-10 w-10 text-slate-300" />
          <p>No verification history found.</p>
        </div>
      )}

      {/* History Records */}
      {!loading && !error && filteredHistory.length > 0 && (
        <div className="space-y-3">
          {filteredHistory.map((record) => {
            const isApproved = record.new_status === 'verified'
            const isExpanded = expandedId === record.id

            return (
              <div
                key={record.id}
                className="overflow-hidden rounded-lg border border-slate-200"
              >
                <button
                  type="button"
                  onClick={() =>
                    setExpandedId(isExpanded ? null : record.id)
                  }
                  aria-expanded={isExpanded}
                  className="flex w-full flex-wrap items-center justify-between gap-3 p-4 text-left hover:bg-slate-50"
                >
                  <div className="flex items-center gap-3">
                    {isApproved ? (
                      <CheckCircle className="h-5 w-5 text-green-600" />
                    ) : (
                      <XCircle className="h-5 w-5 text-red-600" />
                    )}

                    <div>
                      <p className="font-semibold text-slate-900">
                        {record.pharmacist_name || 'Unknown Pharmacist'}
                      </p>
                      <p className="text-xs text-slate-500">
                        {formatDate(record.created_at)}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-3">
                    <span
                      className={`rounded-full px-3 py-1 text-xs font-semibold ${
                        isApproved
                          ? 'bg-green-100 text-green-700'
                          : 'bg-red-100 text-red-700'
                      }`}
                    >
                      {isApproved ? 'Approved' : 'Rejected'}
                    </span>

                    {isExpanded ? (
                      <ChevronUp className="h-4 w-4" />
                    ) : (
                      <ChevronDown className="h-4 w-4" />
                    )}
                  </div>
                </button>

                {isExpanded && (
                  <div className="border-t border-slate-200 bg-slate-50 p-5">
                    <div className="grid gap-5 sm:grid-cols-2">

                      <div>
                        <p className="text-xs text-slate-500">
                          Pharmacist Registration Code
                        </p>
                        <p className="mt-1 text-sm font-medium">
                          {record.registration_code || 'Not available'}
                        </p>
                      </div>

                      <div>
                        <div className="flex items-center gap-2 text-xs text-slate-500">
                          <Building2 className="h-4 w-4" />
                          Pharmacy
                        </div>
                        <p className="mt-1 text-sm font-medium">
                          {record.pharmacy_name || 'No pharmacy linked'}
                        </p>
                      </div>

                      <div>
                        <p className="text-xs text-slate-500">
                          Previous Status
                        </p>
                        <p className="mt-1 text-sm font-medium capitalize">
                          {record.previous_status}
                        </p>
                      </div>

                      <div>
                        <p className="text-xs text-slate-500">
                          New Status
                        </p>
                        <p className="mt-1 text-sm font-medium capitalize">
                          {record.new_status}
                        </p>
                      </div>

                      <div>
                        <div className="flex items-center gap-2 text-xs text-slate-500">
                          <ShieldCheck className="h-4 w-4" />
                          Administrator
                        </div>
                        <p className="mt-1 text-sm font-medium">
                          {record.admin_name || 'Unknown Administrator'}
                        </p>
                      </div>

                      <div>
                        <p className="text-xs text-slate-500">
                          Verification Reference
                        </p>
                        <p className="mt-1 break-words text-sm font-medium">
                          {record.credential_reference || 'Not provided'}
                        </p>
                      </div>

                      <div className="sm:col-span-2">
                        <p className="text-xs text-slate-500">
                          Decision Reason
                        </p>
                        <p className="mt-1 whitespace-pre-wrap break-words text-sm text-slate-800">
                          {record.decision_reason || 'No reason recorded'}
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

      {!loading && !error && (
        <p className="mt-4 text-xs text-slate-400">
          Showing {filteredHistory.length} of {history.length} most recent audit records (maximum 100).
        </p>
      )}
    </section>
  )
}
