import React, { useState, useEffect } from 'react'
import { Head, usePage, Link } from '@inertiajs/react'
import { router } from '@inertiajs/react'

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
}

interface RideBookingDetailsProps {
  booking: RideBooking
  user: any
}

const RideBookingDetails: React.FC<RideBookingDetailsProps> = ({ booking, user }) => {
  const [loading, setLoading] = useState(false)
  const [cancelling, setCancelling] = useState(false)

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
      const response = await fetch(`/api/ride-bookings/${booking.id}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          status: 'cancelled'
        })
      })

      if (response.ok) {
        router.visit('/customer/dashboard', {
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

  const canCancelBooking = () => {
    return ['pending', 'confirmed', 'driver_assigned'].includes(booking.status)
  }

  const formatCurrency = (amount: number) => `₹${amount.toFixed(2)}`

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

  return (
    <>
      <Head>
        <title>Ride Booking Details - {booking.booking_number}</title>
      </Head>

      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <div className="bg-white shadow-sm border-b">
          <div className="max-w-4xl mx-auto px-4 py-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <Link
                  href="/customer/dashboard"
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
                <span className={`px-3 py-1 rounded-full text-sm font-medium border ${getStatusColor(booking.status)}`}>
                  {booking.status.replace('_', ' ').toUpperCase()}
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

                {booking.distance_km && (
                  <div className="mt-4 pt-4 border-t border-gray-200">
                    <div className="flex items-center justify-between text-sm text-gray-600">
                      <span>Distance: {booking.distance_km.toFixed(1)} km</span>
                      {booking.estimated_duration && (
                        <span>Estimated time: ~{Math.ceil(booking.estimated_duration / 60)} min</span>
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
                      {booking.driver.rating && (
                        <div className="flex items-center mt-1">
                          <svg className="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                          <span className="text-sm text-gray-600">{booking.driver.rating.toFixed(1)}</span>
                        </div>
                      )}
                    </div>
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
                      {booking.service_type.replace('_', ' ').toUpperCase()}
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
                      {booking.payment_method.toUpperCase()}
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
                    <span className={`px-2 py-1 rounded-full text-xs font-medium border ${getPaymentStatusColor(booking.payment_status)}`}>
                      {booking.payment_status.toUpperCase()}
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