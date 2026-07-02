import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { 
    Grid, 
    Paper, 
    Text, 
    Group, 
    Stack, 
    Avatar, 
    Badge, 
    Tabs, 
    Table, 
    Button, 
    ActionIcon, 
    Divider,
    SimpleGrid,
    Card,
    ThemeIcon,
    Indicator,
    Progress,
    Switch,
    TagsInput,
    Textarea
} from '@mantine/core';
import { 
    Phone, 
    Car, 
    MapPin, 
    ArrowLeft, 
    Check, 
    Ban, 
    ShieldCheck,
    Map,
    TrendingUp,
    Navigation,
    Activity,
    ExternalLink,
    Clock,
    Save,
    Languages,
    BadgeCheck
} from 'lucide-react';

interface DriverRideBooking {
    id: number;
    booking_number: string;
    scheduled_at: string;
    pickup_location: string;
    dropoff_location: string;
    status: string;
    total_fare: number;
}

interface DriverTourAssignment {
    id: number;
    role?: string;
    schedule?: {
        tour?: {
            id: number;
            title: string;
        };
    };
}

interface Driver {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: 'pending' | 'active' | 'suspended';
    vehicle_number: string;
    vehicle_type: string;
    can_short_ride: boolean;
    can_long_ride: boolean;
    can_tour_lead: boolean;
    can_tour_transport: boolean;
    can_rental_delivery: boolean;
    languages: string[] | null;
    expertise_tags: string[] | null;
    certification_notes: string | null;
    created_at: string;
    assigned_ride_bookings: DriverRideBooking[];
    driver_availability?: {
        is_online: boolean;
        is_available: boolean;
        current_lat: number;
        current_lng: number;
        last_ping: string;
    };
    wallet?: {
        balance: number;
    };
    tour_driver_assignments: DriverTourAssignment[];
}

interface DriverShowProps {
    title: string;
    driver: Driver;
    stats: {
        total_rides: number;
        completed_rides: number;
        total_earned: number;
        wallet_balance: number;
        is_online: boolean;
        is_available: boolean;
    };
}

