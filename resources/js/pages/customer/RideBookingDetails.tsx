import React, { useState } from 'react'
import { Head, Link } from '@inertiajs/react'
import { router } from '@inertiajs/react'
import RideTracker from '@/components/ride_booking/RideTracker'

interface RideBooking {
  id: number
  booking_number: string
  status: string
  pickup_location: string
  dropoff_location?: string
  pickup_lat: number
  pickup_lng: number
  dropoff_lat?: number
  dropoff_lng?: number
  scheduled_at: string
  total_fare: number
  distance_km?: number
  estimated_duration?: number
  customer_name: string
  customer_email: string
  customer_phone: string
  service_type: string
  payment_method: string
  payment_status: string
  special_requests?: string
  created_at: string
  driver?: {
    id: number
    name: string
    phone: string
    rating?: number
    vehicle_number?: string
    vehicle_type?: string
  }
  current_lat?: number
  current_lng?: number
  last_location_update?: string
  start_ride_pin?: string
  start_pin_verified_at?: string
  my_review?: {
    id: number
    rating: number
    comment?: string
  } | null
  my_tips_total?: number
}

interface RideBookingDetailsProps {
  booking?: RideBooking | null
  user: any
}

const RideBookingDetails: React.FC<RideBookingDetailsProps> = ({ booking, user }) => {
  const [cancelling, setCancelling] = useState(false)
  const [showTracker, setShowTracker] = useState(false)
  const [reviewRating, setReviewRating] = useState<number>(booking?.my_review?.rating || 5)
  const [reviewComment, setReviewComment] = useState<string>(booking?.my_review?.comment || '')
  const [tipAmount, setTipAmount] = useState<string>('')
  const [tipMessage, setTipMessage] = useState<string>('')
  const [submittingReview, setSubmittingReview] = useState(false)
  const [submittingTip, setSubmittingTip] = useState(false)

  if (!booking) {
    return (
      <>
        <Head title="Ride Booking Details" />
        <div className="min-h-screen bg-gray-50">
          <div className="mx-auto max-w-3xl px-4 py-12">
            <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
              <h1 className="text-xl font-semibold text-gray-900">Booking not found</h1>
              <p className="mt-2 text-sm text-gray-600">Unable to load this booking right now.</p>
              <Link
                href="/dashboard"
                className="mt-4 inline-block rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
              >
                Back to Dashboard
              </Link>
            </div>
          </div>
        </div>
      </>
    )
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200'
      case 'confirmed':
        return 'bg-blue-100 text-blue-800 border-blue-200'
      case 'driver_assigned':
        return 'bg-indigo-100 text-indigo-800 border-indigo-200'
      case 'driver_arriving':
        return 'bg-purple-100 text-purple-800 border-purple-200'
      case 'pickup':
        return 'bg-cyan-100 text-cyan-800 border-cyan-200'
      case 'in_transit':
        return 'bg-green-100 text-green-800 border-green-200'
      case 'completed':
        return 'bg-emerald-100 text-emerald-800 border-emerald-200'
      case 'cancelled':
        return 'bg-red-100 text-red-800 border-red-200'
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200'
    }
  }

  const getPaymentStatusColor = (status: string) => {
    switch (status) {
      case 'pending':
        return 'bg-orange-100 text-orange-800 border-orange-200'
      case 'paid':
        return 'bg-green-100 text-green-800 border-green-200'
      case 'failed':
        return 'bg-red-100 text-red-800 border-red-200'
      default:
        return 'bg-gray-100 text-gray-800 border-gray-200'
    }
  }

  const handleCancelBooking = async () => {
    if (!confirm('Are you sure you want to cancel this ride booking?')) {
      return
    }

    setCancelling(true)
    try {
      const response = await fetch(`/ride-bookings/${booking.id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          status: 'cancelled'
        })
      })

      if (response.ok) {
        router.visit('/dashboard', {
          onSuccess: () => {
            // Show success message
            window.location.reload()
          }
        })
      } else {
        alert('Failed to cancel booking. Please try again.')
      }
    } catch (error) {
      console.error('Error cancelling booking:', error)
      alert('An error occurred. Please try again.')
    } finally {
      setCancelling(false)
    }
  }

  const status = booking.status || 'pending'
  const serviceType = booking.service_type || 'point_to_point'
  const paymentMethod = booking.payment_method || 'cash'
  const paymentStatus = booking.payment_status || 'pending'
  const canCancelBooking = () => {
    return ['pending', 'confirmed', 'driver_assigned', 'driver_arriving'].includes(status)
  }
  const canTrackBooking = !['completed', 'cancelled'].includes(status)
  const shouldShowStartPin =
    Boolean(booking.start_ride_pin) &&
    ['driver_assigned', 'driver_arriving', 'pickup'].includes(status) &&
    !booking.start_pin_verified_at
  const canReviewOrTip = status === 'completed'

  const formatCurrency = (amount: number) => `₹${Number(amount).toFixed(2)}`

  const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-IN', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  const handleSubmitReview = async () => {
    setSubmittingReview(true)
    try {
      const response = await fetch(`/api/ride-bookings/${booking.id}/review`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          rating: reviewRating,
          comment: reviewComment || null,
        }),
      })
      const data = await response.json().catch(() => ({}))
      if (!response.ok) {
        alert(data?.message || 'Failed to submit review.')
        return
      }
      alert('Review submitted successfully.')
      router.reload()
    } catch (error) {
      alert('Failed to submit review.')
    } finally {
      setSubmittingReview(false)
    }
  }

  const handleSendTip = async () => {
    const amount = Number(tipAmount)
    if (!Number.isFinite(amount) || amount < 10) {
      alert('Minimum tip amount is 10.')
      return
    }

    setSubmittingTip(true)
    try {
      const response = await fetch(`/api/ride-bookings/${booking.id}/tip`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          amount,
          message: tipMessage || null,
        }),
      })
      const data = await response.json().catch(() => ({}))
      if (!response.ok) {
        alert(data?.message || 'Failed to send tip.')
        return
      }
      alert('Tip sent successfully.')
      setTipAmount('')
      setTipMessage('')
      router.reload()
    } catch (error) {
      alert('Failed to send tip.')
    } finally {
      setSubmittingTip(false)
    }
  }

  return (
    <>
      <Head title={`Ride Booking Details - ${booking.booking_number || 'N/A'}`} />

      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <div className="bg-white shadow-sm border-b">
          <div className="max-w-4xl mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <Link
                  href="/dashboard"
                  className="text-blue-600 hover:text-blue-700 flex items-center space-x-1"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                  </svg>
                  <span>Back to Dashboard</span>
                </Link>
                <div className="h-6 w-px bg-gray-300"></div>
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">Ride Booking Details</h1>
                  <p className="text-sm text-gray-600">Booking #{booking.booking_number}</p>
                </div>
              </div>
              
              <div className="flex items-center space-x-3">
                {canTrackBooking && (
                  <button
                    onClick={() => setShowTracker((prev) => !prev)}
                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                  >
                    {showTracker ? 'Hide Tracking' : 'Track Booking'}
                  </button>
                )}
                <span className={`px-3 py-1 rounded-full text-sm font-medium border ${getStatusColor(status)}`}>
                  {status.replace('_', ' ').toUpperCase()}
                </span>
                {canCancelBooking() && (
                  <button
                    onClick={handleCancelBooking}
                    disabled={cancelling}
                    className="bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                  >
                    {cancelling ? 'Cancelling...' : 'Cancel Booking'}
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="max-w-4xl mx-auto px-4 py-8">
          {showTracker && (
            <div className="mb-6">
              <RideTracker
                booking={booking}
                onBookingCancelled={() => {
                  setShowTracker(false)
                  router.reload()
                }}
                onStatusChange={() => {
                  router.reload()
                }}
              />
            </div>
          )}

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Main Details */}
            <div className="lg:col-span-2 space-y-6">
              {/* Route Information */}
              <div className="bg-white rounded-xl shadow-lg p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Route Information</h2>
                
                <div className="space-y-4">
                  <div className="flex items-start space-x-4">
                    <div className="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                      <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                    </div>
                    <div className="flex-1">
                      <h3 className="font-medium text-gray-900">Pickup Location</h3>
                      <p className="text-gray-600">{booking.pickup_location}</p>
                    </div>
                  </div>

                  {booking.dropoff_location && (
                    <div className="flex items-start space-x-4">
                      <div className="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <svg className="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                      </div>
                      <div className="flex-1">
                        <h3 className="font-medium text-gray-900">Dropoff Location</h3>
                        <p className="text-gray-600">{booking.dropoff_location}</p>
                      </div>
                    </div>
                  )}
                </div>

                {booking.distance_km !== undefined && booking.distance_km !== null && (
                  <div className="mt-4 pt-4 border-t border-gray-200">
                    <div className="flex items-center justify-between text-sm text-gray-600">
                      <span>Distance: {Number(booking.distance_km || 0).toFixed(1)} km</span>
                      {booking.estimated_duration !== undefined && booking.estimated_duration !== null && (
                        <span>Estimated time: ~{Math.ceil(Number(booking.estimated_duration || 0) / 60)} hour(s)</span>
                      )}
                    </div>
                  </div>
                )}
              </div>

              {/* Customer Information */}
              <div className="bg-white rounded-xl shadow-lg p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Customer Information</h2>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Name</label>
                    <p className="mt-1 text-sm text-gray-900">{booking.customer_name}</p>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Phone</label>
                    <p className="mt-1 text-sm text-gray-900">{booking.customer_phone}</p>
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-sm font-medium text-gray-700">Email</label>
                    <p className="mt-1 text-sm text-gray-900">{booking.customer_email}</p>
                  </div>
                </div>

                {booking.special_requests && (
                  <div className="mt-4">
                    <label className="block text-sm font-medium text-gray-700">Special Requests</label>
                    <p className="mt-1 text-sm text-gray-900">{booking.special_requests}</p>
                  </div>
                )}
              </div>

              {/* Driver Information (if assigned) */}
              {booking.driver && (
                <div className="bg-white rounded-xl shadow-lg p-6">
                  <h2 className="text-xl font-bold text-gray-900 mb-4">Driver Information</h2>
                  
                  <div className="flex items-center space-x-4">
                    <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                      <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                    <div className="flex-1">
                      <h3 className="font-medium text-gray-900">{booking.driver.name}</h3>
                      <p className="text-sm text-gray-600">{booking.driver.phone}</p>
                      {booking.driver.vehicle_number && (
                        <p className="text-sm text-gray-600">Vehicle: {booking.driver.vehicle_number}</p>
                      )}
                      {booking.driver.rating !== undefined && booking.driver.rating !== null && (
                        <div className="flex items-center mt-1">
                          <svg className="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                          <span className="text-sm text-gray-600">{Number(booking.driver.rating).toFixed(1)}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              )}

              {shouldShowStartPin && (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-lg">
                  <h2 className="mb-2 text-xl font-bold text-amber-900">Ride Start PIN</h2>
                  <p className="mb-3 text-sm text-amber-800">
                    Share this 4-digit PIN with your driver at pickup to start the ride.
                  </p>
                  <div className="inline-flex rounded-lg bg-white px-4 py-2 text-2xl font-bold tracking-[0.35em] text-amber-900">
                    {booking.start_ride_pin}
                  </div>
                </div>
              )}

              {canReviewOrTip && (
                <div className="rounded-xl border border-green-200 bg-green-50 p-6 shadow-lg">
                  <h2 className="mb-4 text-xl font-bold text-green-900">Review & Tip</h2>

                  <div className="mb-4 rounded-lg bg-white p-4">
                    <h3 className="mb-2 text-sm font-semibold text-gray-900">Rate your ride</h3>
                    <div className="mb-2">
                      <select
                        value={reviewRating}
                        onChange={(e) => setReviewRating(Number(e.target.value))}
                        className="rounded border border-gray-300 px-3 py-2 text-sm"
                      >
                        <option value={5}>5 - Excellent</option>
                        <option value={4}>4 - Good</option>
                        <option value={3}>3 - Average</option>
                        <option value={2}>2 - Poor</option>
                        <option value={1}>1 - Bad</option>
                      </select>
                    </div>
                    <textarea
                      rows={3}
                      value={reviewComment}
                      onChange={(e) => setReviewComment(e.target.value)}
                      placeholder="Write your feedback..."
                      className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                    />
                    <button
                      type="button"
                      onClick={handleSubmitReview}
                      disabled={submittingReview}
                      className="mt-2 rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-60"
                    >
                      {submittingReview ? 'Submitting...' : booking.my_review ? 'Update Review' : 'Submit Review'}
                    </button>
                  </div>

                  <div className="rounded-lg bg-white p-4">
                    <h3 className="mb-2 text-sm font-semibold text-gray-900">
                      Give a tip to driver (Wallet)
                    </h3>
                    <p className="mb-2 text-xs text-gray-600">
                      Your total tips on this ride: Rs {Number(booking.my_tips_total || 0).toFixed(2)}
                    </p>
                    <input
                      type="number"
                      min={10}
                      value={tipAmount}
                      onChange={(e) => setTipAmount(e.target.value)}
                      placeholder="Tip amount"
                      className="mb-2 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                    />
                    <textarea
                      rows={2}
                      value={tipMessage}
                      onChange={(e) => setTipMessage(e.target.value)}
                      placeholder="Optional message for driver..."
                      className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                    />
                    <button
                      type="button"
                      onClick={handleSendTip}
                      disabled={submittingTip}
                      className="mt-2 rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
                    >
                      {submittingTip ? 'Sending...' : 'Send Tip'}
                    </button>
                  </div>
                </div>
              )}
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Booking Summary */}
              <div className="bg-white rounded-xl shadow-lg p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Booking Summary</h2>
                
                <div className="space-y-3">
                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">Service Type</span>
                    <span className="text-sm font-medium text-gray-900">
                      {serviceType.replace('_', ' ').toUpperCase()}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">Scheduled Date</span>
                    <span className="text-sm font-medium text-gray-900">
                      {formatDateTime(booking.scheduled_at)}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">Payment Method</span>
                    <span className="text-sm font-medium text-gray-900">
                      {paymentMethod.toUpperCase()}
                    </span>
                  </div>
                  <div className="border-t pt-3">
                    <div className="flex justify-between">
                      <span className="text-lg font-semibold text-gray-900">Total Fare</span>
                      <span className="text-lg font-bold text-gray-900">
                        {formatCurrency(booking.total_fare)}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="mt-4 pt-4 border-t border-gray-200">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-gray-700">Payment Status</span>
                    <span className={`px-2 py-1 rounded-full text-xs font-medium border ${getPaymentStatusColor(paymentStatus)}`}>
                      {paymentStatus.toUpperCase()}
                    </span>
                  </div>
                </div>
              </div>

              {/* Quick Actions */}
              <div className="bg-white rounded-xl shadow-lg p-6">
                <h2 className="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
                
                <div className="space-y-3">
                  <Link
                    href="/ride-booking"
                    className="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors text-center block"
                  >
                    Book Another Ride
                  </Link>
                  {canTrackBooking && (
                    <button
                      onClick={() => setShowTracker((prev) => !prev)}
                      className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                    >
                      {showTracker ? 'Hide Tracking' : 'Track Booking'}
                    </button>
                  )}
                  <button
                    onClick={() => window.print()}
                    className="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                  >
                    Print Details
                  </button>
                  <button
                    onClick={() => {
                      const text = `Ride Booking Details\nBooking: ${booking.booking_number}\nFrom: ${booking.pickup_location}\nTo: ${booking.dropoff_location}\nDate: ${formatDateTime(booking.scheduled_at)}`
                      navigator.clipboard.writeText(text)
                      alert('Details copied to clipboard!')
                    }}
                    className="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                  >
                    Copy Details
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  )
}

export default RideBookingDetails
