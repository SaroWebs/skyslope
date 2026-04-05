import React from 'react'
import { Head, Link } from '@inertiajs/react'

interface User {
  name: string
  email: string
  phone?: string
}

interface Availability {
  vehicle_type?: string
  vehicle_number?: string
  rating?: number
}

interface Props {
  title?: string
  user: User
  availability?: Availability
}

export default function Profile({ title = 'Driver Profile', user, availability }: Props) {
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
            <p><span className="font-semibold">Name:</span> {user.name}</p>
            <p><span className="font-semibold">Email:</span> {user.email}</p>
            <p><span className="font-semibold">Phone:</span> {user.phone || '-'}</p>
            <p><span className="font-semibold">Vehicle:</span> {availability?.vehicle_number || '-'}</p>
            <p><span className="font-semibold">Vehicle Type:</span> {availability?.vehicle_type || '-'}</p>
            <p><span className="font-semibold">Rating:</span> {availability?.rating ?? 'N/A'}</p>
          </div>
        </div>
      </div>
    </div>
  )
}
