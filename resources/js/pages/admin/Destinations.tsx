import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../layoutes/AdminLayout';

interface Destination {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    state: string;
    region: string | null;
    type: string;
    latitude: number | null;
    longitude: number | null;
    popular_routes: string[] | null;
    distance_from_guwahati: number | null;
    estimated_travel_time: number | null;
    best_time_to_visit: string[] | null;
    attractions: string[] | null;
    images: string[] | null;
    is_active: boolean;
    sort_order: number | null;
    created_at: string;
    updated_at: string;
}

interface DestinationsProps {
    title: string;
    destinations: {
        data: Destination[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Destinations({ title, destinations }: DestinationsProps) {
    const { url } = usePage();

    const getTypeIcon = (type: string) => {
        const icons = {
            city: '🏙️',
            hill_station: '⛰️',
            beach: '🏖️',
            historical: '🏛️',
            cultural: '🎭',
            nature: '🌿',
            adventure: '🏔️',
            religious: '🕌'
        };
        return icons[type as keyof typeof icons] || '📍';
    };

    const getTypeColor = (type: string) => {
        const colors = {
            city: 'bg-blue-100 text-blue-800',
            hill_station: 'bg-green-100 text-green-800',
            beach: 'bg-cyan-100 text-cyan-800',
            historical: 'bg-yellow-100 text-yellow-800',
            cultural: 'bg-purple-100 text-purple-800',
            nature: 'bg-emerald-100 text-emerald-800',
            adventure: 'bg-orange-100 text-orange-800',
            religious: 'bg-indigo-100 text-indigo-800'
        };
        return colors[type as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const formatDistance = (distance: number | null) => {
        if (!distance) return 'N/A';
        return `${distance} km`;
    };

    const formatTravelTime = (time: number | null) => {
        if (!time) return 'N/A';
        return `${time} hours`;
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Destinations Management</h2>
                        <Link
                            href="/admin/destinations/create"
                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                        >
                            Add New Destination
                        </Link>
                    </div>

                    {/* Filters */}
                    <div className="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label htmlFor="state-filter" className="block text-sm font-medium text-gray-700 mb-1">
                                State
                            </label>
                            <select
                                id="state-filter"
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                onChange={(e) => {
                                    const url = new URL(window.location.href);
                                    if (e.target.value) {
                                        url.searchParams.set('state', e.target.value);
                                    } else {
                                        url.searchParams.delete('state');
                                    }
                                    window.location.href = url.toString();
                                }}
                                value={new URLSearchParams(window.location.search).get('state') || ''}
                            >
                                <option value="">All States</option>
                                <option value="assam">Assam</option>
                                <option value="meghalaya">Meghalaya</option>
                                <option value="manipur">Manipur</option>
                                <option value="mizoram">Mizoram</option>
                                <option value="nagaland">Nagaland</option>
                                <option value="sikkim">Sikkim</option>
                                <option value="tripura">Tripura</option>
                                <option value="arunachal_pradesh">Arunachal Pradesh</option>
                            </select>
                        </div>

                        <div>
                            <label htmlFor="type-filter" className="block text-sm font-medium text-gray-700 mb-1">
                                Type
                            </label>
                            <select
                                id="type-filter"
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                onChange={(e) => {
                                    const url = new URL(window.location.href);
                                    if (e.target.value) {
                                        url.searchParams.set('type', e.target.value);
                                    } else {
                                        url.searchParams.delete('type');
                                    }
                                    window.location.href = url.toString();
                                }}
                                value={new URLSearchParams(window.location.search).get('type') || ''}
                            >
                                <option value="">All Types</option>
                                <option value="city">City</option>
                                <option value="hill_station">Hill Station</option>
                                <option value="beach">Beach</option>
                                <option value="historical">Historical</option>
                                <option value="cultural">Cultural</option>
                                <option value="nature">Nature</option>
                                <option value="adventure">Adventure</option>
                                <option value="religious">Religious</option>
                            </select>
                        </div>

                        <div>
                            <label htmlFor="region-filter" className="block text-sm font-medium text-gray-700 mb-1">
                                Region
                            </label>
                            <select
                                id="region-filter"
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                onChange={(e) => {
                                    const url = new URL(window.location.href);
                                    if (e.target.value) {
                                        url.searchParams.set('region', e.target.value);
                                    } else {
                                        url.searchParams.delete('region');
                                    }
                                    window.location.href = url.toString();
                                }}
                                value={new URLSearchParams(window.location.search).get('region') || ''}
                            >
                                <option value="">All Regions</option>
                                <option value="northeast_india">Northeast India</option>
                                <option value="north_india">North India</option>
                                <option value="east_india">East India</option>
                                <option value="central_india">Central India</option>
                            </select>
                        </div>

                        <div>
                            <label htmlFor="active-filter" className="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select
                                id="active-filter"
                                className="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                onChange={(e) => {
                                    const url = new URL(window.location.href);
                                    if (e.target.value !== '') {
                                        url.searchParams.set('active', e.target.value);
                                    } else {
                                        url.searchParams.delete('active');
                                    }
                                    window.location.href = url.toString();
                                }}
                                value={new URLSearchParams(window.location.search).get('active') || ''}
                            >
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {destinations.data.map((destination) => (
                            <div key={destination.id} className="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                <div className="aspect-w-16 aspect-h-9">
                                    {destination.images && destination.images.length > 0 ? (
                                        <img
                                            src={destination.images[0]}
                                            alt={destination.name}
                                            className="w-full h-48 object-cover rounded-t-lg"
                                        />
                                    ) : (
                                        <div className="w-full h-48 bg-gray-200 rounded-t-lg flex items-center justify-center">
                                            <span className="text-4xl">{getTypeIcon(destination.type)}</span>
                                        </div>
                                    )}
                                </div>
                                <div className="p-6">
                                    <div className="flex items-center justify-between mb-2">
                                        <h3 className="text-lg font-medium text-gray-900">{destination.name}</h3>
                                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${getTypeColor(destination.type)}`}>
                                            {destination.type.replace('_', ' ')}
                                        </span>
                                    </div>
                                    
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm text-gray-600">{destination.state}</span>
                                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                            destination.is_active 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {destination.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                    
                                    <p className="text-sm text-gray-600 mb-3 line-clamp-2">{destination.description}</p>

                                    <div className="space-y-1 mb-4 text-sm">
                                        <div className="flex justify-between">
                                            <span className="text-gray-500">Distance:</span>
                                            <span className="font-medium">{formatDistance(destination.distance_from_guwahati)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-500">Travel Time:</span>
                                            <span className="font-medium">{formatTravelTime(destination.estimated_travel_time)}</span>
                                        </div>
                                        {destination.region && (
                                            <div className="flex justify-between">
                                                <span className="text-gray-500">Region:</span>
                                                <span className="font-medium capitalize">{destination.region.replace('_', ' ')}</span>
                                            </div>
                                        )}
                                    </div>

                                    {destination.attractions && destination.attractions.length > 0 && (
                                        <div className="mb-4">
                                            <span className="text-xs font-medium text-gray-500">Top Attractions:</span>
                                            <div className="flex flex-wrap gap-1 mt-1">
                                                {destination.attractions.slice(0, 3).map((attraction, index) => (
                                                    <span
                                                        key={index}
                                                        className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                    >
                                                        {attraction}
                                                    </span>
                                                ))}
                                                {destination.attractions.length > 3 && (
                                                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        +{destination.attractions.length - 3} more
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex space-x-2">
                                        <Link
                                            href={`/admin/destinations/${destination.id}`}
                                            className="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-200 text-sm font-medium text-center"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            href={`/admin/destinations/${destination.id}/edit`}
                                            className="flex-1 bg-yellow-100 text-yellow-700 px-3 py-2 rounded-md hover:bg-yellow-200 text-sm font-medium text-center"
                                        >
                                            Edit
                                        </Link>
                                        <form method="POST" action={`/admin/destinations/${destination.id}`} className="flex-1">
                                            <input type="hidden" name="_method" value="DELETE" />
                                            <button
                                                type="submit"
                                                className="w-full bg-red-100 text-red-700 px-3 py-2 rounded-md hover:bg-red-200 text-sm font-medium"
                                                onClick={(e) => {
                                                    if (!confirm('Are you sure you want to delete this destination?')) {
                                                        e.preventDefault();
                                                    }
                                                }}
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {destinations.data.length === 0 && (
                        <div className="text-center py-12">
                            <div className="text-gray-500">No destinations found.</div>
                            <Link
                                href="/admin/destinations/create"
                                className="mt-2 inline-flex items-center text-blue-600 hover:text-blue-800"
                            >
                                Create your first destination →
                            </Link>
                        </div>
                    )}

                    {/* Pagination */}
                    {destinations.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-gray-700">
                                Showing {((destinations.current_page - 1) * destinations.per_page) + 1} to {Math.min(destinations.current_page * destinations.per_page, destinations.total)} of {destinations.total} results
                            </div>
                            <div className="flex space-x-2">
                                {destinations.current_page > 1 && (
                                    <Link
                                        href={`${url}?page=${destinations.current_page - 1}`}
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {destinations.current_page < destinations.last_page && (
                                    <Link
                                        href={`${url}?page=${destinations.current_page + 1}`}
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