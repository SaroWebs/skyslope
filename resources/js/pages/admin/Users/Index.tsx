import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../../layoutes/AdminLayout';

interface User {
    id: number;
    name: string;
    email: string;
    phone: string;
    roles: Array<{
        id: number;
        name: string;
    }>;
}

interface UsersIndexProps {
    title: string;
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    roles: Array<{
        id: number;
        name: string;
    }>;
}

export default function UsersIndex({ title, users, roles }: UsersIndexProps) {
    const { url } = usePage();

    return (
        <AdminLayout title={title}>
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="flex justify-between items-center mb-6">
                        <h2 className="text-lg font-medium text-gray-900">Users Management</h2>
                        <Link
                            href="/admin/users/create"
                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium"
                        >
                            Add New User
                        </Link>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Phone
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Roles
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {users.data.map((user) => (
                                    <tr key={user.id}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-500">{user.email}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="text-sm text-gray-500">{user.phone}</div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.map((role) => (
                                                    <span
                                                        key={role.id}
                                                        className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                    >
                                                        {role.name}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={`/admin/users/${user.id}`}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    href={`/admin/users/${user.id}/edit`}
                                                    className="text-yellow-600 hover:text-yellow-900"
                                                >
                                                    Edit
                                                </Link>
                                                <Link
                                                    href={`/admin/users/${user.id}`}
                                                    method="delete"
                                                    className="text-red-600 hover:text-red-900"
                                                    onClick={(e) => {
                                                        if (!confirm('Are you sure you want to delete this user?')) {
                                                            e.preventDefault();
                                                        }
                                                    }}
                                                >
                                                    Delete
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    <div className="mt-6 flex items-center justify-between">
                        <div className="text-sm text-gray-700">
                            Showing {((users.current_page - 1) * users.per_page) + 1} to {Math.min(users.current_page * users.per_page, users.total)} of {users.total} results
                        </div>
                        <div className="flex space-x-2">
                            {users.current_page > 1 && (
                                <Link
                                    href={`${url}?page=${users.current_page - 1}`}
                                    className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                            )}
                            {users.current_page < users.last_page && (
                                <Link
                                    href={`${url}?page=${users.current_page + 1}`}
                                    className="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}