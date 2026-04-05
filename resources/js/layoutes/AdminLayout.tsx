import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';

interface AdminLayoutProps {
    children: React.ReactNode;
    title?: string;
}

interface NavigationItem {
    name: string;
    href: string;
    icon: string;
    current?: boolean;
}

const AdminLayout = ({ children, title = 'Admin Panel' }: AdminLayoutProps) => {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { url } = usePage();

    const navigation: NavigationItem[] = [
        { name: 'Dashboard', href: '/admin/dashboard', icon: '🏠', current: url === '/admin/dashboard' },
        { name: 'Users', href: '/admin/users', icon: '👥', current: url.startsWith('/admin/users') },
        { name: 'Tours', href: '/admin/tours', icon: '🗺️', current: url.startsWith('/admin/tours') },
        { name: 'Bookings', href: '/admin/bookings', icon: '📋', current: url.startsWith('/admin/bookings') },
        { name: 'Ride Bookings', href: '/admin/ride-bookings', icon: '🚕', current: url.startsWith('/admin/ride-bookings') },
        { name: 'Places', href: '/admin/places', icon: '📍', current: url.startsWith('/admin/places') },
        { name: 'Car Rentals', href: '/admin/car-rentals', icon: '🚗', current: url.startsWith('/admin/car-rentals') },
        { name: 'Car Categories', href: '/admin/car-categories', icon: '🏷️', current: url.startsWith('/admin/car-categories') },
        { name: 'Destinations', href: '/admin/destinations', icon: '🌍', current: url.startsWith('/admin/destinations') },
        { name: 'Settings', href: '/admin/settings', icon: '⚙️', current: url.startsWith('/admin/settings') },
    ];

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-gray-50 relative flex w-full">
                {/* Mobile sidebar backdrop */}
                {sidebarOpen && (
                    <div
                        className="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden transition-opacity duration-300"
                        onClick={() => setSidebarOpen(false)}
                    />
                )}

                {/* Sidebar */}
                <div className={`fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl transform ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'} transition-transform duration-300 ease-in-out lg:translate-x-0 lg:fixed lg:inset-y-0 border-r border-gray-200 overflow-y-auto`}>
                    <div className="flex items-center justify-between h-20 px-6 bg-gradient-to-r from-blue-600 to-blue-700">
                        <h1 className="text-xl font-bold text-white truncate">Admin Panel</h1>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="p-1 text-white hover:bg-white hover:bg-opacity-20 rounded-md lg:hidden transition-colors duration-200 flex-shrink-0"
                        >
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <nav className="mt-6 pb-6">
                        <div className="px-4 space-y-1">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`group flex items-center px-4 py-3.5 text-sm font-medium rounded-lg transition-all duration-200 ${
                                        item.current
                                            ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600 shadow-sm'
                                            : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600'
                                    }`}
                                >
                                    <span className="mr-3 text-lg flex-shrink-0">{item.icon}</span>
                                    <span className="truncate">{item.name}</span>
                                </Link>
                            ))}
                        </div>
                    </nav>
                </div>

                {/* Main content */}
                <div className="lg:ml-72 flex flex-col min-h-screen w-full">
                    {/* Top bar */}
                    <div className="sticky top-0 z-30 flex items-center justify-between h-20 px-4 sm:px-6 bg-white shadow-sm border-b border-gray-200">
                        <div className="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1">
                            <button
                                onClick={() => setSidebarOpen(true)}
                                className="p-2 text-gray-500 rounded-lg lg:hidden hover:text-gray-900 hover:bg-gray-100 transition-colors duration-200 flex-shrink-0"
                            >
                                <span className="sr-only">Open sidebar</span>
                                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <div className="min-w-0 flex-1">
                                <h2 className="text-lg sm:text-xl font-semibold text-gray-900 truncate">{title}</h2>
                                <p className="text-xs sm:text-sm text-gray-500 truncate">Manage your application</p>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2 sm:space-x-4 flex-shrink-0">
                            <div className="hidden md:block text-right">
                                <p className="text-sm font-medium text-gray-900">Welcome back!</p>
                                <p className="text-xs text-gray-500">Administrator</p>
                            </div>
                            <div className="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                <span className="text-white font-semibold text-xs sm:text-sm">A</span>
                            </div>
                            <Link
                                href="/logout"
                                method="post"
                                className="bg-red-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-red-700 text-xs sm:text-sm font-medium transition-colors duration-200 shadow-sm"
                            >
                                <span className="hidden sm:inline">Logout</span>
                                <span className="sm:hidden">Out</span>
                            </Link>
                        </div>
                    </div>

                    {/* Page content */}
                    <main className="flex-1">
                        <div className="p-4 sm:p-6 lg:p-8">
                            <div className="max-w-7xl mx-auto">
                                {children}
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
};

export default AdminLayout;
