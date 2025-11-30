import React from 'react';
import { Link } from '@inertiajs/react';
import AdminLayout from '../../../layoutes/AdminLayout';

interface Place {
    id: number;
    name: string;
    description: string;
    media: Array<{
        id: number;
        file_path: string;
        file_type: string;
    }>;
}

interface Itinerary {
    id: number;
    day_index: number;
    time: string | null;
    details: string;
    place: Place;
}

interface Tour {
    id: number;
    title: string;
    description: string;
}

interface TourItinerariesProps {
    title: string;
    user: any;
    tour: Tour;
    itineraries: Itinerary[];
}

export default function TourItineraries({ title, user, tour, itineraries }: TourItinerariesProps) {
    // Group itineraries by day
    const groupedItineraries = itineraries.reduce((groups: Record<number, Itinerary[]>, itinerary) => {
        const day = itinerary.day_index;
        if (!groups[day]) {
            groups[day] = [];
        }
        groups[day].push(itinerary);
        return groups;
    }, {});

    const sortedDays = Object.keys(groupedItineraries)
        .map(Number)
        .sort((a, b) => a - b);

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">
                            Itineraries for: {tour.title}
                        </h2>
                        <div className="flex space-x-3">
                            <Link
                                href={`/admin/tours/${tour.id}`}
                                className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                            >
                                Back to Tour
                            </Link>
                            <Link
                                href={`/admin/tours/${tour.id}/itineraries/create`}
                                className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                            >
                                Add Itinerary
                            </Link>
                        </div>
                    </div>

                    {itineraries.length === 0 ? (
                        <div className="text-center py-12">
                            <div className="text-gray-500 text-lg mb-4">No itineraries found</div>
                            <Link
                                href={`/admin/tours/${tour.id}/itineraries/create`}
                                className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                            >
                                Create First Itinerary
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-8">
                            {sortedDays.map((day) => (
                                <div key={day} className="border rounded-lg overflow-hidden">
                                    <div className="bg-blue-50 px-6 py-4 border-b">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Day {day}
                                        </h3>
                                    </div>
                                    <div className="divide-y">
                                        {groupedItineraries[day]
                                            .sort((a, b) => {
                                                if (!a.time && !b.time) return 0;
                                                if (!a.time) return 1;
                                                if (!b.time) return -1;
                                                return a.time.localeCompare(b.time);
                                            })
                                            .map((itinerary) => (
                                                <div key={itinerary.id} className="p-6 hover:bg-gray-50">
                                                    <div className="flex justify-between items-start">
                                                        <div className="flex-1">
                                                            <div className="flex items-center space-x-3">
                                                                <h4 className="text-lg font-medium text-gray-900">
                                                                    {itinerary.place.name}
                                                                </h4>
                                                                {itinerary.time && (
                                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                        {itinerary.time}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            
                                                            <p className="text-sm text-gray-600 mt-1">
                                                                {itinerary.place.description}
                                                            </p>
                                                            
                                                            {itinerary.details && (
                                                                <p className="text-sm text-gray-700 mt-2">
                                                                    <span className="font-medium">Details:</span> {itinerary.details}
                                                                </p>
                                                            )}
                                                            
                                                            {/* Media Preview */}
                                                            {itinerary.place.media && itinerary.place.media.length > 0 && (
                                                                <div className="mt-3">
                                                                    <div className="flex space-x-2">
                                                                        {itinerary.place.media.slice(0, 3).map((media) => (
                                                                            <div key={media.id} className="w-16 h-16 bg-gray-200 rounded overflow-hidden">
                                                                                {media.file_type === 'image' ? (
                                                                                    <img
                                                                                        src={`/storage/${media.file_path}`}
                                                                                        alt="Place media"
                                                                                        className="w-full h-full object-cover"
                                                                                    />
                                                                                ) : (
                                                                                    <div className="w-full h-full flex items-center justify-center text-xs text-gray-500">
                                                                                        📹
                                                                                    </div>
                                                                                )}
                                                                            </div>
                                                                        ))}
                                                                        {itinerary.place.media.length > 3 && (
                                                                            <div className="w-16 h-16 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-500">
                                                                                +{itinerary.place.media.length - 3}
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                        
                                                        <div className="flex space-x-2 ml-4">
                                                            <Link
                                                                href={`/admin/tours/${tour.id}/itineraries/${itinerary.id}`}
                                                                className="text-blue-600 hover:text-blue-500 text-sm font-medium"
                                                            >
                                                                View
                                                            </Link>
                                                            <Link
                                                                href={`/admin/tours/${tour.id}/itineraries/${itinerary.id}/edit`}
                                                                className="text-yellow-600 hover:text-yellow-500 text-sm font-medium"
                                                            >
                                                                Edit
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}