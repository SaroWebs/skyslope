import React, { useState, useEffect } from 'react'
import { Head } from '@inertiajs/react'
import WebsiteLayout from '@/layoutes/WebsiteLayout'
import RideBookingForm from '@/components/ride_booking/RideBookingForm'
import RideTracker from '@/components/ride_booking/RideTracker'
import RideBookingSearch from '@/components/ride_booking/RideBookingSearch'

interface Location {
  lat: number
  lng: number
  address: string
  type?: string
  id?: string | number
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
    id?: number
    name: string
    phone: string
  }
  current_lat?: number
  current_lng?: number
}

const RideBooking = () => {
  const [currentStep, setCurrentStep] = useState<'search' | 'booking' | 'tracking'>('search')
  const [pickupLocation, setPickupLocation] = useState<Location | null>(null)
  const [dropoffLocation, setDropoffLocation] = useState<Location | null>(null)
  const [activeBooking, setActiveBooking] = useState<RideBooking | null>(null)

  // Check for localStorage data on component mount
  useEffect(() => {
    const searchData = localStorage.getItem('ride_booking_search')
    if (searchData) {
      try {
        const parsed = JSON.parse(searchData)
        if (parsed.pickup) {
          setPickupLocation(parsed.pickup)
          if (parsed.dropoff) {
            setDropoffLocation(parsed.dropoff)
          }
          setCurrentStep('booking')
          // Clear the storage after retrieving
          localStorage.removeItem('ride_booking_search')
        }
      } catch (error) {
        console.error('Error parsing search data:', error)
      }
    }
  }, [])

  const handleBookingCreated = (booking: RideBooking) => {
    setActiveBooking(booking)
    setCurrentStep('tracking')
  }

  const handleBackFromBooking = () => {
    setCurrentStep('search')
  }

  const handleBookingCancelled = () => {
    setActiveBooking(null)
    setCurrentStep('search')
    setPickupLocation(null)
    setDropoffLocation(null)
  }

  const handleSearchLocationsSelected = (pickup: Location, dropoff?: Location) => {
    setPickupLocation(pickup)
    setDropoffLocation(dropoff || null)
    setCurrentStep('booking')
  }

  return (
    <WebsiteLayout page='ride-booking'>
      <Head>
        <title>Ride Booking - Ola/Uber-like Service</title>
        <meta name="description" content="Book rides with real-time tracking, just like Ola and Uber. Fast, reliable, and convenient transportation." />
      </Head>

      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        {/* Header */}
        <div className="bg-white shadow-sm">
          <div className="container-modern py-4 px-4">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Ride Booking</h1>
                <p className="text-gray-600">Book rides with real-time tracking</p>
              </div>
              <div className="flex items-center space-x-2">
                <div className={`w-3 h-3 rounded-full ${currentStep === 'search' ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                <div className={`w-3 h-3 rounded-full ${currentStep === 'booking' ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
                <div className={`w-3 h-3 rounded-full ${currentStep === 'tracking' ? 'bg-blue-500' : 'bg-gray-300'}`}></div>
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="container-modern py-8 px-4">
          {/* Search Section */}
          {currentStep === 'search' && (
            <div className="mb-8">
              <RideBookingSearch 
                onLocationsSelected={handleSearchLocationsSelected}
                initialPickup={pickupLocation}
                initialDropoff={dropoffLocation}
              />
            </div>
          )}

          {currentStep === 'booking' && pickupLocation && (
            <div className="space-y-6">
              {/* Search Summary */}
              <div className="bg-white rounded-xl shadow-lg p-4">
                <div className="flex items-center justify-between text-sm text-gray-600 mb-2">
                  <span>Selected Route</span>
                  <button
                    onClick={() => setCurrentStep('search')}
                    className="text-blue-600 hover:text-blue-700"
                  >
                    Modify Search
                  </button>
                </div>
                <div className="flex items-center space-x-3">
                  <div className="flex items-center space-x-2">
                    <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span className="text-sm">{pickupLocation?.address}</span>
                  </div>
                  {dropoffLocation && (
                    <>
                      <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7l4-4m0 0l4 4m-4-4v18" />
                      </svg>
                      <div className="flex items-center space-x-2">
                        <div className="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span className="text-sm">{dropoffLocation?.address}</span>
                      </div>
                    </>
                  )}
                </div>
              </div>

              <RideBookingForm
                pickupLocation={pickupLocation}
                dropoffLocation={dropoffLocation}
                onBookingCreated={handleBookingCreated}
                onBack={handleBackFromBooking}
              />
            </div>
          )}

          {currentStep === 'tracking' && activeBooking && (
            <RideTracker
              booking={activeBooking}
              onBookingCancelled={handleBookingCancelled}
            />
          )}
        </div>
      </div>
    </WebsiteLayout>
  )
}

export default RideBooking
