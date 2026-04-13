import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../layouts/AdminLayout';
import { 
    Paper, 
    Stack, 
    Group, 
    Text, 
    Button, 
    Badge, 
    SimpleGrid, 
    Avatar, 
    ThemeIcon, 
    Divider, 
    Timeline, 
    Box, 
    Tooltip,
    ActionIcon,
    rem,
    Grid
} from '@mantine/core';
import { 
    ArrowLeft, 
    Pencil, 
    Calendar, 
    IndianRupee, 
    Users, 
    UserCheck, 
    MapPin, 
    Clock, 
    Info, 
    TrendingUp, 
    Tag,
    Navigation,
    BookOpen,
    ShieldCheck
} from 'lucide-react';

interface Tour {
    id: number;
    title: string;
    description: string;
    price: number;
    discount: number;
    available_from: string;
    available_to: string;
    guides: Array<{
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
        };
    }>;
    drivers: Array<{
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
        };
    }>;
    itineraries: Array<{
        id: number;
        day_index: number;
        time: string;
        details: string;
        place: {
            id: number;
            name: string;
            description: string;
            media: Array<{
                id: number;
                file_path: string;
                file_type: string;
            }>;
        };
    }>;
    bookings: Array<{
        id: number;
        user: {
            id: number;
            name: string;
            email: string;
        };
        status: string;
        total_amount: number;
        created_at: string;
    }>;
}

interface ShowTourProps {
    title: string;
    tour: Tour;
}

