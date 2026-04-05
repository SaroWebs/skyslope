import React from 'react'
import { Head, Link } from '@inertiajs/react'
import DriverWallet from '@/components/wallet/DriverWallet'

interface Props {
  title?: string
}

export default function Wallet({ title = 'Driver Wallet' }: Props) {
  return (
    <div className="min-h-screen bg-gray-100">
      <Head title={title} />

      <div className="mx-auto max-w-6xl px-4 py-6">
        <div className="mb-4">
          <Link href="/driver" className="text-sm font-medium text-orange-600 hover:text-orange-700">
            ← Back to dashboard
          </Link>
        </div>
        <DriverWallet />
      </div>
    </div>
  )
}
