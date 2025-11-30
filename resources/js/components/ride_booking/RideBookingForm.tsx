import React, { useState, useEffect } from 'react'
import { notificationService } from '@/services/notification'

interface Location {
  lat: number
  lng: number
  address: string
  type?: string
  id?: string | number
}

interface PricingEstimate {
  distance_km: number
  estimated_duration: number
  pricing: {
    base_fare: number
    distance_fare: number
    surge_multiplier: number
    subtotal: number
  }
  nearby_drivers: number
}

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
}

interface RideBookingFormProps {
  pickupLocation: Location
  dropoffLocation?: Location | null
  onBookingCreated: (booking: RideBooking) => void
  onBack: () => void
}

const RideBookingForm: React.FC<RideBookingFormProps> = ({
  pickupLocation,
  dropoffLocation,
  onBookingCreated,
  onBack
}) => {
  const [formData, setFormData] = useState({
    service_type: 'point_to_point',
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    scheduled_at: '',
    special_requests: '',
    payment_method: 'cash'
  })

  const [pricing, setPricing] = useState<PricingEstimate | null>(null)
  const [loading, setLoading] = useState(false)
  const [estimating, setEstimating] = useState(false)

  useEffect(() => {
    if (pickupLocation) {
      estimatePrice()
    }
  }, [pickupLocation, dropoffLocation])

  const estimatePrice = async () => {
    setEstimating(true)
    try {
      const estimateData: any = {
        pickup_lat: pickupLocation.lat,
        pickup_lng: pickupLocation.lng,
        service_type: formData.service_type,
        scheduled_at: new Date().toISOString()
      }

      if (dropoffLocation) {
        estimateData.dropoff_lat = dropoffLocation.lat
        estimateData.dropoff_lng = dropoffLocation.lng
      }

      const response = await fetch('/api/ride-bookings/estimate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(estimateData)
      })

      if (response.ok) {
        const data = await response.json()
        setPricing(data)
      } else {
        notificationService.error('Failed to estimate price')
      }
    } catch (error) {
      console.error('Error estimating price:', error)
      notificationService.error('Failed to estimate price')
    } finally {
      setEstimating(false)
    }
  }

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }))

    // Re-estimate price if service type changes
    if (field === 'service_type') {
      setTimeout(() => estimatePrice(), 100)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!pricing) {
      notificationService.error('Price estimation is required')
      return
    }

    setLoading(true)
    try {
      const bookingData = {
        ...formData,
        pickup_location: pickupLocation.address,
        pickup_lat: pickupLocation.lat,
        pickup_lng: pickupLocation.lng,
        dropoff_location: dropoffLocation?.address || null,
        dropoff_lat: dropoffLocation?.lat || null,
        dropoff_lng: dropoffLocation?.lng || null,
      }

      const response = await fetch('/api/ride-bookings', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(bookingData)
      })

      const result = await response.json()

      if (response.ok) {
        notificationService.success('Ride booked successfully!')
        onBookingCreated(result.ride_booking)
      } else {
        notificationService.error(result.message || 'Failed to book ride')
      }
    } catch (error) {
      console.error('Booking error:', error)
      notificationService.error('An unexpected error occurred')
    } finally {
      setLoading(false)
    }
  }

  const formatCurrency = (amount: number) => `₹${amount.toFixed(2)}`

  return (
    <div className="max-w-2xl mx-auto">
      <div className="bg-white rounded-xl shadow-lg p-6">
        <div className="flex items-center mb-6">
          <button
            onClick={onBack}
            className="mr-4 p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h2 className="text-2xl font-bold text-gray-900">Book Your Ride</h2>
        </div>

        {/* Route Summary */}
        <div className="bg-gray-50 rounded-lg p-4 mb-6">
          <div className="flex items-start space-x-3">
            <div className="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
              <svg className="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div className="flex-1">
              <div className="text-sm font-medium text-gray-900">From</div>
              <div className="text-sm text-gray-600">{pickupLocation.address}</div>
            </div>
          </div>

          {dropoffLocation && (
            <>
              <div className="flex items-center justify-center my-2">
                <div className="w-px h-6 bg-gray-300"></div>
              </div>
              <div className="flex items-start space-x-3">
                <div className="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                  <svg className="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div className="flex-1">
                  <div className="text-sm font-medium text-gray-900">To</div>
                  <div className="text-sm text-gray-600">{dropoffLocation.address}</div>
                </div>
              </div>
            </>
          )}
        </div>

        {/* Price Estimate */}
        {pricing && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm font-medium text-blue-900">Estimated Fare</span>
              <span className="text-lg font-bold text-blue-900">{formatCurrency(pricing.pricing.subtotal)}</span>
            </div>
            <div className="space-y-1 text-sm text-blue-800">
              <div className="flex justify-between">
                <span>Distance:</span>
                <span>{pricing.distance_km.toFixed(1)} km</span>
              </div>
              <div className="flex justify-between">
                <span>Duration:</span>
                <span>~{Math.ceil(pricing.estimated_duration / 60)} min</span>
              </div>
              <div className="flex justify-between">
                <span>Drivers nearby:</span>
                <span>{pricing.nearby_drivers}</span>
              </div>
              {pricing.pricing.surge_multiplier > 1 && (
                <div className="flex justify-between text-orange-600">
                  <span>Surge pricing:</span>
                  <span>{pricing.pricing.surge_multiplier}x</span>
                </div>
              )}
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Service Type */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
            <select
              value={formData.service_type}
              onChange={(e) => handleInputChange('service_type', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="point_to_point">Point to Point</option>
              <option value="hourly_rental">Hourly Rental</option>
              <option value="round_trip">Round Trip</option>
            </select>
          </div>

          {/* Customer Details */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
              <input
                type="text"
                required
                value={formData.customer_name}
                onChange={(e) => handleInputChange('customer_name', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter your full name"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
              <input
                type="tel"
                required
                value={formData.customer_phone}
                onChange={(e) => handleInputChange('customer_phone', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter your phone number"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
            <input
              type="email"
              required
              value={formData.customer_email}
              onChange={(e) => handleInputChange('customer_email', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Enter your email address"
            />
          </div>

          {/* Schedule Time */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Pickup Time</label>
            <input
              type="datetime-local"
              required
              value={formData.scheduled_at}
              onChange={(e) => handleInputChange('scheduled_at', e.target.value)}
              min={new Date().toISOString().slice(0, 16)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          {/* Special Requests */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Special Requests (Optional)</label>
            <textarea
              value={formData.special_requests}
              onChange={(e) => handleInputChange('special_requests', e.target.value)}
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Any special requirements or instructions..."
            />
          </div>

          {/* Payment Method */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
            <select
              value={formData.payment_method}
              onChange={(e) => handleInputChange('payment_method', e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="wallet">Digital Wallet</option>
            </select>
          </div>

          {/* Submit Button */}
          <button
            type="submit"
            disabled={loading || !pricing}
            className="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg transition-colors"
          >
            {loading ? 'Booking...' : `Book Ride - ${pricing ? formatCurrency(pricing.pricing.subtotal) : 'Calculating...'}`}
          </button>
        </form>
      </div>
    </div>
  )
}

export default RideBookingForm