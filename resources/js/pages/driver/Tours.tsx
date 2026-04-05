import React from 'react'
import { Head, Link } from '@inertiajs/react'

interface TourItem {
  id: number
  tour?: {
    name: string
    destination?: string
    available_from?: string
  }
}

interface Tour {
  id: number
  name: string
  destination?: string
  available_from?: string
}

interface Props {
  title?: string
  my_tours: TourItem[]
  upcoming_tours: Tour[]
}

export default function Tours({ title = 'My Tours', my_tours = [], upcoming_tours = [] }: Props) {
  return (
    <div className="min-h-screen bg-gray-100">
      <Head title={title} />
      <div className="mx-auto max-w-5xl px-4 py-6">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
          <Link href="/driver" className="text-sm font-medium text-orange-600 hover:text-orange-700">
            Back to dashboard
          </Link>
        </div>

        <div className="grid gap-4 md:grid-cols-2">
          <div className="rounded-lg bg-white p-4 shadow-sm">
            <h2 className="mb-3 text-lg font-semibold text-gray-900">Assigned Tours</h2>
            <div className="space-y-2">
              {my_tours.length ? my_tours.map((item) => (
                <div key={item.id} className="rounded border border-gray-100 p-3">
                  <p className="font-medium text-gray-900">{item.tour?.name || 'Tour'}</p>
                  <p className="text-sm text-gray-600">{item.tour?.destination || '-'}</p>
                </div>
              )) : <p className="text-sm text-gray-500">No assigned tours.</p>}
            </div>
          </div>

          <div className="rounded-lg bg-white p-4 shadow-sm">
            <h2 className="mb-3 text-lg font-semibold text-gray-900">Upcoming Tours</h2>
            <div className="space-y-2">
              {upcoming_tours.length ? upcoming_tours.map((tour) => (
                <div key={tour.id} className="rounded border border-gray-100 p-3">
                  <p className="font-medium text-gray-900">{tour.name}</p>
                  <p className="text-sm text-gray-600">{tour.destination || '-'}</p>
                </div>
              )) : <p className="text-sm text-gray-500">No upcoming tours.</p>}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