export default function Show({ title, tour }: ShowTourProps) {
    const finalPrice = tour.price * (1 - tour.discount / 100);

    return (
        <AdminLayout title={title}>
            <Head title={`Tour: ${tour.title}`} />

            <Stack gap="xl">
                {/* Header Action Bar */}
                <Group justify="space-between" align="flex-end">
                    <Stack gap={2}>
                        <Group gap="xs">
                            <ThemeIcon variant="light" color="blue" size="sm">
                                <Navigation size={14} />
                            </ThemeIcon>
                            <Text size="xs" fw={700} color="dimmed" tt="uppercase">Tour Management Console</Text>
                        </Group>
                        <Text size="h3" fw={800}>{tour.title}</Text>
                    </Stack>
                    <Group gap="sm">
                        <Button 
                            component={Link} 
                            href="/admin/tours" 
                            variant="light" 
                            color="gray" 
                            leftSection={<ArrowLeft size={16} />}
                            radius="md"
                        >
                            Back to Index
                        </Button>
                        <Button 
                            component={Link} 
                            href={`/admin/tours/${tour.id}/edit`} 
                            color="yellow" 
                            leftSection={<Pencil size={16} />}
                            radius="md"
                        >
                            Edit Package
                        </Button>
                    </Group>
                </Group>

                <Grid gutter="xl">
                    <Grid.Col span={{ base: 12, lg: 8 }}>
                        <Stack gap="xl">
                            {/* Detailed Economics */}
                            <Paper p="xl" radius="md" withBorder shadow="sm">
                                <Text fw={800} size="lg" mb="xl">Financial Overview</Text>
                                <SimpleGrid cols={{ base: 1, sm: 3 }} spacing="xl">
                                    <Box>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase">Base Price</Text>
                                        <Group gap={4} mt={4}>
                                            <IndianRupee size={16} color="gray" />
                                            <Text size="xl" fw={800}>{parseFloat(tour.price.toString()).toLocaleString()}</Text>
                                        </Group>
                                    </Box>
                                    <Box>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase">Active Discount</Text>
                                        <Group gap={4} mt={4}>
                                            <Tag size={16} color="var(--mantine-color-red-6)" />
                                            <Text size="xl" fw={800} color="red.7">{tour.discount}% OFF</Text>
                                        </Group>
                                    </Box>
                                    <Box>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase">Market Price</Text>
                                        <Group gap={4} mt={4}>
                                            <TrendingUp size={16} color="var(--mantine-color-green-6)" />
                                            <Text size="xl" fw={800} color="green.7">₹{finalPrice.toLocaleString()}</Text>
                                        </Group>
                                    </Box>
                                </SimpleGrid>
                                
                                <Divider my="xl" />
                                
                                <SimpleGrid cols={{ base: 1, sm: 2 }}>
                                    <Group gap="md">
                                        <ThemeIcon variant="light" color="blue" radius="md">
                                            <Calendar size={18} />
                                        </ThemeIcon>
                                        <Stack gap={0}>
                                            <Text size="xs" color="dimmed" fw={700}>STARTING WINDOW</Text>
                                            <Text fw={700}>{new Date(tour.available_from).toLocaleDateString()}</Text>
                                        </Stack>
                                    </Group>
                                    <Group gap="md">
                                        <ThemeIcon variant="light" color="blue" radius="md">
                                            <Calendar size={18} />
                                        </ThemeIcon>
                                        <Stack gap={0}>
                                            <Text size="xs" color="dimmed" fw={700}>ENDING WINDOW</Text>
                                            <Text fw={700}>{new Date(tour.available_to).toLocaleDateString()}</Text>
                                        </Stack>
                                    </Group>
                                </SimpleGrid>
                            </Paper>

                            {/* Tour Persona & Narrative */}
                            <Paper p="xl" radius="md" withBorder shadow="sm">
                                <Text fw={800} size="lg" mb="md">Package Narrative</Text>
                                <Text size="sm" color="gray.7" style={{ lineHeight: 1.6 }}>{tour.description}</Text>
                            </Paper>

                            {/* Travel Itinerary Preview */}
                            <Paper p="xl" radius="md" withBorder shadow="sm">
                                <Group justify="space-between" mb="xl">
                                    <Text fw={800} size="lg">Journey Timeline</Text>
                                    <Button 
                                        variant="subtle" 
                                        size="xs" 
                                        component={Link} 
                                        href={`/admin/tours/${tour.id}/itineraries`}
                                        rightSection={<ArrowRight size={14} />}
                                    >
                                        Manage Mapping
                                    </Button>
                                </Group>
                                
                                {tour.itineraries && tour.itineraries.length > 0 ? (
                                    <Timeline bulletSize={24} lineWidth={2}>
                                        {tour.itineraries.map((it) => (
                                            <Timeline.Item 
                                                key={it.id} 
                                                bullet={<MapPin size={12} />} 
                                                title={
                                                    <Group justify="space-between">
                                                        <Text fw={700} size="sm">Day {it.day_index}: {it.place.name}</Text>
                                                        {it.time && <Badge size="xs" variant="outline">{it.time}</Badge>}
                                                    </Group>
                                                }
                                            >
                                                <Text size="xs" color="dimmed" mb={4}>{it.place.description}</Text>
                                                {it.details && (
                                                    <Text size="xs" color="gray.6" italic>Note: {it.details}</Text>
                                                )}
                                            </Timeline.Item>
                                        ))}
                                    </Timeline>
                                ) : (
                                    <Text size="sm" color="dimmed" ta="center" py="xl">No itineraries defined for this package.</Text>
                                )}
                            </Paper>
                        </Stack>
                    </Grid.Col>

                    <Grid.Col span={{ base: 12, lg: 4 }}>
                        <Stack gap="lg">
                            {/* Assigned Resources */}
                            <Paper p="xl" radius="md" withBorder shadow="sm">
                                <Text fw={800} size="md" mb="xl">Assigned Resources</Text>
                                
                                <Stack gap="lg">
                                    <Box>
                                        <Group justify="space-between" mb="sm">
                                            <Group gap="xs">
                                                <UserCheck size={16} color="var(--mantine-color-blue-6)" />
                                                <Text size="xs" fw={700} tt="uppercase">Tour Guides</Text>
                                            </Group>
                                            <Badge size="xs">{tour.guides?.length || 0}</Badge>
                                        </Group>
                                        <Stack gap="xs">
                                            {tour.guides?.map(g => (
                                                <Group key={g.id} gap="sm" p="xs" style={{ border: '1px solid #f1f3f5', borderRadius: '8px' }}>
                                                    <Avatar size="sm" color="blue">{g.user.name.charAt(0)}</Avatar>
                                                    <Stack gap={0}>
                                                        <Text size="xs" fw={700}>{g.user.name}</Text>
                                                        <Text size="xs" color="dimmed">{g.user.email}</Text>
                                                    </Stack>
                                                </Group>
                                            ))}
                                            {(!tour.guides || tour.guides.length === 0) && <Text size="xs" color="dimmed">No guides assigned.</Text>}
                                        </Stack>
                                    </Box>

                                    <Box>
                                        <Group justify="space-between" mb="sm">
                                            <Group gap="xs">
                                                <Users size={16} color="var(--mantine-color-teal-6)" />
                                                <Text size="xs" fw={700} tt="uppercase">Fleet Support</Text>
                                            </Group>
                                            <Badge size="xs">{tour.drivers?.length || 0}</Badge>
                                        </Group>
                                        <Stack gap="xs">
                                            {tour.drivers?.map(d => (
                                                <Group key={d.id} gap="sm" p="xs" style={{ border: '1px solid #f1f3f5', borderRadius: '8px' }}>
                                                    <Avatar size="sm" color="teal">{d.user.name.charAt(0)}</Avatar>
                                                    <Stack gap={0}>
                                                        <Text size="xs" fw={700}>{d.user.name}</Text>
                                                        <Text size="xs" color="dimmed">{d.user.email}</Text>
                                                    </Stack>
                                                </Group>
                                            ))}
                                            {(!tour.drivers || tour.drivers.length === 0) && <Text size="xs" color="dimmed">No drivers assigned.</Text>}
                                        </Stack>
                                    </Box>
                                </Stack>
                            </Paper>

                            {/* Booking Intelligence */}
                            <Paper p="xl" radius="md" withBorder shadow="sm">
                                <Group justify="space-between" mb="xl">
                                    <Group gap="xs">
                                        <BookOpen size={16} color="var(--mantine-color-indigo-6)" />
                                        <Text fw={800} size="md">Sales Ledger</Text>
                                    </Group>
                                    <Badge color="indigo">{tour.bookings?.length || 0}</Badge>
                                </Group>

                                <Stack gap="md">
                                    {tour.bookings?.slice(0, 5).map(b => (
                                        <Box key={b.id} p="sm" style={{ border: '1px solid #f1f3f5', borderRadius: '8px' }}>
                                            <Group justify="space-between" mb={4}>
                                                <Text size="xs" fw={700}>{b.user.name}</Text>
                                                <Text size="xs" fw={800} color="green.8">₹{parseFloat(b.total_amount.toString()).toLocaleString()}</Text>
                                            </Group>
                                            <Group justify="space-between">
                                                <Badge size="xs" color={b.status === 'confirmed' ? 'green' : 'yellow'} variant="light">
                                                    {b.status}
                                                </Badge>
                                                <Text size="xs" color="dimmed">{new Date(b.created_at).toLocaleDateString()}</Text>
                                            </Group>
                                        </Box>
                                    ))}
                                    {(!tour.bookings || tour.bookings.length === 0) && <Text size="xs" color="dimmed" ta="center">No bookings recorded.</Text>}
                                    {tour.bookings && tour.bookings.length > 5 && (
                                        <Button variant="subtle" size="xs" fullWidth mt="xs">View All Transactions</Button>
                                    )}
                                </Stack>
                            </Paper>
                        </Stack>
                    </Grid.Col>
                </Grid>
            </Stack>
        </AdminLayout>
    );
}