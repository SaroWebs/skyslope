import React from 'react';
import { Link } from '@inertiajs/react';
import AdminLayout from '../../../layoutes/AdminLayout';

interface Tour {
    id: number;
    title: string;
    description: string;
    price: number;
    discount: number;
    available_from: string;
    available_to: string;
    guides: Array<{
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
        };
    }>;
    drivers: Array<{
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
        };
    }>;
    itineraries: Array<{
        id: number;
        day_index: number;
        time: string;
        details: string;
        place: {
            id: number;
            name: string;
            description: string;
            media: Array<{
                id: number;
                file_path: string;
                file_type: string;
            }>;
        };
    }>;
    bookings: Array<{
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
        };
        status: string;
        total_amount: number;
        created_at: string;
    }>;
}

interface ShowTourProps {
    title: string;
    user: any;
    tour: Tour;
}

export default function Show({ title, user, tour }: ShowTourProps) {
    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Tour Details</h2>
                        <div className="flex space-x-3">
                            <Link
                                href="/admin/tours"
                                className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                            >
                                Back to Tours
                            </Link>
                            <Link
                                href={`/admin/tours/${tour.id}/edit`}
                                className="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 text-sm font-medium"
                            >
                                Edit Tour
                            </Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Basic Information */}
                            <div className="bg-gray-50 rounded-lg p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Tour Name</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{tour.title}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Price</dt>
                                        <dd className="mt-1 text-sm text-gray-900">${tour.price}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Discount</dt>
                                        <dd className="mt-1 text-sm text-gray-900">{tour.discount}%</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Final Price</dt>
                                        <dd className="mt-1 text-sm text-gray-900">${(tour.price * (1 - tour.discount / 100)).toFixed(2)}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Available From</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {new Date(tour.available_from).toLocaleDateString()}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Available To</dt>
                                        <dd className="mt-1 text-sm text-gray-900">
                                            {new Date(tour.available_to).toLocaleDateString()}
                                        </dd>
                                    </div>
                                </div>
                            </div>

                            {/* Description */}
                            <div className="bg-gray-50 rounded-lg p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Description</h3>
                                <p className="text-sm text-gray-900 whitespace-pre-wrap">{tour.description}</p>
                            </div>

                            {/* Itineraries */}
                            {tour.itineraries && tour.itineraries.length > 0 && (
                                <div className="bg-gray-50 rounded-lg p-6">
                                    <div className="flex justify-between items-center mb-4">
                                        <h3 className="text-lg font-medium text-gray-900">Itineraries</h3>
                                        <Link
                                            href={`/admin/tours/${tour.id}/itineraries`}
                                            className="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700"
                                        >
                                            Manage Itineraries
                                        </Link>
                                    </div>
                                    <div className="space-y-4">
                                        {tour.itineraries.map((itinerary) => (
                                            <div key={itinerary.id} className="border-l-4 border-blue-500 pl-4">
                                                <div className="flex justify-between items-start">
                                                    <h4 className="font-medium text-gray-900">
                                                        Day {itinerary.day_index}
                                                        {itinerary.time && (
                                                            <span className="text-sm text-gray-600 ml-2">
                                                                at {itinerary.time}
                                                            </span>
                                                        )}
                                                    </h4>
                                                    <Link
                                                        href={`/admin/tours/${tour.id}/itineraries/${itinerary.id}/edit`}
                                                        className="text-blue-600 hover:text-blue-500 text-sm"
                                                    >
                                                        Edit
                                                    </Link>
                                                </div>
                                                {itinerary.place && (
                                                    <div className="mt-2">
                                                        <h5 className="text-sm font-medium text-gray-700">
                                                            Place: {itinerary.place.name}
                                                        </h5>
                                                        <p className="text-sm text-gray-600 mt-1">
                                                            {itinerary.place.description}
                                                        </p>
                                                    </div>
                                                )}
                                                {itinerary.details && (
                                                    <p className="text-sm text-gray-600 mt-2">
                                                        <span className="font-medium">Details:</span> {itinerary.details}
                                                    </p>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Staff */}
                            <div className="bg-gray-50 rounded-lg p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Staff</h3>
                                
                                {/* Guides */}
                                <div className="mb-4">
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">
                                        Guides ({tour.guides?.length || 0})
                                    </h4>
                                    {tour.guides && tour.guides.length > 0 ? (
                                        <div className="space-y-2">
                                            {tour.guides.map((guide) => (
                                                <div key={guide.id} className="text-sm">
                                                    <div className="font-medium text-gray-900">{guide.user.name}</div>
                                                    <div className="text-gray-600">{guide.user.email}</div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-500">No guides assigned</p>
                                    )}
                                </div>

                                {/* Drivers */}
                                <div>
                                    <h4 className="text-sm font-medium text-gray-700 mb-2">
                                        Drivers ({tour.drivers?.length || 0})
                                    </h4>
                                    {tour.drivers && tour.drivers.length > 0 ? (
                                        <div className="space-y-2">
                                            {tour.drivers.map((driver) => (
                                                <div key={driver.id} className="text-sm">
                                                    <div className="font-medium text-gray-900">{driver.user.name}</div>
                                                    <div className="text-gray-600">{driver.user.email}</div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-500">No drivers assigned</p>
                                    )}
                                </div>
                            </div>

                            {/* Recent Bookings */}
                            {tour.bookings && tour.bookings.length > 0 && (
                                <div className="bg-gray-50 rounded-lg p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Bookings</h3>
                                    <div className="space-y-3">
                                        {tour.bookings.slice(0, 5).map((booking) => (
                                            <div key={booking.id} className="text-sm">
                                                <div className="font-medium text-gray-900">{booking.user.name}</div>
                                                <div className="text-gray-600">${booking.total_amount}</div>
                                                <div className="flex justify-between items-center mt-1">
                                                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                        booking.status === 'confirmed' ? 'bg-green-100 text-green-800' :
                                                        booking.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-red-100 text-red-800'
                                                    }`}>
                                                        {booking.status}
                                                    </span>
                                                    <span className="text-gray-500">
                                                        {new Date(booking.created_at).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    {tour.bookings.length > 5 && (
                                        <div className="mt-3 text-sm">
                                            <Link
                                                href="/admin/bookings"
                                                className="text-blue-600 hover:text-blue-500"
                                            >
                                                View all bookings →
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}