export default function DriverShow({ title, driver, stats }: DriverShowProps) {
    const { data, setData, put, processing, errors } = useForm({
        can_short_ride: Boolean(driver.can_short_ride),
        can_long_ride: Boolean(driver.can_long_ride),
        can_tour_lead: Boolean(driver.can_tour_lead),
        can_tour_transport: Boolean(driver.can_tour_transport),
        can_rental_delivery: Boolean(driver.can_rental_delivery),
        languages: driver.languages ?? [],
        expertise_tags: driver.expertise_tags ?? [],
        certification_notes: driver.certification_notes ?? '',
    });

    const handleApprove = () => router.post(`/admin/drivers/${driver.id}/approve`, {}, { preserveScroll: true });
    const handleSuspend = () => router.post(`/admin/drivers/${driver.id}/suspend`, {}, { preserveScroll: true });
    const handleActivate = () => router.post(`/admin/drivers/${driver.id}/activate`, {}, { preserveScroll: true });
    const handleCapabilitiesSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        put(`/admin/drivers/${driver.id}/capabilities`, { preserveScroll: true });
    };

    return (
        <AdminLayout title={title}>
            <Head title={`Driver: ${driver.name}`} />

            <Stack gap="lg">
                <Group justify="space-between">
                    <Button 
                        variant="subtle" 
                        color="gray" 
                        leftSection={<ArrowLeft size={16} />}
                        component={Link}
                        href="/admin/drivers"
                    >
                        Back to Drivers
                    </Button>
                    <Group gap="sm">
                        {driver.status === 'pending' && (
                            <Button 
                                variant="filled" 
                                color="green" 
                                leftSection={<ShieldCheck size={16} />}
                                onClick={handleApprove}
                            >
                                Approve Driver
                            </Button>
                        )}
                        {driver.status === 'suspended' ? (
                            <Button 
                                variant="outline" 
                                color="green" 
                                leftSection={<Check size={16} />}
                                onClick={handleActivate}
                            >
                                Reactivate
                            </Button>
                        ) : (
                            <Button 
                                variant="outline" 
                                color="red" 
                                leftSection={<Ban size={16} />}
                                onClick={handleSuspend}
                            >
                                Suspend
                            </Button>
                        )}
                    </Group>
                </Group>

                <Grid gutter="lg">
                    {/* Driver Profile */}
                    <Grid.Col span={{ base: 12, md: 4 }}>
                        <Stack gap="lg">
                            <Paper p="xl" radius="md" withBorder>
                                <Stack align="center" gap="md">
                                    <Indicator 
                                        color={stats.is_online ? 'green' : 'gray'} 
                                        size={20} 
                                        offset={8} 
                                        position="bottom-end" 
                                        processing={stats.is_online}
                                        withBorder
                                    >
                                        <Avatar size={100} radius="xl" color="blue">
                                            {driver.name.charAt(0)}
                                        </Avatar>
                                    </Indicator>
                                    <Stack align="center" gap={4}>
                                        <Text size="xl" fw={700}>{driver.name}</Text>
                                        <Badge 
                                            variant="light" 
                                            color={driver.status === 'active' ? 'green' : driver.status === 'pending' ? 'yellow' : 'red'}
                                            size="lg"
                                        >
                                            {driver.status.toUpperCase()}
                                        </Badge>
                                    </Stack>
                                </Stack>

                                <Divider my="xl" />

                                <Stack gap="sm">
                                    <Group gap="md">
                                        <ThemeIcon variant="light" color="gray" radius="md">
                                            <Phone size={16} />
                                        </ThemeIcon>
                                        <div>
                                            <Text size="xs" color="dimmed">Mobile Number</Text>
                                            <Text size="sm" fw={500}>{driver.phone}</Text>
                                        </div>
                                    </Group>
                                    <Group gap="md">
                                        <ThemeIcon variant="light" color="gray" radius="md">
                                            <Car size={16} />
                                        </ThemeIcon>
                                        <div>
                                            <Text size="xs" color="dimmed">Vehicle Number</Text>
                                            <Text size="sm" fw={500}>{driver.vehicle_number}</Text>
                                        </div>
                                    </Group>
                                     <Group gap="md">
                                        <ThemeIcon variant="light" color="gray" radius="md">
                                            <Clock size={16} />
                                        </ThemeIcon>
                                        <div>
                                            <Text size="xs" color="dimmed">Last Seen</Text>
                                            <Text size="sm" fw={500}>
                                                {driver.driver_availability?.last_ping ? new Date(driver.driver_availability.last_ping).toLocaleString() : 'Never'}
                                            </Text>
                                        </div>
                                    </Group>
                                </Stack>
                            </Paper>

                            <Paper p="xl" radius="md" withBorder bg="teal.9">
                                <Group justify="space-between" mb="xs">
                                     <Text color="teal.1" size="xs" fw={700} tt="uppercase">Total Earnings</Text>
                                     <TrendingUp size={16} color="white" />
                                </Group>
                                <Text color="white" size="h1" fw={800}>₹{parseFloat(stats.total_earned.toString()).toLocaleString()}</Text>
                                <Text color="teal.1" size="xs" mt={4}>Current Wallet: ₹{stats.wallet_balance}</Text>
                                <Button fullWidth variant="white" color="teal.9" mt="xl" size="sm">Payout History</Button>
                            </Paper>

                            <Paper p="xl" radius="md" withBorder>
                                <form onSubmit={handleCapabilitiesSubmit}>
                                    <Stack gap="lg">
                                        <Group gap="sm">
                                            <ThemeIcon variant="light" color="blue" radius="md">
                                                <BadgeCheck size={18} />
                                            </ThemeIcon>
                                            <div>
                                                <Text fw={700}>Capabilities</Text>
                                                <Text size="xs" color="dimmed">Driver app eligibility and guide-style profile.</Text>
                                            </div>
                                        </Group>

                                        <Stack gap="sm">
                                            <Switch
                                                label="Short rides"
                                                checked={data.can_short_ride}
                                                onChange={(event) => setData('can_short_ride', event.currentTarget.checked)}
                                            />
                                            <Switch
                                                label="Long rides"
                                                checked={data.can_long_ride}
                                                onChange={(event) => setData('can_long_ride', event.currentTarget.checked)}
                                            />
                                            <Switch
                                                label="Tour lead"
                                                checked={data.can_tour_lead}
                                                onChange={(event) => setData('can_tour_lead', event.currentTarget.checked)}
                                            />
                                            <Switch
                                                label="Tour transport"
                                                checked={data.can_tour_transport}
                                                onChange={(event) => setData('can_tour_transport', event.currentTarget.checked)}
                                            />
                                            <Switch
                                                label="Rental delivery"
                                                checked={data.can_rental_delivery}
                                                onChange={(event) => setData('can_rental_delivery', event.currentTarget.checked)}
                                            />
                                        </Stack>

                                        <Divider />

                                        <TagsInput
                                            label="Languages"
                                            placeholder="Add language"
                                            value={data.languages}
                                            onChange={(value) => setData('languages', value)}
                                            leftSection={<Languages size={16} />}
                                            error={errors.languages}
                                            splitChars={[',']}
                                        />
                                        <TagsInput
                                            label="Expertise tags"
                                            placeholder="Add expertise"
                                            value={data.expertise_tags}
                                            onChange={(value) => setData('expertise_tags', value)}
                                            error={errors.expertise_tags}
                                            splitChars={[',']}
                                        />
                                        <Textarea
                                            label="Certification notes"
                                            placeholder="Tour guide certifications, local permits, training notes"
                                            rows={4}
                                            value={data.certification_notes}
                                            onChange={(event) => setData('certification_notes', event.currentTarget.value)}
                                            error={errors.certification_notes}
                                        />

                                        <Button type="submit" leftSection={<Save size={16} />} loading={processing}>
                                            Save Capabilities
                                        </Button>
                                    </Stack>
                                </form>
                            </Paper>
                        </Stack>
                    </Grid.Col>

                    {/* Driver Stats and Activity */}
                    <Grid.Col span={{ base: 12, md: 8 }}>
                        <Stack gap="lg">
                            <SimpleGrid cols={{ base: 1, sm: 3 }} spacing="lg">
                                <Card padding="lg" radius="md" withBorder>
                                    <Stack gap={4}>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase">Successful Rides</Text>
                                        <Group justify="space-between" align="flex-end">
                                            <Group gap="xs">
                                                <Check size={18} color="var(--mantine-color-green-6)" />
                                                <Text size="xl" fw={700}>{stats.completed_rides}</Text>
                                            </Group>
                                            <Text size="xs" color="green" fw={600}>{stats.total_rides > 0 ? Math.round((stats.completed_rides / stats.total_rides) * 100) : 0}% success</Text>
                                        </Group>
                                        <Progress value={stats.total_rides > 0 ? (stats.completed_rides / stats.total_rides) * 100 : 0} size="xs" color="green" mt="sm" />
                                    </Stack>
                                </Card>
                                <Card padding="lg" radius="md" withBorder>
                                    <Stack gap={4}>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase">Total Rides</Text>
                                        <Group gap="xs">
                                            <Activity size={18} color="var(--mantine-color-blue-6)" />
                                            <Text size="xl" fw={700}>{stats.total_rides}</Text>
                                        </Group>
                                    </Stack>
                                </Card>
                                <Card padding="lg" radius="md" withBorder>
                                    <Stack gap={4}>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase">Work Status</Text>
                                        <Badge 
                                            fullWidth 
                                            size="lg" 
                                            variant="dot" 
                                            color={stats.is_online ? (stats.is_available ? 'green' : 'orange') : 'gray'}
                                        >
                                            {stats.is_online ? (stats.is_available ? 'Ready' : 'In Ride') : 'Offline'}
                                        </Badge>
                                    </Stack>
                                </Card>
                            </SimpleGrid>

                            <Paper radius="md" withBorder>
                                <Tabs defaultValue="rides" variant="outline">
                                    <Tabs.List px="md" pt="md">
                                        <Tabs.Tab value="rides" leftSection={<Navigation size={14} />}>Recent Rides</Tabs.Tab>
                                        <Tabs.Tab value="tours" leftSection={<Map size={14} />}>Assigned Tours</Tabs.Tab>
                                        <Tabs.Tab value="location" leftSection={<MapPin size={14} />}>Live Tracker</Tabs.Tab>
                                    </Tabs.List>

                                    <Tabs.Panel value="rides" p="md">
                                        <Table verticalSpacing="sm" highlightOnHover>
                                            <Table.Thead>
                                                <Table.Tr>
                                                    <Table.Th>ID</Table.Th>
                                                    <Table.Th>Date</Table.Th>
                                                    <Table.Th>Route</Table.Th>
                                                    <Table.Th>Status</Table.Th>
                                                    <Table.Th>Earnings</Table.Th>
                                                    <Table.Th />
                                                </Table.Tr>
                                            </Table.Thead>
                                            <Table.Tbody>
                                                {driver.assigned_ride_bookings.length > 0 ? driver.assigned_ride_bookings.map((ride) => (
                                                    <Table.Tr key={ride.id}>
                                                        <Table.Td><Text size="sm" fw={500}>#{ride.booking_number}</Text></Table.Td>
                                                        <Table.Td><Text size="xs">{new Date(ride.scheduled_at).toLocaleDateString()}</Text></Table.Td>
                                                        <Table.Td>
                                                            <Text size="xs" lineClamp={1}>{ride.pickup_location} → {ride.dropoff_location}</Text>
                                                        </Table.Td>
                                                        <Table.Td>
                                                            <Badge size="xs" variant="light" color={ride.status === 'completed' ? 'green' : 'blue'}>{ride.status}</Badge>
                                                        </Table.Td>
                                                        <Table.Td><Text size="sm" fw={600}>₹{ride.total_fare}</Text></Table.Td>
                                                        <Table.Td>
                                                            <ActionIcon variant="subtle" color="gray" component={Link} href={`/admin/ride-bookings/${ride.id}`}>
                                                                <ExternalLink size={14} />
                                                            </ActionIcon>
                                                        </Table.Td>
                                                    </Table.Tr>
                                                )) : (
                                                    <Table.Tr><Table.Td colSpan={6} py="xl"><Text ta="center" color="dimmed">No ride activity recorded yet</Text></Table.Td></Table.Tr>
                                                )}
                                            </Table.Tbody>
                                        </Table>
                                    </Tabs.Panel>

                                    <Tabs.Panel value="tours" p="md">
                                        <Table verticalSpacing="sm">
                                            <Table.Tbody>
                                                {driver.tour_driver_assignments.length > 0 ? driver.tour_driver_assignments.map((td) => (
                                                    <Table.Tr key={td.id}>
                                                        <Table.Td><Text size="sm" fw={600}>{td.schedule?.tour?.title}</Text></Table.Td>
                                                        <Table.Td><Text size="xs">{td.role ?? 'Tour Driver'}</Text></Table.Td>
                                                        <Table.Td>
                                                            <ActionIcon variant="subtle" color="blue" component={Link} href={`/admin/tours/${td.schedule?.tour?.id}`}>
                                                                <ExternalLink size={14} />
                                                            </ActionIcon>
                                                        </Table.Td>
                                                    </Table.Tr>
                                                )) : (
                                                    <Table.Tr><Table.Td colSpan={3} py="xl"><Text ta="center" color="dimmed">No tours assigned to this driver</Text></Table.Td></Table.Tr>
                                                )}
                                            </Table.Tbody>
                                        </Table>
                                    </Tabs.Panel>

                                    <Tabs.Panel value="location" p="xl">
                                        <Stack align="center" py="xl">
                                            <MapPin size={48} strokeWidth={1} color="var(--mantine-color-gray-4)" />
                                            <Text color="dimmed" size="sm">Google Maps interactive tracker integration pending.</Text>
                                            <Text size="xs" ta="center">Last Coordinates: {driver.driver_availability?.current_lat || 'N/A'}, {driver.driver_availability?.current_lng || 'N/A'}</Text>
                                        </Stack>
                                    </Tabs.Panel>
                                </Tabs>
                            </Paper>
                        </Stack>
                    </Grid.Col>
                </Grid>
            </Stack>
        </AdminLayout>
    );
}
