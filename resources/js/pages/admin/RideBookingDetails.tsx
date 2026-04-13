import React, { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { 
    Grid, 
    Paper, 
    Text, 
    Group, 
    Stack, 
    Avatar, 
    Badge, 
    Button, 
    ActionIcon, 
    Divider,
    SimpleGrid,
    Box,
    Card,
    Tooltip,
    Select,
    Timeline,
    ThemeIcon,
    Alert
} from '@mantine/core';
import { 
    ArrowLeft, 
    Navigation, 
    MapPin, 
    Car, 
    User, 
    Phone, 
    Mail, 
    Calendar, 
    Clock, 
    IndianRupee,
    Truck,
    ShieldCheck,
    AlertCircle,
    RotateCcw,
    Map,
    ExternalLink,
    CheckCircle2
} from 'lucide-react';

interface DriverOption {
    id: number;
    name: string;
    email: string;
    phone?: string;
    is_online: boolean;
    is_available: boolean;
    rating?: number | null;
    vehicle_number?: string | null;
}

interface RideBooking {
    id: number;
    booking_number: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    pickup_location: string;
    dropoff_location?: string;
    scheduled_at: string;
    total_fare: number;
    status: string;
    payment_status: string;
    service_type: string;
    special_requests?: string;
    driver_id?: number | null;
    customer?: {
        id: number;
        name: string;
        phone?: string;
        email: string;
    };
    driver?: {
        id: number;
        name: string;
        phone?: string;
        email: string;
    };
}

interface Props {
    title?: string;
    booking: RideBooking;
    drivers: DriverOption[];
    can_undo_last_change?: boolean;
}

export default function RideBookingDetails({ title = 'Ride Booking Details', booking, drivers, can_undo_last_change = false }: Props) {
    const [selectedDriverId, setSelectedDriverId] = useState<string>(booking.driver_id ? String(booking.driver_id) : '');
    const [assigningDriver, setAssigningDriver] = useState(false);
    const [undoing, setUndoing] = useState(false);

    const canAssignDriver = !['completed', 'cancelled'].includes(booking.status);
    const selectedDriver = useMemo(
        () => drivers.find((driver) => String(driver.id) === selectedDriverId),
        [drivers, selectedDriverId]
    );

    const handleAssignDriver = () => {
        if (!selectedDriverId) return;
        setAssigningDriver(true);
        router.post(`/admin/ride-bookings/${booking.id}/assign-driver`, {
            driver_id: Number(selectedDriverId)
        }, {
            onFinish: () => setAssigningDriver(false),
            preserveScroll: true
        });
    };

    const handleUndoLastChange = () => {
        setUndoing(true);
        router.post(`/admin/ride-bookings/${booking.id}/undo-last-change`, {}, {
            onFinish: () => setUndoing(false),
            preserveScroll: true
        });
    };

    const getStatusVariant = (status: string) => {
        const s = status.toLowerCase();
        if (s === 'pending') return { color: 'yellow', icon: <Clock size={16} /> };
        if (s === 'confirmed') return { color: 'blue', icon: <CheckCircle2 size={16} /> };
        if (s === 'completed') return { color: 'green', icon: <ShieldCheck size={16} /> };
        if (s === 'cancelled') return { color: 'red', icon: <XCircle size={16} /> };
        return { color: 'indigo', icon: <Car size={16} /> };
    };

    const statusInfo = getStatusVariant(booking.status);

    return (
        <AdminLayout title={title}>
            <Head title={`Ride #${booking.booking_number}`} />

            <Stack gap="lg">
                <Group justify="space-between">
                    <Button 
                        variant="subtle" 
                        color="gray" 
                        leftSection={<ArrowLeft size={16} />}
                        component={Link}
                        href="/admin/ride-bookings"
                    >
                        Back to Ride Bookings
                    </Button>
                    <Group gap="sm">
                        {can_undo_last_change && (
                            <Button 
                                variant="outline" 
                                color="orange" 
                                leftSection={<RotateCcw size={16} />}
                                onClick={handleUndoLastChange}
                                loading={undoing}
                            >
                                Undo Last Change
                            </Button>
                        )}
                        <Button variant="light" color="blue" leftSection={<Navigation size={16} />}>
                            Live Track
                        </Button>
                    </Group>
                </Group>

                <Grid gutter="lg">
                    {/* Main Content */}
                    <Grid.Col span={{ base: 12, md: 8 }}>
                        <Stack gap="lg">
                            <Paper p="xl" radius="md" withBorder>
                                <Group justify="space-between" mb="xl">
                                    <Stack gap={0}>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase">Booking Number</Text>
                                        <Text size="h3" fw={800}>{booking.booking_number}</Text>
                                    </Stack>
                                    <Badge 
                                        size="xl" 
                                        radius="sm" 
                                        color={statusInfo.color} 
                                        leftSection={statusInfo.icon}
                                        variant="filled"
                                    >
                                        {booking.status.replace('_', ' ').toUpperCase()}
                                    </Badge>
                                </Group>

                                <SimpleGrid cols={{ base: 1, sm: 3 }} spacing="xl">
                                    <Box>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase" mb={4}>Service Type</Text>
                                        <Group gap="xs">
                                            <Car size={14} color="var(--mantine-color-blue-6)" />
                                            <Text size="sm" fw={600}>{booking.service_type || 'Standard Ride'}</Text>
                                        </Group>
                                    </Box>
                                    <Box>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase" mb={4}>Scheduled At</Text>
                                        <Group gap="xs">
                                            <Calendar size={14} color="var(--mantine-color-blue-6)" />
                                            <Text size="sm" fw={600}>{new Date(booking.scheduled_at).toLocaleString()}</Text>
                                        </Group>
                                    </Box>
                                    <Box>
                                        <Text size="xs" color="dimmed" fw={700} tt="uppercase" mb={4}>Total Fare</Text>
                                        <Group gap="xs">
                                            <IndianRupee size={16} color="var(--mantine-color-green-7)" />
                                            <Text size="lg" fw={800}>₹{booking.total_fare}</Text>
                                        </Group>
                                    </Box>
                                </SimpleGrid>

                                <Divider my="xl" label="Route Information" labelPosition="center" />

                                <Timeline active={1} bulletSize={30} lineWidth={2}>
                                    <Timeline.Item 
                                        bullet={<ThemeIcon size={22} radius="xl" color="blue"><MapPin size={12} /></ThemeIcon>} 
                                        title={<Text size="sm" fw={700} color="dimmed" tt="uppercase">Pickup Location</Text>}
                                    >
                                        <Text size="sm" fw={500} mt={4}>{booking.pickup_location}</Text>
                                    </Timeline.Item>

                                    <Timeline.Item 
                                        bullet={<ThemeIcon size={22} radius="xl" color="red"><Navigation size={12} /></ThemeIcon>} 
                                        title={<Text size="sm" fw={700} color="dimmed" tt="uppercase">Dropoff Location</Text>}
                                    >
                                        <Text size="sm" fw={500} mt={4}>{booking.dropoff_location || 'Point to Point Ride'}</Text>
                                    </Timeline.Item>
                                </Timeline>
                            </Paper>

                            <Paper p="xl" radius="md" withBorder>
                                <Text fw={700} size="lg" mb="lg">Customer Details</Text>
                                <Group gap="xl">
                                    <Avatar size={60} radius="xl" color="blue">
                                        {booking.customer_name?.charAt(0)}
                                    </Avatar>
                                    <Box style={{ flex: 1 }}>
                                        <Text size="lg" fw={700}>{booking.customer_name}</Text>
                                        <Group gap="lg" mt="xs">
                                            <Group gap={6}>
                                                <Phone size={14} color="gray" />
                                                <Text size="sm" color="dimmed">{booking.customer_phone}</Text>
                                            </Group>
                                            <Group gap={6}>
                                                <Mail size={14} color="gray" />
                                                <Text size="sm" color="dimmed">{booking.customer_email}</Text>
                                            </Group>
                                        </Group>
                                    </Box>
                                    <Button variant="outline" size="sm">View User History</Button>
                                </Group>

                                {booking.special_requests && (
                                    <>
                                        <Divider my="lg" dashed />
                                        <Alert variant="light" color="indigo" title="Special Requests" icon={<AlertCircle size={16} />}>
                                            <Text size="sm">{booking.special_requests}</Text>
                                        </Alert>
                                    </>
                                )}
                            </Paper>
                        </Stack>
                    </Grid.Col>

                    {/* Sidebar: Driver Assignment */}
                    <Grid.Col span={{ base: 12, md: 4 }}>
                        <Stack gap="lg">
                            <Paper p="xl" radius="md" withBorder>
                                <Text fw={700} size="lg" mb="md">Driver Assignment</Text>
                                
                                {booking.driver ? (
                                    <Card withBorder padding="md" radius="md" mb="xl" bg="gray.0">
                                        <Group justify="space-between" mb="xs">
                                             <Text size="xs" color="dimmed" fw={700} tt="uppercase">Currently Assigned</Text>
                                             <Badge size="xs" color="green">Verified</Badge>
                                        </Group>
                                        <Group gap="sm">
                                            <Avatar color="teal" radius="md">{booking.driver.name.charAt(0)}</Avatar>
                                            <Stack gap={0}>
                                                <Text size="sm" fw={700}>{booking.driver.name}</Text>
                                                <Text size="xs" color="dimmed">{booking.driver.phone}</Text>
                                            </Stack>
                                        </Group>
                                    </Card>
                                ) : (
                                    <Alert variant="light" color="orange" mb="xl" icon={<AlertCircle size={16} />}>
                                        No driver has been assigned to this booking yet.
                                    </Alert>
                                )}

                                <Stack gap="xs">
                                    <Select 
                                        label="Select Available Driver"
                                        placeholder="Pick a driver from the fleet"
                                        data={drivers.map(d => ({
                                            value: String(d.id),
                                            label: `${d.name} (${d.is_online ? 'Online' : 'Offline'})${d.vehicle_number ? ` - ${d.vehicle_number}` : ''}`,
                                            disabled: !d.is_available && d.id !== booking.driver_id
                                        }))}
                                        value={selectedDriverId}
                                        onChange={(val) => setSelectedDriverId(val || '')}
                                        disabled={!canAssignDriver || assigningDriver}
                                        searchable
                                        radius="md"
                                    />
                                    
                                    {selectedDriver && selectedDriverId !== String(booking.driver_id) && (
                                        <Paper p="sm" withBorder bg="blue.0" radius="md" mt="xs">
                                            <Text size="xs" fw={700} color="blue.9">Selected Driver Info:</Text>
                                            <Text size="xs">Rating: {selectedDriver.rating ? `${selectedDriver.rating}/5` : 'New'}</Text>
                                            <Text size="xs">Vehicle: {selectedDriver.vehicle_number || 'N/A'}</Text>
                                        </Paper>
                                    )}

                                    <Button 
                                        fullWidth 
                                        mt="md" 
                                        color="blue" 
                                        radius="md"
                                        onClick={handleAssignDriver}
                                        loading={assigningDriver}
                                        disabled={!canAssignDriver || !selectedDriverId || selectedDriverId === String(booking.driver_id)}
                                        leftSection={<Truck size={16} />}
                                    >
                                        {booking.driver ? 'Reassign Driver' : 'Assign Driver'}
                                    </Button>
                                </Stack>
                            </Paper>

                            <Paper p="xl" radius="md" withBorder>
                                <Text fw={700} size="sm" mb="md">Actions</Text>
                                <Stack gap="sm">
                                    <Button fullWidth variant="light" color="gray" leftSection={<Mail size={14} />}>Resend Email</Button>
                                    <Button fullWidth variant="light" color="teal" leftSection={<Map size={14} />}>View On Maps</Button>
                                </Stack>
                            </Paper>
                        </Stack>
                    </Grid.Col>
                </Grid>
            </Stack>
        </AdminLayout>
    );
}

const XCircle = ({ size, color }: any) => (
    <Box style={{ color }}>
         {/* Simple X icon */}
         <svg 
            width={size} height={size} 
            viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" 
            strokeLinecap="round" strokeLinejoin="round"
         >
            <circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>
         </svg>
    </Box>
);
