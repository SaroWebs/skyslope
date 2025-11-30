import React from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import AdminLayout from '@/layoutes/AdminLayout';

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

interface ShowPlaceProps {
    title: string;
    user: any;
    place: Place;
}

export default function Show({ title, user, place }: ShowPlaceProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/admin/places/${place.id}/media`, {
            forceFormData: true,
            onSuccess: () => reset(),
        });
    };

    const handleDeleteMedia = (mediaId: number) => {
        if (confirm('Are you sure you want to delete this media?')) {
            router.delete(`/admin/media/${mediaId}`, {
                onSuccess: () => {
                    // Optionally refresh the page or update state
                },
            });
        }
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Place Details</h2>
                        <div className="space-x-2">
                            <Link
                                href={`/admin/places/${place.id}/edit`}
                                className="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 text-sm font-medium"
                            >
                                Edit Place
                            </Link>
                            <Link
                                href="/admin/places"
                                className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                            >
                                Back to Places
                            </Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 className="text-md font-medium text-gray-900 mb-4">Place Information</h3>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Name</dt>
                                    <dd className="text-sm text-gray-900">{place.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Description</dt>
                                    <dd className="text-sm text-gray-900">{place.description}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Longitude</dt>
                                    <dd className="text-sm text-gray-900">{place.lng || 'N/A'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Latitude</dt>
                                    <dd className="text-sm text-gray-900">{place.lat || 'N/A'}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Status</dt>
                                    <dd className="text-sm text-gray-900 capitalize">{place.status}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 className="text-md font-medium text-gray-900 mb-4">Media Management</h3>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <label htmlFor="file" className="block text-sm font-medium text-gray-700">
                                        Upload Media
                                    </label>
                                    <input
                                        type="file"
                                        id="file"
                                        onChange={(e) => setData('file', e.target.files?.[0] || null)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        accept="image/*,video/*"
                                        required
                                    />
                                    {errors.file && <div className="text-red-600 text-sm mt-1">{errors.file}</div>}
                                </div>

                                <div>
                                    <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                                        Description (Optional)
                                    </label>
                                    <input
                                        type="text"
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    {errors.description && <div className="text-red-600 text-sm mt-1">{errors.description}</div>}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50"
                                >
                                    {processing ? 'Uploading...' : 'Upload Media'}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div>
                        <h3 className="text-md font-medium text-gray-900 mb-4">Existing Media</h3>
                        {place.media.length > 0 ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {place.media.map((media) => (
                                    <div key={media.id} className="border border-gray-200 rounded-lg p-4">
                                        {media.file_type === 'image' ? (
                                            <img
                                                src={`/storage/${media.file_path}`}
                                                alt={media.description || 'Media'}
                                                className="w-full h-32 object-cover rounded-md mb-2"
                                            />
                                        ) : (
                                            <video
                                                src={`/storage/${media.file_path}`}
                                                className="w-full h-32 object-cover rounded-md mb-2"
                                                controls
                                            />
                                        )}
                                        <p className="text-sm text-gray-600">{media.description || 'No description'}</p>
                                        <button
                                            onClick={() => handleDeleteMedia(media.id)}
                                            className="mt-2 bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 text-sm font-medium"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500">No media uploaded yet.</p>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}