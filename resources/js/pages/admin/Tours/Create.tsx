import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../layoutes/AdminLayout';

interface CreateTourProps {
    title: string;
    user: any;
}

export default function Create({ title, user }: CreateTourProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        price: '',
        discount: '',
        available_from: '',
        available_to: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/tours');
    };

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Create New Tour</h2>
                        <Link
                            href="/admin/tours"
                            className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 text-sm font-medium"
                        >
                            Back to Tours
                        </Link>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="grid grid-cols-1 gap-6">
                            <div>
                                <label htmlFor="title" className="block text-sm font-medium text-gray-700">
                                    Tour Name
                                </label>
                                <input
                                    type="text"
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    required
                                />
                                {errors.title && <div className="text-red-600 text-sm mt-1">{errors.title}</div>}
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
                                    <label htmlFor="price" className="block text-sm font-medium text-gray-700">
                                        Price ($)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        id="price"
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    />
                                    {errors.price && <div className="text-red-600 text-sm mt-1">{errors.price}</div>}
                                </div>

                                <div>
                                    <label htmlFor="discount" className="block text-sm font-medium text-gray-700">
                                        Discount (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        id="discount"
                                        value={data.discount}
                                        onChange={(e) => setData('discount', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    />
                                    {errors.discount && <div className="text-red-600 text-sm mt-1">{errors.discount}</div>}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="available_from" className="block text-sm font-medium text-gray-700">
                                        Available From
                                    </label>
                                    <input
                                        type="date"
                                        id="available_from"
                                        value={data.available_from}
                                        onChange={(e) => setData('available_from', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    />
                                    {errors.available_from && <div className="text-red-600 text-sm mt-1">{errors.available_from}</div>}
                                </div>

                                <div>
                                    <label htmlFor="available_to" className="block text-sm font-medium text-gray-700">
                                        Available To
                                    </label>
                                    <input
                                        type="date"
                                        id="available_to"
                                        value={data.available_to}
                                        onChange={(e) => setData('available_to', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    />
                                    {errors.available_to && <div className="text-red-600 text-sm mt-1">{errors.available_to}</div>}
                                </div>
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <Link
                                href="/admin/tours"
                                className="mr-3 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-sm font-medium"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium disabled:opacity-50"
                            >
                                {processing ? 'Creating...' : 'Create Tour'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}