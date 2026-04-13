import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../../layouts/AdminLayout';
import { 
    Table, 
    Badge, 
    Text, 
    Group, 
    ActionIcon, 
    Button, 
    Paper, 
    Pagination, 
    Avatar, 
    Tooltip,
    Stack,
    Box,
    TextInput,
    Menu,
    rem
} from '@mantine/core';
import { 
    Search, 
    Plus, 
    Eye, 
    Pencil, 
    Trash, 
    MoreVertical, 
    User, 
    Mail, 
    Phone, 
    Shield, 
    Key,
    ExternalLink
} from 'lucide-react';

interface UserData {
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
        data: UserData[];
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

    const getRoleColor = (role: string) => {
        const r = role.toLowerCase();
        if (r === 'admin') return 'red';
        if (r === 'guide') return 'blue';
        if (r === 'driver') return 'teal';
        return 'gray';
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this staff member?')) {
            router.delete(`/admin/users/${id}`);
        }
    };

    return (
        <AdminLayout title={title}>
            <Head title="Staff Management" />

            <Stack gap="lg">
                <Paper p="xl" radius="md" withBorder>
                    <Group justify="space-between" mb="xl">
                        <Group gap="md" style={{ flex: 1 }}>
                            <TextInput
                                placeholder="Search by name, email, or phone..."
                                leftSection={<Search size={16} />}
                                radius="md"
                                style={{ flex: 1, maxWidth: 400 }}
                            />
                        </Group>
                        <Button 
                            component={Link} 
                            href="/admin/users/create" 
                            leftSection={<Plus size={16} />}
                            radius="md"
                        >
                            Add New Staff
                        </Button>
                    </Group>

                    <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Staff Member</Table.Th>
                                    <Table.Th>Contact Info</Table.Th>
                                    <Table.Th>Roles & Permissions</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {users.data.map((user) => (
                                    <Table.Tr key={user.id}>
                                        <Table.Td>
                                            <Group gap="sm">
                                                <Avatar color="blue" radius="xl" size="md">
                                                    {user.name.charAt(0)}
                                                </Avatar>
                                                <Stack gap={0}>
                                                    <Text size="sm" fw={700}>{user.name}</Text>
                                                    <Text size="xs" color="dimmed">ID: #USR-{user.id}</Text>
                                                </Stack>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Stack gap={4}>
                                                <Group gap={6}>
                                                    <Mail size={12} color="gray" />
                                                    <Text size="xs" fw={500}>{user.email}</Text>
                                                </Group>
                                                <Group gap={6}>
                                                    <Phone size={12} color="gray" />
                                                    <Text size="xs" color="dimmed">{user.phone}</Text>
                                                </Group>
                                            </Stack>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4}>
                                                {user.roles.map((role) => (
                                                    <Badge 
                                                        key={role.id} 
                                                        variant="light" 
                                                        color={getRoleColor(role.name)}
                                                        leftSection={<Shield size={10} />}
                                                        radius="sm"
                                                        size="sm"
                                                    >
                                                        {role.name.toUpperCase()}
                                                    </Badge>
                                                ))}
                                                {user.roles.length === 0 && (
                                                    <Text size="xs" color="dimmed italic">No roles assigned</Text>
                                                )}
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4} justify="flex-end">
                                                <Tooltip label="View Details">
                                                    <ActionIcon 
                                                        variant="light" 
                                                        color="blue" 
                                                        component={Link} 
                                                        href={`/admin/users/${user.id}`}
                                                    >
                                                        <Eye size={16} />
                                                    </ActionIcon>
                                                </Tooltip>
                                                <Tooltip label="Edit Staff">
                                                    <ActionIcon 
                                                        variant="light" 
                                                        color="yellow" 
                                                        component={Link} 
                                                        href={`/admin/users/${user.id}/edit`}
                                                    >
                                                        <Pencil size={16} />
                                                    </ActionIcon>
                                                </Tooltip>
                                                <Menu shadow="md" width={180} position="bottom-end">
                                                    <Menu.Target>
                                                        <ActionIcon variant="subtle" color="gray">
                                                            <MoreVertical size={16} />
                                                        </ActionIcon>
                                                    </Menu.Target>
                                                    <Menu.Dropdown>
                                                        <Menu.Label>Accounting</Menu.Label>
                                                        <Menu.Item leftSection={<Key size={14} />}>Reset Password</Menu.Item>
                                                        <Menu.Divider />
                                                        <Menu.Label>Danger Zone</Menu.Label>
                                                        <Menu.Item 
                                                            color="red" 
                                                            leftSection={<Trash size={14} />}
                                                            onClick={() => handleDelete(user.id)}
                                                        >
                                                            Delete Staff
                                                        </Menu.Item>
                                                    </Menu.Dropdown>
                                                </Menu>
                                            </Group>
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Table.ScrollContainer>

                    {users.data.length === 0 ? (
                        <Stack align="center" py={60}>
                            <User size={48} strokeWidth={1} color="gray" />
                            <Text color="dimmed">No staff members found.</Text>
                        </Stack>
                    ) : (
                        <Group justify="space-between" mt="xl">
                            <Text size="sm" color="dimmed">
                                Showing {users.data.length} of {users.total} staff members
                            </Text>
                            <Pagination 
                                total={users.last_page} 
                                value={users.current_page} 
                                onChange={(page) => router.get(`${url}?page=${page}`)}
                                radius="md"
                                color="blue"
                            />
                        </Group>
                    )}
                </Paper>
            </Stack>
        </AdminLayout>
    );
}