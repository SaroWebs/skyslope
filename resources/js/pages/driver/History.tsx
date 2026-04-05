import React, { useState } from 'react'
import { Head, Link } from '@inertiajs/react'
import axios from '@/lib/axios'

interface Ride {
  id: number
  booking_number: string
  status: string
  pickup_location: string
  dropoff_location?: string
  scheduled_at: string
  completed_at?: string
  total_fare: number
  customer_name: string
  customer_phone?: string
  customer_email?: string
  pickup_lat?: number | null
  pickup_lng?: number | null
  dropoff_lat?: number | null
  dropoff_lng?: number | null
  payment_status?: string
  payment_method?: string
  driver_notes?: string
  reviews?: Array<{
    id: number
    rating: number
    comment?: string
    created_at: string
  }>
  tips?: Array<{
    id: number
    amount: number
    message?: string
    created_at: string
  }>
  tips_total?: number
}

interface PaginatedRides {
  data: Ride[]
}

interface Props {
  title?: string
  rides: PaginatedRides
}

export default function History({ title = 'Ride History', rides }: Props) {
  const [expandedId, setExpandedId] = useState<number | null>(null)
  const [savingNotes, setSavingNotes] = useState<number | null>(null)
  const [notesDraft, setNotesDraft] = useState<Record<number, string>>({})
  const [paymentModeDraft, setPaymentModeDraft] = useState<Record<number, string>>({})

  const saveRideNote = async (ride: Ride) => {
    const value = notesDraft[ride.id] ?? ride.driver_notes ?? ''
    setSavingNotes(ride.id)
    try {
      await axios.post(`/driver/rides/${ride.id}/notes`, {
        driver_notes: value || null,
      })
      window.location.reload()
    } catch (error) {
      alert('Failed to save note')
    } finally {
      setSavingNotes(null)
    }
  }

  const collectRidePayment = async (ride: Ride) => {
    const paymentMode = paymentModeDraft[ride.id] || ride.payment_method || 'cash'
    setSavingNotes(ride.id)
    try {
      await axios.post(`/driver/rides/${ride.id}/payment-status`, {
        payment_status: 'paid',
        payment_method: paymentMode,
      })
      window.location.reload()
    } catch (error: any) {
      alert(error?.response?.data?.message || 'Failed to collect payment')
    } finally {
      setSavingNotes(null)
    }
  }

  return (
    <div className="min-h-screen bg-gray-100">
      <Head title={title} />
      <div className="mx-auto max-w-5xl px-4 py-6">
        <div className="mb-6 flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
            <p className="text-sm text-gray-600">Completed and past rides</p>
          </div>
          <Link href="/driver" className="text-sm font-medium text-orange-600 hover:text-orange-700">
            Back to dashboard
          </Link>
        </div>

        <div className="space-y-3">
          {rides?.data?.length ? (
            rides.data.map((ride) => (
              <div key={ride.id} className="rounded-lg bg-white p-4 shadow-sm">
                <div className="mb-2 flex items-center justify-between gap-2">
                  <p className="font-semibold text-gray-900">#{ride.booking_number}</p>
                  <span className="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                    {ride.status}
                  </span>
                </div>
                <p className="text-sm text-gray-700">Customer: {ride.customer_name}</p>
                <p className="text-sm text-gray-600">Pickup: {ride.pickup_location}</p>
                <p className="text-sm text-gray-600">Drop: {ride.dropoff_location || '-'}</p>
                <div className="mt-2 flex items-center justify-between text-sm">
                  <span className="text-gray-500">{new Date(ride.scheduled_at).toLocaleString()}</span>
                  <span className="font-semibold text-green-600">Rs {Number(ride.total_fare).toFixed(2)}</span>
                </div>

                <div className="mt-3">
                  <button
                    type="button"
                    onClick={() => setExpandedId(expandedId === ride.id ? null : ride.id)}
                    className="rounded border border-orange-300 px-3 py-1 text-xs font-medium text-orange-700 hover:bg-orange-50"
                  >
                    {expandedId === ride.id ? 'Hide details' : 'View details'}
                  </button>
                </div>

                {expandedId === ride.id && (
                  <div className="mt-4 space-y-4 rounded border border-gray-100 bg-gray-50 p-4">
                    <div className="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                      <p><span className="font-semibold">Customer:</span> {ride.customer_name}</p>
                      <p><span className="font-semibold">Phone:</span> {ride.customer_phone || '-'}</p>
                      <p><span className="font-semibold">Email:</span> {ride.customer_email || '-'}</p>
                      <p><span className="font-semibold">Payment:</span> {ride.payment_status || 'pending'}</p>
                      <p><span className="font-semibold">Completed:</span> {ride.completed_at ? new Date(ride.completed_at).toLocaleString() : '-'}</p>
                      <p><span className="font-semibold">Tips Total:</span> Rs {(ride.tips_total || 0).toFixed(2)}</p>
                    </div>

                    {ride.payment_status !== 'paid' && (
                      <div className="rounded border border-emerald-200 bg-emerald-50 p-3">
                        <p className="mb-2 text-sm font-semibold text-emerald-900">Collect Payment</p>
                        <div className="flex flex-wrap gap-2">
                          <select
                            value={paymentModeDraft[ride.id] || ride.payment_method || 'cash'}
                            onChange={(e) =>
                              setPaymentModeDraft((prev) => ({ ...prev, [ride.id]: e.target.value }))
                            }
                            className="rounded border border-gray-300 px-3 py-2 text-sm"
                          >
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="wallet">Wallet</option>
                            <option value="bank_transfer">Bank Transfer</option>
                          </select>
                          <button
                            type="button"
                            onClick={() => collectRidePayment(ride)}
                            disabled={savingNotes === ride.id}
                            className="rounded bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
                          >
                            Mark Paid
                          </button>
                        </div>
                      </div>
                    )}

                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                      <a
                        href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
                          ride.pickup_lat && ride.pickup_lng ? `${ride.pickup_lat},${ride.pickup_lng}` : ride.pickup_location
                        )}`}
                        target="_blank"
                        rel="noreferrer"
                        className="rounded bg-blue-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-blue-700"
                      >
                        Pickup Map
                      </a>
                      <a
                        href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
                          ride.dropoff_lat && ride.dropoff_lng ? `${ride.dropoff_lat},${ride.dropoff_lng}` : (ride.dropoff_location || '')
                        )}`}
                        target="_blank"
                        rel="noreferrer"
                        className="rounded bg-indigo-600 px-3 py-2 text-center text-sm font-medium text-white hover:bg-indigo-700"
                      >
                        Drop-off Map
                      </a>
                    </div>

                    <div>
                      <h3 className="mb-1 text-sm font-semibold text-gray-900">Customer Reviews</h3>
                      {ride.reviews?.length ? (
                        <div className="space-y-2">
                          {ride.reviews.map((review) => (
                            <div key={review.id} className="rounded border border-gray-200 bg-white p-2 text-sm">
                              <p className="font-medium">Rating: {review.rating}/5</p>
                              <p className="text-gray-700">{review.comment || 'No comment'}</p>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-sm text-gray-500">No reviews yet.</p>
                      )}
                    </div>

                    <div>
                      <h3 className="mb-1 text-sm font-semibold text-gray-900">Tips</h3>
                      {ride.tips?.length ? (
                        <div className="space-y-2">
                          {ride.tips.map((tip) => (
                            <div key={tip.id} className="rounded border border-gray-200 bg-white p-2 text-sm">
                              <p className="font-medium text-green-700">Rs {Number(tip.amount).toFixed(2)}</p>
                              <p className="text-gray-700">{tip.message || 'No message'}</p>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-sm text-gray-500">No tips for this ride.</p>
                      )}
                    </div>

                    <div>
                      <h3 className="mb-1 text-sm font-semibold text-gray-900">Driver Notes</h3>
                      <textarea
                        rows={3}
                        value={notesDraft[ride.id] ?? ride.driver_notes ?? ''}
                        onChange={(e) => setNotesDraft((prev) => ({ ...prev, [ride.id]: e.target.value }))}
                        className="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-orange-500 focus:outline-none"
                        placeholder="Add additional notes for this ride..."
                      />
                      <button
                        type="button"
                        onClick={() => saveRideNote(ride)}
                        disabled={savingNotes === ride.id}
                        className="mt-2 rounded bg-orange-600 px-3 py-2 text-sm font-medium text-white hover:bg-orange-700 disabled:opacity-60"
                      >
                        {savingNotes === ride.id ? 'Saving...' : 'Save Notes'}
                      </button>
                    </div>
                  </div>
                )}
              </div>
            ))
          ) : (
            <div className="rounded-lg bg-white p-8 text-center text-gray-500 shadow-sm">
              No rides available.
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
