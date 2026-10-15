
import { useEffect, useMemo, useState } from 'react'
import {
  Users,
  RefreshCw,
  Search,
  HeartPulse,
  ShieldCheck,
  AlertTriangle,
  Activity,
  UserRound,
  Eye,
  X,
} from 'lucide-react'

const riskStyles = {
  low: 'bg-green-100 text-green-800',
  medium: 'bg-amber-100 text-amber-800',
  high: 'bg-red-100 text-red-800',
  critical: 'bg-red-200 text-red-950',
}

function DetailField({ label, value }) {
  const displayValue =
    value === null || value === undefined || value === ''
      ? 'Not provided'
      : String(value)

  return (
    <div className="rounded-lg bg-slate-50 p-4">
      <p className="text-xs font-medium uppercase tracking-wide text-slate-500">
        {label}
      </p>

      <p className="mt-2 whitespace-pre-wrap break-words text-sm font-medium text-slate-800">
        {displayValue}
      </p>
    </div>
  )
}

function Patients() {
  const [patients, setPatients] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [refreshKey, setRefreshKey] = useState(0)

  const [search, setSearch] = useState('')
  const [riskFilter, setRiskFilter] = useState('all')

  // Patient details modal
  const [selectedPatientId, setSelectedPatientId] = useState(null)
  const [patientDetails, setPatientDetails] = useState(null)
  const [detailsLoading, setDetailsLoading] = useState(false)
  const [detailsError, setDetailsError] = useState('')

  // Load patient list
  useEffect(() => {
    const controller = new AbortController()

    const loadPatients = async () => {
      setLoading(true)
      setError('')

      try {
        const response = await fetch(
          '/admin-api/get_all_patients.php',
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Unable to retrieve patients.'
          )
        }

        if (!controller.signal.aborted) {
          setPatients(
            Array.isArray(data.patients)
              ? data.patients
              : []
          )
        }
      } catch (err) {
        if (!controller.signal.aborted) {
          setError(err.message || 'Failed to load patients.')
          setPatients([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false)
        }
      }
    }

    loadPatients()

    return () => controller.abort()
  }, [refreshKey])

  // Load one patient's details only when requested
  useEffect(() => {
    if (selectedPatientId === null) return

    const controller = new AbortController()

    const loadDetails = async () => {
      setDetailsLoading(true)
      setDetailsError('')
      setPatientDetails(null)

      try {
        const response = await fetch(
          `/admin-api/get_patient_details.php?id=${encodeURIComponent(
            selectedPatientId
          )}`,
          {
            credentials: 'include',
            cache: 'no-store',
            signal: controller.signal,
          }
        )

        const data = await response.json()

        if (!response.ok || !data.success) {
          throw new Error(
            data.message || 'Unable to retrieve patient details.'
          )
        }

        if (!controller.signal.aborted) {
          setPatientDetails(data.patient)
        }
      } catch (err) {
        if (!controller.signal.aborted) {
          setDetailsError(
            err.message || 'Failed to load patient details.'
          )
        }
      } finally {
        if (!controller.signal.aborted) {
          setDetailsLoading(false)
        }
      }
    }

    loadDetails()

    return () => controller.abort()
  }, [selectedPatientId])

  const closeDetails = () => {
    setSelectedPatientId(null)
    setPatientDetails(null)
    setDetailsError('')
  }

  // Statistics
  const stats = useMemo(() => {
    const countRisk = (risk) =>
      patients.filter(
        (p) =>
          String(p.risk_level || '').toLowerCase() === risk
      ).length

    return {
      total: patients.length,
      low: countRisk('low'),
      medium: countRisk('medium'),
      high: countRisk('high'),
      critical: countRisk('critical'),
    }
  }, [patients])

  // Search and filter
  const filteredPatients = useMemo(() => {
    const term = search.trim().toLowerCase()

    return patients.filter((patient) => {
      const searchableText = [
        patient.id,
        patient.name,
        patient.surname,
        patient.email,
        patient.phone,
        patient.doctor_name,
        patient.doctor_surname,
      ]
        .filter((value) => value != null)
        .join(' ')
        .toLowerCase()

      const matchesSearch = searchableText.includes(term)

      const matchesRisk =
        riskFilter === 'all' ||
        String(patient.risk_level || '').toLowerCase() ===
          riskFilter

      return matchesSearch && matchesRisk
    })
  }, [patients, search, riskFilter])

  const statisticCards = [
    {
      title: 'Total Patients',
      value: stats.total,
      icon: Users,
      color: 'text-green-700',
    },
    {
      title: 'Low Risk',
      value: stats.low,
      icon: ShieldCheck,
      color: 'text-green-600',
    },
    {
      title: 'Medium Risk',
      value: stats.medium,
      icon: Activity,
      color: 'text-amber-600',
    },
    {
      title: 'High Risk',
      value: stats.high,
      icon: AlertTriangle,
      color: 'text-red-600',
    },
    {
      title: 'Critical Risk',
      value: stats.critical,
      icon: HeartPulse,
      color: 'text-red-800',
    },
  ]

  return (
    <div className="space-y-6">

      {/* Heading */}
      <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h2 className="text-2xl font-bold text-slate-800">
            Patient Management
          </h2>

          <p className="mt-1 text-sm text-slate-500">
            View registered patients and their assigned doctors.
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
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
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

      {/* Patient Table */}
      <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div className="border-b border-slate-200 p-5">
          <h3 className="text-lg font-semibold text-slate-800">
            Registered Patients
          </h3>

          <p className="mt-1 text-sm text-slate-500">
            Search and filter patient records.
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
                placeholder="Search patients..."
                className="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-green-600"
              />
            </div>

            <select
              value={riskFilter}
              onChange={(event) =>
                setRiskFilter(event.target.value)
              }
              className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm"
            >
              <option value="all">All Risk Levels</option>
              <option value="low">Low Risk</option>
              <option value="medium">Medium Risk</option>
              <option value="high">High Risk</option>
              <option value="critical">Critical Risk</option>
            </select>
          </div>
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
          <table className="w-full min-w-[950px] text-left text-sm">
            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-5 py-4">Patient</th>
                <th className="px-5 py-4">Age / Gender</th>
                <th className="px-5 py-4">Contact</th>
                <th className="px-5 py-4">Assigned Doctor</th>
                <th className="px-5 py-4">Risk Level</th>
                <th className="px-5 py-4">Registered</th>
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
                    Loading patients...
                  </td>
                </tr>
              )}

              {!loading &&
                !error &&
                filteredPatients.map((patient) => {
                  const risk = String(
                    patient.risk_level || ''
                  ).toLowerCase()

                  const doctorName = [
                    patient.doctor_name,
                    patient.doctor_surname,
                  ]
                    .filter(Boolean)
                    .join(' ')

                  return (
                    <tr
                      key={patient.id}
                      className="hover:bg-slate-50"
                    >
                      <td className="px-5 py-4">
                        <div className="flex items-center gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-green-50">
                            <UserRound
                              size={20}
                              className="text-green-700"
                            />
                          </div>

                          <div>
                            <p className="font-semibold text-slate-800">
                              {patient.name} {patient.surname}
                            </p>

                            <p className="mt-1 text-xs text-slate-400">
                              ID: {patient.id}
                            </p>
                          </div>
                        </div>
                      </td>

                      <td className="px-5 py-4">
                        <p className="text-slate-700">
                          {patient.age != null
                            ? `${patient.age} years`
                            : 'Not provided'}
                        </p>

                        <p className="mt-1 text-xs text-slate-500">
                          {patient.gender || 'Not provided'}
                        </p>
                      </td>

                      <td className="px-5 py-4">
                        <p className="text-slate-700">
                          {patient.email || 'Not provided'}
                        </p>

                        <p className="mt-1 text-xs text-slate-500">
                          {patient.phone || 'Not provided'}
                        </p>
                      </td>

                      <td className="px-5 py-4">
                        {patient.doc_id != null ? (
                          <div>
                            <p className="font-medium text-slate-700">
                              {doctorName || 'Unknown Doctor'}
                            </p>

                            <p className="mt-1 text-xs text-slate-400">
                              Doctor ID: {patient.doc_id}
                            </p>
                          </div>
                        ) : (
                          <span className="text-slate-400">
                            Not Assigned
                          </span>
                        )}
                      </td>

                      <td className="px-5 py-4">
                        <span
                          className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                            riskStyles[risk] ||
                            'bg-slate-100 text-slate-700'
                          }`}
                        >
                          {risk || 'Unknown'}
                        </span>
                      </td>

                      <td className="px-5 py-4 text-slate-600">
                        {patient.created_at || 'Not available'}
                      </td>

                      <td className="px-5 py-4">
                        <button
                          type="button"
                          onClick={() =>
                            setSelectedPatientId(patient.id)
                          }
                          className="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                          <Eye size={15} />
                          View Details
                        </button>
                      </td>
                    </tr>
                  )
                })}

              {!loading &&
                !error &&
                filteredPatients.length === 0 && (
                  <tr>
                    <td
                      colSpan={7}
                      className="px-5 py-12 text-center text-slate-500"
                    >
                      No patients found.
                    </td>
                  </tr>
                )}
            </tbody>
          </table>
        </div>

        {!loading && !error && (
          <div className="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
            Showing {filteredPatients.length} of{' '}
            {patients.length} patients
          </div>
        )}
      </section>

      {/* Patient Details Modal */}
      {selectedPatientId !== null && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) {
              closeDetails()
            }
          }}
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="patient-details-title"
            className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
          >
            {/* Modal Header */}
            <div className="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white p-5">
              <div>
                <h3
                  id="patient-details-title"
                  className="text-xl font-bold text-slate-800"
                >
                  Patient Details
                </h3>

                <p className="mt-1 text-sm text-slate-500">
                  Patient ID: {selectedPatientId}
                </p>
              </div>

              <button
                type="button"
                onClick={closeDetails}
                aria-label="Close patient details"
                className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"
              >
                <X size={22} />
              </button>
            </div>

            {/* Loading */}
            {detailsLoading && (
              <div className="p-10 text-center text-slate-500">
                Loading patient details...
              </div>
            )}

            {/* Error */}
            {!detailsLoading && detailsError && (
              <div
                role="alert"
                className="m-6 rounded-lg bg-red-50 p-4 text-sm text-red-700"
              >
                {detailsError}
              </div>
            )}

            {/* Patient Information */}
            {!detailsLoading &&
              !detailsError &&
              patientDetails && (
                <div className="space-y-7 p-6">

                  {/* Personal */}
                  <section>
                    <h4 className="mb-3 font-semibold text-slate-800">
                      Personal Information
                    </h4>

                    <div className="grid gap-3 sm:grid-cols-2">
                      <DetailField
                        label="First Name"
                        value={patientDetails.name}
                      />
                      <DetailField
                        label="Surname"
                        value={patientDetails.surname}
                      />
                      <DetailField
                        label="Age"
                        value={patientDetails.age}
                      />
                      <DetailField
                        label="Gender"
                        value={patientDetails.gender}
                      />
                      <DetailField
                        label="Email"
                        value={patientDetails.email}
                      />
                      <DetailField
                        label="Phone"
                        value={patientDetails.phone}
                      />
                    </div>
                  </section>

                  {/* Address */}
                  <section>
                    <h4 className="mb-3 font-semibold text-slate-800">
                      Address
                    </h4>

                    <DetailField
                      label="Home Address"
                      value={patientDetails.home_address}
                    />
                  </section>

                  {/* Emergency Contact */}
                  <section>
                    <h4 className="mb-3 font-semibold text-slate-800">
                      Emergency Contact
                    </h4>

                    <div className="grid gap-3 sm:grid-cols-2">
                      <DetailField
                        label="Contact Name"
                        value={
                          patientDetails.emergency_contact_name
                        }
                      />
                      <DetailField
                        label="Contact Number"
                        value={
                          patientDetails.emergency_contact_number
                        }
                      />
                      <DetailField
                        label="Relationship"
                        value={
                          patientDetails.emergency_contact_relationship
                        }
                      />
                    </div>
                  </section>

                  {/* Medical Information */}
                  <section>
                    <h4 className="mb-3 font-semibold text-slate-800">
                      Medical Information
                    </h4>

                    <div className="space-y-3">
                      <DetailField
                        label="Allergies"
                        value={patientDetails.allergies}
                      />

                      <DetailField
                        label="Medical Conditions"
                        value={patientDetails.medical_conditions}
                      />

                      <div className="rounded-lg border border-slate-200 p-4">
                        <p className="mb-2 text-xs font-medium uppercase text-slate-500">
                          Recorded Risk Level
                        </p>

                        <span
                          className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize ${
                            riskStyles[
                              String(
                                patientDetails.risk_level || ''
                              ).toLowerCase()
                            ] || 'bg-slate-100 text-slate-700'
                          }`}
                        >
                          {patientDetails.risk_level || 'Unknown'}
                        </span>
                      </div>
                    </div>
                  </section>

                  {/* Doctor */}
                  <section>
                    <h4 className="mb-3 font-semibold text-slate-800">
                      Assigned Doctor
                    </h4>

                    {patientDetails.doc_id != null ? (
                      <div className="grid gap-3 sm:grid-cols-2">
                        <DetailField
                          label="Doctor Name"
                          value={[
                            patientDetails.doctor_name,
                            patientDetails.doctor_surname,
                          ].filter(Boolean).join(' ')}
                        />

                        <DetailField
                          label="Doctor ID"
                          value={patientDetails.doc_id}
                        />

                        <DetailField
                          label="Specialization"
                          value={
                            patientDetails.doctor_specialization
                          }
                        />

                        <DetailField
                          label="Clinic"
                          value={patientDetails.doctor_clinic}
                        />
                      </div>
                    ) : (
                      <p className="rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                        No doctor is currently assigned to this patient.
                      </p>
                    )}
                  </section>

                  {/* Account */}
                  <section>
                    <h4 className="mb-3 font-semibold text-slate-800">
                      Account Information
                    </h4>

                    <div className="grid gap-3 sm:grid-cols-2">
                      <DetailField
                        label="Patient ID"
                        value={patientDetails.id}
                      />

                      <DetailField
                        label="Registration Date"
                        value={patientDetails.created_at}
                      />
                    </div>
                  </section>

                </div>
              )}

            {/* Modal Footer */}
            <div className="sticky bottom-0 flex justify-end border-t border-slate-200 bg-white p-5">
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
    </div>
  )
}

export default Patients
