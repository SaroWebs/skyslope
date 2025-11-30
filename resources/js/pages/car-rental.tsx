import WebsiteLayout from '@/layoutes/WebsiteLayout'
import { Head, useForm, usePage, Link } from '@inertiajs/react'
import React, { useEffect, useState } from 'react'
import { Drawer } from '@mantine/core'
import CarCategoryCard from '@/components/car_rent/CarCategoryCard'
import CarRentForm from '@/components/car_rent/CarRentForm'
import { notificationService } from '@/services/notification'

// Extend Window interface for Google Maps
declare global {
  interface Window {
    google: typeof google
  }
}

interface Location {
  lat: number
  lng: number
  address: string
}

interface CarCategory {
  id: number
  name: string
  description: string
  vehicle_type: string
  seats: number
  has_ac: boolean
  base_price_per_day: number
  price_per_km: number
  features: string[]
}

interface Destination {
  id: number
  name: string
  state: string
  type: string
}

const CarRental = () => {
  const { auth } = usePage().props

  if (!(auth as any).user) {
    return (
      <WebsiteLayout page='car-rental'>
        <div className="container-modern py-12 sm:py-20 px-4">
          <div className="max-w-md mx-auto text-center">
            <h2 className="text-xl sm:text-2xl font-bold text-neutral-900 mb-3 sm:mb-4">Login Required</h2>
            <p className="text-neutral-600 mb-4 sm:mb-6 text-sm sm:text-base">You need to login to rent a car.</p>
            <Link href="/login" className="modern-btn modern-btn-primary text-sm sm:text-base px-6 sm:px-8 py-2 sm:py-3">Login</Link>
          </div>
        </div>
      </WebsiteLayout>
    )
  }

  const { data, setData, post, processing, errors, reset } = useForm({
    car_category_id: '',
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    start_date: '',
    end_date: '',
    pickup_location: '',
  })

  const [carCategories, setCarCategories] = useState<CarCategory[]>([])
  const [destinations, setDestinations] = useState<Destination[]>([])
  const [selectedCategory, setSelectedCategory] = useState<CarCategory | null>(null)
  const [totalPrice, setTotalPrice] = useState<number>(0)
  const [loading, setLoading] = useState<boolean>(true)
  const [pickupLocation, setPickupLocation] = useState<Location | null>(null)
  const [isDrawerOpen, setIsDrawerOpen] = useState<boolean>(false)

  const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || 'your_google_maps_api_key_here'

  useEffect(() => {
    fetchCarCategories()
    fetchDestinations()
  }, [])

  useEffect(() => {
    if (data.start_date && data.end_date && selectedCategory) {
      calculatePrice()
    }
  }, [data.start_date, data.end_date, selectedCategory])



  const fetchCarCategories = async () => {
    try {
      const response = await fetch('/api/car-categories')
      if (!response.ok) {
        throw new Error('Failed to fetch car categories')
      }
      const data = await response.json()
      setCarCategories(Array.isArray(data) ? data : [])
    } catch (error) {
      console.error('Error fetching car categories:', error)
      setCarCategories([])
    } finally {
      setLoading(false)
    }
  }

  const fetchDestinations = async () => {
    try {
      const response = await fetch('/api/destinations')
      if (!response.ok) {
        throw new Error('Failed to fetch destinations')
      }
      const data = await response.json()
      setDestinations(Array.isArray(data) ? data : [])
    } catch (error) {
      console.error('Error fetching destinations:', error)
      setDestinations([])
    }
  }

  const calculatePrice = () => {
    if (!data.start_date || !data.end_date || !selectedCategory) return

    const startDate = new Date(data.start_date)
    const endDate = new Date(data.end_date)
    const diffTime = Math.abs(endDate.getTime() - startDate.getTime())
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1

    const basePrice = selectedCategory.base_price_per_day * diffDays

    setTotalPrice(basePrice)
  }

  const handleCategoryChange = (categoryId: string) => {
    const category = carCategories.find(cat => cat.id.toString() === categoryId)
    setSelectedCategory(category || null)
    setData('car_category_id', categoryId)
    setIsDrawerOpen(true);
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    try {
      const response = await fetch('/api/book-car', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(data),
      })

      const result = await response.json()

      if (response.ok && result.message) {
        // Show success notification
        notificationService.success(result.message)
        
        // Reset form and close drawer
        reset()
        setSelectedCategory(null)
        setTotalPrice(0)
        setPickupLocation(null)
        setIsDrawerOpen(false)
      } else {
        // Show error notification
        notificationService.error(result.message || 'Failed to create booking')
      }
    } catch (error) {
      console.error('Booking error:', error)
      notificationService.error('An unexpected error occurred. Please try again.')
    }
  }





  return (
    <WebsiteLayout page='car-rental'>
      <Head>
        <title>Car Rental - SkySlope</title>
        <meta name="description" content="Rent premium cars with experienced drivers for your Northeast India travel. Choose from Innova Crysta, Swift Dzire, Tempo Travellers and more." />
        <meta name="keywords" content="car rental, northeast india, innova rental, tempo traveller, guwahati car rental, assam car rental" />
      </Head>

      {/* Hero Section */}
      <section className="relative bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 text-white overflow-hidden animate-fade-in">
        <div className="absolute inset-0 bg-black/20" />
        <div className="relative container-modern py-16 sm:py-20 lg:py-28">
          <div className="max-w-4xl mx-auto text-center px-4">
            <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-4 sm:mb-6 animate-fade-in leading-tight">
              Premium Car Rentals
              <span className="block text-secondary-300">Northeast India</span>
            </h1>
            <p className="text-lg sm:text-xl md:text-2xl text-primary-100 mb-6 sm:mb-8 animate-fade-in px-4" style={{ animationDelay: '200ms' }}>
              Travel in comfort and style with our premium fleet and experienced drivers
            </p>
            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center items-center animate-fade-in px-4" style={{ animationDelay: '400ms' }}>
              <div className="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-3 sm:px-4 py-2 w-full sm:w-auto justify-center">
                <svg className="w-4 h-4 sm:w-5 sm:h-5 text-secondary-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span className="text-xs sm:text-sm text-center">Experienced Drivers</span>
              </div>
              <div className="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-3 sm:px-4 py-2 w-full sm:w-auto justify-center">
                <svg className="w-4 h-4 sm:w-5 sm:h-5 text-secondary-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span className="text-xs sm:text-sm text-center">Well Maintained Vehicles</span>
              </div>
              <div className="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-full px-3 sm:px-4 py-2 w-full sm:w-auto justify-center">
                <svg className="w-4 h-4 sm:w-5 sm:h-5 text-secondary-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span className="text-xs sm:text-sm text-center">24/7 Support</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Main Content */}
      <section className="py-12 sm:py-16 lg:py-20 bg-gradient-to-b from-neutral-50 to-white">
        <div className="container-modern px-4">
          <div className="max-w-6xl mx-auto">
            <div className="text-center mb-12 sm:mb-16">
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-neutral-900 mb-3 sm:mb-4 animate-fade-in px-4">
                Choose Your Perfect Ride
              </h2>
              <p className="text-base sm:text-lg text-neutral-600 max-w-2xl mx-auto animate-fade-in px-4" style={{ animationDelay: '100ms' }}>
                Select from our premium fleet of well-maintained vehicles with experienced drivers
              </p>
            </div>

            <div className="max-w-5xl mx-auto">
              {/* Vehicle Selection */}
              <div className="animate-slide-in">
                <h3 className="text-xl sm:text-2xl font-bold text-neutral-900 mb-6 sm:mb-8 px-4">Our Fleet</h3>
                <div className="space-y-4 sm:space-y-6">
                  {loading ? (
                    <div className="flex flex-col items-center justify-center py-12 px-4">
                      <div className="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mb-3"></div>
                      <span className="text-neutral-600 text-center">Loading vehicles...</span>
                    </div>
                  ) : carCategories.length > 0 ? (
                    carCategories.map((category, index) => (
                      <div
                        key={category.id}
                        className="animate-fade-in px-2 sm:px-0"
                        style={{ animationDelay: `${index * 100}ms` }}
                      >
                        <CarCategoryCard
                          category={category}
                          isSelected={selectedCategory?.id === category.id}
                          onClick={() => handleCategoryChange(category.id.toString())}
                        />
                      </div>
                    ))
                  ) : (
                    <div className="text-center py-12 px-4">
                      <svg className="w-12 h-12 text-neutral-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <h3 className="text-lg font-medium text-neutral-900 mb-2">No vehicles available</h3>
                      <p className="text-neutral-600 text-sm sm:text-base">Please check back later or contact us for availability.</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Booking Drawer */}
      <Drawer
        opened={isDrawerOpen}
        onClose={() => setIsDrawerOpen(false)}
        title={
          <div className="flex items-center justify-between w-full px-2">
            <span className="text-lg sm:text-xl font-semibold">Book Your Car</span>
          </div>
        }
        position="right"
        size="md"
        styles={{
          header: {
            backgroundColor: '#f8fafc',
            borderBottom: '1px solid #e2e8f0',
            padding: '16px'
          },
          body: {
            padding: 0,
            backgroundColor: '#ffffff'
          },
          content: {
            backgroundColor: '#ffffff'
          },
          close: {
            marginRight: '8px'
          }
        }}
      >
        <div className="p-4 sm:p-6">
          <div className="bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl p-4 sm:p-6 mb-4 sm:mb-6">
            <h3 className="text-lg sm:text-xl font-semibold mb-2">Book Your Car</h3>
            <p className="text-primary-100 text-sm sm:text-base">
              {selectedCategory
                ? `Selected: ${selectedCategory.name} - ₹${selectedCategory.base_price_per_day}/day`
                : 'Please select a vehicle to continue'
              }
            </p>
          </div>
          <CarRentForm
            selectedCategory={selectedCategory}
            totalPrice={totalPrice}
            onSubmit={handleSubmit}
            processing={processing}
            formData={data}
            formErrors={errors}
            onFormChange={(field, value) => setData(field as any, value)}
          />
        </div>
      </Drawer>

      {/* Features Section */}
      <section className="py-12 sm:py-16 lg:py-20 bg-white">
        <div className="container-modern px-4">
          <div className="text-center mb-12 sm:mb-16">
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-neutral-900 mb-3 sm:mb-4 px-4">
              Why Choose SkySlope?
            </h2>
            <p className="text-base sm:text-lg text-neutral-600 max-w-2xl mx-auto px-4">
              Experience the difference with our premium service and attention to detail
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
            {[
              {
                icon: (
                  <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                ),
                title: "Verified Drivers",
                description: "All our drivers are background verified and professionally trained"
              },
              {
                icon: (
                  <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                ),
                title: "24/7 Support",
                description: "Round-the-clock customer support for all your travel needs"
              },
              {
                icon: (
                  <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                  </svg>
                ),
                title: "Best Rates",
                description: "Competitive pricing with no hidden charges or surprise fees"
              },
              {
                icon: (
                  <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                ),
                title: "Instant Booking",
                description: "Quick and easy booking process with instant confirmation"
              }
            ].map((feature, index) => (
              <div key={index} className="text-center modern-card hover:shadow-lg transition-all duration-300 animate-fade-in p-4 sm:p-6" style={{ animationDelay: `${index * 100}ms` }}>
                <div className="w-12 h-12 sm:w-16 sm:h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4 text-primary-600">
                  {feature.icon}
                </div>
                <h3 className="text-lg sm:text-xl font-semibold text-neutral-900 mb-2">{feature.title}</h3>
                <p className="text-neutral-600 text-sm sm:text-base leading-relaxed">{feature.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

    </WebsiteLayout>
  )
}

export default CarRental