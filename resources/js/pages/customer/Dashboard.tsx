import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Stack,
    Group,
    Text,
    Title,
    Paper,
    Button,
    ThemeIcon,
    SimpleGrid,
    Badge,
    ActionIcon,
    Card,
    Image,
    ScrollArea,
    Box,
    rem,
    Progress,
    Divider,
    Avatar,
    Anchor,
    UnstyledButton
} from '@mantine/core';
import {
    Car,
    MapPin,
    Calendar,
    Clock,
    ArrowRight,
    Search,
    History,
    Star,
    Navigation,
    Plus,
    Compass,
    Zap,
    Settings,
    Briefcase
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useAuth } from '@/context/AuthContext';

interface DashboardProps {
    title: string;
    user: any;
    my_bookings: any[];
    ride_bookings?: any[];
    available_tours: any[];
}

const getRideStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'yellow';
        case 'confirmed': return 'blue';
        case 'driver_assigned': return 'indigo';
        case 'driver_arriving': return 'purple';
        case 'pickup': return 'cyan';
        case 'in_transit': return 'green';
        case 'completed': return 'emerald';
        case 'cancelled': return 'red';
        default: return 'gray';
    }
}

export default function Dashboard({ title, user, my_bookings, ride_bookings = [], available_tours }: DashboardProps) {
    const { user: authUser } = useAuth();

    const formatDate = (value?: string) => {
        if (!value) return 'N/A'
        const parsed = new Date(value)
        return Number.isNaN(parsed.getTime()) ? 'N/A' : parsed.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric'
        });
    }

    const activeRide = ride_bookings.find(r => ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'].includes(r.status));

    return (
        <AppLayout title="Dashboard">
            <Head title={title} />

            <Stack gap="xl">
                {/* Greeting & Active Engagement */}
                <Box>
                    <Group justify="space-between" align="flex-end">
                        <Stack gap={2}>
                            <Text size="sm" color="dimmed" fw={500}>Safe Travels,</Text>
                            <Title order={3} fw={900}>{user.name.split(' ')[0]} 👋</Title>
                        </Stack>
                        <Badge variant="light" color="blue" size="lg" radius="sm">
                            Explorer Class
                        </Badge>
                    </Group>
                </Box>

                {/* Live Activity (HUD Style) */}
                {activeRide && (
                    <Paper
                        radius="md"
                        p="md"
                        withBorder
                        style={{
                            background: 'var(--mantine-color-blue-0)',
                            borderColor: 'var(--mantine-color-blue-2)',
                            position: 'relative',
                            overflow: 'hidden'
                        }}
                    >
                        <Stack gap="xs">
                            <Group justify="space-between">
                                <Group gap="xs">
                                    <ThemeIcon variant="filled" color="blue" radius="xl" size="sm">
                                        <Zap size={12} strokeWidth={3} />
                                    </ThemeIcon>
                                    <Text size="xs" fw={700} tt="uppercase" style={{ color: 'var(--mantine-color-blue-8)' }}>
                                        Active Journey
                                    </Text>
                                </Group>
                                <Badge color={getRideStatusColor(activeRide.status)} variant="filled" size="xs">
                                    {activeRide.status.replace('_', ' ')}
                                </Badge>
                            </Group>

                            <Box mt={4}>
                                <Text size="sm" fw={700}>{activeRide.pickup_location}</Text>
                                <Group gap={4} mt={2}>
                                    <Text size="xs" color="dimmed">Heading to</Text>
                                    <Text size="xs" fw={600}>{activeRide.dropoff_location || 'Ongoing Trip'}</Text>
                                </Group>
                            </Box>

                            <Progress value={75} size="xs" radius="xl" mt="xs" animated />

                            <Button
                                variant="white"
                                color="blue"
                                size="xs"
                                fullWidth
                                mt="sm"
                                radius="md"
                                onClick={() => router.visit(`/customer/ride-bookings/${activeRide.id}`)}
                                rightSection={<Navigation size={14} />}
                            >
                                Track Live Location
                            </Button>
                        </Stack>
                    </Paper>
                )}

                {/* Quick Service Grid */}
                <SimpleGrid cols={2} spacing="md">
                    <Card
                        withBorder
                        radius="md"
                        p="md"
                        component={Link}
                        href="/ride-booking"
                        style={{ cursor: 'pointer', transition: 'transform 0.2s ease' }}
                        className="hover-scale"
                    >
                        <ThemeIcon size={40} radius="md" color="blue" variant="light">
                            <Car size={24} />
                        </ThemeIcon>
                        <Text fw={700} size="sm" mt="sm">Book a Ride</Text>
                        <Text size="xs" color="dimmed">Instant or Scheduled</Text>
                    </Card>

                    <Card
                        withBorder
                        radius="md"
                        p="md"
                        component={Link}
                        href="/book-now"
                        style={{ cursor: 'pointer', transition: 'transform 0.2s ease' }}
                        className="hover-scale"
                    >
                        <ThemeIcon size={40} radius="md" color="indigo" variant="light">
                            <Compass size={24} />
                        </ThemeIcon>
                        <Text fw={700} size="sm" mt="sm">Explore Tours</Text>
                        <Text size="xs" color="dimmed">Curated experiences</Text>
                    </Card>
                </SimpleGrid>

                {/* My Journeys Ledger */}
                <Box>
                    <Group justify="space-between" mb="xs">
                        <Title order={5} fw={900}>Recent Activity</Title>
                        <Anchor component={Link} href="/customer/rides" size="xs" fw={700}>View Ledger</Anchor>
                    </Group>

                    <Stack gap="xs">
                        {ride_bookings.length > 0 ? (
                            ride_bookings.slice(0, 3).map((booking) => (
                                <Paper
                                    key={booking.id}
                                    p="sm"
                                    radius="md"
                                    withBorder
                                    onClick={() => router.visit(`/customer/ride-bookings/${booking.id}`)}
                                    style={{ cursor: 'pointer' }}
                                >
                                    <Group justify="space-between" wrap="nowrap">
                                        <Group gap="sm" wrap="nowrap">
                                            <ThemeIcon variant="light" color={getRideStatusColor(booking.status)} radius="sm" size="lg">
                                                <History size={18} />
                                            </ThemeIcon>
                                            <Stack gap={0} style={{ overflow: 'hidden' }}>
                                                <Text size="sm" fw={700} truncate>{booking.pickup_location}</Text>
                                                <Text size="xs" color="dimmed">{formatDate(booking.scheduled_at)}</Text>
                                            </Stack>
                                        </Group>
                                        <Box ta="right">
                                            <Badge color={getRideStatusColor(booking.status)} variant="light" size="xs">
                                                {booking.status}
                                            </Badge>
                                            <Text fw={700} size="xs" mt={4}>${booking.total_price || '0.00'}</Text>
                                        </Box>
                                    </Group>
                                </Paper>
                            ))
                        ) : (
                            <Paper p="xl" radius="md" withBorder style={{ borderStyle: 'dashed', textAlign: 'center' }}>
                                <Text size="sm" color="dimmed">No recent ride activities</Text>
                            </Paper>
                        )}
                    </Stack>
                </Box>

                {/* Featured Tours Scroll */}
                <Box>
                    <Group justify="space-between" mb="xs">
                        <Title order={5} fw={900}>Featured Experiences</Title>
                        <Anchor component={Link} href="/tours" size="xs" fw={700}>Explore All</Anchor>
                    </Group>

                    <ScrollArea offsetScrollbars scrollbarSize={4}>
                        <Group gap="md" pb="xs" wrap="nowrap">
                            {available_tours.map((tour) => (
                                <Card
                                    key={tour.id}
                                    padding="xs"
                                    radius="md"
                                    withBorder
                                    w={180}
                                    style={{ cursor: 'pointer' }}
                                    onClick={() => router.visit(`/tours/${tour.id}`)}
                                >
                                    <Card.Section>
                                        <Image
                                            src={tour.image_path || 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?q=80&w=2021&auto=format&fit=crop'}
                                            height={100}
                                            alt={tour.title}
                                        />
                                    </Card.Section>
                                    <Stack gap={2} mt="xs">
                                        <Text size="xs" fw={700} truncate>{tour.title}</Text>
                                        <Text size="xs" color="blue" fw={700}>From ${tour.price}</Text>
                                    </Stack>
                                </Card>
                            ))}
                        </Group>
                    </ScrollArea>
                </Box>

                {/* Secondary Actions */}
                <SimpleGrid cols={3} spacing="xs">
                    <UnstyledButton
                        p="sm"
                        style={{ textAlign: 'center', borderRadius: 'var(--mantine-radius-md)', background: '#fff', border: '1px solid var(--mantine-color-gray-2)' }}
                        component={Link}
                        href="/destinations"
                    >
                        <Briefcase size={20} color="var(--mantine-color-gray-6)" />
                        <Text size={rem(10)} fw={600} mt={4}>Places</Text>
                    </UnstyledButton>
                    <UnstyledButton
                        p="sm"
                        style={{ textAlign: 'center', borderRadius: 'var(--mantine-radius-md)', background: '#fff', border: '1px solid var(--mantine-color-gray-2)' }}
                        component={Link}
                        href="/contact"
                    >
                        <Star size={20} color="var(--mantine-color-gray-6)" />
                        <Text size={rem(10)} fw={600} mt={4}>Support</Text>
                    </UnstyledButton>
                    <UnstyledButton
                        p="sm"
                        style={{ textAlign: 'center', borderRadius: 'var(--mantine-radius-md)', background: '#fff', border: '1px solid var(--mantine-color-gray-2)' }}
                        component={Link}
                        href="/settings"
                    >
                        <Settings size={20} color="var(--mantine-color-gray-6)" />
                        <Text size={rem(10)} fw={600} mt={4}>Safety</Text>
                    </UnstyledButton>
                </SimpleGrid>
            </Stack>
        </AppLayout>
    );
}
