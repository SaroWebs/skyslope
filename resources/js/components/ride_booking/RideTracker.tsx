import React, { useState, useEffect } from 'react'
import { notificationService } from '@/services/notification'

interface RideBooking {
  id: number
  booking_number: string
  status: string
  pickup_location: string
  dropoff_location?: string
  scheduled_at: string
  total_fare: number
  driver?: {
    name: string
    phone: string
  }
  current_lat?: number
  current_lng?: number
}

interface RideTrackerProps {
  booking: RideBooking
  onBookingCancelled: () => void
}

const RideTracker: React.FC<RideTrackerProps> = ({ booking, onBookingCancelled }) => {
  const [currentStatus, setCurrentStatus] = useState(booking.status)
  const [driverLocation, setDriverLocation] = useState<{ lat: number; lng: number } | null>(null)
  const [eta, setEta] = useState<number | null>(null)

  useEffect(() => {
    // Poll for booking updates
    const interval = setInterval(fetchBookingUpdate, 10000) // Update every 10 seconds
    return () => clearInterval(interval)
  }, [booking.id])

  const fetchBookingUpdate = async () => {
    try {
      const response = await fetch(`/api/ride-bookings/${booking.id}`)
      if (response.ok) {
        const data = await response.json()
        setCurrentStatus(data.status)

        if (data.current_lat && data.current_lng) {
          setDriverLocation({
            lat: data.current_lat,
            lng: data.current_lng
          })
        }

        // Update booking if status changed
        if (data.status !== currentStatus) {
          // Could emit event or callback here
        }
      }
    } catch (error) {
      console.error('Error fetching booking update:', error)
    }
  }

  const handleCancelBooking = async () => {
    if (!confirm('Are you sure you want to cancel this booking?')) {
      return
    }

    try {
      const response = await fetch(`/api/ride-bookings/${booking.id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        }
      })

      if (response.ok) {
        notificationService.success('Booking cancelled successfully')
        onBookingCancelled()
      } else {
        notificationService.error('Failed to cancel booking')
      }
    } catch (error) {
      notificationService.error('Failed to cancel booking')
    }
  }

  const getStatusInfo = (status: string) => {
    const statusMap = {
      pending: {
        label: 'Finding Driver',
        color: 'text-yellow-600',
        bgColor: 'bg-yellow-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        )
      },
      confirmed: {
        label: 'Booking Confirmed',
        color: 'text-blue-600',
        bgColor: 'bg-blue-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        )
      },
      driver_assigned: {
        label: 'Driver Assigned',
        color: 'text-purple-600',
        bgColor: 'bg-purple-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        )
      },
      driver_arriving: {
        label: 'Driver Arriving',
        color: 'text-indigo-600',
        bgColor: 'bg-indigo-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        )
      },
      pickup: {
        label: 'Arrived for Pickup',
        color: 'text-orange-600',
        bgColor: 'bg-orange-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        )
      },
      in_transit: {
        label: 'In Transit',
        color: 'text-green-600',
        bgColor: 'bg-green-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        )
      },
      completed: {
        label: 'Ride Completed',
        color: 'text-gray-600',
        bgColor: 'bg-gray-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        )
      },
      cancelled: {
        label: 'Booking Cancelled',
        color: 'text-red-600',
        bgColor: 'bg-red-100',
        icon: (
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        )
      }
    }

    return statusMap[status as keyof typeof statusMap] || statusMap.pending
  }

  const statusInfo = getStatusInfo(currentStatus)
  const canCancel = ['pending', 'confirmed', 'driver_assigned'].includes(currentStatus)

  return (
    <div className="max-w-2xl mx-auto">
      <div className="bg-white rounded-xl shadow-lg p-6">
        <div className="text-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Track Your Ride</h2>
          <p className="text-gray-600">Booking #{booking.booking_number}</p>
        </div>

        {/* Status Card */}
        <div className={`rounded-lg p-4 mb-6 ${statusInfo.bgColor}`}>
          <div className="flex items-center space-x-3">
            <div className={`p-2 rounded-full ${statusInfo.bgColor} ${statusInfo.color}`}>
              {statusInfo.icon}
            </div>
            <div>
              <h3 className={`font-semibold ${statusInfo.color}`}>{statusInfo.label}</h3>
              {eta && (
                <p className="text-sm text-gray-600">ETA: {Math.ceil(eta / 60)} minutes</p>
              )}
            </div>
          </div>
        </div>

        {/* Driver Info */}
        {booking.driver && (
          <div className="bg-gray-50 rounded-lg p-4 mb-6">
            <h4 className="font-semibold text-gray-900 mb-2">Your Driver</h4>
            <div className="flex items-center space-x-3">
              <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <p className="font-medium text-gray-900">{booking.driver.name}</p>
                <p className="text-sm text-gray-600">{booking.driver.phone}</p>
              </div>
            </div>
          </div>
        )}

        {/* Trip Details */}
        <div className="bg-gray-50 rounded-lg p-4 mb-6">
          <h4 className="font-semibold text-gray-900 mb-3">Trip Details</h4>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-gray-600">From:</span>
              <span className="text-gray-900">{booking.pickup_location}</span>
            </div>
            {booking.dropoff_location && (
              <div className="flex justify-between">
                <span className="text-gray-600">To:</span>
                <span className="text-gray-900">{booking.dropoff_location}</span>
              </div>
            )}
            <div className="flex justify-between">
              <span className="text-gray-600">Scheduled:</span>
              <span className="text-gray-900">
                {new Date(booking.scheduled_at).toLocaleString()}
              </span>
            </div>
            <div className="flex justify-between font-semibold">
              <span className="text-gray-600">Total Fare:</span>
              <span className="text-gray-900">₹{booking.total_fare.toFixed(2)}</span>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex space-x-3">
          {canCancel && (
            <button
              onClick={handleCancelBooking}
              className="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
            >
              Cancel Booking
            </button>
          )}

          <button
            onClick={() => window.location.reload()}
            className="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
          >
            Refresh Status
          </button>
        </div>

        {/* Map Placeholder */}
        <div className="mt-6 bg-gray-100 rounded-lg h-48 flex items-center justify-center">
          <div className="text-center">
            <svg className="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p className="text-gray-500 text-sm">Live tracking map would be displayed here</p>
            {driverLocation && (
              <p className="text-xs text-gray-400 mt-1">
                Driver at: {driverLocation.lat.toFixed(4)}, {driverLocation.lng.toFixed(4)}
              </p>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

export default RideTracker