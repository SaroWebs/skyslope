import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

interface LoginProps {
    errors?: {
        email?: string;
        password?: string;
    };
}

export default function Login({ errors }: LoginProps) {
    const [data, setData] = useState({
        email: '',
        password: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/login', data);
    };

    const handleInputChange = (field: string, value: string) => {
        setData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    return (
        <>
            <Head title="Login" />

            <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
                <div className="max-w-md w-full space-y-8">
                    <div className="text-center">
                        <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
                            Sign in to your account
                        </h2>
                        <p className="mt-2 text-sm text-gray-600">
                            Or{' '}
                            <Link
                                href="/"
                                className="font-medium text-blue-600 hover:text-blue-500"
                            >
                                return to homepage
                            </Link>
                        </p>
                    </div>

                    <div className="bg-white shadow-md rounded-lg p-6">
                        <div className="mb-6">
                            <h3 className="text-xl font-semibold text-gray-800 mb-2">Welcome Back</h3>
                            <p className="text-gray-600">
                                Enter your credentials to access your account
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {errors?.email && (
                                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                                    {errors.email}
                                </div>
                            )}

                            <div className="space-y-2">
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                    Email Address
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => handleInputChange('email', e.target.value)}
                                    placeholder="Enter your email"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    required
                                />
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                                    Password
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => handleInputChange('password', e.target.value)}
                                    placeholder="Enter your password"
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    required
                                />
                            </div>

                            <button
                                type="submit"
                                className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                Sign In
                            </button>
                        </form>

                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-600 mb-2">
                                Demo Credentials:
                            </p>
                            <div className="text-xs text-gray-500 space-y-1">
                                <p><strong>Admin:</strong> admin@skyslope.com / password</p>
                                <p><strong>Guide:</strong> mike@example.com / password</p>
                                <p><strong>Driver:</strong> emma@example.com / password</p>
                                <p><strong>Customer:</strong> john@example.com / password</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}