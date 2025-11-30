import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../layoutes/AdminLayout';

interface Booking {
    id: number;
    booking_number: string;
    status: string;
    total_amount: number;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    tour: {
        id: number;
        name: string;
    };
}

interface BookingsProps {
    title: string;
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Bookings({ title, bookings }: BookingsProps) {
    const { url } = usePage();

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'confirmed':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Bookings Management</h2>
                        <div className="flex space-x-2">
                            <select className="border border-gray-300 rounded-md px-3 py-2 text-sm">
                                <option>All Status</option>
                                <option>Pending</option>
                                <option>Confirmed</option>
                                <option>Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Booking #
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Customer
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tour
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {bookings.data.map((booking) => (
                                    <tr key={booking.id}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900">{booking.booking_number}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900">{booking.user.name}</div>
                                            <div className="text-sm text-gray-500">{booking.user.email}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-900">{booking.tour.name}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900">${booking.total_amount}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(booking.status)}`}>
                                                {booking.status}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-500">
                                                {new Date(booking.created_at).toLocaleDateString()}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={`/admin/bookings/${booking.id}`}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    href={`/admin/bookings/${booking.id}/edit`}
                                                    className="text-yellow-600 hover:text-yellow-900"
                                                >
                                                    Edit
                                                </Link>
                                                {booking.status.toLowerCase() === 'pending' && (
                                                    <button className="text-green-600 hover:text-green-900">
                                                        Confirm
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {bookings.data.length === 0 && (
                        <div className="text-center py-12">
                            <div className="text-gray-500">No bookings found.</div>
                        </div>
                    )}

                    {/* Pagination */}
                    {bookings.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-gray-700">
                                Showing {((bookings.current_page - 1) * bookings.per_page) + 1} to {Math.min(bookings.current_page * bookings.per_page, bookings.total)} of {bookings.total} results
                            </div>
                            <div className="flex space-x-2">
                                {bookings.current_page > 1 && (
                                    <Link
                                        href={`${url}?page=${bookings.current_page - 1}`}
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {bookings.current_page < bookings.last_page && (
                                    <Link
                                        href={`${url}?page=${bookings.current_page + 1}`}
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}