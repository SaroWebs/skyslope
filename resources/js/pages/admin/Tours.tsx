import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../layoutes/AdminLayout';

interface Tour {
    id: number;
    title: string;
    description: string;
    price: number;
    available_from: string;
    available_to: string;
    itineraries_count?: number;
    guides: Array<{
        id: number;
        user: {
            id: number;
            name: string;
        };
    }>;
    drivers: Array<{
        id: number;
        user: {
            id: number;
            name: string;
        };
    }>;
}

interface ToursProps {
    title: string;
    tours: {
        data: Tour[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Tours({ title, tours }: ToursProps) {
    const { url } = usePage();
    console.log(tours);

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Tours Management</h2>
                        <Link
                            href="/admin/tours/create"
                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                        >
                            Add New Tour
                        </Link>
                    </div>

                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Tour Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Price
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Duration
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Available Dates
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Staff
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {tours.data.map((tour) => (
                                        <tr key={tour.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">{tour.title}</div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="text-sm text-gray-600 max-w-xs truncate" title={tour.description}>
                                                    {tour.description}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">${tour.price}</div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">
                                                    {tour.itineraries_count ? `${tour.itineraries_count} days` : 'N/A'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-900">
                                                    {new Date(tour.available_from).toLocaleDateString()} - {new Date(tour.available_to).toLocaleDateString()}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-600">
                                                    <div>Guides: {tour.guides.length}</div>
                                                    <div>Drivers: {tour.drivers.length}</div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div className="flex space-x-2">
                                                    <Link
                                                        href={`/admin/tours/${tour.id}`}
                                                        className="bg-gray-100 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-200 text-xs font-medium"
                                                    >
                                                        View
                                                    </Link>
                                                    <Link
                                                        href={`/admin/tours/${tour.id}/edit`}
                                                        className="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-md hover:bg-yellow-200 text-xs font-medium"
                                                    >
                                                        Edit
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {tours.data.length === 0 && (
                        <div className="text-center py-12">
                            <div className="text-gray-500">No tours found.</div>
                            <Link
                                href="/admin/tours/create"
                                className="mt-2 inline-flex items-center text-blue-600 hover:text-blue-800"
                            >
                                Create your first tour →
                            </Link>
                        </div>
                    )}

                    {/* Pagination */}
                    {tours.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-gray-700">
                                Showing {((tours.current_page - 1) * tours.per_page) + 1} to {Math.min(tours.current_page * tours.per_page, tours.total)} of {tours.total} results
                            </div>
                            <div className="flex space-x-2">
                                {tours.current_page > 1 && (
                                    <Link
                                        href={`${url}?page=${tours.current_page - 1}`}
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {tours.current_page < tours.last_page && (
                                    <Link
                                        href={`${url}?page=${tours.current_page + 1}`}
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