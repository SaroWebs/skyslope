import React, { useState, useEffect, useCallback, useRef } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    Stack, 
    Group, 
    Text, 
    Title, 
    Paper, 
    Button, 
    ThemeIcon, 
    Badge, 
    ActionIcon, 
    Card, 
    Avatar, 
    Box, 
    rem,
    Divider,
    SimpleGrid,
    Switch,
    RingProgress,
    ScrollArea,
    Transition,
    Alert,
    Indicator,
    TextInput
} from '@mantine/core';
import { 
    Car, 
    MapPin, 
    Navigation, 
    Phone, 
    MessageCircle, 
    DollarSign, 
    TrendingUp, 
    Star, 
    Clock, 
    Zap, 
    ShieldCheck, 
    Bell, 
    History,
    Wallet,
    Power,
    Check,
    X,
    ChevronRight,
    Play,
    CheckCircle2,
    Lock
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { useAppNotifications } from '@/app';
import { useDriverRidesChannel, trackingApi } from '@/lib/useWebSocket';
import axios from '@/lib/axios';

interface RideBooking {
    id: number;
    booking_number: string;
    status: string;
    pickup_location: string;
    pickup_lat: number;
    pickup_lng: number;
    dropoff_location: string;
    dropoff_lat: number;
    dropoff_lng: number;
    scheduled_at: string;
    total_fare: number;
    distance_km: number;
    customer_name: string;
    customer_phone: string;
    service_type: string;
    payment_status?: 'pending' | 'paid' | 'failed' | 'refunded';
    payment_method?: 'cash' | 'card' | 'wallet' | 'upi' | 'bank_transfer';
    vehicle_number?: string;
    start_pin_verified_at?: string | null;
}

interface DashboardProps {
    title: string;
    user: {
        id: number;
        name: string;
        email: string;
        phone: string;
        avatar?: string;
    };
    wallet?: {
        balance: number;
        currency: string;
    };
    stats?: {
        today_earnings: number;
        today_rides: number;
        total_rides: number;
        rating: number;
    };
}

export default function DriverDashboard({ title, user, wallet, stats }: DashboardProps) {
    const { logout } = useAuth();
    const addNotification = useAppNotifications();
    
    // UI State
    const [isAvailable, setIsAvailable] = useState(true);
    const [activeRide, setActiveRide] = useState<RideBooking | null>(null);
    const [pendingRides, setPendingRides] = useState<RideBooking[]>([]);
    const [currentLocation, setCurrentLocation] = useState<{ lat: number; lng: number } | null>(null);
    const [loading, setLoading] = useState(true);
    const [startPin, setStartPin] = useState('');
    const [showPinInput, setShowPinInput] = useState(false);

    // Real-time infrastructure
    useDriverRidesChannel(user.id, (status) => {
        addNotification(`New Update: Ride ${status.booking_number} is now ${status.status}`, 'info');
        fetchInitialData();
    });

    const fetchInitialData = useCallback(async () => {
        try {
            const [activeRes, pendingRes] = await Promise.all([
                axios.get('/driver/active-ride'),
                axios.get('/driver/pending-rides')
            ]);
            if (activeRes.data.success) setActiveRide(activeRes.data.ride);
            if (pendingRes.data.success) setPendingRides(pendingRes.data.rides);
        } catch (error) {
            console.error('Failed to sync driver state');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchInitialData();
        
        // Geolocation setup
        if ('geolocation' in navigator) {
            const watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const loc = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    setCurrentLocation(loc);
                    if (isAvailable) {
                        trackingApi.updateDriverLocation(loc.lat, loc.lng, true);
                    }
                },
                (err) => console.error(err),
                { enableHighAccuracy: true }
            );
            return () => navigator.geolocation.clearWatch(watchId);
        }
    }, [fetchInitialData, isAvailable]);

    const handleToggleAvailability = async () => {
        const nextState = !isAvailable;
        setIsAvailable(nextState);
        if (currentLocation) {
            await trackingApi.updateDriverLocation(currentLocation.lat, currentLocation.lng, nextState);
        }
        addNotification(`You are now ${nextState ? 'Online' : 'Offline'}`, nextState ? 'success' : 'info');
    };

    const handleAcceptRide = async (rideId: number) => {
        try {
            const res = await axios.post(`/driver/rides/${rideId}/accept`);
            if (res.data.success) {
                setActiveRide(res.data.ride);
                setPendingRides(prev => prev.filter(r => r.id !== rideId));
                addNotification('Ride accepted! Start navigation.', 'success');
            }
        } catch (error) {
            addNotification('Failed to accept ride', 'error');
        }
    };

    const handleUpdateStatus = async (status: string) => {
        if (!activeRide) return;
        
        if (status === 'in_transit' && !showPinInput) {
            setShowPinInput(true);
            return;
        }

        try {
            await trackingApi.updateRideStatus(activeRide.id, status, undefined, status === 'in_transit' ? startPin : undefined);
            addNotification(`Status updated: ${status.replace('_', ' ')}`, 'success');
            setShowPinInput(false);
            setStartPin('');
            fetchInitialData();
        } catch (error: any) {
            addNotification(error.response?.data?.message || 'Error updating status', 'error');
        }
    };

    const handlePayment = async (mode: string) => {
        if (!activeRide) return;
        try {
            await axios.post(`/driver/rides/${activeRide.id}/payment-status`, {
                payment_status: 'paid',
                payment_method: mode
            });
            addNotification(`Payment confirmed via ${mode.toUpperCase()}`, 'success');
            fetchInitialData();
        } catch (error) {
            addNotification('Failed to update payment', 'error');
        }
    };

    return (
        <AppLayout title="Control Center">
            <Head title="Driver Dashboard" />

            <Stack gap="lg">
                {/* Driver Status HUD */}
                <Paper radius="md" p="md" withBorder shadow="sm" style={{ 
                    background: isAvailable ? 'var(--mantine-color-green-0)' : 'var(--mantine-color-gray-0)',
                    borderColor: isAvailable ? 'var(--mantine-color-green-2)' : 'var(--mantine-color-gray-3)',
                    transition: 'all 0.3s ease'
                }}>
                    <Group justify="space-between">
                        <Group gap="sm">
                            <Indicator color={isAvailable ? 'green' : 'gray'} processing={isAvailable} size={10} offset={2}>
                                <Avatar size={40} radius="xl" color="orange">
                                    {user.name.charAt(0)}
                                </Avatar>
                            </Indicator>
                            <Stack gap={0}>
                                <Text fw={700} size="sm">Available to dispatch</Text>
                                <Text size="xs" color="dimmed">{isAvailable ? 'Broadcasting live location' : 'Go online to receive rides'}</Text>
                            </Stack>
                        </Group>
                        <Switch 
                            size="lg" 
                            color="green" 
                            checked={isAvailable} 
                            onChange={handleToggleAvailability}
                            thumbIcon={isAvailable ? <Check size={14} color="green" /> : <X size={14} color="gray" />}
                        />
                    </Group>
                </Paper>

                {/* Earnings & Stats Grid */}
                <SimpleGrid cols={2} spacing="md">
                    <Card withBorder radius="md" p="md">
                        <Group justify="space-between" mb="xs">
                            <ThemeIcon variant="light" color="blue">
                                <DollarSign size={18} />
                            </ThemeIcon>
                            <Text size="xs" color="dimmed" fw={700}>TODAY</Text>
                        </Group>
                        <Text size="xl" fw={900}>₹{stats?.today_earnings || 0}</Text>
                        <Text size="xs" color="dimmed">from {stats?.today_rides || 0} journeys</Text>
                    </Card>

                    <Card withBorder radius="md" p="md">
                        <Group justify="space-between" mb="xs">
                            <ThemeIcon variant="light" color="orange">
                                <Star size={18} />
                            </ThemeIcon>
                            <Text size="xs" color="dimmed" fw={700}>RATING</Text>
                        </Group>
                        <Text size="xl" fw={900}>{stats?.rating?.toFixed(1) || '5.0'}</Text>
                        <Group gap={4}>
                            <Rating value={stats?.rating || 5} size="xs" readOnly />
                        </Group>
                    </Card>
                </SimpleGrid>

                {/* Active Mission HUD */}
                {activeRide && (
                    <Box>
                        <Divider label="Active Mission" labelPosition="center" mb="md" />
                        <Paper radius="md" p="md" withBorder shadow="md" style={{ borderLeft: '4px solid var(--mantine-color-blue-6)' }}>
                            <Stack gap="md">
                                <Group justify="space-between">
                                    <Badge color={activeRide.status === 'in_transit' ? 'green' : 'blue'} variant="filled">
                                        {activeRide.status.replace('_', ' ')}
                                    </Badge>
                                    <Text fw={900} size="xs" color="dimmed">#{activeRide.booking_number}</Text>
                                </Group>

                                <Box>
                                    <Group gap="sm" mb="xs">
                                        <div style={{ width: rem(8), height: rem(8), borderRadius: '50%', background: 'var(--mantine-color-green-5)' }} />
                                        <Text size="sm" fw={700} truncate>{activeRide.pickup_location}</Text>
                                    </Group>
                                    <Group gap="sm">
                                        <div style={{ width: rem(8), height: rem(8), borderRadius: '50%', background: 'var(--mantine-color-red-5)' }} />
                                        <Text size="sm" fw={700} truncate>{activeRide.dropoff_location}</Text>
                                    </Group>
                                </Box>

                                <SimpleGrid cols={2} spacing="xs">
                                    <Button 
                                        component="a" 
                                        target="_blank"
                                        href={`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(activeRide.status === 'in_transit' ? activeRide.dropoff_location : activeRide.pickup_location)}`}
                                        variant="light" 
                                        leftSection={<Navigation size={16} />}
                                    >
                                        Navigate
                                    </Button>
                                    <Button 
                                        component="a" 
                                        href={`tel:${activeRide.customer_phone}`}
                                        variant="light" 
                                        color="green" 
                                        leftSection={<Phone size={16} />}
                                    >
                                        Call Rider
                                    </Button>
                                </SimpleGrid>

                                {showPinInput ? (
                                    <Stack gap="xs">
                                        <TextInput 
                                            placeholder="Enter 4-digit PIN" 
                                            label="Security Token Verification"
                                            radius="md"
                                            maxLength={4}
                                            value={startPin}
                                            onChange={(e) => setStartPin(e.target.value.replace(/\D/g, ''))}
                                            leftSection={<Lock size={16} />}
                                        />
                                        <Group grow>
                                            <Button variant="default" onClick={() => setShowPinInput(false)}>Cancel</Button>
                                            <Button color="green" onClick={() => handleUpdateStatus('in_transit')}>Start Journey</Button>
                                        </Group>
                                    </Stack>
                                ) : (
                                    <>
                                        {activeRide.status === 'driver_assigned' && (
                                            <Button color="blue" fullWidth onClick={() => handleUpdateStatus('driver_arriving')}>I'm Heading to Pickup</Button>
                                        )}
                                        {activeRide.status === 'driver_arriving' && (
                                            <Button color="orange" fullWidth onClick={() => handleUpdateStatus('pickup')}>I have Arrived</Button>
                                        )}
                                        {activeRide.status === 'pickup' && (
                                            <Button color="green" fullWidth onClick={() => setShowPinInput(true)}>Verify PIN & Start</Button>
                                        )}
                                        {activeRide.status === 'in_transit' && (
                                            <Button color="green" fullWidth onClick={() => handleUpdateStatus('completed')}>Arrived at Dropoff</Button>
                                        )}
                                    </>
                                )}
                                
                                {activeRide.status === 'completed' && activeRide.payment_status !== 'paid' && (
                                    <Box>
                                        <Text size="xs" fw={700} mb={5}>COLLECT FARE: ₹{activeRide.total_fare}</Text>
                                        <SimpleGrid cols={2}>
                                            <Button color="green" onClick={() => handlePayment('cash')}>Cash Received</Button>
                                            <Button color="indigo" onClick={() => handlePayment('wallet')}>Wallet Deletion</Button>
                                        </SimpleGrid>
                                    </Box>
                                )}
                            </Stack>
                        </Paper>
                    </Box>
                )}

                {/* Dispatch Queue (Requests) */}
                {!activeRide && isAvailable && (
                    <Box>
                        <Group justify="space-between" mb="xs">
                            <Title order={5} fw={900}>Live Dispatch Queue</Title>
                            <Badge variant="light" color="green">{pendingRides.length} Requests</Badge>
                        </Group>
                        <Stack gap="xs">
                            {pendingRides.length > 0 ? (
                                pendingRides.map(ride => (
                                    <Paper key={ride.id} p="md" radius="md" withBorder shadow="xs">
                                        <Stack gap="sm">
                                            <Group justify="space-between">
                                                <Badge size="xs" variant="outline">{ride.service_type}</Badge>
                                                <Text fw={900} color="green.7">₹{ride.total_fare}</Text>
                                            </Group>
                                            <Box>
                                                <Group gap="xs" mb={4}>
                                                    <MapPin size={14} color="var(--mantine-color-green-5)" />
                                                    <Text size="xs" fw={600} truncate>{ride.pickup_location}</Text>
                                                </Group>
                                                <Group gap="xs">
                                                    <MapPin size={14} color="var(--mantine-color-red-5)" />
                                                    <Text size="xs" color="dimmed" truncate>to {ride.dropoff_location}</Text>
                                                </Group>
                                            </Box>
                                            <Group grow gap="xs">
                                                <Button size="xs" variant="default">Decline</Button>
                                                <Button size="xs" color="green" onClick={() => handleAcceptRide(ride.id)}>Accept Quest</Button>
                                            </Group>
                                        </Stack>
                                    </Paper>
                                ))
                            ) : (
                                <Paper p="xl" radius="md" withBorder style={{ borderStyle: 'dashed', textAlign: 'center' }}>
                                    <Stack align="center" gap="xs">
                                        <Loader color="gray" size="sm" type="dots" />
                                        <Text size="sm" color="dimmed">Scanning for nearby signals...</Text>
                                    </Stack>
                                </Paper>
                            )}
                        </Stack>
                    </Box>
                )}

                {/* Passive Engagement */}
                <SimpleGrid cols={3} spacing="xs">
                    <UnstyledButton 
                        p="sm" 
                        style={{ textAlign: 'center', borderRadius: 'var(--mantine-radius-md)', background: '#fff', border: '1px solid var(--mantine-color-gray-2)' }}
                        component={Link}
                        href="/driver/wallet"
                    >
                        <Wallet size={20} color="var(--mantine-color-gray-6)" />
                        <Text size={rem(10)} fw={600} mt={4}>Wallet</Text>
                    </UnstyledButton>
                    <UnstyledButton 
                        p="sm" 
                        style={{ textAlign: 'center', borderRadius: 'var(--mantine-radius-md)', background: '#fff', border: '1px solid var(--mantine-color-gray-2)' }}
                        component={Link}
                        href="/driver/history"
                    >
                        <History size={20} color="var(--mantine-color-gray-6)" />
                        <Text size={rem(10)} fw={600} mt={4}>Ledger</Text>
                    </UnstyledButton>
                    <UnstyledButton 
                        p="sm" 
                        style={{ textAlign: 'center', borderRadius: 'var(--mantine-radius-md)', background: '#fff', border: '1px solid var(--mantine-color-gray-2)' }}
                        component={Link}
                        href="/driver/safety"
                    >
                        <ShieldCheck size={20} color="var(--mantine-color-gray-6)" />
                        <Text size={rem(10)} fw={600} mt={4}>SOS</Text>
                    </UnstyledButton>
                </SimpleGrid>
            </Stack>
        </AppLayout>
    );
}
