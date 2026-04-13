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
    Timeline,
    Alert,
    Rating,
    Textarea,
    NumberInput,
    Transition,
    Progress,
    CopyButton,
    Tooltip,
    Indicator,
    SimpleGrid
} from '@mantine/core';
import { 
    MapPin, 
    Phone, 
    MessageCircle, 
    X, 
    Navigation, 
    Clock, 
    CreditCard, 
    ShieldCheck, 
    Star, 
    History,
    CheckCircle2,
    Info,
    ChevronLeft,
    Car,
    User,
    Copy,
    Share2,
    Zap
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useAuth } from '@/context/AuthContext';
import { useAppNotifications } from '@/app';
import { useRideTracking, trackingApi, RideLocationEvent, RideStatusEvent } from '@/lib/useWebSocket';
import axios from '@/lib/axios';

interface RideBooking {
    id: number;
    booking_number: string;
    status: string;
    pickup_location: string;
    dropoff_location?: string;
    pickup_lat: number;
    pickup_lng: number;
    dropoff_lat?: number;
    dropoff_lng?: number;
    scheduled_at: string;
    total_fare: number;
    distance_km?: number;
    estimated_duration?: number;
    customer_name: string;
    customer_email: string;
    customer_phone: string;
    service_type: string;
    payment_method: string;
    payment_status: string;
    special_requests?: string;
    created_at: string;
    driver?: {
        id: number;
        name: string;
        phone: string;
        rating?: number;
        vehicle_number?: string;
        vehicle_type?: string;
    };
    current_lat?: number;
    current_lng?: number;
    last_location_update?: string;
    start_ride_pin?: string;
    start_pin_verified_at?: string;
    my_review?: {
        id: number;
        rating: number;
        comment?: string;
    } | null;
    my_tips_total?: number;
}

interface RideBookingDetailsProps {
    booking?: RideBooking | null;
    user: any;
}

const getStatusColor = (status: string) => {
    switch (status) {
        case 'pending': return 'yellow';
        case 'confirmed': return 'blue';
        case 'driver_assigned': return 'indigo';
        case 'driver_arriving': return 'purple';
        case 'pickup': return 'cyan';
        case 'in_transit': return 'green';
        case 'completed': return 'gray';
        case 'cancelled': return 'red';
        default: return 'gray';
    }
}

