import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';

interface CarCategory {
    id: number;
    name: string;
    vehicle_type: string;
    base_price_per_day: number;
    price_per_km: number;
    is_active: boolean;
}

interface Destination {
    id: number;
    name: string;
    is_active: boolean;
}

interface Driver {
    id: number;
    name: string;
    email: string;
}

interface CreateCarRentalProps {
    title: string;
    user: any;
    car_categories: CarCategory[];
    destinations: Destination[];
    drivers: Driver[];
}

export default function Create({ title, user, car_categories, destinations, drivers }: CreateCarRentalProps) {
    const { data, setData, post, processing, errors } = useForm({
        car_category_id: '',
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        customer_address: '',
        start_date: '',
        end_date: '',
        start_time: '09:00',
        end_time: '18:00',
        pickup_location: '',
        dropoff_location: '',
        destination_details: '',
        distance_km: '',
        special_requests: '',
        status: 'pending',
        payment_status: 'pending',
        payment_method: 'cash',
        assigned_driver: '',
        vehicle_number: '',
        internal_notes: '',
        whatsapp_notification: true as boolean,
        email_notification: true as boolean,
        sms_notification: false as boolean,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/car-rentals');
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Create New Car Rental</h2>
                        <Link
                            href="/admin/car-rentals"
                            className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                        >
                            Back to Car Rentals
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 gap-6">
                            {/* Customer Information */}
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Customer Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="customer_name" className="block text-sm font-medium text-gray-700">
                                            Customer Name *
                                        </label>
                                        <input
                                            type="text"
                                            id="customer_name"
                                            value={data.customer_name}
                                            onChange={(e) => setData('customer_name', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        />
                                        {errors.customer_name && <div className="text-red-600 text-sm mt-1">{errors.customer_name}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="customer_email" className="block text-sm font-medium text-gray-700">
                                            Customer Email *
                                        </label>
                                        <input
                                            type="email"
                                            id="customer_email"
                                            value={data.customer_email}
                                            onChange={(e) => setData('customer_email', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        />
                                        {errors.customer_email && <div className="text-red-600 text-sm mt-1">{errors.customer_email}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="customer_phone" className="block text-sm font-medium text-gray-700">
                                            Customer Phone *
                                        </label>
                                        <input
                                            type="text"
                                            id="customer_phone"
                                            value={data.customer_phone}
                                            onChange={(e) => setData('customer_phone', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        />
                                        {errors.customer_phone && <div className="text-red-600 text-sm mt-1">{errors.customer_phone}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="customer_address" className="block text-sm font-medium text-gray-700">
                                            Customer Address
                                        </label>
                                        <textarea
                                            id="customer_address"
                                            value={data.customer_address}
                                            onChange={(e) => setData('customer_address', e.target.value)}
                                            rows={2}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {errors.customer_address && <div className="text-red-600 text-sm mt-1">{errors.customer_address}</div>}
                                    </div>
                                </div>
                            </div>

                            {/* Car Details */}
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Car Details</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="car_category_id" className="block text-sm font-medium text-gray-700">
                                            Car Category *
                                        </label>
                                        <select
                                            id="car_category_id"
                                            value={data.car_category_id}
                                            onChange={(e) => setData('car_category_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        >
                                            <option value="">Select Car Category</option>
                                            {car_categories.map((category) => (
                                                <option key={category.id} value={category.id}>
                                                    {category.name} - {category.vehicle_type} (₹{category.base_price_per_day}/day)
                                                </option>
                                            ))}
                                        </select>
                                        {errors.car_category_id && <div className="text-red-600 text-sm mt-1">{errors.car_category_id}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="assigned_driver" className="block text-sm font-medium text-gray-700">
                                            Assigned Driver
                                        </label>
                                        <select
                                            id="assigned_driver"
                                            value={data.assigned_driver}
                                            onChange={(e) => setData('assigned_driver', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="">No Driver Assigned</option>
                                            {drivers.map((driver) => (
                                                <option key={driver.id} value={driver.id}>
                                                    {driver.name} ({driver.email})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.assigned_driver && <div className="text-red-600 text-sm mt-1">{errors.assigned_driver}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="vehicle_number" className="block text-sm font-medium text-gray-700">
                                            Vehicle Number
                                        </label>
                                        <input
                                            type="text"
                                            id="vehicle_number"
                                            value={data.vehicle_number}
                                            onChange={(e) => setData('vehicle_number', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="e.g., AS-01-AB-1234"
                                        />
                                        {errors.vehicle_number && <div className="text-red-600 text-sm mt-1">{errors.vehicle_number}</div>}
                                    </div>
                                </div>
                            </div>

                            {/* Trip Information */}
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Trip Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label htmlFor="start_date" className="block text-sm font-medium text-gray-700">
                                            Start Date *
                                        </label>
                                        <input
                                            type="date"
                                            id="start_date"
                                            value={data.start_date}
                                            onChange={(e) => setData('start_date', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        />
                                        {errors.start_date && <div className="text-red-600 text-sm mt-1">{errors.start_date}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="end_date" className="block text-sm font-medium text-gray-700">
                                            End Date *
                                        </label>
                                        <input
                                            type="date"
                                            id="end_date"
                                            value={data.end_date}
                                            onChange={(e) => setData('end_date', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        />
                                        {errors.end_date && <div className="text-red-600 text-sm mt-1">{errors.end_date}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="start_time" className="block text-sm font-medium text-gray-700">
                                            Start Time
                                        </label>
                                        <input
                                            type="time"
                                            id="start_time"
                                            value={data.start_time}
                                            onChange={(e) => setData('start_time', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {errors.start_time && <div className="text-red-600 text-sm mt-1">{errors.start_time}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="end_time" className="block text-sm font-medium text-gray-700">
                                            End Time
                                        </label>
                                        <input
                                            type="time"
                                            id="end_time"
                                            value={data.end_time}
                                            onChange={(e) => setData('end_time', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {errors.end_time && <div className="text-red-600 text-sm mt-1">{errors.end_time}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="pickup_location" className="block text-sm font-medium text-gray-700">
                                            Pickup Location *
                                        </label>
                                        <textarea
                                            id="pickup_location"
                                            value={data.pickup_location}
                                            onChange={(e) => setData('pickup_location', e.target.value)}
                                            rows={2}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        />
                                        {errors.pickup_location && <div className="text-red-600 text-sm mt-1">{errors.pickup_location}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="dropoff_location" className="block text-sm font-medium text-gray-700">
                                            Dropoff Location
                                        </label>
                                        <textarea
                                            id="dropoff_location"
                                            value={data.dropoff_location}
                                            onChange={(e) => setData('dropoff_location', e.target.value)}
                                            rows={2}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {errors.dropoff_location && <div className="text-red-600 text-sm mt-1">{errors.dropoff_location}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="destination_details" className="block text-sm font-medium text-gray-700">
                                            Destination Details
                                        </label>
                                        <textarea
                                            id="destination_details"
                                            value={data.destination_details}
                                            onChange={(e) => setData('destination_details', e.target.value)}
                                            rows={3}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Additional destination information..."
                                        />
                                        {errors.destination_details && <div className="text-red-600 text-sm mt-1">{errors.destination_details}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="distance_km" className="block text-sm font-medium text-gray-700">
                                            Distance (km)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.1"
                                            id="distance_km"
                                            value={data.distance_km}
                                            onChange={(e) => setData('distance_km', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {errors.distance_km && <div className="text-red-600 text-sm mt-1">{errors.distance_km}</div>}
                                    </div>
                                </div>
                            </div>

                            {/* Status and Payment */}
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Status & Payment</h3>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                                            Status *
                                        </label>
                                        <select
                                            id="status"
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="confirmed">Confirmed</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                        {errors.status && <div className="text-red-600 text-sm mt-1">{errors.status}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="payment_status" className="block text-sm font-medium text-gray-700">
                                            Payment Status *
                                        </label>
                                        <select
                                            id="payment_status"
                                            value={data.payment_status}
                                            onChange={(e) => setData('payment_status', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="paid">Paid</option>
                                            <option value="failed">Failed</option>
                                            <option value="refunded">Refunded</option>
                                        </select>
                                        {errors.payment_status && <div className="text-red-600 text-sm mt-1">{errors.payment_status}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="payment_method" className="block text-sm font-medium text-gray-700">
                                            Payment Method *
                                        </label>
                                        <select
                                            id="payment_method"
                                            value={data.payment_method}
                                            onChange={(e) => setData('payment_method', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            required
                                        >
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="upi">UPI</option>
                                        </select>
                                        {errors.payment_method && <div className="text-red-600 text-sm mt-1">{errors.payment_method}</div>}
                                    </div>
                                </div>
                            </div>

                            {/* Additional Information */}
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Additional Information</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <label htmlFor="special_requests" className="block text-sm font-medium text-gray-700">
                                            Special Requests
                                        </label>
                                        <textarea
                                            id="special_requests"
                                            value={data.special_requests}
                                            onChange={(e) => setData('special_requests', e.target.value)}
                                            rows={3}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Any special requirements or requests..."
                                        />
                                        {errors.special_requests && <div className="text-red-600 text-sm mt-1">{errors.special_requests}</div>}
                                    </div>

                                    <div>
                                        <label htmlFor="internal_notes" className="block text-sm font-medium text-gray-700">
                                            Internal Notes
                                        </label>
                                        <textarea
                                            id="internal_notes"
                                            value={data.internal_notes}
                                            onChange={(e) => setData('internal_notes', e.target.value)}
                                            rows={3}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Internal notes for staff only..."
                                        />
                                        {errors.internal_notes && <div className="text-red-600 text-sm mt-1">{errors.internal_notes}</div>}
                                    </div>
                                </div>
                            </div>

                            {/* Notifications */}
                            <div className="bg-gray-50 p-4 rounded-md">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Notification Preferences</h3>
                                <div className="space-y-3">
                                    <div className="flex items-center">
                                        <input
                                            id="whatsapp_notification"
                                            name="whatsapp_notification"
                                            type="checkbox"
                                            checked={data.whatsapp_notification}
                                            onChange={(e) => setData('whatsapp_notification', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="whatsapp_notification" className="ml-2 block text-sm text-gray-900">
                                            WhatsApp Notifications
                                        </label>
                                    </div>
                                    <div className="flex items-center">
                                        <input
                                            id="email_notification"
                                            name="email_notification"
                                            type="checkbox"
                                            checked={data.email_notification}
                                            onChange={(e) => setData('email_notification', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="email_notification" className="ml-2 block text-sm text-gray-900">
                                            Email Notifications
                                        </label>
                                    </div>
                                    <div className="flex items-center">
                                        <input
                                            id="sms_notification"
                                            name="sms_notification"
                                            type="checkbox"
                                            checked={data.sms_notification}
                                            onChange={(e) => setData('sms_notification', e.target.checked)}
                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label htmlFor="sms_notification" className="ml-2 block text-sm text-gray-900">
                                            SMS Notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <Link
                                href="/admin/car-rentals"
                                className="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-sm font-medium"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50"
                            >
                                {processing ? 'Creating...' : 'Create Car Rental'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}