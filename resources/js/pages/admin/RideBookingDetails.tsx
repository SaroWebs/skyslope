import React, { useMemo, useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import AdminLayout from '@/layoutes/AdminLayout'

interface DriverOption {
  id: number
  name: string
  email: string
  phone?: string
  is_online: boolean
  is_available: boolean
  rating?: number | null
  vehicle_number?: string | null
}

interface RideBooking {
  id: number
  booking_number: string
  customer_name: string
  customer_email: string
  customer_phone: string
  pickup_location: string
  dropoff_location?: string
  scheduled_at: string
  total_fare: number
  status: string
  payment_status: string
  service_type: string
  special_requests?: string
  pickup_lat?: number
  pickup_lng?: number
  dropoff_lat?: number
  dropoff_lng?: number
  current_lat?: number
  current_lng?: number
  last_admin_changed_at?: string | null
  driver_id?: number | null
  user?: {
    id: number
    name: string
    email: string
    phone?: string
  }
  driver?: {
    id: number
    name: string
    email: string
    phone?: string
  }
}

interface Props {
  title?: string
  booking: RideBooking
  drivers: DriverOption[]
  can_undo_last_change?: boolean
}

const RideBookingDetails: React.FC<Props> = ({ title = 'Ride Booking Details', booking, drivers, can_undo_last_change = false }) => {
  const [selectedDriverId, setSelectedDriverId] = useState<string>(booking.driver_id ? String(booking.driver_id) : '')
  const [assigningDriver, setAssigningDriver] = useState(false)
  const [undoing, setUndoing] = useState(false)
  const [tracking, setTracking] = useState<any>(null)
  const [trackingLoading, setTrackingLoading] = useState(false)

  const canAssignDriver = !['completed', 'cancelled'].includes(booking.status)

  const selectedDriver = useMemo(
    () => drivers.find((driver) => String(driver.id) === selectedDriverId),
    [drivers, selectedDriverId],
  )

  const handleAssignDriver = async () => {
    if (!selectedDriverId) {
      alert('Please select a driver.')
      return
    }

    setAssigningDriver(true)
    try {
      const response = await fetch(`/admin/ride-bookings/${booking.id}/assign-driver`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ driver_id: Number(selectedDriverId) }),
      })

      const data = await response.json().catch(() => ({}))
      if (!response.ok) {
        alert(data?.message || 'Failed to assign driver.')
        return
      }

      alert('Driver assigned successfully.')
      router.reload()
    } catch (error) {
      console.error('Driver assignment failed:', error)
      alert('Failed to assign driver.')
    } finally {
      setAssigningDriver(false)
    }
  }

  const handleUndoLastChange = async () => {
    setUndoing(true)
    try {
      const response = await fetch(`/admin/ride-bookings/${booking.id}/undo-last-change`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      const data = await response.json().catch(() => ({}))
      if (!response.ok) {
        alert(data?.message || 'Failed to undo last change.')
        return
      }

      alert('Last change undone successfully.')
      router.reload()
    } catch (error) {
      alert('Failed to undo last change.')
    } finally {
      setUndoing(false)
    }
  }

  const handleTrackBooking = async () => {
    setTrackingLoading(true)
    try {
      const response = await fetch(`/api/tracking/ride/${booking.id}`, {
        headers: {
          Accept: 'application/json',
        },
      })
      const data = await response.json().catch(() => ({}))
      if (!response.ok) {
        alert(data?.message || 'Failed to fetch tracking info.')
        return
      }
      setTracking(data?.data || null)
    } catch (error) {
      alert('Failed to fetch tracking info.')
    } finally {
      setTrackingLoading(false)
    }
  }

  const formatDateTime = (dateString: string) =>
    new Date(dateString).toLocaleString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })

  return (
    <AdminLayout title={title}>
      <Head title={`${title} - ${booking.booking_number}`} />

      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Ride Booking #{booking.booking_number}</h1>
            <p className="text-sm text-gray-600">Admin dashboard ride-booking details</p>
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={handleTrackBooking}
              className="rounded-lg border border-indigo-300 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50"
            >
              {trackingLoading ? 'Tracking...' : 'Track Booking'}
            </button>
            {can_undo_last_change && (
              <button
                type="button"
                onClick={handleUndoLastChange}
                disabled={undoing}
                className="rounded-lg border border-amber-300 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 disabled:opacity-60"
              >
                {undoing ? 'Undoing...' : 'Undo Last Change (10m)'}
              </button>
            )}
            <Link
              href="/admin/ride-bookings"
              className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Back to Ride Bookings
            </Link>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="space-y-6 lg:col-span-2">
            <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
              <h2 className="mb-4 text-lg font-semibold text-gray-900">Trip Details</h2>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Status</p>
                  <p className="text-sm font-medium text-gray-900">{booking.status.replace('_', ' ')}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Scheduled</p>
                  <p className="text-sm font-medium text-gray-900">{formatDateTime(booking.scheduled_at)}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Pickup</p>
                  <p className="text-sm font-medium text-gray-900">{booking.pickup_location}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Dropoff</p>
                  <p className="text-sm font-medium text-gray-900">{booking.dropoff_location || 'Not provided'}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Fare</p>
                  <p className="text-sm font-medium text-gray-900">INR {Number(booking.total_fare || 0).toFixed(2)}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Payment</p>
                  <p className="text-sm font-medium text-gray-900">{booking.payment_status}</p>
                </div>
              </div>
            </div>

            {tracking && (
              <div className="rounded-lg border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
                <h2 className="mb-4 text-lg font-semibold text-indigo-900">Live Tracking</h2>
                <div className="space-y-2 text-sm text-indigo-900">
                  <p>
                    Current Location:{' '}
                    {tracking.current_location
                      ? `${tracking.current_location.latitude}, ${tracking.current_location.longitude}`
                      : 'No live location yet'}
                  </p>
                  <p>Pickup: {tracking.pickup?.address || booking.pickup_location}</p>
                  <p>Dropoff: {tracking.dropoff?.address || booking.dropoff_location || '-'}</p>
                </div>
                {tracking.current_location && (
                  <a
                    href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
                      `${tracking.current_location.latitude},${tracking.current_location.longitude}`
                    )}`}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-3 inline-flex rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                  >
                    Open Current Location in Maps
                  </a>
                )}
              </div>
            )}

            <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
              <h2 className="mb-4 text-lg font-semibold text-gray-900">Customer</h2>
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Name</p>
                  <p className="text-sm font-medium text-gray-900">{booking.customer_name}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-wide text-gray-500">Phone</p>
                  <p className="text-sm font-medium text-gray-900">{booking.customer_phone || '-'}</p>
                </div>
                <div className="md:col-span-2">
                  <p className="text-xs uppercase tracking-wide text-gray-500">Email</p>
                  <p className="text-sm font-medium text-gray-900">{booking.customer_email}</p>
                </div>
              </div>
            </div>
          </div>

          <div className="space-y-6">
            <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
              <h2 className="mb-4 text-lg font-semibold text-gray-900">Assign Driver</h2>

              {booking.driver ? (
                <p className="mb-4 text-sm text-gray-600">
                  Current: <span className="font-medium text-gray-900">{booking.driver.name}</span>
                </p>
              ) : (
                <p className="mb-4 text-sm text-gray-600">No driver assigned yet.</p>
              )}

              <label className="mb-1 block text-sm font-medium text-gray-700">Driver</label>
              <select
                value={selectedDriverId}
                onChange={(event) => setSelectedDriverId(event.target.value)}
                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                disabled={!canAssignDriver || assigningDriver}
              >
                <option value="">Select driver</option>
                {drivers.map((driver) => (
                  <option key={driver.id} value={driver.id}>
                    {driver.name} ({driver.is_online ? 'online' : 'offline'}, {driver.is_available ? 'available' : 'busy'})
                  </option>
                ))}
              </select>

              {selectedDriver && (
                <div className="mt-3 rounded-md border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700">
                  <p>{selectedDriver.email}</p>
                  <p>{selectedDriver.phone || '-'}</p>
                  <p>Rating: {selectedDriver.rating ?? 'N/A'}</p>
                  <p>Vehicle: {selectedDriver.vehicle_number || 'N/A'}</p>
                </div>
              )}

              <button
                type="button"
                onClick={handleAssignDriver}
                disabled={!canAssignDriver || assigningDriver}
                className="mt-4 w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
              >
                {assigningDriver ? 'Assigning...' : booking.driver ? 'Reassign Driver' : 'Assign Driver'}
              </button>

              {!canAssignDriver && (
                <p className="mt-3 text-xs text-red-600">
                  Driver assignment is locked for {booking.status} rides.
                </p>
              )}
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  )
}

export default RideBookingDetails