export default function RideBookingDetails({ booking: initialBooking, user }: RideBookingDetailsProps) {
    const addNotification = useAppNotifications();
    const [booking, setBooking] = useState<RideBooking | null>(initialBooking || null);
    const [currentStatus, setCurrentStatus] = useState(booking?.status || 'pending');
    const [driverLocation, setDriverLocation] = useState<{ lat: number; lng: number } | null>(
        booking?.current_lat && booking?.current_lng 
            ? { lat: booking.current_lat, lng: booking.current_lng }
            : null
    );
    const [eta, setEta] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);
    
    // Review & Tip State
    const [reviewRating, setReviewRating] = useState(booking?.my_review?.rating || 0);
    const [reviewComment, setReviewComment] = useState(booking?.my_review?.comment || '');
    const [tipAmount, setTipAmount] = useState<number | string>('');

    // Real-time location & status updates
    const handleLocationUpdate = useCallback((data: RideLocationEvent) => {
        setDriverLocation({ lat: data.latitude, lng: data.longitude });
        if (data.eta) setEta(data.eta);
        // Silently update booking object if needed
    }, []);

    const handleStatusUpdate = useCallback((data: RideStatusEvent) => {
        setCurrentStatus(data.status);
        addNotification(`Ride Status Updated: ${data.status.replace('_', ' ')}`, 'info');
        // Refresh full data for status-specific fields
        router.reload({ only: ['booking'] });
    }, [addNotification]);

    useRideTracking(booking?.id || null, handleLocationUpdate, handleStatusUpdate);

    useEffect(() => {
        if (initialBooking) {
            setBooking(initialBooking);
            setCurrentStatus(initialBooking.status);
        }
    }, [initialBooking]);

    if (!booking) return <AppLayout title="Error">Ride not found</AppLayout>;

    const handleCancel = async () => {
        if (!confirm('Are you sure you want to cancel this ride?')) return;
        setLoading(true);
        try {
            await axios.put(`/api/ride-bookings/${booking.id}/cancel`);
            addNotification('Ride cancelled successfully', 'success');
            router.visit('/customer/dashboard');
        } catch (error: any) {
            addNotification(error.response?.data?.message || 'Failed to cancel', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmitReview = async () => {
        if (reviewRating === 0) return;
        setLoading(true);
        try {
            await axios.post(`/api/ride-bookings/${booking.id}/review`, {
                rating: reviewRating,
                comment: reviewComment
            });
            addNotification('Thank you for your feedback!', 'success');
            router.reload();
        } catch (error) {
            addNotification('Failed to submit review', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleSendTip = async () => {
        const amount = Number(tipAmount);
        if (!amount || amount < 1) return;
        setLoading(true);
        try {
            await axios.post(`/api/ride-bookings/${booking.id}/tip`, { amount });
            addNotification(`$${amount} tip sent to your driver!`, 'success');
            setTipAmount('');
            router.reload();
        } catch (error) {
            addNotification('Failed to send tip', 'error');
        } finally {
            setLoading(false);
        }
    };

    const isLive = ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'].includes(currentStatus);
    const isCompleted = currentStatus === 'completed';
    const canCancel = ['pending', 'confirmed', 'driver_assigned', 'driver_arriving'].includes(currentStatus);

    return (
        <AppLayout title="Ride Details" backPath="/customer/dashboard">
            <Head title={`Ride #${booking.booking_number}`} />

            <Stack gap="lg">
                {/* Visual Status Header */}
                <Box>
                    <Group justify="space-between" align="flex-start">
                        <Stack gap={2}>
                            <Title order={4} fw={900}>Ride #{booking.booking_number}</Title>
                            <Text size="xs" color="dimmed">Created on {new Date(booking.created_at).toLocaleDateString()}</Text>
                        </Stack>
                        <Badge 
                            variant="filled" 
                            color={getStatusColor(currentStatus)} 
                            size="lg" 
                            radius="sm"
                            leftSection={isLive ? <Indicator size={6} color="white" processing offset={-2}><Zap size={10} /></Indicator> : null}
                        >
                            {currentStatus.replace('_', ' ')}
                        </Badge>
                    </Group>
                </Box>

                {/* Map Display (Stylized Placeholder) */}
                <Paper radius="md" overflow="hidden" withBorder h={220} pos="relative">
                    <Box 
                        style={{ 
                            height: '100%', 
                            width: '100%', 
                            background: 'url(https://images.unsplash.com/photo-1524661135-423995f22d0b?q=80&w=2074&auto=format&fit=crop) center/cover',
                            filter: 'grayscale(0.3) brightness(0.9)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center'
                        }}
                    >
                        <Stack align="center" gap="xs">
                            <ThemeIcon size={40} radius="xl" color="blue" shadow="md">
                                <Navigation size={20} />
                            </ThemeIcon>
                            <Paper py={4} px={12} radius="xl" shadow="xs" withBorder>
                                <Text size="xs" fw={700}>
                                    {isLive ? 'Tracking Live Location' : 'Route Map Ready'}
                                </Text>
                            </Paper>
                        </Stack>
                    </Box>
                    
                    {driverLocation && (
                        <Box pos="absolute" bottom={10} right={10}>
                            <Badge color="blue" variant="filled" size="xs">Live GPS</Badge>
                        </Box>
                    )}
                </Paper>

                {/* Driver & Action HUD */}
                {booking.driver ? (
                    <Paper radius="md" p="md" withBorder shadow="sm">
                        <Stack gap="md">
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <Avatar size={48} radius="md" color="blue">
                                        {booking.driver.name.charAt(0)}
                                    </Avatar>
                                    <Stack gap={0}>
                                        <Text fw={700} size="sm">{booking.driver.name}</Text>
                                        <Group gap={4}>
                                            <Rating value={booking.driver.rating || 5} readOnly size="xs" />
                                            <Text size="xs" color="dimmed">({booking.driver.rating || '5.0'})</Text>
                                        </Group>
                                    </Stack>
                                </Group>
                                <Stack gap={2} align="flex-end">
                                    <Text fw={900} size="xs" color="blue">{booking.driver.vehicle_number || 'TBA'}</Text>
                                    <Text size={rem(10)} color="dimmed" tt="uppercase">{booking.driver.vehicle_type || 'Classic Sedan'}</Text>
                                </Stack>
                            </Group>

                            {!isCompleted && !currentStatus.includes('cancelled') && (
                                <SimpleGrid cols={2} spacing="sm">
                                    <Button 
                                        variant="light" 
                                        color="green" 
                                        leftSection={<Phone size={16} />}
                                        radius="md"
                                        component="a"
                                        href={`tel:${booking.driver.phone}`}
                                    >
                                        Call Driver
                                    </Button>
                                    <Button 
                                        variant="light" 
                                        color="blue" 
                                        leftSection={<MessageCircle size={16} />}
                                        radius="md"
                                    >
                                        Message
                                    </Button>
                                </SimpleGrid>
                            )}
                        </Stack>
                    </Paper>
                ) : (
                    <Alert icon={<Info size={16} />} title="Driver Matching" color="indigo" radius="md">
                        We are currently matching a professional driver for your journey. You'll be notified via the app once assigned.
                    </Alert>
                )}

                {/* Security PIN for Pickup */}
                {booking.start_ride_pin && !booking.start_pin_verified_at && (
                    <Box>
                        <Divider label="Security Protocol" labelPosition="center" mb="md" />
                        <Paper radius="md" p="md" style={{ background: 'var(--mantine-color-yellow-0)', border: '1px dashed var(--mantine-color-yellow-6)' }}>
                            <Stack gap="xs" align="center">
                                <Text size="xs" fw={700} color="yellow.9" tt="uppercase">Ride Verification Token</Text>
                                <Text fw={900} size="xl" style={{ letterSpacing: rem(10), marginLeft: rem(10) }} color="yellow.9">
                                    {booking.start_ride_pin}
                                </Text>
                                <Text size={rem(10)} ta="center" color="yellow.8">Provide this code to your driver ONLY at the time of boarding.</Text>
                            </Stack>
                        </Paper>
                    </Box>
                )}

                {/* Ride Timeline */}
                <Box>
                    <Title order={6} fw={900} mb="sm">Journey Progress</Title>
                    <Timeline active={['pending', 'confirmed', 'driver_assigned', 'driver_arriving', 'pickup', 'in_transit', 'completed'].indexOf(currentStatus)} bulletSize={20} lineWidth={2}>
                        <Timeline.Item bullet={<Calendar size={12} />} title="Booking Recorded">
                            <Text color="dimmed" size="xs">Confirmed on {new Date(booking.created_at).toLocaleTimeString()}</Text>
                        </Timeline.Item>
                        <Timeline.Item bullet={<User size={12} />} title="Driver Assigned">
                            <Text color="dimmed" size="xs">{booking.driver ? `Matched with ${booking.driver.name}` : 'Awaiting assignment'}</Text>
                        </Timeline.Item>
                        <Timeline.Item bullet={<MapPin size={12} />} title="Pickup Arrival">
                            <Text color="dimmed" size="xs">Pickup point: {booking.pickup_location}</Text>
                        </Timeline.Item>
                        <Timeline.Item bullet={<CheckCircle2 size={12} />} title="Destination Reached">
                            <Text color="dimmed" size="xs">Dropoff: {booking.dropoff_location || 'Return journey'}</Text>
                        </Timeline.Item>
                    </Timeline>
                </Box>

                {/* Economical Summary */}
                <Paper radius="md" p="md" withBorder>
                    <Stack gap="xs">
                        <Group justify="space-between">
                            <Text size="sm" color="dimmed">Service Portfolio</Text>
                            <Text size="sm" fw={700}>{booking.service_type.replace('_', ' ')}</Text>
                        </Group>
                        <Group justify="space-between">
                            <Text size="sm" color="dimmed">Payment Protocol</Text>
                            <Badge variant="outline" size="xs">{booking.payment_method} / {booking.payment_status}</Badge>
                        </Group>
                        <Divider mt="xs" />
                        <Group justify="space-between">
                            <Title order={5} fw={900}>Total Economic Value</Title>
                            <Title order={5} fw={900} color="blue">${booking.total_fare}</Title>
                        </Group>
                    </Stack>
                </Paper>

                {/* Post-Ride Experience */}
                {isCompleted && (
                    <Box mt="md">
                        <Divider label="Post-Journey Experience" labelPosition="center" mb="lg" />
                        <Stack gap="md">
                            <Paper radius="md" p="md" withBorder>
                                <Stack gap="sm">
                                    <Text fw={700} ta="center">Rate Your Experience</Text>
                                    <Group justify="center">
                                        <Rating 
                                            value={reviewRating} 
                                            onChange={setReviewRating} 
                                            size="lg" 
                                            readOnly={!!booking.my_review}
                                        />
                                    </Group>
                                    {!booking.my_review && (
                                        <>
                                            <Textarea 
                                                placeholder="Share your feedback for the driver..."
                                                radius="md"
                                                value={reviewComment}
                                                onChange={(e) => setReviewComment(e.target.value)}
                                            />
                                            <Button 
                                                radius="md" 
                                                fullWidth 
                                                onClick={handleSubmitReview}
                                                loading={loading}
                                            >
                                                Submit Forensic Review
                                            </Button>
                                        </>
                                    )}
                                    {booking.my_review && (
                                        <Alert color="green" radius="md">
                                            Your feedback has been recorded and attributed to the driver's profile.
                                        </Alert>
                                    )}
                                </Stack>
                            </Paper>

                            <Paper radius="md" p="md" withBorder style={{ background: 'var(--mantine-color-green-0)', border: '1px solid var(--mantine-color-green-2)' }}>
                                <Stack gap="sm">
                                    <Text fw={700} ta="center" color="green.9">Express Gratitude</Text>
                                    <Group grow gap="xs">
                                        <Button variant="outline" color="green" radius="md" size="xs" onClick={() => setTipAmount(5)}>$5</Button>
                                        <Button variant="outline" color="green" radius="md" size="xs" onClick={() => setTipAmount(10)}>$10</Button>
                                        <Button variant="outline" color="green" radius="md" size="xs" onClick={() => setTipAmount(20)}>$20</Button>
                                    </Group>
                                    <NumberInput 
                                        prefix="$" 
                                        placeholder="Service gratitude amount" 
                                        radius="md"
                                        value={tipAmount}
                                        onChange={setTipAmount}
                                    />
                                    <Button 
                                        color="green" 
                                        radius="md" 
                                        fullWidth 
                                        onClick={handleSendTip}
                                        loading={loading}
                                    >
                                        Disburse Gratuity
                                    </Button>
                                </Stack>
                            </Paper>
                        </Stack>
                    </Box>
                )}

                {/* Auxiliary Actions */}
                <Stack gap="sm" mt="md">
                    {canCancel && (
                        <Button 
                            variant="subtle" 
                            color="red" 
                            fullWidth 
                            radius="md" 
                            leftSection={<X size={16} />}
                            onClick={handleCancel}
                            loading={loading}
                        >
                            Abort Journey Request
                        </Button>
                    )}
                    
                    <Group grow gap="sm">
                        <CopyButton value={`Ride #${booking.booking_number}: ${booking.pickup_location} -> ${booking.dropoff_location || 'Unspecified'}`}>
                            {({ copied, copy }) => (
                                <Button 
                                    variant="light" 
                                    color={copied ? 'teal' : 'gray'} 
                                    radius="md" 
                                    onClick={copy}
                                    leftSection={copied ? <CheckCircle2 size={16} /> : <Copy size={16} />}
                                >
                                    {copied ? 'Copied' : 'Copy Intel'}
                                </Button>
                            )}
                        </CopyButton>
                        <Button variant="light" color="gray" radius="md" leftSection={<Share2 size={16} />}>Share Status</Button>
                    </Group>
                </Stack>
            </Stack>
        </AppLayout>
    );
}
