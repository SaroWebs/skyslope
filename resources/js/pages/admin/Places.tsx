import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../layoutes/AdminLayout';

interface Place {
    id: number;
    name: string;
    description: string;
    lng: number | null;
    lat: number | null;
    status: string;
    media: Array<{
        id: number;
        file_path: string;
        file_type: string;
        description: string | null;
    }>;
}

interface PlacesProps {
    title: string;
    places: {
        data: Place[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Places({ title, places }: PlacesProps) {
    const { url } = usePage();

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Places Management</h2>
                        <Link
                            href="/admin/places/create"
                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                        >
                            Add New Place
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {places.data.map((place) => (
                            <div key={place.id} className="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                <div className="aspect-w-16 aspect-h-9">
                                    {place.media.length > 0 ? (
                                        <img
                                            src={`/storage/${place.media[0].file_path}`}
                                            alt={place.name}
                                            className="w-full h-48 object-cover rounded-t-lg"
                                        />
                                    ) : (
                                        <div className="w-full h-48 bg-gray-200 rounded-t-lg flex items-center justify-center">
                                            <span className="text-gray-400">No Image</span>
                                        </div>
                                    )}
                                </div>
                                <div className="p-6">
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">{place.name}</h3>
                                    <p className="text-sm text-gray-600 mb-2 line-clamp-2">{place.description}</p>

                                    <div className="space-y-2 mb-4">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Longitude:</span>
                                            <span className="font-medium">{place.lng || 'N/A'}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Latitude:</span>
                                            <span className="font-medium">{place.lat || 'N/A'}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Status:</span>
                                            <span className="font-medium capitalize">{place.status}</span>
                                        </div>
                                    </div>

                                    <div className="flex space-x-2">
                                        <Link
                                            href={`/admin/places/${place.id}`}
                                            className="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-200 text-sm font-medium text-center"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            href={`/admin/places/${place.id}/edit`}
                                            className="flex-1 bg-yellow-100 text-yellow-700 px-3 py-2 rounded-md hover:bg-yellow-200 text-sm font-medium text-center"
                                        >
                                            Edit
                                        </Link>
                                        <Link
                                            href={`/admin/places/${place.id}`}
                                            className="flex-1 bg-blue-100 text-blue-700 px-3 py-2 rounded-md hover:bg-blue-200 text-sm font-medium text-center"
                                        >
                                            Manage Media
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {places.data.length === 0 && (
                        <div className="text-center py-12">
                            <div className="text-gray-500">No places found.</div>
                            <Link
                                href="/admin/places/create"
                                className="mt-2 inline-flex items-center text-blue-600 hover:text-blue-800"
                            >
                                Create your first place →
                            </Link>
                        </div>
                    )}

                    {/* Pagination */}
                    {places.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-gray-700">
                                Showing {((places.current_page - 1) * places.per_page) + 1} to {Math.min(places.current_page * places.per_page, places.total)} of {places.total} results
                            </div>
                            <div className="flex space-x-2">
                                {places.current_page > 1 && (
                                    <Link
                                        href={`${url}?page=${places.current_page - 1}`}
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {places.current_page < places.last_page && (
                                    <Link
                                        href={`${url}?page=${places.current_page + 1}`}
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