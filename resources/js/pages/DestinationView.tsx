import WebsiteLayout from '@/layoutes/WebsiteLayout'
import { Place, Tour } from '@/types'
import { Head, Link } from '@inertiajs/react'
import { motion } from 'framer-motion'
import React, { useMemo } from 'react'

type Props = {
  place: Place
}

const getStatusStyles = (status: string) => {
  switch (status) {
    case 'available':
      return 'bg-emerald-100 text-emerald-800 ring-emerald-200'
    case 'restricted':
      return 'bg-amber-100 text-amber-800 ring-amber-200'
    case 'unavailable':
      return 'bg-rose-100 text-rose-800 ring-rose-200'
    default:
      return 'bg-gray-100 text-gray-700 ring-gray-200'
  }
}

const formatDate = (value: string) =>
  new Date(value).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })

const DestinationView = ({ place }: Props) => {
  const media = place.media ?? []
  const heroImage = media[0] ? `/storage/${media[0].file_path}` : null
  const hasCoordinates = typeof place.lat === 'number' && typeof place.lng === 'number'
  const statusLabel = place.status ? `${place.status[0].toUpperCase()}${place.status.slice(1)}` : 'Unknown'

  const featuredTours = useMemo(() => {
    const itineraries = place.itineraries ?? []
    const tourMap = new Map<number, { tour: Tour; dayCount: number }>()

    itineraries.forEach((itinerary) => {
      const tour = itinerary.tour
      if (!tour?.id) return

      const existing = tourMap.get(tour.id)
      if (!existing) {
        tourMap.set(tour.id, {
          tour,
          dayCount: itinerary.day_index || 1,
        })
        return
      }

      if ((itinerary.day_index || 0) > existing.dayCount) {
        existing.dayCount = itinerary.day_index
      }
    })

    return Array.from(tourMap.values())
  }, [place.itineraries])

  return (
    <WebsiteLayout page="destinations">
      <Head>
        <title>{place.name ? `${place.name} - SkySlope` : 'Destination - SkySlope'}</title>
        <meta name="description" content={place.description || 'Explore this destination with SkySlope'} />
      </Head>

      <section className="relative overflow-hidden">
        <div className="absolute inset-0">
          {heroImage ? (
            <img src={heroImage} alt={place.name} className="h-full w-full object-cover" />
          ) : (
            <div className="h-full w-full bg-gradient-to-br from-emerald-700 via-teal-700 to-cyan-800" />
          )}
          <div className="absolute inset-0 bg-black/50" />
        </div>

        <div className="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
          <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.45 }}>
            <p className="mb-6 text-sm font-medium uppercase tracking-[0.18em] text-emerald-200">
              Destination Spotlight
            </p>

            <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
              <div className="lg:col-span-2">
                <h1 className="text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">{place.name}</h1>
                <p className="mt-5 max-w-3xl text-base leading-relaxed text-gray-200 sm:text-lg">{place.description}</p>

                <div className="mt-7 flex flex-wrap items-center gap-3">
                  <span className={`rounded-full px-3 py-1 text-sm font-semibold ring-1 ${getStatusStyles(place.status)}`}>
                    {statusLabel}
                  </span>
                  <span className="rounded-full bg-white/15 px-3 py-1 text-sm font-medium text-white ring-1 ring-white/30">
                    {featuredTours.length} curated tours
                  </span>
                  <span className="rounded-full bg-white/15 px-3 py-1 text-sm font-medium text-white ring-1 ring-white/30">
                    {media.length} photos
                  </span>
                </div>

                <div className="mt-8 flex flex-wrap gap-3">
                  <Link
                    href="/tours"
                    className="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-gray-900 transition hover:bg-gray-100"
                  >
                    Explore Tours
                  </Link>
                  <Link
                    href="/destinations"
                    className="rounded-lg border border-white/60 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15"
                  >
                    Back to Destinations
                  </Link>
                </div>
              </div>

              <div className="self-end rounded-2xl border border-white/20 bg-white/10 p-5 backdrop-blur-sm">
                <p className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-200">Destination Facts</p>
                <div className="mt-4 space-y-4">
                  <div>
                    <p className="text-xs uppercase tracking-wide text-gray-300">Added On</p>
                    <p className="mt-1 text-sm font-medium text-white">{formatDate(place.created_at)}</p>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-wide text-gray-300">Coordinates</p>
                    <p className="mt-1 text-sm font-medium text-white">
                      {hasCoordinates ? `${place.lat.toFixed(6)}, ${place.lng.toFixed(6)}` : 'Not available'}
                    </p>
                  </div>
                  {hasCoordinates && (
                    <a
                      href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${place.lat},${place.lng}`)}`}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex w-full justify-center rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600"
                    >
                      Open in Maps
                    </a>
                  )}
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <div className="space-y-8 lg:col-span-2">
            <motion.article
              initial={{ opacity: 0, y: 24 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.45, delay: 0.1 }}
              className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
              <h2 className="text-2xl font-bold text-gray-900">About This Destination</h2>
              <p className="mt-4 text-base leading-relaxed text-gray-700">{place.description}</p>
            </motion.article>

            {media.length > 0 && (
              <motion.article
                initial={{ opacity: 0, y: 24 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.45, delay: 0.15 }}
                className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
              >
                <h2 className="text-2xl font-bold text-gray-900">Gallery</h2>
                <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                  {media.slice(0, 6).map((item, index) => (
                    <div key={item.id} className={index === 0 ? 'sm:col-span-2' : ''}>
                      <img
                        src={`/storage/${item.file_path}`}
                        alt={item.description || place.name}
                        className={`w-full rounded-xl object-cover ${index === 0 ? 'h-72' : 'h-52'}`}
                      />
                    </div>
                  ))}
                </div>
              </motion.article>
            )}

            <motion.article
              initial={{ opacity: 0, y: 24 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.45, delay: 0.2 }}
              className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
            >
              <div className="flex items-center justify-between gap-3">
                <h2 className="text-2xl font-bold text-gray-900">Tours Featuring This Destination</h2>
                <Link href="/tours" className="text-sm font-semibold text-emerald-700 hover:text-emerald-800">
                  View all tours
                </Link>
              </div>

              {featuredTours.length === 0 ? (
                <p className="mt-4 text-sm text-gray-600">No curated tours are available for this destination yet.</p>
              ) : (
                <div className="mt-5 space-y-4">
                  {featuredTours.slice(0, 5).map(({ tour, dayCount }) => (
                    <div key={tour.id} className="rounded-xl border border-gray-200 bg-gray-50 p-4">
                      <h3 className="text-lg font-semibold text-gray-900">{tour.title}</h3>
                      <p className="mt-2 line-clamp-2 text-sm text-gray-700">{tour.description}</p>
                      <div className="mt-3 flex flex-wrap items-center gap-3 text-sm">
                        <span className="rounded-full bg-emerald-100 px-2.5 py-1 font-medium text-emerald-800">
                          {dayCount} days
                        </span>
                        <span className="font-semibold text-gray-900">₹{Number(tour.price || 0).toLocaleString('en-IN')}</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </motion.article>
          </div>

          <motion.aside
            initial={{ opacity: 0, y: 24 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.45, delay: 0.25 }}
            className="h-fit rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
          >
            <h2 className="text-xl font-bold text-gray-900">Plan Your Visit</h2>
            <p className="mt-3 text-sm leading-relaxed text-gray-600">
              Make the most of your experience by checking available tours and preparing your route in advance.
            </p>

            <div className="mt-6 space-y-3">
              <Link
                href="/tours"
                className="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
              >
                Find Matching Tours
              </Link>
              <Link
                href="/book-now"
                className="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-800 transition hover:bg-gray-50"
              >
                Book Now
              </Link>
            </div>

            <div className="mt-8 border-t border-gray-200 pt-6">
              <h3 className="text-sm font-semibold uppercase tracking-[0.14em] text-gray-500">Snapshot</h3>
              <div className="mt-4 space-y-3 text-sm text-gray-700">
                <div className="flex items-center justify-between">
                  <span>Status</span>
                  <span className="font-semibold">{statusLabel}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span>Tours</span>
                  <span className="font-semibold">{featuredTours.length}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span>Photos</span>
                  <span className="font-semibold">{media.length}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span>Added</span>
                  <span className="font-semibold">{formatDate(place.created_at)}</span>
                </div>
              </div>
            </div>
          </motion.aside>
        </div>
      </section>
    </WebsiteLayout>
  )
}

export default DestinationView
