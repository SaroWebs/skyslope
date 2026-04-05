import React from 'react'
import { Head, Link } from '@inertiajs/react'

interface Availability {
  is_online?: boolean
  is_available?: boolean
  last_ping?: string
}

interface Props {
  title?: string
  availability?: Availability
}

export default function Settings({ title = 'Driver Settings', availability }: Props) {
  return (
    <div className="min-h-screen bg-gray-100">
      <Head title={title} />
      <div className="mx-auto max-w-3xl px-4 py-6">
        <div className="mb-4">
          <Link href="/driver" className="text-sm font-medium text-orange-600 hover:text-orange-700">
            ← Back to dashboard
          </Link>
        </div>

        <div className="rounded-lg bg-white p-6 shadow-sm">
          <h1 className="mb-4 text-2xl font-bold text-gray-900">{title}</h1>
          <div className="space-y-2 text-sm text-gray-700">
            <p><span className="font-semibold">Online:</span> {availability?.is_online ? 'Yes' : 'No'}</p>
            <p><span className="font-semibold">Available:</span> {availability?.is_available ? 'Yes' : 'No'}</p>
            <p>
              <span className="font-semibold">Last ping:</span>{' '}
              {availability?.last_ping ? new Date(availability.last_ping).toLocaleString() : '-'}
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
