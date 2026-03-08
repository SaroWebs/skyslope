import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../layoutes/AdminLayout';

interface CarCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    vehicle_type: string;
    seats: number;
    has_ac: boolean;
    has_driver: boolean;
    base_price_per_day: number;
    price_per_km: number;
    features: string[] | null;
    images: string[] | null;
    fuel_type: string | null;
    year: number | null;
    is_active: boolean;
    sort_order: number | null;
    created_at: string;
    updated_at: string;
}

interface CarCategoriesProps {
    title: string;
    car_categories: {
        data: CarCategory[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function CarCategories({ title, car_categories }: CarCategoriesProps) {
    const { url } = usePage();

    const getVehicleTypeIcon = (type: string) => {
        const icons = {
            sedan: '🚗',
            suv: '🚙',
            hatchback: '🚗',
            convertible: '🏎️',
            van: '🚐',
            truck: '🚚'
        };
        return icons[type as keyof typeof icons] || '🚗';
    };

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(price);
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Car Categories Management</h2>
                        <Link
                            href="/admin/car-categories/create"
                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                        >
                            Add New Category
                        </Link>
                    </div>

                    {/* Filters */}
                    <div className="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label htmlFor="type-filter" className="block text-sm font-medium text-gray-700 mb-1">
                                Vehicle Type
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
                                <option value="sedan">Sedan</option>
                                <option value="suv">SUV</option>
                                <option value="hatchback">Hatchback</option>
                                <option value="convertible">Convertible</option>
                                <option value="van">Van</option>
                                <option value="truck">Truck</option>
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
                        {car_categories.data.map((category) => (
                            <div key={category.id} className="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                                <div className="aspect-w-16 aspect-h-9">
                                    {category.images && category.images.length > 0 ? (
                                        <img
                                            src={category.images[0]}
                                            alt={category.name}
                                            className="w-full h-48 object-cover rounded-t-lg"
                                        />
                                    ) : (
                                        <div className="w-full h-48 bg-gray-200 rounded-t-lg flex items-center justify-center">
                                            <span className="text-4xl">{getVehicleTypeIcon(category.vehicle_type)}</span>
                                        </div>
                                    )}
                                </div>
                                <div className="p-6">
                                    <div className="flex items-center justify-between mb-2">
                                        <h3 className="text-lg font-medium text-gray-900">{category.name}</h3>
                                        <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                            category.is_active 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {category.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                    
                                    <p className="text-sm text-gray-600 mb-2 line-clamp-2">{category.description}</p>

                                    <div className="space-y-2 mb-4">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Type:</span>
                                            <span className="font-medium capitalize">{category.vehicle_type}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Seats:</span>
                                            <span className="font-medium">{category.seats}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Daily Rate:</span>
                                            <span className="font-medium">{formatPrice(category.base_price_per_day)}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-500">Per KM:</span>
                                            <span className="font-medium">₹{category.price_per_km}</span>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-1 mb-4">
                                        {category.has_ac && (
                                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                AC
                                            </span>
                                        )}
                                        {category.has_driver && (
                                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                Driver
                                            </span>
                                        )}
                                        {category.fuel_type && (
                                            <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 capitalize">
                                                {category.fuel_type}
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex space-x-2">
                                        <Link
                                            href={`/admin/car-categories/${category.id}`}
                                            className="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-200 text-sm font-medium text-center"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            href={`/admin/car-categories/${category.id}/edit`}
                                            className="flex-1 bg-yellow-100 text-yellow-700 px-3 py-2 rounded-md hover:bg-yellow-200 text-sm font-medium text-center"
                                        >
                                            Edit
                                        </Link>
                                        <form method="POST" action={`/admin/car-categories/${category.id}`} className="flex-1">
                                            <input type="hidden" name="_method" value="DELETE" />
                                            <button
                                                type="submit"
                                                className="w-full bg-red-100 text-red-700 px-3 py-2 rounded-md hover:bg-red-200 text-sm font-medium"
                                                onClick={(e) => {
                                                    if (!confirm('Are you sure you want to delete this car category?')) {
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

                    {car_categories.data.length === 0 && (
                        <div className="text-center py-12">
                            <div className="text-gray-500">No car categories found.</div>
                            <Link
                                href="/admin/car-categories/create"
                                className="mt-2 inline-flex items-center text-blue-600 hover:text-blue-800"
                            >
                                Create your first category →
                            </Link>
                        </div>
                    )}

                    {/* Pagination */}
                    {car_categories.last_page > 1 && (
                        <div className="mt-6 flex items-center justify-between">
                            <div className="text-sm text-gray-700">
                                Showing {((car_categories.current_page - 1) * car_categories.per_page) + 1} to {Math.min(car_categories.current_page * car_categories.per_page, car_categories.total)} of {car_categories.total} results
                            </div>
                            <div className="flex space-x-2">
                                {car_categories.current_page > 1 && (
                                    <Link
                                        href={`${url}?page=${car_categories.current_page - 1}`}
                                        className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {car_categories.current_page < car_categories.last_page && (
                                    <Link
                                        href={`${url}?page=${car_categories.current_page + 1}`}
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