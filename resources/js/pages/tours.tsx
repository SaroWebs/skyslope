import WebsiteLayout from '@/layouts/WebsiteLayout'
import { Head } from '@inertiajs/react'
import React from 'react'
import { Tour } from '@/types'
import TourCard from '@/components/tour/TourCard'

type Props = {
  tours: Tour[]
}

const tours = ({ tours }: Props) => {
  

  return (
    <WebsiteLayout page='tours'>
        <Head>
          <title>Our Tours - SkySlope</title>
          <meta name="description" content="Explore our curated collection of premium tours and travel packages. From adventure trips to cultural experiences, find your perfect journey." />
          <meta name="keywords" content="travel tours, tour packages, adventure tours, cultural tours, guided tours" />
        </Head>
        <div className="py-12 sm:py-16 lg:py-20">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center mb-12 sm:mb-16">
              <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 sm:mb-6">
                Our Tours
              </h1>
              <p className="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto px-4">
                Discover extraordinary journeys crafted by our travel experts. Each tour is designed to create unforgettable memories and authentic experiences.
              </p>
            </div>

            {tours.length > 0 ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                {tours.map((tour) => (
                  <TourCard key={tour.id} tour={tour} />
                ))}
              </div>
            ) : (
              <div className="text-center py-16 px-4">
                <div className="text-6xl mb-4">🏔️</div>
                <h3 className="text-xl sm:text-2xl font-semibold text-gray-900 mb-2">No Tours Available</h3>
                <p className="text-gray-600 text-sm sm:text-base">Check back soon for exciting new tour packages!</p>
              </div>
            )}
          </div>
        </div>
    </WebsiteLayout>
  )
}

export default tours