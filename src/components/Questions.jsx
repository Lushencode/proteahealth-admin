
import { useEffect, useMemo, useState } from 'react'
import DeleteConfirmDialog from './DeleteConfirmDialog'
import {
  MessageCircle,
  RefreshCw,
  Search,
  CheckCircle,
  Clock,
  Eye,
  Trash2,
  X,
  UserRound,
} from 'lucide-react'

function Questions() {
const [questionToDelete, setQuestionToDelete] = useState(null)
  const [questions, setQuestions] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')

  const [selectedQuestion, setSelectedQuestion] = useState(null)
  const [deletingId, setDeletingId] = useState(null)
  const [deleteError, setDeleteError] = useState('')

  // Load questions from MySQL
  useEffect(() => {
    const controller = new AbortController()

    const loadQuestions = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_all_questions.php',
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Unable to retrieve questions.'
          )
        }

        if (!controller.signal.aborted) {
          setQuestions(
            Array.isArray(data.questions) ? data.questions : []
          )
        }
      } catch (err) {
        if (!controller.signal.aborted) {
          setError(err.message || 'Failed to load questions.')
          setQuestions([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadQuestions()

    return () => controller.abort()
  }, [refreshKey])

  // Delete a question
  const handleDeleteQuestion = async (question) => {
    setQuestionToDelete(null)

    try {
      const formData = new FormData()
      formData.append('question_id', question.question_id)

      const response = await fetch(
        '/admin-api/admin_delete_question.php',
        {
          method: 'POST',
          credentials: 'include',
          body: formData,
        }
      )

      const data = await response.json()

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || 'Unable to delete question.'
        )
      }

      setQuestions((previous) =>
        previous.filter(
          (item) =>
            String(item.question_id) !==
            String(question.question_id)
        )
      )

      setSelectedQuestion((previous) =>
        String(previous?.question_id) ===
        String(question.question_id)
          ? null
          : previous
      )

    } catch (err) {
      setDeleteError(
        err.message || 'Failed to delete question.'
      )
    } finally {
      setDeletingId(null)
    }
  }

  // Statistics
  const stats = useMemo(() => ({
    total: questions.length,
    answered: questions.filter(
      (q) => Number(q.answer_count) > 0
    ).length,
    unanswered: questions.filter(
      (q) => Number(q.answer_count) === 0
    ).length,
    anonymous: questions.filter(
      (q) => Number(q.is_anonymous) === 1
    ).length,
  }), [questions])

  // Search and filter
  const filteredQuestions = useMemo(() => {
    const term = search.trim().toLowerCase()

    return questions.filter((question) => {
      const searchable = [
        question.question_id,
        question.question,
        question.category,
        question.userName,
      ]
        .filter((value) => value != null)
        .join(' ')
        .toLowerCase()

      const matchesSearch = searchable.includes(term)

      const answerCount = Number(question.answer_count)

      const matchesStatus =
        statusFilter === 'all' ||
        (statusFilter === 'answered' && answerCount > 0) ||
        (statusFilter === 'unanswered' && answerCount === 0)

      return matchesSearch && matchesStatus
    })
  }, [questions, search, statusFilter])

  const statisticCards = [
    {
      title: 'Total Questions',
      value: stats.total,
      icon: MessageCircle,
      color: 'text-green-700',
    },
    {
      title: 'Answered',
      value: stats.answered,
      icon: CheckCircle,
      color: 'text-green-600',
    },
    {
      title: 'Unanswered',
      value: stats.unanswered,
      icon: Clock,
      color: 'text-amber-600',
    },
    {
      title: 'Anonymous',
      value: stats.anonymous,
      icon: UserRound,
      color: 'text-slate-600',
    },
  ]

  return (
    <div className="space-y-6">

      {/* Heading */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Questions Management
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            Monitor and moderate patient questions
            submitted through the Health Hub.
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

      {/* Questions Table */}
      <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div className="border-b border-slate-200 p-5">
          <h3 className="text-lg font-semibold text-slate-800">
            Patient Questions
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Search and manage questions from the community.
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
                placeholder="Search questions..."
                className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-600"
              />
            </div>

            <select
              value={statusFilter}
              onChange={(event) => setStatusFilter(event.target.value)}
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"
            >
              <option value="all">All Questions</option>
              <option value="answered">Answered</option>
              <option value="unanswered">Unanswered</option>
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

        {deleteError && (
          <div
            role="alert"
            className="m-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"
          >
            {deleteError}
          </div>
        )}

        <div className="overflow-x-auto">
          <table className="w-full min-w-[950px] text-left text-sm">

            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-5 py-4">Question</th>
                <th className="px-5 py-4">Patient</th>
                <th className="px-5 py-4">Category</th>
                <th className="px-5 py-4">Answers</th>
                <th className="px-5 py-4">Submitted</th>
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
                    Loading questions...
                  </td>
                </tr>
              )}

              {!loading && !error &&
                filteredQuestions.map((question) => {
                  const answered =
                    Number(question.answer_count) > 0

                  return (
                    <tr
                      key={question.question_id}
                      className="hover:bg-slate-50"
                    >

                      <td className="px-5 py-4">
                        <p className="max-w-sm break-words font-semibold text-slate-800">
                          {question.question}
                        </p>

                        <p className="mt-1 text-xs text-slate-400">
                          ID: {question.question_id}
                        </p>
                      </td>

                      <td className="px-5 py-4 text-slate-700">
                        {question.userName}
                      </td>

                      <td className="px-5 py-4 text-slate-600">
                        {question.category}
                      </td>

                      <td className="px-5 py-4">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-semibold ${
                            answered
                              ? 'bg-green-100 text-green-800'
                              : 'bg-amber-100 text-amber-800'
                          }`}
                        >
                          {answered
                            ? `${question.answer_count} Answer(s)`
                            : 'Unanswered'}
                        </span>
                      </td>

                      <td className="px-5 py-4 text-slate-600">
                        {question.created_at || 'Not available'}
                      </td>

                      <td className="px-5 py-4">
                        <div className="flex items-center gap-2">

                          <button
                            type="button"
                            onClick={() => setSelectedQuestion(question)}
                            className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                          >
                            <Eye size={15} />
                            View
                          </button>

                          <button
                            type="button"
                            onClick={() => {
  setDeleteError('')
  setQuestionToDelete(question)
}}
                            disabled={deletingId !== null}
                            className="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50"
                          >
                            <Trash2 size={15} />
                            {deletingId === question.question_id
                              ? 'Deleting...'
                              : 'Delete'}
                          </button>

                        </div>
                      </td>
                    </tr>
                  )
                })}

              {!loading && !error &&
                filteredQuestions.length === 0 && (
                  <tr>
                    <td
                      colSpan={6}
                      className="px-5 py-12 text-center text-slate-500"
                    >
                      No questions found.
                    </td>
                  </tr>
                )}
            </tbody>
          </table>
        </div>

        {!loading && !error && (
          <div className="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
            Showing {filteredQuestions.length} of{' '}
            {questions.length} questions
          </div>
        )}
      </section>

        {/* Delete Question Dialog */}
<DeleteConfirmDialog
  open={questionToDelete !== null}
  title="Delete Question"
  itemName={questionToDelete?.question || ''}
  description="Are you sure you want to permanently delete this patient question?"
  warning="This action cannot be undone. All doctors' answers associated with this question will also be permanently deleted."
  loading={deletingId !== null}
  error={deleteError}
  onCancel={() => {
    setQuestionToDelete(null)
    setDeleteError('')
  }}
  onConfirm={() => {
    if (questionToDelete) {
      handleDeleteQuestion(questionToDelete)
    }
  }}
/>
      {/* Question Details Modal */}



      {selectedQuestion && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
              setSelectedQuestion(null)
            }
          }}
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="question-details-title"
            className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
          >

            <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white p-5">
              <h3
                id="question-details-title"
                className="text-xl font-bold text-slate-800"
              >
                Question Details
              </h3>

              <button
                type="button"
                onClick={() => setSelectedQuestion(null)}
                aria-label="Close details"
                className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
              >
                <X size={22} />
              </button>
            </div>

            <div className="space-y-5 p-6">

              <div>
                <p className="text-xs font-medium uppercase text-slate-500">
                  Patient Question
                </p>

                <p className="mt-2 whitespace-pre-wrap break-words text-lg font-medium text-slate-800">
                  {selectedQuestion.question}
                </p>
              </div>

              <div className="grid gap-3 sm:grid-cols-2">

                {[
                  ['Question ID', selectedQuestion.question_id],
                  ['Patient', selectedQuestion.userName],
                  ['Category', selectedQuestion.category],
                  ['Answers', selectedQuestion.answer_count],
                  ['Database Status', selectedQuestion.status],
                  ['Submitted', selectedQuestion.created_at],
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
                onClick={() => setSelectedQuestion(null)}
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

export default Questions
