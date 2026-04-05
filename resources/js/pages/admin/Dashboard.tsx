import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../layoutes/AdminLayout';

interface DashboardProps {
    title: string;
    user: any;
    stats: {
        total_users: number;
        total_tours: number;
        total_bookings: number;
        total_ride_bookings?: number;
        total_places: number;
        active_tours?: number;
        recent_bookings?: any[];
    };
    recent_users: any[];
    upcoming_tours: any[];
}

export default function Dashboard({ title, user, stats, recent_users, upcoming_tours }: DashboardProps) {
    return (
        <AdminLayout title={title}>
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                            <span className="text-white font-bold text-sm">👥</span>
                                        </div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Users
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.total_users}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                            <span className="text-white font-bold text-sm">🗺️</span>
                                        </div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Tours
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.total_tours}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                            <span className="text-white font-bold text-sm">📋</span>
                                        </div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Bookings
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.total_bookings}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                            <span className="text-white font-bold text-xs">RB</span>
                                        </div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Ride Bookings
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.total_ride_bookings ?? 0}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                            <span className="text-white font-bold text-sm">📍</span>
                                        </div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Places
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {stats.total_places}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity and Quick Actions */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Recent Users */}
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Recent Users
                                </h3>
                                <div className="space-y-3">
                                    {recent_users.map((user) => (
                                        <div key={user.id} className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">{user.name}</p>
                                                <p className="text-sm text-gray-500">{user.email}</p>
                                            </div>
                                            <div className="flex space-x-2">
                                                {user.roles.map((role: any) => (
                                                    <span key={role.id} className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {role.name}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href="/admin/users"
                                        className="text-sm text-blue-600 hover:text-blue-500"
                                    >
                                        View all users →
                                    </Link>
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
                                    {upcoming_tours.map((tour) => (
                                        <div key={tour.id} className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">{tour.name}</p>
                                                <p className="text-sm text-gray-500">
                                                    {new Date(tour.start_date).toLocaleDateString()}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm text-gray-500">
                                                    {tour.guides?.length || 0} guides
                                                </p>
                                                <p className="text-sm text-gray-500">
                                                    {tour.drivers?.length || 0} drivers
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href="/admin/tours"
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
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <Link
                                    href="/admin/users/create"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    Add New User
                                </Link>
                                <Link
                                    href="/admin/tours"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Manage Tours
                                </Link>
                                <Link
                                    href="/admin/bookings"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    View Bookings
                                </Link>
                                <Link
                                    href="/admin/ride-bookings"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    Manage Ride Bookings
                                </Link>
                                <Link
                                    href="/admin/settings"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    System Settings
                                </Link>
                            </div>
                        </div>
                    </div>
        </AdminLayout>
        );
    }
