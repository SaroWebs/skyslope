import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface DashboardProps {
    title: string;
    user: any;
    my_tours: any[];
    upcoming_tours: any[];
}

export default function Dashboard({ title, user, my_tours, upcoming_tours }: DashboardProps) {
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
                        {/* My Tours */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    My Assigned Tours
                                </h3>
                                <div className="space-y-3">
                                    {my_tours.length > 0 ? (
                                        my_tours.map((tourGuide) => (
                                            <div key={tourGuide.id} className="border rounded-lg p-4">
                                                <h4 className="font-medium text-gray-900">{tourGuide.tour.name}</h4>
                                                <p className="text-sm text-gray-500">
                                                    {new Date(tourGuide.tour.start_date).toLocaleDateString()} - {new Date(tourGuide.tour.end_date).toLocaleDateString()}
                                                </p>
                                                <p className="text-sm text-gray-500">{tourGuide.tour.description}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500">No tours assigned yet.</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Upcoming Tours */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Upcoming Tours
                                </h3>
                                <div className="space-y-3">
                                    {upcoming_tours.length > 0 ? (
                                        upcoming_tours.map((tour) => (
                                            <div key={tour.id} className="border rounded-lg p-4">
                                                <h4 className="font-medium text-gray-900">{tour.name}</h4>
                                                <p className="text-sm text-gray-500">
                                                    {new Date(tour.start_date).toLocaleDateString()}
                                                </p>
                                                <p className="text-sm text-gray-500">{tour.description}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-gray-500">No upcoming tours.</p>
                                    )}
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
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Link
                                    href="/tours"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    View All Tours
                                </Link>
                                <Link
                                    href="/bookings"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    My Bookings
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