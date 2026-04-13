import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    Stack, 
    Group, 
    Text, 
    Title, 
    Paper, 
    Badge, 
    ThemeIcon, 
    Box, 
    rem, 
    Divider, 
    ScrollArea,
    Pagination,
    TextInput
} from '@mantine/core';
import { 
    History, 
    MapPin, 
    Calendar, 
    Search, 
    ChevronRight, 
    Car,
    Clock,
    DollarSign
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';

interface Ride {
    id: number;
    booking_number: string;
    status: string;
    pickup_location: string;
    dropoff_location?: string;
    scheduled_at: string;
    total_fare: number;
}

interface Props {
    rides: {
        data: Ride[];
        current_page: number;
        last_page: number;
    };
}

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'yellow';
        case 'confirmed': return 'blue';
        case 'completed': return 'green';
        case 'cancelled': return 'red';
        default: return 'gray';
    }
}

export default function RideHistory({ rides }: Props) {
    return (
        <AppLayout title="My Journeys" backPath="/customer/dashboard">
            <Head title="Ride History" />
            
            <Stack gap="lg">
                <Box>
                    <Title order={4} fw={900}>Journey Ledger</Title>
                    <Text size="xs" color="dimmed" mb="md">Comprehensive historical record of your travels</Text>
                    
                    <TextInput 
                        placeholder="Search by location or trip ID..." 
                        leftSection={<Search size={16} />}
                        radius="md"
                    />
                </Box>

                <Stack gap="sm">
                    {rides?.data?.length ? (
                        rides.data.map((ride) => (
                            <Paper 
                                key={ride.id} 
                                p="md" 
                                radius="md" 
                                withBorder 
                                shadow="xs"
                                onClick={() => router.visit(`/customer/ride-bookings/${ride.id}`)}
                                style={{ cursor: 'pointer' }}
                            >
                                <Group justify="space-between" mb="xs">
                                    <Group gap="xs">
                                        <Text size="xs" fw={700} color="dimmed">#{ride.booking_number}</Text>
                                        <Badge color={getStatusColor(ride.status)} variant="light" size="xs">
                                            {ride.status}
                                        </Badge>
                                    </Group>
                                    <Text size="xs" color="dimmed">
                                        {new Date(ride.scheduled_at).toLocaleDateString()}
                                    </Text>
                                </Group>

                                <Box mb="md">
                                    <Group gap="sm" mb={4} wrap="nowrap">
                                        <div style={{ width: 8, height: 8, borderRadius: '50%', background: 'var(--mantine-color-green-5)' }} />
                                        <Text size="sm" fw={600} truncate>{ride.pickup_location}</Text>
                                    </Group>
                                    <Group gap="sm" wrap="nowrap">
                                        <div style={{ width: 8, height: 8, borderRadius: '50%', background: 'var(--mantine-color-red-5)' }} />
                                        <Text size="sm" color="dimmed" truncate>{ride.dropoff_location || 'Return journey'}</Text>
                                    </Group>
                                </Box>

                                <Divider dashed mb="sm" />

                                <Group justify="space-between">
                                    <Group gap="xl">
                                        <Stack gap={0}>
                                            <Text size={rem(10)} color="dimmed" tt="uppercase" fw={700}>Fare</Text>
                                            <Text size="sm" fw={800}>${ride.total_fare}</Text>
                                        </Stack>
                                        <Stack gap={0}>
                                            <Text size={rem(10)} color="dimmed" tt="uppercase" fw={700}>Type</Text>
                                            <Text size="sm" fw={800}>Economy</Text>
                                        </Stack>
                                    </Group>
                                    <ActionIcon variant="light" color="gray" radius="xl">
                                        <ChevronRight size={16} />
                                    </ActionIcon>
                                </Group>
                            </Paper>
                        ))
                    ) : (
                        <Paper p="xl" radius="md" withBorder style={{ borderStyle: 'dashed', textAlign: 'center' }}>
                            <Stack align="center" gap="xs">
                                <History size={32} color="var(--mantine-color-gray-4)" />
                                <Text size="sm" color="dimmed">Your journey history is currently empty.</Text>
                            </Stack>
                        </Paper>
                    )}
                </Stack>

                {rides?.last_page > 1 && (
                    <Group justify="center" mt="md">
                        <Pagination 
                            total={rides.last_page} 
                            value={rides.current_page} 
                            onChange={(p) => router.get(window.location.pathname, { page: p })}
                            size="sm"
                            radius="md"
                        />
                    </Group>
                )}
            </Stack>
        </AppLayout>
    );
}
