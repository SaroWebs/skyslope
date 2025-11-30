import React, { useState, useEffect } from 'react'
import { Head, usePage } from '@inertiajs/react'
import AdminLayout from '@/layoutes/AdminLayout'
import { notificationService } from '@/services/notification'

interface CarRental {
  id: number
  booking_number: string
  customer_name: string
  customer_email: string
  customer_phone: string
  carCategory: {
    id: number
    name: string
    vehicle_type: string
  }
  start_date: string
  end_date: string
  pickup_location: string
  dropoff_location: string
  distance_km: number
  total_price: number
  status: string
  payment_status: string
  created_at: string
}

interface CarCategory {
  id: number
  name: string
  vehicle_type: string
  base_price_per_day: number
  is_active: boolean
}

const CarRentals = () => {
  const { flash } = usePage().props as any
  const [carRentals, setCarRentals] = useState<CarRental[]>([])
  const [carCategories, setCarCategories] = useState<CarCategory[]>([])
  const [loading, setLoading] = useState(true)
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [editingRental, setEditingRental] = useState<CarRental | null>(null)
  const [filterStatus, setFilterStatus] = useState('')

  useEffect(() => {
    fetchCarRentals()
    fetchCarCategories()
  }, [filterStatus])

  const fetchCarRentals = async () => {
    try {
      const params = new URLSearchParams()
      if (filterStatus) params.append('status', filterStatus)

      const response = await fetch(`/admin/car-rentals?${params}`)
      if (!response.ok) throw new Error('Failed to fetch car rentals')

      const data = await response.json()
      setCarRentals(Array.isArray(data.data) ? data.data : [])
    } catch (error) {
      console.error('Error fetching car rentals:', error)
      setCarRentals([])
    } finally {
      setLoading(false)
    }
  }

  const fetchCarCategories = async () => {
    try {
      const response = await fetch('/api/car-categories')
      if (!response.ok) throw new Error('Failed to fetch car categories')

      const data = await response.json()
      setCarCategories(Array.isArray(data) ? data : [])
    } catch (error) {
      console.error('Error fetching car categories:', error)
      setCarCategories([])
    }
  }

  const getStatusBadge = (status: string) => {
    const badges = {
      pending: 'bg-yellow-100 text-yellow-800',
      confirmed: 'bg-blue-100 text-blue-800',
      in_progress: 'bg-green-100 text-green-800',
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

  return (
    <AdminLayout>
      <Head>
        <title>Car Rentals Management - Admin</title>
      </Head>

      <div className="container mx-auto px-4 py-8">
        <div className="flex justify-between items-center mb-6">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Car Rentals</h1>
            <p className="text-gray-600 mt-1">Manage car rental bookings and extras</p>
          </div>
          <button
            onClick={() => setShowCreateModal(true)}
            className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
          >
            Add New Rental
          </button>
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
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
        </div>

        {/* Car Rentals Table */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          {loading ? (
            <div className="flex items-center justify-center py-12">
              <div className="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
              <span className="ml-3 text-gray-600">Loading car rentals...</span>
            </div>
          ) : carRentals.length > 0 ? (
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
                  {carRentals.map((rental) => (
                    <tr key={rental.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {rental.booking_number}
                          </div>
                          <div className="text-sm text-gray-500">
                            {rental.carCategory.name}
                          </div>
                          <div className="text-xs text-gray-400">
                            {new Date(rental.created_at).toLocaleDateString()}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {rental.customer_name}
                          </div>
                          <div className="text-sm text-gray-500">
                            {rental.customer_email}
                          </div>
                          <div className="text-sm text-gray-500">
                            {rental.customer_phone}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm text-gray-900">
                          <div><strong>From:</strong> {rental.pickup_location}</div>
                          <div><strong>To:</strong> {rental.dropoff_location || 'Same as pickup'}</div>
                          <div><strong>Distance:</strong> {rental.distance_km} km</div>
                          <div><strong>Dates:</strong> {new Date(rental.start_date).toLocaleDateString()} - {new Date(rental.end_date).toLocaleDateString()}</div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          ₹{rental.total_price}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div>
                          <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadge(rental.status)}`}>
                            {rental.status.replace('_', ' ')}
                          </span>
                          <div className="mt-1">
                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPaymentStatusBadge(rental.payment_status)}`}>
                              {rental.payment_status}
                            </span>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div className="flex space-x-2">
                          <button
                            onClick={() => setEditingRental(rental)}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            Edit
                          </button>
                          <button className="text-gray-600 hover:text-gray-900">
                            View
                          </button>
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
              <h3 className="text-lg font-medium text-gray-900 mb-2">No car rentals found</h3>
              <p className="text-gray-600">Get started by creating your first car rental booking.</p>
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

export default CarRentals