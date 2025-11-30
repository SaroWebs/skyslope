import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/layoutes/AdminLayout';

interface Place {
    id: number;
    name: string;
    description: string;
    lng: number | null;
    lat: number | null;
    status: string;
}

interface EditPlaceProps {
    title: string;
    user: any;
    place: Place;
}

export default function Edit({ title, user, place }: EditPlaceProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: place.name,
        description: place.description,
        lng: place.lng || '',
        lat: place.lat || '',
        status: place.status,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/places/${place.id}`);
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Edit Place</h2>
                        <Link
                            href="/admin/places"
                            className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                        >
                            Back to Places
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 gap-6">
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                    Name
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                />
                                {errors.name && <div className="text-red-600 text-sm mt-1">{errors.name}</div>}
                            </div>

                            <div>
                                <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                                    Description
                                </label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                />
                                {errors.description && <div className="text-red-600 text-sm mt-1">{errors.description}</div>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="lng" className="block text-sm font-medium text-gray-700">
                                        Longitude
                                    </label>
                                    <input
                                        type="number"
                                        step="any"
                                        id="lng"
                                        value={data.lng}
                                        onChange={(e) => setData('lng', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    {errors.lng && <div className="text-red-600 text-sm mt-1">{errors.lng}</div>}
                                </div>

                                <div>
                                    <label htmlFor="lat" className="block text-sm font-medium text-gray-700">
                                        Latitude
                                    </label>
                                    <input
                                        type="number"
                                        step="any"
                                        id="lat"
                                        value={data.lat}
                                        onChange={(e) => setData('lat', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    {errors.lat && <div className="text-red-600 text-sm mt-1">{errors.lat}</div>}
                                </div>
                            </div>

                            <div>
                                <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                                    Status
                                </label>
                                <select
                                    id="status"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                    <option value="restricted">Restricted</option>
                                </select>
                                {errors.status && <div className="text-red-600 text-sm mt-1">{errors.status}</div>}
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <Link
                                href="/admin/places"
                                className="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-sm font-medium"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50"
                            >
                                {processing ? 'Updating...' : 'Update Place'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}