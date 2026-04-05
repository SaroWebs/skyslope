import WebsiteLayout from '@/layoutes/WebsiteLayout'
import { Head, useForm } from '@inertiajs/react'
import React, { useEffect, useMemo, useState } from 'react'
import { Drawer } from '@mantine/core'
import CarCategoryCard from '@/components/car_rent/CarCategoryCard'
import CarRentForm from '@/components/car_rent/CarRentForm'
import { notificationService } from '@/services/notification'

interface Location {
  lat: number
  lng: number
  address: string
}

interface SearchResult {
  id: string
  name: string
  address: string
  type: string
  lat?: number
  lng?: number
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

const CarRental = () => {
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
  const [selectedCategory, setSelectedCategory] = useState<CarCategory | null>(null)
  const [totalPrice, setTotalPrice] = useState<number>(0)
  const [loading, setLoading] = useState<boolean>(true)
  const [pickupLocation, setPickupLocation] = useState<Location | null>(null)
  const [selectedLocation, setSelectedLocation] = useState<SearchResult | null>(null)
  const [isDrawerOpen, setIsDrawerOpen] = useState<boolean>(false)
  const [searchQuery, setSearchQuery] = useState<string>('')
  const [vehicleTypeFilter, setVehicleTypeFilter] = useState<string>('all')
  const [acFilter, setAcFilter] = useState<string>('all')
  const [seatFilter, setSeatFilter] = useState<string>('all')
  const [maxPriceFilter, setMaxPriceFilter] = useState<string>('')
  const [sortBy, setSortBy] = useState<string>('price-asc')
  const [currentPage, setCurrentPage] = useState<number>(1)
  const itemsPerPage = 6

  useEffect(() => {
    fetchCarCategories()
  }, [])

  useEffect(() => {
    if (data.start_date && data.end_date && selectedCategory) {
      calculatePrice()
    }
  }, [data.start_date, data.end_date, selectedCategory])

  const fetchCarCategories = async () => {
    try {
      const response = await fetch('/api/car-categories/active')
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

  const resetFilters = () => {
    setSearchQuery('')
    setVehicleTypeFilter('all')
    setAcFilter('all')
    setSeatFilter('all')
    setMaxPriceFilter('')
    setSortBy('price-asc')
    setCurrentPage(1)
  }

  const handleLocationSelect = (location: SearchResult) => {
    setSelectedLocation(location)
    setPickupLocation({
      lat: location.lat || 0,
      lng: location.lng || 0,
      address: location.address
    })
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
        setSelectedLocation(null)
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

  const filteredAndSortedCategories = useMemo(() => {
    const normalizedQuery = searchQuery.trim().toLowerCase()

    const filtered = carCategories.filter((category) => {
      const matchesSearch =
        normalizedQuery.length === 0 ||
        category.name.toLowerCase().includes(normalizedQuery) ||
        category.description.toLowerCase().includes(normalizedQuery) ||
        category.vehicle_type.toLowerCase().includes(normalizedQuery) ||
        category.features?.some((feature) => feature.toLowerCase().includes(normalizedQuery))

      const matchesVehicleType =
        vehicleTypeFilter === 'all' || category.vehicle_type === vehicleTypeFilter

      const matchesAC =
        acFilter === 'all' ||
        (acFilter === 'ac' && category.has_ac) ||
        (acFilter === 'non-ac' && !category.has_ac)

      const matchesSeats =
        seatFilter === 'all' ||
        (seatFilter === '4' && category.seats <= 4) ||
        (seatFilter === '5-6' && category.seats >= 5 && category.seats <= 6) ||
        (seatFilter === '7+' && category.seats >= 7)

      const maxPrice = Number(maxPriceFilter || 0)
      const matchesPrice = !maxPrice || category.base_price_per_day <= maxPrice

      return matchesSearch && matchesVehicleType && matchesAC && matchesSeats && matchesPrice
    })

    return filtered.sort((a, b) => {
      switch (sortBy) {
        case 'price-desc':
          return b.base_price_per_day - a.base_price_per_day
        case 'seats-desc':
          return b.seats - a.seats
        case 'name-asc':
          return a.name.localeCompare(b.name)
        case 'price-asc':
        default:
          return a.base_price_per_day - b.base_price_per_day
      }
    })
  }, [carCategories, searchQuery, vehicleTypeFilter, acFilter, seatFilter, maxPriceFilter, sortBy])

  const totalPages = Math.max(1, Math.ceil(filteredAndSortedCategories.length / itemsPerPage))

  const paginatedCategories = useMemo(() => {
    const startIndex = (currentPage - 1) * itemsPerPage
    return filteredAndSortedCategories.slice(startIndex, startIndex + itemsPerPage)
  }, [filteredAndSortedCategories, currentPage])

  useEffect(() => {
    setCurrentPage(1)
  }, [searchQuery, vehicleTypeFilter, acFilter, seatFilter, maxPriceFilter, sortBy])

  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages)
    }
  }, [currentPage, totalPages])

