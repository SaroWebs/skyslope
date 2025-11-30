import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import AdminLayout from '../../layoutes/AdminLayout';

interface SettingsProps {
    title: string;
    settings?: {
        site_name?: string;
        site_description?: string;
        contact_email?: string;
        contact_phone?: string;
        address?: string;
        social_links?: {
            facebook?: string;
            twitter?: string;
            instagram?: string;
        };
    };
}

export default function Settings({ title, settings = {} }: SettingsProps) {
    const { data, setData, post, processing, errors } = useForm({
        site_name: settings.site_name || '',
        site_description: settings.site_description || '',
        contact_email: settings.contact_email || '',
        contact_phone: settings.contact_phone || '',
        address: settings.address || '',
        facebook_url: settings.social_links?.facebook || '',
        twitter_url: settings.social_links?.twitter || '',
        instagram_url: settings.social_links?.instagram || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/settings');
    };

    return (
        <AdminLayout title={title}>
            <div className="max-w-4xl mx-auto">
                <div className="bg-white shadow rounded-lg">
                    <div className="px-4 py-5 sm:p-6">
                        <h2 className="text-lg font-medium text-gray-900 mb-6">System Settings</h2>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* General Settings */}
                            <div>
                                <h3 className="text-md font-medium text-gray-900 mb-4">General Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label htmlFor="site_name" className="block text-sm font-medium text-gray-700">
                                            Site Name
                                        </label>
                                        <input
                                            type="text"
                                            id="site_name"
                                            value={data.site_name}
                                            onChange={(e) => setData('site_name', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Your Site Name"
                                        />
                                        {errors.site_name && <p className="mt-1 text-sm text-red-600">{errors.site_name}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="contact_email" className="block text-sm font-medium text-gray-700">
                                            Contact Email
                                        </label>
                                        <input
                                            type="email"
                                            id="contact_email"
                                            value={data.contact_email}
                                            onChange={(e) => setData('contact_email', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="contact@example.com"
                                        />
                                        {errors.contact_email && <p className="mt-1 text-sm text-red-600">{errors.contact_email}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="contact_phone" className="block text-sm font-medium text-gray-700">
                                            Contact Phone
                                        </label>
                                        <input
                                            type="tel"
                                            id="contact_phone"
                                            value={data.contact_phone}
                                            onChange={(e) => setData('contact_phone', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="+1 (555) 123-4567"
                                        />
                                        {errors.contact_phone && <p className="mt-1 text-sm text-red-600">{errors.contact_phone}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="address" className="block text-sm font-medium text-gray-700">
                                            Address
                                        </label>
                                        <input
                                            type="text"
                                            id="address"
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="123 Main St, City, State"
                                        />
                                        {errors.address && <p className="mt-1 text-sm text-red-600">{errors.address}</p>}
                                    </div>
                                </div>

                                <div className="mt-4">
                                    <label htmlFor="site_description" className="block text-sm font-medium text-gray-700">
                                        Site Description
                                    </label>
                                    <textarea
                                        id="site_description"
                                        rows={3}
                                        value={data.site_description}
                                        onChange={(e) => setData('site_description', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Brief description of your site..."
                                    />
                                    {errors.site_description && <p className="mt-1 text-sm text-red-600">{errors.site_description}</p>}
                                </div>
                            </div>

                            {/* Social Media Links */}
                            <div>
                                <h3 className="text-md font-medium text-gray-900 mb-4">Social Media Links</h3>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label htmlFor="facebook_url" className="block text-sm font-medium text-gray-700">
                                            Facebook URL
                                        </label>
                                        <input
                                            type="url"
                                            id="facebook_url"
                                            value={data.facebook_url}
                                            onChange={(e) => setData('facebook_url', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="https://facebook.com/yourpage"
                                        />
                                        {errors.facebook_url && <p className="mt-1 text-sm text-red-600">{errors.facebook_url}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="twitter_url" className="block text-sm font-medium text-gray-700">
                                            Twitter URL
                                        </label>
                                        <input
                                            type="url"
                                            id="twitter_url"
                                            value={data.twitter_url}
                                            onChange={(e) => setData('twitter_url', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="https://twitter.com/yourhandle"
                                        />
                                        {errors.twitter_url && <p className="mt-1 text-sm text-red-600">{errors.twitter_url}</p>}
                                    </div>

                                    <div>
                                        <label htmlFor="instagram_url" className="block text-sm font-medium text-gray-700">
                                            Instagram URL
                                        </label>
                                        <input
                                            type="url"
                                            id="instagram_url"
                                            value={data.instagram_url}
                                            onChange={(e) => setData('instagram_url', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="https://instagram.com/yourhandle"
                                        />
                                        {errors.instagram_url && <p className="mt-1 text-sm text-red-600">{errors.instagram_url}</p>}
                                    </div>
                                </div>
                            </div>

                            {/* Submit Button */}
                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                                >
                                    {processing ? 'Saving...' : 'Save Settings'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}