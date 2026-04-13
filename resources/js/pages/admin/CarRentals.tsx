import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { 
    Table, 
    Badge, 
    Text, 
    Group, 
    ActionIcon, 
    Button, 
    Paper, 
    Pagination, 
    Select, 
    Avatar,
    Tooltip,
    Stack,
    Box,
    TextInput,
    Divider,
    Menu,
    rem
} from '@mantine/core';
import { 
    Search, 
    Filter, 
    Eye, 
    Car, 
    MapPin, 
    Calendar, 
    Clock, 
    IndianRupee,
    User,
    CheckCircle2,
    XCircle,
    MoreVertical,
    Pencil,
    Trash,
    ExternalLink,
    ArrowRight,
    Navigation,
    MoveRight
} from 'lucide-react';

interface CarRental {
    id: number;
    booking_number: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    carCategory: {
        id: number;
        name: string;
        vehicle_type: string;
    };
    start_date: string;
    end_date: string;
    pickup_location: string;
    dropoff_location: string;
    distance_km: number;
    total_price: number;
    status: string;
    payment_status: string;
    created_at: string;
}

interface CarRentalsProps {
    title: string;
    car_rentals: {
        data: CarRental[];
        current_page: number;
        last_page: number;
        total: number;
    };
}

export default function CarRentals({ title, car_rentals }: CarRentalsProps) {
    const { url } = usePage();

    const getStatusProps = (status: string) => {
        const s = status.toLowerCase();
        if (s === 'pending') return { color: 'yellow', label: 'Pending' };
        if (s === 'confirmed') return { color: 'blue', label: 'Confirmed' };
        if (s === 'in_progress') return { color: 'indigo', label: 'In Progress' };
        if (s === 'completed') return { color: 'green', label: 'Completed' };
        if (s === 'cancelled') return { color: 'red', label: 'Cancelled' };
        return { color: 'gray', label: status };
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this car rental?')) {
            router.delete(`/admin/car-rentals/${id}`);
        }
    };

    const handleFilter = (val: string | null) => {
        router.get('/admin/car-rentals', { status: val || '' }, { preserveState: true });
    };

    return (
        <AdminLayout title={title}>
            <Head title="Car Rentals" />

            <Stack gap="lg">
                <Paper p="xl" radius="md" withBorder>
                    <Group justify="space-between" mb="xl">
                        <Group gap="md" style={{ flex: 1 }}>
                            <TextInput
                                placeholder="Search by booking # or guest name..."
                                leftSection={<Search size={16} />}
                                radius="md"
                                style={{ flex: 1, maxWidth: 400 }}
                            />
                            <Select
                                placeholder="Status"
                                data={[
                                    { value: 'pending', label: 'Pending' },
                                    { value: 'confirmed', label: 'Confirmed' },
                                    { value: 'in_progress', label: 'In Progress' },
                                    { value: 'completed', label: 'Completed' },
                                    { value: 'cancelled', label: 'Cancelled' },
                                ]}
                                value={new URLSearchParams(window.location.search).get('status')}
                                onChange={handleFilter}
                                clearable
                                radius="md"
                                style={{ width: 200 }}
                            />
                        </Group>
                        <Button 
                            component={Link} 
                            href="/admin/car-rentals/create" 
                            leftSection={<Plus size={16} />}
                            radius="md"
                        >
                            New Rental
                        </Button>
                    </Group>

                    <Table.ScrollContainer minWidth={1000}>
                        <Table verticalSpacing="md" highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Booking & Car</Table.Th>
                                    <Table.Th>Customer</Table.Th>
                                    <Table.Th>Trip Logic</Table.Th>
                                    <Table.Th>Budget & Status</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {car_rentals.data.map((rental) => {
                                    const statusProps = getStatusProps(rental.status);
                                    return (
                                        <Table.Tr key={rental.id}>
                                            <Table.Td>
                                                <Stack gap={2}>
                                                    <Text size="sm" fw={700}>{rental.booking_number}</Text>
                                                    <Group gap={6}>
                                                        <Car size={12} color="var(--mantine-color-blue-6)" />
                                                        <Text size="xs" fw={600} color="blue">{rental.carCategory?.name}</Text>
                                                    </Group>
                                                    <Text size="xs" color="dimmed">{new Date(rental.created_at).toLocaleDateString()}</Text>
                                                </Stack>
                                            </Table.Td>
                                            <Table.Td>
                                                <Group gap="sm">
                                                    <Avatar color="blue" radius="xl" size="sm">
                                                        {rental.customer_name?.charAt(0)}
                                                    </Avatar>
                                                    <Stack gap={0}>
                                                        <Text size="sm" fw={600}>{rental.customer_name}</Text>
                                                        <Text size="xs" color="dimmed">{rental.customer_phone}</Text>
                                                    </Stack>
                                                </Group>
                                            </Table.Td>
                                            <Table.Td>
                                                <Stack gap={4}>
                                                    <Group gap={6}>
                                                        <MapPin size={12} color="gray" />
                                                        <Text size="xs" fw={500} lineClamp={1}>{rental.pickup_location}</Text>
                                                        <MoveRight size={10} color="gray" />
                                                        <Text size="xs" fw={500} lineClamp={1}>{rental.dropoff_location || 'Return'}</Text>
                                                    </Group>
                                                    <Group gap={12}>
                                                        <Group gap={4}>
                                                            <Calendar size={12} color="gray" />
                                                            <Text size="xs" color="dimmed">
                                                                {new Date(rental.start_date).toLocaleDateString()} - {new Date(rental.end_date).toLocaleDateString()}
                                                            </Text>
                                                        </Group>
                                                        <Group gap={4}>
                                                            <Navigation size={12} color="gray" />
                                                            <Text size="xs" color="dimmed">{rental.distance_km} km</Text>
                                                        </Group>
                                                    </Group>
                                                </Stack>
                                            </Table.Td>
                                            <Table.Td>
                                                <Stack gap={4}>
                                                    <Group gap={4}>
                                                        <IndianRupee size={14} />
                                                        <Text size="sm" fw={700}>{parseFloat(rental.total_price.toString()).toLocaleString()}</Text>
                                                    </Group>
                                                    <Group gap={4}>
                                                        <Badge variant="light" color={statusProps.color} size="xs" radius="sm">
                                                            {statusProps.label}
                                                        </Badge>
                                                        <Badge variant="outline" color={rental.payment_status === 'paid' ? 'green' : 'gray'} size="xs" radius="sm">
                                                            {rental.payment_status}
                                                        </Badge>
                                                    </Group>
                                                </Stack>
                                            </Table.Td>
                                            <Table.Td>
                                                <Group gap={4} justify="flex-end">
                                                    <Tooltip label="View Details">
                                                        <ActionIcon 
                                                            variant="light" 
                                                            color="blue" 
                                                            component={Link} 
                                                            href={`/admin/car-rentals/${rental.id}`}
                                                        >
                                                            <Eye size={16} />
                                                        </ActionIcon>
                                                    </Tooltip>
                                                    <Tooltip label="Edit Rental">
                                                        <ActionIcon 
                                                            variant="light" 
                                                            color="yellow" 
                                                            component={Link} 
                                                            href={`/admin/car-rentals/${rental.id}/edit`}
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
                                                            <Menu.Label>Danger Zone</Menu.Label>
                                                            <Menu.Item 
                                                                color="red" 
                                                                leftSection={<Trash size={14} />}
                                                                onClick={() => handleDelete(rental.id)}
                                                            >
                                                                Delete Rental
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

                    {car_rentals.data.length === 0 ? (
                        <Stack align="center" py={60}>
                            <Car size={48} strokeWidth={1} color="gray" />
                            <Text color="dimmed" mt="md">No car rentals found. Expand your search or add a new booking.</Text>
                        </Stack>
                    ) : (
                        <Group justify="space-between" mt="xl">
                            <Text size="sm" color="dimmed">
                                Showing {car_rentals.data.length} of {car_rentals.total} rentals
                            </Text>
                            <Pagination 
                                total={car_rentals.last_page} 
                                value={car_rentals.current_page} 
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