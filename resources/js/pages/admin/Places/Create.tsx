import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/layoutes/AdminLayout';
import LocationInput from '@/components/ui/LocationInput';

interface CreatePlaceProps {
    title: string;
    user: any;
}

interface SearchResult {
    id: string;
    name: string;
    address: string;
    type: string;
    lat?: number;
    lng?: number;
}

export default function Create({ title, user }: CreatePlaceProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        lng: '',
        lat: '',
        status: 'available',
    });

    const handleLocationSelect = (location: SearchResult) => {
        setData('name', location.name);
        setData('lat', location.lat?.toString() || '');
        setData('lng', location.lng?.toString() || '');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/places');
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Create New Place</h2>
                        <Link
                            href="/admin/places"
                            className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                        >
                            Back to Places
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 gap-6">
                            <LocationInput
                                label="Location"
                                placeholder="Search for a location..."
                                value={data.name}
                                onChange={(value) => setData('name', value)}
                                onLocationSelect={handleLocationSelect}
                                error={errors.name}
                                required
                            />

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
                                        readOnly
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
                                        readOnly
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
                                {processing ? 'Creating...' : 'Create Place'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}