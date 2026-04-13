import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../layouts/AdminLayout';
import { 
    Table, 
    Badge, 
    Text, 
    Group, 
    ActionIcon, 
    TextInput, 
    Button, 
    Paper, 
    Pagination, 
    Select, 
    Avatar,
    Menu,
    rem,
    Tooltip,
    Stack,
    Box
} from '@mantine/core';
import { 
    Search, 
    Filter, 
    MoreVertical, 
    Eye, 
    Check, 
    X, 
    Ban, 
    UserCheck,
    MessageSquare,
    ExternalLink,
    ChevronRight
} from 'lucide-react';
import { useDebouncedValue } from '@mantine/hooks';

interface Customer {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: 'active' | 'suspended';
    ride_bookings_count: number;
    car_rentals_count: number;
    bookings_count: number;
    created_at: string;
}

interface CustomersProps {
    title: string;
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        search: string;
        status: string;
    };
}

export default function Customers({ title, customers, filters }: CustomersProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/admin/customers', { search: value, status }, { preserveState: true, replace: true });
    };

    const handleStatusFilter = (value: string | null) => {
        setStatus(value || '');
        router.get('/admin/customers', { search, status: value }, { preserveState: true, replace: true });
    };

    const handlePageChange = (page: number) => {
        router.get('/admin/customers', { search, status, page }, { preserveState: true });
    };

    const toggleStatus = (customer: Customer) => {
         router.post(`/admin/customers/${customer.id}/toggle-status`, {}, {
             preserveScroll: true,
             onSuccess: () => {
                 // Component re-renders with fresh data automatically
             }
         });
    };

    return (
        <AdminLayout title={title}>
            <Head title="Customers" />

            <Stack gap="lg">
                <Paper p="xl" radius="md" withBorder>
                    <Group justify="space-between" mb="xl">
                        <Group gap="md" style={{ flex: 1 }}>
                            <TextInput
                                placeholder="Search customers by name, email or phone..."
                                leftSection={<Search size={16} />}
                                value={search}
                                onChange={(e) => handleSearch(e.currentTarget.value)}
                                style={{ flex: 1, maxWidth: 400 }}
                                radius="md"
                            />
                            <Select
                                placeholder="Status"
                                data={[
                                    { value: 'active', label: 'Active' },
                                    { value: 'suspended', label: 'Suspended' }
                                ]}
                                value={status}
                                onChange={handleStatusFilter}
                                clearable
                                radius="md"
                                style={{ width: 150 }}
                            />
                        </Group>
                        <Button color="blue" radius="md" leftSection={<UserCheck size={16} />}>
                            Add Customer
                        </Button>
                    </Group>

                    <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Customer</Table.Th>
                                    <Table.Th>Phone</Table.Th>
                                    <Table.Th>Activity</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th>Registered</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {customers.data.map((customer) => (
                                    <Table.Tr key={customer.id}>
                                        <Table.Td>
                                            <Group gap="sm">
                                                <Avatar color="blue" radius="xl">{customer.name.charAt(0)}</Avatar>
                                                <Stack gap={0}>
                                                    <Text size="sm" fw={600}>{customer.name}</Text>
                                                    <Text size="xs" color="dimmed">{customer.email}</Text>
                                                </Stack>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Text size="sm">{customer.phone}</Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4}>
                                                <Tooltip label="Rides">
                                                    <Badge variant="dot" color="blue" size="sm">{customer.ride_bookings_count}</Badge>
                                                </Tooltip>
                                                <Tooltip label="Rentals">
                                                    <Badge variant="dot" color="teal" size="sm">{customer.car_rentals_count}</Badge>
                                                </Tooltip>
                                                <Tooltip label="Tours">
                                                    <Badge variant="dot" color="grape" size="sm">{customer.bookings_count}</Badge>
                                                </Tooltip>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge 
                                                variant="light" 
                                                color={customer.status === 'active' ? 'green' : 'red'}
                                                radius="sm"
                                            >
                                                {customer.status}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            <Text size="xs" color="dimmed">
                                                {new Date(customer.created_at).toLocaleDateString()}
                                            </Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4} justify="flex-end">
                                                <Tooltip label="View Details">
                                                    <ActionIcon 
                                                        variant="light" 
                                                        color="blue" 
                                                        component={Link} 
                                                        href={`/admin/customers/${customer.id}`}
                                                    >
                                                        <Eye size={16} />
                                                    </ActionIcon>
                                                </Tooltip>
                                                
                                                <Menu shadow="md" width={200} position="bottom-end">
                                                    <Menu.Target>
                                                        <ActionIcon variant="subtle" color="gray">
                                                            <MoreVertical size={16} />
                                                        </ActionIcon>
                                                    </Menu.Target>

                                                    <Menu.Dropdown>
                                                        <Menu.Item 
                                                            leftSection={<MessageSquare size={14} />}
                                                            onClick={() => window.open(`https://wa.me/${customer.phone.replace(/\D/g,'')}`, '_blank')}
                                                        >
                                                            WhatsApp Contact
                                                        </Menu.Item>
                                                        <Menu.Divider />
                                                        {customer.status === 'suspended' ? (
                                                            <Menu.Item 
                                                                color="green" 
                                                                leftSection={<Check size={14} />}
                                                                onClick={() => toggleStatus(customer)}
                                                            >
                                                                Activate Customer
                                                            </Menu.Item>
                                                        ) : (
                                                            <Menu.Item 
                                                                color="red" 
                                                                leftSection={<Ban size={14} />}
                                                                onClick={() => toggleStatus(customer)}
                                                            >
                                                                Suspend Customer
                                                            </Menu.Item>
                                                        )}
                                                    </Menu.Dropdown>
                                                </Menu>
                                            </Group>
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Table.ScrollContainer>

                    <Group justify="space-between" mt="xl">
                        <Text size="sm" color="dimmed">
                            Showing {customers.data.length} of {customers.total} customers
                        </Text>
                        <Pagination 
                            total={customers.last_page} 
                            value={customers.current_page} 
                            onChange={handlePageChange}
                            color="blue"
                            radius="md"
                        />
                    </Group>
                </Paper>
            </Stack>
        </AdminLayout>
    );
}
