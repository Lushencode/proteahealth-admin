
import { useEffect, useMemo, useState } from 'react'
import {
  MessagesSquare,
  Stethoscope,
  MessageCircle,
  RefreshCw,
  Search,
  Eye,
  Trash2,
  X,
  CalendarDays,
} from 'lucide-react'
import DeleteConfirmDialog from './DeleteConfirmDialog'

function Answers() {
  const [answers, setAnswers] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  const [search, setSearch] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('all')

  const [selectedAnswer, setSelectedAnswer] = useState(null)
  const [answerToDelete, setAnswerToDelete] = useState(null)
  const [deletingId, setDeletingId] = useState(null)
  const [deleteError, setDeleteError] = useState('')

  // Fetch answers from PHP/MySQL
  useEffect(() => {
    const controller = new AbortController()

    const loadAnswers = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_all_answers.php',
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Unable to retrieve answers.'
          )
        }

        if (!controller.signal.aborted) {
          setAnswers(
            Array.isArray(data.answers) ? data.answers : []
          )
        }
      } catch (err) {
        if (!controller.signal.aborted) {
          setAnswers([])
          setError(err.message || 'Failed to load answers.')
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadAnswers()

    return () => controller.abort()
  }, [refreshKey])

  // Available categories
  const categories = useMemo(() => {
    return [...new Set(
      answers.map((answer) => answer.category)
        .filter(Boolean)
    )].sort()
  }, [answers])

  // Statistics
  const stats = useMemo(() => ({
    total: answers.length,
    doctors: new Set(
      answers
        .map((answer) => answer.provider_id)
        .filter((id) => id != null)
    ).size,
    questions: new Set(
      answers.map((answer) => answer.question_id)
    ).size,
    categories: categories.length,
  }), [answers, categories])

  // Search and filter
  const filteredAnswers = useMemo(() => {
    const term = search.trim().toLowerCase()

    return answers.filter((answer) => {
      const searchable = [
        answer.answer_id,
        answer.question_id,
        answer.question_text,
        answer.answer_text,
        answer.doctor_name,
        answer.category,
      ]
        .filter((value) => value != null)
        .join(' ')
        .toLowerCase()

      const matchesSearch = searchable.includes(term)

      const matchesCategory =
        categoryFilter === 'all' ||
        answer.category === categoryFilter

      return matchesSearch && matchesCategory
    })
  }, [answers, search, categoryFilter])

  // Delete an individual answer
  const handleDeleteAnswer = async (answer) => {
    if (deletingId !== null) return

    setDeletingId(answer.answer_id)
    setDeleteError('')

    try {
      const formData = new FormData()
      formData.append('answer_id', answer.answer_id)

      const response = await fetch(
        '/admin-api/admin_delete_answer.php',
        {
          method: 'POST',
          credentials: 'include',
          body: formData,
        }
      )

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || 'Unable to delete answer.'
        )
      }

      setAnswers((previous) =>
        previous.filter(
          (item) =>
            String(item.answer_id) !==
            String(answer.answer_id)
        )
      )

      setSelectedAnswer((previous) =>
        String(previous?.answer_id) ===
        String(answer.answer_id)
          ? null
          : previous
      )

      setAnswerToDelete(null)

    } catch (err) {
      setDeleteError(
        err.message || 'Failed to delete answer.'
      )
    } finally {
      setDeletingId(null)
    }
  }

  const statisticCards = [
    {
      title: 'Total Answers',
      value: stats.total,
      icon: MessagesSquare,
      color: 'text-green-700',
    },
    {
      title: 'Contributing Doctors',
      value: stats.doctors,
      icon: Stethoscope,
      color: 'text-blue-600',
    },
    {
      title: 'Questions Answered',
      value: stats.questions,
      icon: MessageCircle,
      color: 'text-amber-600',
    },
    {
      title: 'Categories',
      value: stats.categories,
      icon: CalendarDays,
      color: 'text-slate-600',
    },
  ]

  return (
    <div className="space-y-6">

      {/* Heading */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Answers Management
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            Review and moderate doctors' answers
            in the ProteaHealth community.
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

                <Icon size={21} className={color} />
              </div>

              <p className="mt-4 text-3xl font-bold text-slate-800">
                {loading ? '...' : error ? '—' : value}
              </p>
            </div>
          )
        )}
      </div>

      {/* Answers Table */}
      <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div className="border-b border-slate-200 p-5">
          <h3 className="text-lg font-semibold text-slate-800">
            Doctor Answers
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Browse and manage answers submitted by doctors.
          </p>

          <div className="mt-5 flex flex-col gap-3 md:flex-row">

            <div className="relative flex-1">
              <Search
                size={18}
                className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
              />

              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Search answers, questions, or doctors..."
                className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-600"
              />
            </div>

            <select
              value={categoryFilter}
              onChange={(event) => setCategoryFilter(event.target.value)}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"
            >
              <option value="all">All Categories</option>

              {categories.map((category) => (
                <option key={category} value={category}>
                  {category}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Errors */}
        {error && (
          <div
            role="alert"
            className="m-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"
          >
            {error}
          </div>
        )}

        <div className="overflow-x-auto">
          <table className="w-full min-w-[1000px] text-left text-sm">

            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-5 py-4">Answer</th>
                <th className="px-5 py-4">Doctor</th>
                <th className="px-5 py-4">Question</th>
                <th className="px-5 py-4">Category</th>
                <th className="px-5 py-4">Answered At</th>
                <th className="px-5 py-4">Actions</th>
              </tr>
            </thead>

            <tbody className="divide-y divide-slate-100">

              {loading && (
                <tr>
                  <td
                    colSpan={6}
                    className="px-5 py-12 text-center text-slate-500"
                  >
                    Loading answers...
                  </td>
                </tr>
              )}

              {!loading && !error &&
                filteredAnswers.map((answer) => (
                  <tr
                    key={answer.answer_id}
                    className="hover:bg-slate-50"
                  >
                    <td className="px-5 py-4">
                      <p className="max-w-sm break-words font-semibold text-slate-800">
                        {answer.answer_text}
                      </p>

                      <p className="mt-1 text-xs text-slate-400">
                        Answer ID: {answer.answer_id}
                      </p>
                    </td>

                    <td className="px-5 py-4 text-slate-700">
                      {answer.doctor_name}
                    </td>

                    <td className="px-5 py-4">
                      <p className="max-w-xs break-words text-slate-600">
                        {answer.question_text}
                      </p>

                      <p className="mt-1 text-xs text-slate-400">
                        Question #{answer.question_id}
                      </p>
                    </td>

                    <td className="px-5 py-4 text-slate-600">
                      {answer.category}
                    </td>

                    <td className="px-5 py-4 text-slate-600">
                      {answer.answered_at || 'Not available'}
                    </td>

                    <td className="px-5 py-4">
                      <div className="flex items-center gap-2">

                        <button
                          type="button"
                          onClick={() => setSelectedAnswer(answer)}
                          className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                          <Eye size={15} />
                          View
                        </button>

                        <button
                          type="button"
                          onClick={() => {
                            setDeleteError('')
                            setAnswerToDelete(answer)
                          }}
                          disabled={deletingId !== null}
                          className="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50"
                        >
                          <Trash2 size={15} />
                          Delete
                        </button>

                      </div>
                    </td>
                  </tr>
                ))}

              {!loading && !error &&
                filteredAnswers.length === 0 && (
                  <tr>
                    <td
                      colSpan={6}
                      className="px-5 py-12 text-center text-slate-500"
                    >
                      No answers found.
                    </td>
                  </tr>
                )}
            </tbody>
          </table>
        </div>

        {!loading && !error && (
          <div className="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
            Showing {filteredAnswers.length} of{' '}
            {answers.length} answers
          </div>
        )}
      </section>

      {/* Delete Confirmation */}
      <DeleteConfirmDialog
        open={answerToDelete !== null}
        title="Delete Doctor Answer"
        itemName={answerToDelete?.answer_text || ''}
        description="Are you sure you want to permanently delete this doctor's answer?"
        warning="This action cannot be undone. Only this answer will be deleted; the patient's original question will remain available."
        loading={deletingId !== null}
        error={deleteError}
        onCancel={() => {
          if (deletingId !== null) return
          setAnswerToDelete(null)
          setDeleteError('')
        }}
        onConfirm={() => {
          if (answerToDelete) {
            handleDeleteAnswer(answerToDelete)
          }
        }}
      />

      {/* Answer Details Modal */}
      {selectedAnswer && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
              setSelectedAnswer(null)
            }
          }}
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="answer-details-title"
            className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
          >

            <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white p-5">
              <h3
                id="answer-details-title"
                className="text-xl font-bold text-slate-800"
              >
                Answer Details
              </h3>

              <button
                type="button"
                onClick={() => setSelectedAnswer(null)}
                aria-label="Close details"
                className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
              >
                <X size={22} />
              </button>
            </div>

            <div className="space-y-5 p-6">

              <div>
                <p className="text-xs font-semibold uppercase text-slate-500">
                  Patient Question
                </p>

                <p className="mt-2 whitespace-pre-wrap break-words text-base text-slate-800">
                  {selectedAnswer.question_text}
                </p>
              </div>

              <div className="rounded-xl border border-green-100 bg-green-50 p-4">
                <p className="text-xs font-semibold uppercase text-green-700">
                  Doctor's Answer
                </p>

                <p className="mt-3 whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-800">
                  {selectedAnswer.answer_text}
                </p>
              </div>

              <div className="grid gap-3 sm:grid-cols-2">
                {[
                  ['Answer ID', selectedAnswer.answer_id],
                  ['Question ID', selectedAnswer.question_id],
                  ['Doctor', selectedAnswer.doctor_name],
                  ['Category', selectedAnswer.category],
                  ['Question Status', selectedAnswer.question_status],
                  ['Answered At', selectedAnswer.answered_at],
                ].map(([label, value]) => (
                  <div
                    key={label}
                    className="rounded-lg bg-slate-50 p-4"
                  >
                    <p className="text-xs text-slate-500">
                      {label}
                    </p>

                    <p className="mt-1 break-words font-medium text-slate-800">
                      {value ?? 'Not available'}
                    </p>
                  </div>
                ))}
              </div>

            </div>

            <div className="sticky bottom-0 flex justify-end border-t border-slate-200 bg-white p-5">
              <button
                type="button"
                onClick={() => setSelectedAnswer(null)}
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

export default Answers
