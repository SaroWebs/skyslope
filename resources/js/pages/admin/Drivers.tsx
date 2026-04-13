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
    Indicator,
    Box
} from '@mantine/core';
import { 
    Search, 
    MoreVertical, 
    Eye, 
    Check, 
    Ban, 
    Truck, 
    ShieldCheck,
    MapPin,
    Phone,
    UserPlus,
    CircleDot
} from 'lucide-react';

interface Driver {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: 'pending' | 'active' | 'suspended';
    vehicle_number: string;
    vehicle_type: string;
    assigned_ride_bookings_count: number;
    created_at: string;
    driver_availability?: {
        is_online: boolean;
        is_available: boolean;
        current_lat: number;
        current_lng: number;
        last_ping: string;
    };
}

interface DriversProps {
    title: string;
    drivers: {
        data: Driver[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        search: string;
        status: string;
    };
}

export default function Drivers({ title, drivers, filters }: DriversProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/admin/drivers', { search: value, status }, { preserveState: true, replace: true });
    };

    const handleStatusFilter = (value: string | null) => {
        setStatus(value || '');
        router.get('/admin/drivers', { search, status: value }, { preserveState: true, replace: true });
    };

    const handlePageChange = (page: number) => {
        router.get('/admin/drivers', { search, status, page }, { preserveState: true });
    };

    const handleApprove = (driver: Driver) => {
        router.post(`/admin/drivers/${driver.id}/approve`, {}, { preserveScroll: true });
    };

    const handleSuspend = (driver: Driver) => {
        router.post(`/admin/drivers/${driver.id}/suspend`, {}, { preserveScroll: true });
    };

    const handleActivate = (driver: Driver) => {
        router.post(`/admin/drivers/${driver.id}/activate`, {}, { preserveScroll: true });
    };

    return (
        <AdminLayout title={title}>
            <Head title="Drivers" />

            <Stack gap="lg">
                <Paper p="xl" radius="md" withBorder shadow="sm">
                    <Group justify="space-between" mb="xl">
                        <Group gap="md" style={{ flex: 1 }}>
                            <TextInput
                                placeholder="Search by name, vehicle #, or phone..."
                                leftSection={<Search size={16} />}
                                value={search}
                                onChange={(e) => handleSearch(e.currentTarget.value)}
                                style={{ flex: 1, maxWidth: 400 }}
                                radius="md"
                            />
                            <Select
                                placeholder="Status"
                                data={[
                                    { value: 'pending', label: 'Pending Approval' },
                                    { value: 'active', label: 'Active' },
                                    { value: 'suspended', label: 'Suspended' }
                                ]}
                                value={status}
                                onChange={handleStatusFilter}
                                clearable
                                radius="md"
                                style={{ width: 180 }}
                            />
                        </Group>
                        <Button color="blue" radius="md" leftSection={<UserPlus size={16} />}>
                            Add Driver
                        </Button>
                    </Group>

                    <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover verticalAlignment="center">
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Driver</Table.Th>
                                    <Table.Th>Vehicle Details</Table.Th>
                                    <Table.Th>Live Status</Table.Th>
                                    <Table.Th>Total Rides</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {drivers.data.map((driver) => {
                                    const isOnline = driver.driver_availability?.is_online;
                                    const isAvailable = driver.driver_availability?.is_available;
                                    
                                    return (
                                        <Table.Tr key={driver.id}>
                                            <Table.Td>
                                                <Group gap="sm">
                                                    <Indicator 
                                                        color={isOnline ? 'green' : 'gray'} 
                                                        offset={4} 
                                                        position="bottom-end" 
                                                        withBorder
                                                        processing={isOnline}
                                                    >
                                                        <Avatar color="blue" radius="xl">{driver.name.charAt(0)}</Avatar>
                                                    </Indicator>
                                                    <Stack gap={0}>
                                                        <Text size="sm" fw={600}>{driver.name}</Text>
                                                        <Text size="xs" color="dimmed">{driver.phone}</Text>
                                                    </Stack>
                                                </Group>
                                            </Table.Td>
                                            <Table.Td>
                                                <Stack gap={0}>
                                                    <Badge variant="outline" color="gray" size="sm" mb={4}>
                                                        {driver.vehicle_number}
                                                    </Badge>
                                                    <Text size="xs" color="dimmed">{driver.vehicle_type || 'Sedan'}</Text>
                                                </Stack>
                                            </Table.Td>
                                            <Table.Td>
                                                {isOnline ? (
                                                    <Group gap={4}>
                                                        <Badge variant="dot" color={isAvailable ? 'green' : 'orange'} size="sm">
                                                            {isAvailable ? 'Available' : 'On Ride'}
                                                        </Badge>
                                                    </Group>
                                                ) : (
                                                    <Text size="xs" color="dimmed">Offline</Text>
                                                )}
                                            </Table.Td>
                                            <Table.Td>
                                                <Text size="sm" fw={500}>{driver.assigned_ride_bookings_count}</Text>
                                            </Table.Td>
                                            <Table.Td>
                                                <Badge 
                                                    variant="light" 
                                                    color={
                                                        driver.status === 'active' ? 'green' : 
                                                        driver.status === 'pending' ? 'yellow' : 'red'
                                                    }
                                                    radius="sm"
                                                >
                                                    {driver.status}
                                                </Badge>
                                            </Table.Td>
                                            <Table.Td>
                                                <Group gap={4} justify="flex-end">
                                                    <Tooltip label="View Dashboard">
                                                        <ActionIcon 
                                                            variant="light" 
                                                            color="blue" 
                                                            component={Link} 
                                                            href={`/admin/drivers/${driver.id}`}
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
                                                            <Menu.Label>Verification</Menu.Label>
                                                            {driver.status === 'pending' && (
                                                                <Menu.Item 
                                                                    color="green" 
                                                                    leftSection={<ShieldCheck size={14} />}
                                                                    onClick={() => handleApprove(driver)}
                                                                >
                                                                    Approve Driver
                                                                </Menu.Item>
                                                            )}
                                                            
                                                            <Menu.Label>Status Actions</Menu.Label>
                                                            {driver.status === 'suspended' ? (
                                                                <Menu.Item 
                                                                    color="green" 
                                                                    leftSection={<Check size={14} />}
                                                                    onClick={() => handleActivate(driver)}
                                                                >
                                                                    Activate Driver
                                                                </Menu.Item>
                                                            ) : (
                                                                <Menu.Item 
                                                                    color="red" 
                                                                    leftSection={<Ban size={14} />}
                                                                    onClick={() => handleSuspend(driver)}
                                                                >
                                                                    Suspend Driver
                                                                </Menu.Item>
                                                            )}
                                                            
                                                            <Menu.Divider />
                                                            <Menu.Item leftSection={<MapPin size={14} />}>
                                                                Track Location
                                                            </Menu.Item>
                                                            <Menu.Item leftSection={<Phone size={14} />}>
                                                                Contact Driver
                                                            </Menu.Item>
                                                        </Menu.Dropdown>
                                                    </Menu>
                                                </Group>
                                            </Table.Td>
                                        </Table.Tr>
                                    );
                                })}
                            </Table.Tbody>
                        </Table>
                    </Table.ScrollContainer>

                    <Group justify="space-between" mt="xl">
                        <Text size="sm" color="dimmed">
                            Showing {drivers.data.length} of {drivers.total} drivers
                        </Text>
                        <Pagination 
                            total={drivers.last_page} 
                            value={drivers.current_page} 
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