  const vehicleTypes = useMemo(() => {
    return Array.from(new Set(carCategories.map((category) => category.vehicle_type)))
  }, [carCategories])

  const activeFilterCount = [
    searchQuery.trim().length > 0,
    vehicleTypeFilter !== 'all',
    acFilter !== 'all',
    seatFilter !== 'all',
    maxPriceFilter.trim().length > 0,
  ].filter(Boolean).length

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

                <div className="mb-6 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-5">
                  <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-6">
                    <input
                      type="text"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      placeholder="Search cars, type, features..."
                      className="lg:col-span-2 h-11 rounded-lg border border-neutral-300 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
                    />

                    <select
                      value={vehicleTypeFilter}
                      onChange={(e) => setVehicleTypeFilter(e.target.value)}
                      className="h-11 rounded-lg border border-neutral-300 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
                    >
                      <option value="all">All Types</option>
                      {vehicleTypes.map((type) => (
                        <option key={type} value={type}>
                          {type.replace('_', ' ')}
                        </option>
                      ))}
                    </select>

                    <select
                      value={acFilter}
                      onChange={(e) => setAcFilter(e.target.value)}
                      className="h-11 rounded-lg border border-neutral-300 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
                    >
                      <option value="all">AC / Non-AC</option>
                      <option value="ac">AC</option>
                      <option value="non-ac">Non-AC</option>
                    </select>

                    <select
                      value={seatFilter}
                      onChange={(e) => setSeatFilter(e.target.value)}
                      className="h-11 rounded-lg border border-neutral-300 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
                    >
                      <option value="all">All Seats</option>
                      <option value="4">Up to 4</option>
                      <option value="5-6">5 to 6</option>
                      <option value="7+">7+</option>
                    </select>

                    <input
                      type="number"
                      min="0"
                      value={maxPriceFilter}
                      onChange={(e) => setMaxPriceFilter(e.target.value)}
                      placeholder="Max ₹/day"
                      className="h-11 rounded-lg border border-neutral-300 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
                    />
                  </div>

                  <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-sm text-neutral-600">
                      Showing <span className="font-semibold text-neutral-900">{filteredAndSortedCategories.length}</span> of{' '}
                      <span className="font-semibold text-neutral-900">{carCategories.length}</span> cars
                      {activeFilterCount > 0 && (
                        <span className="ml-2 rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-700">
                          {activeFilterCount} filter{activeFilterCount > 1 ? 's' : ''} active
                        </span>
                      )}
                    </div>

                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                      <select
                        value={sortBy}
                        onChange={(e) => setSortBy(e.target.value)}
                        className="h-10 rounded-lg border border-neutral-300 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-100"
                      >
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                        <option value="seats-desc">Seats: High to Low</option>
                        <option value="name-asc">Name: A to Z</option>
                      </select>

                      <button
                        type="button"
                        onClick={resetFilters}
                        className="h-10 rounded-lg border border-neutral-300 px-3 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                      >
                        Reset Filters
                      </button>
                    </div>
                  </div>
                </div>

                <div className="space-y-4 sm:space-y-6">
                  {loading ? (
                    <div className="flex flex-col items-center justify-center py-12 px-4">
                      <div className="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mb-3"></div>
                      <span className="text-neutral-600 text-center">Loading vehicles...</span>
                    </div>
                  ) : filteredAndSortedCategories.length > 0 ? (
                    paginatedCategories.map((category, index) => (
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
                      <h3 className="text-lg font-medium text-neutral-900 mb-2">No cars match your filters</h3>
                      <p className="text-neutral-600 text-sm sm:text-base">Try adjusting filters or clear search terms to see more results.</p>
                    </div>
                  )}
                </div>

                {!loading && filteredAndSortedCategories.length > 0 && (
                  <div className="mt-8 flex flex-col items-center justify-between gap-3 rounded-xl border border-neutral-200 bg-white p-4 sm:flex-row">
                    <div className="text-sm text-neutral-600">
                      Page <span className="font-semibold text-neutral-900">{currentPage}</span> of{' '}
                      <span className="font-semibold text-neutral-900">{totalPages}</span>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => setCurrentPage((prev) => Math.max(1, prev - 1))}
                        disabled={currentPage === 1}
                        className="rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                      >
                        Previous
                      </button>
                      <button
                        type="button"
                        onClick={() => setCurrentPage((prev) => Math.min(totalPages, prev + 1))}
                        disabled={currentPage === totalPages}
                        className="rounded-lg border border-neutral-300 px-3 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:cursor-not-allowed disabled:opacity-50"
                      >
                        Next
                      </button>
                    </div>
                  </div>
                )}
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
            onLocationSelect={handleLocationSelect}
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
