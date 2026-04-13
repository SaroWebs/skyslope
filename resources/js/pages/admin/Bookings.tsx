import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '../../layouts/AdminLayout';
import { 
    Table, 
    Badge, 
    Text, 
    Group, 
    ActionIcon, 
    Button, 
    Paper, 
    Pagination, 
    Tooltip,
    Stack,
    Box,
    Avatar,
    TextInput,
    Select,
    Divider
} from '@mantine/core';
import { 
    Search, 
    Filter, 
    Eye, 
    Pencil, 
    Check, 
    X, 
    ExternalLink,
    IndianRupee,
    Calendar,
    Hash
} from 'lucide-react';

interface Booking {
    id: number;
    booking_number: string;
    status: string;
    total_price: number;
    created_at: string;
    travel_date: string;
    customer: {
        id: number;
        name: string;
        email: string;
    };
    tour: {
        id: number;
        title: string;
    };
}

interface BookingsProps {
    title: string;
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Bookings({ title, bookings }: BookingsProps) {
    const { url } = usePage();

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'confirmed': return 'green';
            case 'pending': return 'yellow';
            case 'cancelled': return 'red';
            case 'completed': return 'blue';
            default: return 'gray';
        }
    };

    return (
        <AdminLayout title={title}>
            <Head title="Tour Bookings" />

            <Stack gap="lg">
                <Paper p="xl" radius="md" withBorder>
                    <Group justify="space-between" mb="xl">
                        <Group gap="md" style={{ flex: 1 }}>
                            <TextInput
                                placeholder="Search by booking # or customer..."
                                leftSection={<Search size={16} />}
                                radius="md"
                                style={{ flex: 1, maxWidth: 400 }}
                            />
                            <Select
                                placeholder="Status"
                                data={['All', 'Pending', 'Confirmed', 'Cancelled']}
                                radius="md"
                                defaultValue="All"
                                style={{ width: 150 }}
                            />
                        </Group>
                        <Button variant="light" leftSection={<Filter size={16} />}>Advanced Filters</Button>
                    </Group>

                    <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Booking Info</Table.Th>
                                    <Table.Th>Customer</Table.Th>
                                    <Table.Th>Tour</Table.Th>
                                    <Table.Th>Amount</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th>Travel Date</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {bookings.data.map((booking) => (
                                    <Table.Tr key={booking.id}>
                                        <Table.Td>
                                            <Group gap="xs">
                                                <Hash size={14} color="gray" />
                                                <Text size="sm" fw={700}>{booking.booking_number}</Text>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap="sm">
                                                <Avatar color="blue" radius="xl" size="sm">{booking.customer?.name.charAt(0)}</Avatar>
                                                <Stack gap={0}>
                                                    <Text size="sm" fw={500}>{booking.customer?.name}</Text>
                                                    <Text size="xs" color="dimmed">{booking.customer?.email}</Text>
                                                </Stack>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Text size="sm" fw={500} lineClamp={1}>{booking.tour?.title}</Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4}>
                                                <IndianRupee size={14} />
                                                <Text size="sm" fw={700}>{parseFloat(booking.total_price.toString()).toLocaleString()}</Text>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge variant="light" color={getStatusColor(booking.status)} radius="sm">
                                                {booking.status.toUpperCase()}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4}>
                                                <Calendar size={12} color="gray" />
                                                <Text size="xs">{new Date(booking.travel_date).toLocaleDateString()}</Text>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4} justify="flex-end">
                                                <Tooltip label="View Details">
                                                    <ActionIcon variant="light" color="blue" component={Link} href={`/admin/bookings/${booking.id}`}>
                                                        <Eye size={16} />
                                                    </ActionIcon>
                                                </Tooltip>
                                                {booking.status.toLowerCase() === 'pending' && (
                                                    <Tooltip label="Quick Confirm">
                                                        <ActionIcon variant="light" color="green">
                                                            <Check size={16} />
                                                        </ActionIcon>
                                                    </Tooltip>
                                                )}
                                            </Group>
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Table.ScrollContainer>

                    {bookings.data.length === 0 ? (
                        <Stack align="center" py="xl">
                            <Text color="dimmed">No bookings found for the selected filters.</Text>
                        </Stack>
                    ) : (
                        <Group justify="space-between" mt="xl">
                            <Text size="sm" color="dimmed">
                                Showing {bookings.data.length} of {bookings.total} bookings
                            </Text>
                            <Pagination 
                                total={bookings.last_page} 
                                value={bookings.current_page} 
                                onChange={(page) => router.get(`${url}?page=${page}`)}
                                radius="md"
                            />
                        </Group>
                    )}
                </Paper>
            </Stack>
        </AdminLayout>
    );
}