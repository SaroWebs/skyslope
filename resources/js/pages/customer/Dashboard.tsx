import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface DashboardProps {
    title: string;
    user: any;
    my_bookings: any[];
    ride_bookings?: any[];
    available_tours: any[];
}

const getRideStatusColor = (status: string) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800'
        case 'confirmed':
            return 'bg-blue-100 text-blue-800'
        case 'driver_assigned':
            return 'bg-indigo-100 text-indigo-800'
        case 'driver_arriving':
            return 'bg-purple-100 text-purple-800'
        case 'pickup':
            return 'bg-cyan-100 text-cyan-800'
        case 'in_transit':
            return 'bg-green-100 text-green-800'
        case 'completed':
            return 'bg-emerald-100 text-emerald-800'
        case 'cancelled':
            return 'bg-red-100 text-red-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

export default function Dashboard({ title, user, my_bookings, ride_bookings = [], available_tours }: DashboardProps) {
    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-gray-100">
                {/* Header */}
                <div className="bg-white shadow">
                    <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center">
                            <h1 className="text-3xl font-bold text-gray-900">{title}</h1>
                            <div className="flex items-center space-x-4">
                                <span className="text-gray-700">Welcome, {user.name}</span>
                                <Link
                                    href="/logout"
                                    method="post"
                                    className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700"
                                >
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* My Tour Bookings */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    My Tour Bookings
                                </h3>
                                <div className="space-y-3">
                                    {my_bookings.length > 0 ? (
                                        my_bookings.map((booking) => (
                                            <div key={booking.id} className="border rounded-lg p-4">
                                                <h4 className="font-medium text-gray-900">{booking.tour.name}</h4>
                                                <p className="text-sm text-gray-500">
                                                    Booked on: {new Date(booking.created_at).toLocaleDateString()}
                                                </p>
                                                <p className="text-sm text-gray-500">
                                                    Tour Date: {new Date(booking.tour.start_date).toLocaleDateString()}
                                                </p>
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                    booking.status === 'confirmed'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-yellow-100 text-yellow-800'
                                                }`}>
                                                    {booking.status}
                                                </span>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500">No tour bookings yet.</p>
                                    )}
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href="/book-now"
                                        className="text-sm text-blue-600 hover:text-blue-500"
                                    >
                                        Book a new tour →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* My Ride Bookings */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    My Ride Bookings
                                </h3>
                                <div className="space-y-3">
                                    {ride_bookings.length > 0 ? (
                                        ride_bookings.slice(0, 3).map((booking) => (
                                            <div key={booking.id} className="border rounded-lg p-4">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <h4 className="font-medium text-gray-900">Ride #{booking.booking_number}</h4>
                                                        <p className="text-sm text-gray-500">
                                                            {booking.pickup_location}
                                                        </p>
                                                        {booking.dropoff_location && (
                                                            <p className="text-sm text-gray-500">
                                                                to {booking.dropoff_location}
                                                            </p>
                                                        )}
                                                        <p className="text-sm text-gray-500">
                                                            {new Date(booking.scheduled_at).toLocaleDateString()}
                                                        </p>
                                                    </div>
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRideStatusColor(booking.status)}`}>
                                                        {booking.status.replace('_', ' ')}
                                                    </span>
                                                </div>
                                                <div className="mt-2">
                                                    <Link
                                                        href={`/ride-bookings/${booking.id}`}
                                                        className="text-sm text-blue-600 hover:text-blue-500"
                                                    >
                                                        View Details →
                                                    </Link>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500">No ride bookings yet.</p>
                                    )}
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href="/ride-booking"
                                        className="text-sm text-blue-600 hover:text-blue-500"
                                    >
                                        Book a new ride →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        {/* Available Tours */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Available Tours
                                </h3>
                                <div className="space-y-3">
                                    {available_tours.length > 0 ? (
                                        available_tours.map((tour) => (
                                            <div key={tour.id} className="border rounded-lg p-4">
                                                <h4 className="font-medium text-gray-900">{tour.name}</h4>
                                                <p className="text-sm text-gray-500">
                                                    {new Date(tour.start_date).toLocaleDateString()} - {new Date(tour.end_date).toLocaleDateString()}
                                                </p>
                                                <p className="text-sm text-gray-500">{tour.description}</p>
                                                <div className="mt-2">
                                                    <Link
                                                        href={`/book-now?tour=${tour.id}`}
                                                        className="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"
                                                    >
                                                        Book Now
                                                    </Link>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500">No tours available at the moment.</p>
                                    )}
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href="/tours"
                                        className="text-sm text-blue-600 hover:text-blue-500"
                                    >
                                        View all tours →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="mt-8 bg-white shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Quick Actions
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <Link
                                    href="/tours"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    Browse Tours
                                </Link>
                                <Link
                                    href="/ride-booking"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                                >
                                    Book a Ride
                                </Link>
                                <Link
                                    href="/destinations"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    View Destinations
                                </Link>
                                <Link
                                    href="/contact"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Contact Support
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}