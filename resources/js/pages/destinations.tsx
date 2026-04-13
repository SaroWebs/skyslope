import WebsiteLayout from '@/layouts/WebsiteLayout'
import { Head } from '@inertiajs/react'
import React from 'react'
import { Place } from '@/types'
import DestinationCard from '@/components/tour/DestinationCard'

type Props = {
  places: Place[]
}

const destinations = ({ places }: Props) => {
  return (
    <WebsiteLayout page='destinations'>
        <Head>
          <title>Destinations - SkySlope</title>
          <meta name="description" content="Explore breathtaking destinations around the world. From iconic landmarks to hidden gems, discover places that will inspire your next adventure." />
          <meta name="keywords" content="travel destinations, vacation spots, tourist attractions, world travel, adventure destinations" />
        </Head>
        <div className="py-12 sm:py-16 lg:py-20">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {/* Hero Section with Improved Typography */}
            <div className="text-center mb-12 sm:mb-16">
              <h1 className="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 sm:mb-6 leading-tight">
                Destinations
              </h1>
              <p className="text-lg sm:text-xl text-gray-600 max-w-4xl mx-auto leading-relaxed px-4">
                From iconic landmarks to hidden gems, explore destinations that inspire wanderlust and create lasting memories.
              </p>
            </div>

            {places.length > 0 ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">
                {places.map((place) => (
                  <DestinationCard key={place.id} place={place} />
                ))}
              </div>
            ) : (
              <div className="text-center py-16 px-4">
                <div className="text-6xl mb-4">🌍</div>
                <h3 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">No Destinations Available</h3>
                <p className="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                  Check back soon for exciting new destinations to explore!
                </p>
              </div>
            )}
          </div>
        </div>
    </WebsiteLayout>
  )
}

export default destinations