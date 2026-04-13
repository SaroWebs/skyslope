import React from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';

interface CarRental {
    id: number;
    booking_number: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    customer_address: string;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
    pickup_location: string;
    dropoff_location: string;
    destination_details: string;
    distance_km: number;
    special_requests: string;
    status: string;
    payment_status: string;
    payment_method: string;
    assigned_driver: number | null;
    vehicle_number: string;
    internal_notes: string;
    whatsapp_notification: boolean;
    email_notification: boolean;
    sms_notification: boolean;
    total_price: number;
    base_price: number;
    distance_price: number;
    extras_price: number;
    discount_amount: number;
    number_of_days: number;
    created_at: string;
    carCategory: {
        id: number;
        name: string;
        vehicle_type: string;
        seats: number;
        has_ac: boolean;
        has_driver: boolean;
        base_price_per_day: number;
        price_per_km: number;
    };
    user: {
        id: number;
        name: string;
        email: string;
    };
    driver?: {
        id: number;
        name: string;
        email: string;
    };
    extras: Array<{
        id: number;
        name: string;
        total_price: number;
        quantity: number;
    }>;
}

interface ShowCarRentalProps {
    title: string;
    user: any;
    car_rental: CarRental;
}

export default function Show({ title, user, car_rental }: ShowCarRentalProps) {
    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this car rental? This action cannot be undone.')) {
            router.delete(`/admin/car-rentals/${car_rental.id}`);
        }
    };

    const getStatusBadge = (status: string) => {
        const badges = {
            pending: 'bg-yellow-100 text-yellow-800',
            confirmed: 'bg-blue-100 text-blue-800',
            in_progress: 'bg-green-100 text-green-800',
            completed: 'bg-gray-100 text-gray-800',
            cancelled: 'bg-red-100 text-red-800',
        };
        return badges[status as keyof typeof badges] || 'bg-gray-100 text-gray-800';
    };

    const getPaymentStatusBadge = (status: string) => {
        const badges = {
            pending: 'bg-yellow-100 text-yellow-800',
            paid: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
            refunded: 'bg-gray-100 text-gray-800',
        };
        return badges[status as keyof typeof badges] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">
                            Car Rental Details - {car_rental.booking_number}
                        </h2>
                        <div className="space-x-2">
                            <Link
                                href={`/admin/car-rentals/${car_rental.id}/edit`}
                                className="bg-yellow-600 text-white px-4 py-2 rounded-md hover:bg-yellow-700 text-sm font-medium"
                            >
                                Edit Rental
                            </Link>
                            <button
                                onClick={handleDelete}
                                className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 text-sm font-medium"
                            >
                                Delete Rental
                            </button>
                            <Link
                                href="/admin/car-rentals"
                                className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                            >
                                Back to Car Rentals
                            </Link>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        {/* Customer & Trip Information */}
                        <div className="space-y-6">
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-md font-medium text-gray-900 mb-4">Customer Information</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Name</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.customer_name}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Email</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.customer_email}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Phone</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.customer_phone}</dd>
                                    </div>
                                    {car_rental.customer_address && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Address</dt>
                                            <dd className="text-sm text-gray-900">{car_rental.customer_address}</dd>
                                        </div>
                                    )}
                                </dl>
                            </div>

                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-md font-medium text-gray-900 mb-4">Trip Details</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Pickup Location</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.pickup_location}</dd>
                                    </div>
                                    {car_rental.dropoff_location && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Dropoff Location</dt>
                                            <dd className="text-sm text-gray-900">{car_rental.dropoff_location}</dd>
                                        </div>
                                    )}
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Start Date & Time</dt>
                                        <dd className="text-sm text-gray-900">
                                            {new Date(car_rental.start_date).toLocaleDateString()} at {car_rental.start_time}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">End Date & Time</dt>
                                        <dd className="text-sm text-gray-900">
                                            {new Date(car_rental.end_date).toLocaleDateString()} at {car_rental.end_time}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Duration</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.number_of_days} day(s)</dd>
                                    </div>
                                    {car_rental.distance_km > 0 && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Distance</dt>
                                            <dd className="text-sm text-gray-900">{car_rental.distance_km} km</dd>
                                        </div>
                                    )}
                                    {car_rental.destination_details && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Destination Details</dt>
                                            <dd className="text-sm text-gray-900">{car_rental.destination_details}</dd>
                                        </div>
                                    )}
                                </dl>
                            </div>

                            {car_rental.special_requests && (
                                <div className="bg-gray-50 p-4 rounded-md">
                                    <h3 className="text-md font-medium text-gray-900 mb-4">Special Requests</h3>
                                    <p className="text-sm text-gray-900">{car_rental.special_requests}</p>
                                </div>
                            )}
                        </div>

                        {/* Car & Pricing Information */}
                        <div className="space-y-6">
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-md font-medium text-gray-900 mb-4">Car Details</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Category</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.carCategory.name}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Vehicle Type</dt>
                                        <dd className="text-sm text-gray-900 capitalize">{car_rental.carCategory.vehicle_type}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Seats</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.carCategory.seats}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Air Conditioning</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.carCategory.has_ac ? 'Yes' : 'No'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Driver Included</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.carCategory.has_driver ? 'Yes' : 'No'}</dd>
                                    </div>
                                    {car_rental.vehicle_number && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Vehicle Number</dt>
                                            <dd className="text-sm text-gray-900">{car_rental.vehicle_number}</dd>
                                        </div>
                                    )}
                                    {car_rental.driver && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Assigned Driver</dt>
                                            <dd className="text-sm text-gray-900">{car_rental.driver.name} ({car_rental.driver.email})</dd>
                                        </div>
                                    )}
                                </dl>
                            </div>

                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-md font-medium text-gray-900 mb-4">Pricing Breakdown</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Base Price ({car_rental.number_of_days} days)</dt>
                                        <dd className="text-sm text-gray-900">₹{car_rental.base_price}</dd>
                                    </div>
                                    {car_rental.distance_price > 0 && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Distance Price ({car_rental.distance_km} km)</dt>
                                            <dd className="text-sm text-gray-900">₹{car_rental.distance_price}</dd>
                                        </div>
                                    )}
                                    {car_rental.extras_price > 0 && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Extras</dt>
                                            <dd className="text-sm text-gray-900">₹{car_rental.extras_price}</dd>
                                        </div>
                                    )}
                                    {car_rental.discount_amount > 0 && (
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Discount</dt>
                                            <dd className="text-sm text-red-600">-₹{car_rental.discount_amount}</dd>
                                        </div>
                                    )}
                                    <div className="border-t border-gray-200 pt-3">
                                        <dt className="text-lg font-medium text-gray-900">Total Price</dt>
                                        <dd className="text-lg font-bold text-gray-900">₹{car_rental.total_price}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-md font-medium text-gray-900 mb-4">Status & Payment</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Status</dt>
                                        <dd>
                                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusBadge(car_rental.status)}`}>
                                                {car_rental.status.replace('_', ' ')}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Payment Status</dt>
                                        <dd>
                                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPaymentStatusBadge(car_rental.payment_status)}`}>
                                                {car_rental.payment_status}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Payment Method</dt>
                                        <dd className="text-sm text-gray-900 capitalize">{car_rental.payment_method.replace('_', ' ')}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-md font-medium text-gray-900 mb-4">Notification Preferences</h3>
                                <dl className="space-y-2">
                                    <div className="flex items-center">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${car_rental.whatsapp_notification ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                            WhatsApp: {car_rental.whatsapp_notification ? 'Enabled' : 'Disabled'}
                                        </span>
                                    </div>
                                    <div className="flex items-center">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${car_rental.email_notification ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                            Email: {car_rental.email_notification ? 'Enabled' : 'Disabled'}
                                        </span>
                                    </div>
                                    <div className="flex items-center">
                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${car_rental.sms_notification ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                            SMS: {car_rental.sms_notification ? 'Enabled' : 'Disabled'}
                                        </span>
                                    </div>
                                </dl>
                            </div>

                            {car_rental.internal_notes && (
                                <div className="bg-gray-50 p-4 rounded-md">
                                    <h3 className="text-md font-medium text-gray-900 mb-4">Internal Notes</h3>
                                    <p className="text-sm text-gray-900">{car_rental.internal_notes}</p>
                                </div>
                            )}

                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-md font-medium text-gray-900 mb-4">Booking Information</h3>
                                <dl className="space-y-3">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Created By</dt>
                                        <dd className="text-sm text-gray-900">{car_rental.user.name} ({car_rental.user.email})</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Created At</dt>
                                        <dd className="text-sm text-gray-900">
                                            {new Date(car_rental.created_at).toLocaleString()}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}