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
    SimpleGrid,
    Card,
    Image,
    Divider,
    Avatar
} from '@mantine/core';
import { 
    Plus, 
    Eye, 
    Pencil, 
    Calendar, 
    Users, 
    MapPin, 
    Clock, 
    IndianRupee,
    ChevronRight,
    Search,
    Filter
} from 'lucide-react';

interface Tour {
    id: number;
    title: string;
    description: string;
    price: number;
    available_from: string;
    available_to: string;
    itineraries_count?: number;
    guides: Array<{
        id: number;
        user: {
            id: number;
            name: string;
        };
    }>;
    drivers: Array<{
        id: number;
        driver: {
            id: number;
            name: string;
        };
    }>;
}

interface ToursProps {
    title: string;
    tours: {
        data: Tour[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function Tours({ title, tours }: ToursProps) {
    const { url } = usePage();

    return (
        <AdminLayout title={title}>
            <Head title="Tour Management" />

            <Stack gap="lg">
                <Paper p="md" radius="md" withBorder>
                    <Group justify="space-between">
                        <Group>
                             <Text fw={700}>Filter & Search</Text>
                             <Divider orientation="vertical" />
                             <Group gap="xs">
                                <Button variant="light" size="compact-sm" leftSection={<Filter size={14} />}>Status</Button>
                                <Button variant="light" size="compact-sm" leftSection={<Calendar size={14} />}>Date Range</Button>
                             </Group>
                        </Group>
                        <Button 
                            component={Link} 
                            href="/admin/tours/create" 
                            leftSection={<Plus size={16} />}
                            radius="md"
                        >
                            Add New Tour
                        </Button>
                    </Group>
                </Paper>

                <Paper p="xl" radius="md" withBorder>
                    <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Tour Details</Table.Th>
                                    <Table.Th>Pricing</Table.Th>
                                    <Table.Th>Schedule</Table.Th>
                                    <Table.Th>Inventory/Staff</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {tours.data.map((tour) => (
                                    <Table.Tr key={tour.id}>
                                        <Table.Td>
                                            <Group gap="sm">
                                                <Box 
                                                    w={50} h={50} 
                                                    bg="gray.1" 
                                                    style={{ borderRadius: '8px', display: 'flex', alignItems: 'center', justify: 'center' }}
                                                >
                                                    <Map size={24} color="var(--mantine-color-blue-4)" />
                                                </Box>
                                                <Stack gap={0}>
                                                    <Text size="sm" fw={700}>{tour.title}</Text>
                                                    <Text size="xs" color="dimmed" lineClamp={1} maxW={200}>
                                                        {tour.description}
                                                    </Text>
                                                </Stack>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4}>
                                                <IndianRupee size={14} />
                                                <Text size="sm" fw={700}>{parseFloat(tour.price.toString()).toLocaleString()}</Text>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Stack gap={4}>
                                                <Group gap={6}>
                                                    <Clock size={12} color="gray" />
                                                    <Text size="xs" fw={500}>{tour.itineraries_count || 0} Day(s)</Text>
                                                </Group>
                                                <Text size="xs" color="dimmed">
                                                    {new Date(tour.available_from).toLocaleDateString()} - {new Date(tour.available_to).toLocaleDateString()}
                                                </Text>
                                            </Stack>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap="xs">
                                                <Tooltip label="Assigned Guides">
                                                    <Badge variant="light" leftSection={<Users size={12} />}>{tour.guides.length}</Badge>
                                                </Tooltip>
                                                <Tooltip label="Assigned Drivers">
                                                    <Badge variant="light" color="teal" leftSection={<Car size={12} />}>{tour.drivers.length}</Badge>
                                                </Tooltip>
                                            </Group>
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge color="green" variant="dot">Active</Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={4} justify="flex-end">
                                                <Tooltip label="View Details">
                                                    <ActionIcon 
                                                        variant="light" 
                                                        color="blue" 
                                                        component={Link} 
                                                        href={`/admin/tours/${tour.id}`}
                                                    >
                                                        <Eye size={16} />
                                                    </ActionIcon>
                                                </Tooltip>
                                                <Tooltip label="Edit Tour">
                                                    <ActionIcon 
                                                        variant="light" 
                                                        color="yellow" 
                                                        component={Link} 
                                                        href={`/admin/tours/${tour.id}/edit`}
                                                    >
                                                        <Pencil size={16} />
                                                    </ActionIcon>
                                                </Tooltip>
                                            </Group>
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Table.ScrollContainer>

                    {tours.data.length === 0 ? (
                        <Stack align="center" py="xl">
                            <Text color="dimmed">No tours available. Create your first tour package.</Text>
                            <Button variant="outline" component={Link} href="/admin/tours/create">Establish First Tour</Button>
                        </Stack>
                    ) : (
                        <Group justify="space-between" mt="xl">
                            <Text size="sm" color="dimmed">
                                Showing {tours.data.length} of {tours.total} tours
                            </Text>
                            <Pagination 
                                total={tours.last_page} 
                                value={tours.current_page} 
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