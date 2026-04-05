import React, { useState, useEffect } from 'react'
import { Head, Link, usePage } from '@inertiajs/react'
import AdminLayout from '@/layoutes/AdminLayout'
import { notificationService } from '@/services/notification'

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
  driver?: {
    id: number
    name: string
  }
  created_at: string
}

const RideBookings = () => {
  const { flash } = usePage().props as any
  const [rideBookings, setRideBookings] = useState<RideBooking[]>([])
  const [loading, setLoading] = useState(true)
  const [filterStatus, setFilterStatus] = useState('')

  useEffect(() => {
    fetchRideBookings()
  }, [filterStatus])

  const fetchRideBookings = async () => {
    try {
      const params = new URLSearchParams()
      if (filterStatus) params.append('status', filterStatus)

      const response = await fetch(`/admin/ride-bookings?${params.toString()}`, {
        headers: {
          Accept: 'application/json',
        },
      })
      if (!response.ok) throw new Error('Failed to fetch ride bookings')

      const data = await response.json()
      setRideBookings(Array.isArray(data.data) ? data.data : [])
    } catch (error) {
      console.error('Error fetching ride bookings:', error)
      setRideBookings([])
    } finally {
      setLoading(false)
    }
  }

  const getStatusBadge = (status: string) => {
    const badges = {
      pending: 'bg-yellow-100 text-yellow-800',
      confirmed: 'bg-blue-100 text-blue-800',
      driver_assigned: 'bg-purple-100 text-purple-800',
      driver_arriving: 'bg-indigo-100 text-indigo-800',
      pickup: 'bg-orange-100 text-orange-800',
      in_transit: 'bg-green-100 text-green-800',
      completed: 'bg-gray-100 text-gray-800',
      cancelled: 'bg-red-100 text-red-800',
    }
    return badges[status as keyof typeof badges] || 'bg-gray-100 text-gray-800'
  }

  const getPaymentStatusBadge = (status: string) => {
    const badges = {
      pending: 'bg-yellow-100 text-yellow-800',
      paid: 'bg-green-100 text-green-800',
      failed: 'bg-red-100 text-red-800',
      refunded: 'bg-gray-100 text-gray-800',
    }
    return badges[status as keyof typeof badges] || 'bg-gray-100 text-gray-800'
  }

  const updateBookingStatus = async (bookingId: number, newStatus: string) => {
    try {
      const response = await fetch(`/admin/ride-bookings/${bookingId}/update-status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ status: newStatus })
      })

      if (response.ok) {
        notificationService.success('Booking status updated successfully')
        fetchRideBookings() // Refresh the list
      } else {
        notificationService.error('Failed to update booking status')
      }
    } catch (error) {
      notificationService.error('Failed to update booking status')
    }
  }

  return (
    <AdminLayout>
      <Head>
        <title>Ride Bookings Management - Admin</title>
      </Head>

      <div className="container mx-auto px-4 py-8">
        <div className="flex justify-between items-center mb-6">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Ride Bookings</h1>
            <p className="text-gray-600 mt-1">Manage Ola/Uber-like ride bookings</p>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
          <div className="flex flex-wrap gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="driver_assigned">Driver Assigned</option>
                <option value="driver_arriving">Driver Arriving</option>
                <option value="pickup">Pickup</option>
                <option value="in_transit">In Transit</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
        </div>

        {/* Ride Bookings Table */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
              <span className="ml-3 text-gray-600">Loading ride bookings...</span>
            </div>
          ) : rideBookings.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Booking Details
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Customer
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Trip Details
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Driver
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Pricing
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {rideBookings.map((booking) => (
                    <tr key={booking.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {booking.booking_number}
                          </div>
                          <div className="text-sm text-gray-500">
                            {new Date(booking.created_at).toLocaleDateString()}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {booking.customer_name}
                          </div>
                          <div className="text-sm text-gray-500">
                            {booking.customer_email}
                          </div>
                          <div className="text-sm text-gray-500">
                            {booking.customer_phone}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm text-gray-900">
                          <div><strong>From:</strong> {booking.pickup_location}</div>
                          <div><strong>To:</strong> {booking.dropoff_location || 'Not specified'}</div>
                          <div><strong>Scheduled:</strong> {new Date(booking.scheduled_at).toLocaleString()}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">
                          {booking.driver ? (
                            <div>
                              <div className="font-medium">{booking.driver.name}</div>
                              <div className="text-gray-500">ID: {booking.driver.id}</div>
                            </div>
                          ) : (
                            <span className="text-gray-400">Not assigned</span>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          ₹{booking.total_fare}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div>
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadge(booking.status)}`}>
                            {booking.status.replace('_', ' ')}
                          </span>
                          <div className="mt-1">
                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPaymentStatusBadge(booking.payment_status)}`}>
                              {booking.payment_status}
                            </span>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div className="flex space-x-2">
                          <Link
                            href={`/admin/ride-bookings/${booking.id}`}
                            className="inline-flex items-center rounded border border-blue-200 px-2 py-1 text-xs text-blue-700 hover:bg-blue-50"
                          >
                            View
                          </Link>
                          <select
                            value={booking.status}
                            onChange={(e) => updateBookingStatus(booking.id, e.target.value)}
                            className="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                          >
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="driver_assigned">Driver Assigned</option>
                            <option value="driver_arriving">Driver Arriving</option>
                            <option value="pickup">Pickup</option>
                            <option value="in_transit">In Transit</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                          </select>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-12">
              <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <h3 className="text-lg font-medium text-gray-900 mb-2">No ride bookings found</h3>
              <p className="text-gray-600">Ride bookings will appear here when customers start booking rides.</p>
            </div>
          )}
        </div>
      </div>

      {/* Success Message */}
      {flash?.success && (
        <div className="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg">
          {flash.success}
        </div>
      )}
    </AdminLayout>
  )
}

export default RideBookings
