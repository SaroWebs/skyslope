import React, { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../../layouts/AdminLayout';

interface Place {
    id: number;
    name: string;
    description: string;
}

interface Tour {
    id: number;
    title: string;
    description: string;
}

interface Itinerary {
    id: number;
    day_index: number;
    time: string;
    details: string;
    place: Place;
}

interface EditItineraryProps {
    title: string;
    user: any;
    tour: Tour;
    itinerary: Itinerary;
    places: Place[];
}

export default function EditItinerary({ title, user, tour, itinerary, places }: EditItineraryProps) {
    const { data, setData, put, processing, errors } = useForm({
        day_index: itinerary.day_index.toString(),
        time: itinerary.time || '',
        place_id: itinerary.place.id.toString(),
        details: itinerary.details || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/tours/${tour.id}/itineraries/${itinerary.id}`);
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">
                            Edit Itinerary for: {tour.title}
                        </h2>
                        <Link
                            href={`/admin/tours/${tour.id}/itineraries`}
                            className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                        >
                            Back to Itineraries
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Day Index */}
                            <div>
                                <label htmlFor="day_index" className="block text-sm font-medium text-gray-700">
                                    Day Number *
                                </label>
                                <input
                                    type="number"
                                    id="day_index"
                                    min="1"
                                    value={data.day_index}
                                    onChange={(e) => setData('day_index', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Enter day number (e.g., 1, 2, 3)"
                                />
                                {errors.day_index && (
                                    <p className="mt-1 text-sm text-red-600">{errors.day_index}</p>
                                )}
                            </div>

                            {/* Time */}
                            <div>
                                <label htmlFor="time" className="block text-sm font-medium text-gray-700">
                                    Time
                                </label>
                                <input
                                    type="time"
                                    id="time"
                                    value={data.time}
                                    onChange={(e) => setData('time', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Select time (optional)"
                                />
                                {errors.time && (
                                    <p className="mt-1 text-sm text-red-600">{errors.time}</p>
                                )}
                            </div>

                            {/* Place */}
                            <div className="lg:col-span-2">
                                <label htmlFor="place_id" className="block text-sm font-medium text-gray-700">
                                    Place *
                                </label>
                                <select
                                    id="place_id"
                                    value={data.place_id}
                                    onChange={(e) => setData('place_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option value="">Select a place</option>
                                    {places.map((place) => (
                                        <option key={place.id} value={place.id}>
                                            {place.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.place_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.place_id}</p>
                                )}
                                
                                {/* Place Description */}
                                {data.place_id && (
                                    <div className="mt-2 p-3 bg-gray-50 rounded-md">
                                        {(() => {
                                            const selectedPlace = places.find(p => p.id === parseInt(data.place_id));
                                            return selectedPlace ? (
                                                <p className="text-sm text-gray-600">
                                                    <span className="font-medium">Description:</span> {selectedPlace.description}
                                                </p>
                                            ) : null;
                                        })()}
                                    </div>
                                )}
                            </div>

                            {/* Details */}
                            <div className="lg:col-span-2">
                                <label htmlFor="details" className="block text-sm font-medium text-gray-700">
                                    Details
                                </label>
                                <textarea
                                    id="details"
                                    rows={4}
                                    value={data.details}
                                    onChange={(e) => setData('details', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Additional details about this itinerary item..."
                                />
                                {errors.details && (
                                    <p className="mt-1 text-sm text-red-600">{errors.details}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end space-x-3">
                            <Link
                                href={`/admin/tours/${tour.id}/itineraries`}
                                className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Updating...' : 'Update Itinerary'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}