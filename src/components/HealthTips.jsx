
import { useEffect, useMemo, useState } from 'react'
import DeleteConfirmDialog from './DeleteConfirmDialog'
import {
  BookOpen,
  RefreshCw,
  Search,
  Eye,
  X,
  CheckCircle,
  FileText,
  Archive,
  Trash2,
} from 'lucide-react'

const statusStyles = {
  published: 'bg-green-100 text-green-800',
  draft: 'bg-amber-100 text-amber-800',
  archived: 'bg-slate-100 text-slate-700',
}

function HealthTips() {
    const [tipToDelete, setTipToDelete] = useState(null)
  const [tips, setTips] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')

  const [selectedTip, setSelectedTip] = useState(null)


const [deletingId, setDeletingId] = useState(null)
const [deleteError, setDeleteError] = useState('')

const handleDeleteTip = async (tip) => {
  setTipToDelete(null)

  try {
    const formData = new FormData()
    formData.append('resource_id', tip.resource_id)

    const response = await fetch(
      '/admin-api/delete_health_tip.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData,
      }
    )

    const data = await response.json()

    if (!response.ok || !data.success) {
      throw new Error(
        data.message || 'Unable to delete health tip.'
      )
    }

    // Remove deleted tip from the table
    setTips((previous) =>
      previous.filter(
        (item) => item.resource_id !== tip.resource_id
      )
    )

    // Close details if the deleted tip is open
    setSelectedTip((previous) =>
      previous?.resource_id === tip.resource_id
        ? null
        : previous
    )

  } catch (err) {
    setDeleteError(
      err.message || 'Failed to delete health tip.'
    )
  } finally {
    setDeletingId(null)
  }
}


  // Load health tips from the PHP admin API
  useEffect(() => {
    const controller = new AbortController()

    const loadHealthTips = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_all_health_tips.php',
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Unable to retrieve health tips.'
          )
        }

        if (!controller.signal.aborted) {
          setTips(Array.isArray(data.tips) ? data.tips : [])
        }
      } catch (err) {
        if (!controller.signal.aborted) {
          setError(err.message || 'Failed to load health tips.')
          setTips([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadHealthTips()

    return () => controller.abort()
  }, [refreshKey])

  // Calculate statistics
  const stats = useMemo(() => {
    return {
      total: tips.length,
      published: tips.filter(
        (tip) => tip.status === 'published'
      ).length,
      drafts: tips.filter(
        (tip) => tip.status === 'draft'
      ).length,
      archived: tips.filter(
        (tip) => tip.status === 'archived'
      ).length,
      reviewed: tips.filter(
        (tip) => Number(tip.is_reviewed) === 1
      ).length,
    }
  }, [tips])

  // Search and filter health tips
  const filteredTips = useMemo(() => {
    const term = search.trim().toLowerCase()

    return tips.filter((tip) => {
      const searchable = [
        tip.resource_id,
        tip.title,
        tip.summary,
        tip.health_tip,
        tip.category_id,
        tip.provider_id,
      ]
        .filter((value) => value != null)
        .join(' ')
        .toLowerCase()

      const matchesSearch = searchable.includes(term)

      const matchesStatus =
        statusFilter === 'all' ||
        tip.status === statusFilter

      return matchesSearch && matchesStatus
    })
  }, [tips, search, statusFilter])

  const statisticCards = [
    {
      title: 'Total Health Tips',
      value: stats.total,
      icon: BookOpen,
      color: 'text-green-700',
    },
    {
      title: 'Published',
      value: stats.published,
      icon: CheckCircle,
      color: 'text-green-600',
    },
    {
      title: 'Drafts',
      value: stats.drafts,
      icon: FileText,
      color: 'text-amber-600',
    },
    {
      title: 'Archived',
      value: stats.archived,
      icon: Archive,
      color: 'text-slate-600',
    },
  ]

  return (
    <div className="space-y-6">

      {/* Page header */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Health Tips Management
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            View and manage educational health content
            published through ProteaHealth.
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

      {/* Statistics cards */}
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

                <Icon size={21} className={color} />
              </div>

              <p className="mt-4 text-3xl font-bold text-slate-800">
                {loading ? '...' : error ? '—' : value}
              </p>
            </div>
          )
        )}
      </div>

      {/* Health tips table */}
      <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div className="border-b border-slate-200 p-5">
          <h3 className="text-lg font-semibold text-slate-800">
            Educational Resources
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Search and filter health tips stored in the database.
          </p>

          {/* Search and filter */}
          <div className="mt-5 flex flex-col gap-3 md:flex-row">

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
                placeholder="Search health tips..."
                className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-600"
              />
            </div>

            <select
              value={statusFilter}
              onChange={(event) =>
                setStatusFilter(event.target.value)
              }
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"
            >
              <option value="all">All Statuses</option>
              <option value="published">Published</option>
              <option value="draft">Drafts</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>

        {deleteError && (
  <div
    role="alert"
    className="m-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"
  >
    {deleteError}
  </div>
)}

        {/* API errors */}
        {error && (
          <div
            role="alert"
            className="m-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"
          >
            {error}
          </div>
        )}

        <div className="overflow-x-auto">
          <table className="w-full min-w-[950px] text-left text-sm">

            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-5 py-4">Health Tip</th>
                <th className="px-5 py-4">Category</th>
                <th className="px-5 py-4">Doctor</th>
                <th className="px-5 py-4">Status</th>
                <th className="px-5 py-4">Reviewed</th>
                <th className="px-5 py-4">Created</th>
                <th className="px-5 py-4">Actions</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100">

              {loading && (
                <tr>
                  <td
                    colSpan={7}
                    className="px-5 py-12 text-center text-slate-500"
                  >
                    Loading health tips...
                  </td>
                </tr>
              )}

              {!loading && !error &&
                filteredTips.map((tip) => (
                  <tr
                    key={tip.resource_id}
                    className="hover:bg-slate-50"
                  >

                    <td className="px-5 py-4">
                      <p className="font-semibold text-slate-800">
                        {tip.title}
                      </p>

                      <p className="mt-1 max-w-xs truncate text-xs text-slate-500">
                        {tip.summary}
                      </p>

                      <p className="mt-1 text-xs text-slate-400">
                        ID: {tip.resource_id}
                      </p>
                    </td>

                    <td className="px-5 py-4 text-slate-600">
                      Category ID: {tip.category_id}
                    </td>

                    <td className="px-5 py-4 text-slate-600">
                      Doctor ID: {tip.provider_id}
                    </td>

                    <td className="px-5 py-4">
                      <span
                        className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                          statusStyles[tip.status] ||
                          'bg-slate-100 text-slate-700'
                        }`}
                      >
                        {tip.status}
                      </span>
                    </td>

                    <td className="px-5 py-4">
                      {Number(tip.is_reviewed) === 1 ? (
                        <span className="font-medium text-green-700">
                          Yes
                        </span>
                      ) : (
                        <span className="text-amber-700">
                          No
                        </span>
                      )}
                    </td>

                    <td className="px-5 py-4 text-slate-600">
                      {tip.created_at || 'Not available'}
                    </td>

                    
<td className="px-5 py-4">
  <div className="flex items-center gap-2">

    {/* View Details */}
    <button
      type="button"
      onClick={() => setSelectedTip(tip)}
      className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
    >
      <Eye size={15} />
      View
    </button>

    {/* Delete Health Tip */}
    <button
      type="button"
      onClick={() => {
  setDeleteError('')
  setTipToDelete(tip)
}}
      disabled={deletingId !== null}
      className="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
    >
      <Trash2 size={15} />

      {deletingId === tip.resource_id
        ? 'Deleting...'
        : 'Delete'}
    </button>

  </div>
</td>

                  </tr>
                ))}

              {!loading && !error &&
                filteredTips.length === 0 && (
                  <tr>
                    <td
                      colSpan={7}
                      className="px-5 py-12 text-center text-slate-500"
                    >
                      No health tips found.
                    </td>
                  </tr>
                )}
            </tbody>
          </table>
        </div>

        {!loading && !error && (
          <div className="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
            Showing {filteredTips.length} of {tips.length} health tips
          </div>
        )}
      </section>

{/* Delete Health Tip Dialog */}
<DeleteConfirmDialog
  open={tipToDelete !== null}
  title="Delete Health Tip"
  itemName={tipToDelete?.title || ''}
  description="Are you sure you want to permanently delete this health tip from ProteaHealth?"
  warning="This action cannot be undone. The article will be removed from the database and will no longer appear in the Health Hub."
  loading={deletingId !== null}
  error={deleteError}
  onCancel={() => {
    setTipToDelete(null)
    setDeleteError('')
  }}
  onConfirm={() => {
    if (tipToDelete) {
      handleDeleteTip(tipToDelete)
    }
  }}
/>

      {/* Health tip details modal */}
      {selectedTip && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
              setSelectedTip(null)
            }
          }}
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="health-tip-details-title"
            className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
          >

            <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white p-5">
              <h3
                id="health-tip-details-title"
                className="text-xl font-bold text-slate-800"
              >
                Health Tip Details
              </h3>

              <button
                type="button"
                onClick={() => setSelectedTip(null)}
                aria-label="Close details"
                className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
              >
                <X size={22} />
              </button>
            </div>

            <div className="space-y-5 p-6">

              <h4 className="text-xl font-semibold text-slate-800">
                {selectedTip.title}
              </h4>

              <div className="flex flex-wrap gap-2">
                <span
                  className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                    statusStyles[selectedTip.status] ||
                    'bg-slate-100 text-slate-700'
                  }`}
                >
                  {selectedTip.status}
                </span>

                <span className="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                  {Number(selectedTip.is_reviewed) === 1
                    ? 'Reviewed'
                    : 'Not reviewed'}
                </span>
              </div>

              <div className="grid gap-3 sm:grid-cols-2">
                <div className="rounded-lg bg-slate-50 p-4">
                  <p className="text-xs text-slate-500">
                    Category ID
                  </p>
                  <p className="mt-1 font-medium">
                    {selectedTip.category_id}
                  </p>
                </div>

                <div className="rounded-lg bg-slate-50 p-4">
                  <p className="text-xs text-slate-500">
                    Doctor ID
                  </p>
                  <p className="mt-1 font-medium">
                    {selectedTip.provider_id}
                  </p>
                </div>

                <div className="rounded-lg bg-slate-50 p-4">
                  <p className="text-xs text-slate-500">
                    Reading Time
                  </p>
                  <p className="mt-1 font-medium">
                    {selectedTip.read_time} min
                  </p>
                </div>

                <div className="rounded-lg bg-slate-50 p-4">
                  <p className="text-xs text-slate-500">
                    Created
                  </p>
                  <p className="mt-1 font-medium">
                    {selectedTip.created_at}
                  </p>
                </div>
              </div>

              <section>
                <h5 className="font-semibold text-slate-800">
                  Summary
                </h5>
                <p className="mt-2 whitespace-pre-wrap break-words text-sm text-slate-600">
                  {selectedTip.summary}
                </p>
              </section>

              <section>
                <h5 className="font-semibold text-slate-800">
                  Article Content
                </h5>
                <p className="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-600">
                  {selectedTip.content}
                </p>
              </section>

              {selectedTip.health_tip && (
                <section className="rounded-lg bg-green-50 p-4">
                  <h5 className="font-semibold text-green-800">
                    Additional Health Tip
                  </h5>

                  <p className="mt-2 whitespace-pre-wrap break-words text-sm text-green-900">
                    {selectedTip.health_tip}
                  </p>
                </section>
              )}

            </div>

            <div className="sticky bottom-0 flex justify-end border-t border-slate-200 bg-white p-5">
              <button
                type="button"
                onClick={() => setSelectedTip(null)}
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

export default HealthTips
