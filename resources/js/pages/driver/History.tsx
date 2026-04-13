import React, { useState } from 'react';
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
    Collapse,
    Rating,
    Textarea,
    Timeline,
    SimpleGrid,
    ScrollArea,
    Select,
    TextInput,
    Pagination
} from '@mantine/core';
import { 
    MapPin, 
    Calendar, 
    Clock, 
    DollarSign, 
    Star, 
    ChevronDown, 
    ChevronUp, 
    Navigation, 
    Phone, 
    MessageCircle, 
    History as HistoryIcon,
    Search,
    CheckCircle2,
    FileText,
    ArrowUpRight,
    TrendingUp,
    Map
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useAppNotifications } from '@/app';
import axios from '@/lib/axios';

interface Ride {
    id: number;
    booking_number: string;
    status: string;
    pickup_location: string;
    dropoff_location?: string;
    scheduled_at: string;
    completed_at?: string;
    total_fare: number;
    customer_name: string;
    customer_phone?: string;
    customer_email?: string;
    pickup_lat?: number | null;
    pickup_lng?: number | null;
    dropoff_lat?: number | null;
    dropoff_lng?: number | null;
    payment_status?: string;
    payment_method?: string;
    driver_notes?: string;
    reviews?: Array<{
        id: number;
        rating: number;
        comment?: string;
        created_at: string;
    }>;
    tips?: Array<{
        id: number;
        amount: number;
        message?: string;
        created_at: string;
    }>;
    tips_total?: number;
}

interface PaginatedRides {
    data: Ride[];
    current_page: number;
    last_page: number;
}

interface Props {
    title?: string;
    rides: PaginatedRides;
}

export default function History({ title = 'Journey Ledger', rides }: Props) {
    const addNotification = useAppNotifications();
    const [expandedId, setExpandedId] = useState<number | null>(null);
    const [saving, setSaving] = useState<number | null>(null);
    const [notesDraft, setNotesDraft] = useState<Record<number, string>>({});
    const [searchQuery, setSearchQuery] = useState('');

    const handleSaveNote = async (ride: Ride) => {
        const note = notesDraft[ride.id] ?? ride.driver_notes ?? '';
        setSaving(ride.id);
        try {
            await axios.post(`/driver/rides/${ride.id}/notes`, { driver_notes: note });
            addNotification('Journey notes archived successfully', 'success');
            router.reload({ only: ['rides'] });
        } catch (error) {
            addNotification('Failed to archive notes', 'error');
        } finally {
            setSaving(null);
        }
    };

    const handleCollectPayment = async (ride: Ride, method: string) => {
        setSaving(ride.id);
        try {
            await axios.post(`/driver/rides/${ride.id}/payment-status`, {
                payment_status: 'paid',
                payment_method: method
            });
            addNotification(`Payment of $${ride.total_fare} recorded via ${method.toUpperCase()}`, 'success');
            router.reload({ only: ['rides'] });
        } catch (error) {
            addNotification('Critical: Failed to record payment', 'error');
        } finally {
            setSaving(null);
        }
    };

    const formatDate = (date: string) => new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });

    const formatTime = (date: string) => new Date(date).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });

    return (
        <AppLayout title="History">
            <Head title={title} />
            
            <Stack gap="lg">
                {/* Header & Filter */}
                <Box>
                    <Title order={4} fw={900}>Journey Ledger</Title>
                    <Text size="xs" color="dimmed" mb="md">Audit trail for all completed assignments</Text>
                    
                    <TextInput 
                        placeholder="Search by Booking ID or Customer..." 
                        leftSection={<Search size={16} />}
                        radius="md"
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                    />
                </Box>

                <Stack gap="sm">
                    {rides?.data?.length ? (
                        rides.data.map((ride) => (
                            <Paper key={ride.id} radius="md" withBorder shadow="xs" overflow="hidden">
                                <Box p="md" onClick={() => setExpandedId(expandedId === ride.id ? null : ride.id)} style={{ cursor: 'pointer' }}>
                                    <Group justify="space-between" mb="xs">
                                        <Group gap="xs">
                                            <ThemeIcon variant="light" color={ride.status === 'completed' ? 'green' : 'gray'} radius="sm" size="sm">
                                                <HistoryIcon size={14} />
                                            </ThemeIcon>
                                            <Text size="sm" fw={700}>#{ride.booking_number}</Text>
                                        </Group>
                                        <Badge color={ride.status === 'completed' ? 'green' : 'red'} variant="outline" size="xs">
                                            {ride.status}
                                        </Badge>
                                    </Group>

                                    <Group justify="space-between" align="flex-end">
                                        <Stack gap={2}>
                                            <Text size="xs" color="dimmed">{formatDate(ride.scheduled_at)} • {formatTime(ride.scheduled_at)}</Text>
                                            <Text size="sm" fw={600} truncate w={200}>{ride.customer_name}</Text>
                                        </Stack>
                                        <Stack gap={0} align="flex-end">
                                            <Text fw={900} size="sm" color="green.7">${ride.total_fare}</Text>
                                            <Text size={rem(10)} color="dimmed" tt="uppercase">{ride.payment_status}</Text>
                                        </Stack>
                                    </Group>
                                </Box>

                                <Collapse in={expandedId === ride.id}>
                                    <Divider />
                                    <Box p="md" style={{ background: 'var(--mantine-color-gray-0)' }}>
                                        <Stack gap="md">
                                            {/* Journey Vector */}
                                            <Box>
                                                <Group gap="xs" mb={4}>
                                                    <div style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--mantine-color-green-5)' }} />
                                                    <Text size="xs" fw={600}>{ride.pickup_location}</Text>
                                                </Group>
                                                <Group gap="xs">
                                                    <div style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--mantine-color-red-5)' }} />
                                                    <Text size="xs" color="dimmed">{ride.dropoff_location || 'Return journey'}</Text>
                                                </Group>
                                            </Box>

                                            <Divider dashed />

                                            {/* Customer Intel */}
                                            <SimpleGrid cols={2}>
                                                <Box>
                                                    <Text size={rem(10)} color="dimmed" tt="uppercase" fw={700}>Customer Contact</Text>
                                                    <Text size="xs" fw={600}>{ride.customer_phone || 'N/A'}</Text>
                                                </Box>
                                                <Box>
                                                    <Text size={rem(10)} color="dimmed" tt="uppercase" fw={700}>Gratuity Recived</Text>
                                                    <Text size="xs" fw={600} color="green.7">${(ride.tips_total || 0).toFixed(2)}</Text>
                                                </Box>
                                            </SimpleGrid>

                                            {/* Payment Reconciliation */}
                                            {ride.payment_status !== 'paid' && (
                                                <Paper p="xs" radius="sm" withBorder style={{ background: '#fff' }}>
                                                    <Text size={rem(10)} fw={700} mb={8} tt="uppercase">Reconcile Escrow</Text>
                                                    <Group grow gap="xs">
                                                        <Button size="xs" color="green" onClick={() => handleCollectPayment(ride, 'cash')} loading={saving === ride.id}>Cash</Button>
                                                        <Button size="xs" variant="outline" color="blue" onClick={() => handleCollectPayment(ride, 'wallet')} loading={saving === ride.id}>Wallet</Button>
                                                    </Group>
                                                </Paper>
                                            )}

                                            {/* Reviews HUD */}
                                            {ride.reviews?.length ? (
                                                <Box>
                                                    <Text size={rem(10)} color="dimmed" tt="uppercase" fw={700} mb={4}>Mission Feedback</Text>
                                                    {ride.reviews.map(r => (
                                                        <Paper key={r.id} p="xs" radius="xs" withBorder shadow="none" mb={4}>
                                                            <Rating value={r.rating} readOnly size="xs" />
                                                            <Text size="xs" fs="italic" mt={4}>"{r.comment || 'Exemplary service'}"</Text>
                                                        </Paper>
                                                    ))}
                                                </Box>
                                            ) : null}

                                            {/* Operations Notes */}
                                            <Box>
                                                <Text size={rem(10)} color="dimmed" tt="uppercase" fw={700} mb={4}>Operational Notes</Text>
                                                <Textarea 
                                                    placeholder="Add secure notes for this journey..."
                                                    size="xs"
                                                    radius="sm"
                                                    value={notesDraft[ride.id] ?? ride.driver_notes ?? ''}
                                                    onChange={(e) => setNotesDraft(prev => ({ ...prev, [ride.id]: e.target.value }))}
                                                />
                                                <Button 
                                                    size="xs" 
                                                    mt="xs" 
                                                    variant="light" 
                                                    fullWidth 
                                                    onClick={() => handleSaveNote(ride)}
                                                    loading={saving === ride.id}
                                                >
                                                    Secure Archive Notes
                                                </Button>
                                            </Box>
                                            
                                            <Group grow gap="sm">
                                                <Button size="xs" variant="subtle" fullWidth leftSection={<Map size={14} />} component="a" target="_blank" href={`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(ride.pickup_location)}`}>Pickup</Button>
                                                <Button size="xs" variant="subtle" fullWidth leftSection={<ArrowUpRight size={14} />} component="a" target="_blank" href={`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(ride.dropoff_location || '')}`}>Dropoff</Button>
                                            </Group>
                                        </Stack>
                                    </Box>
                                </Collapse>
                            </Paper>
                        ))
                    ) : (
                        <Paper p="xl" radius="md" withBorder style={{ borderStyle: 'dashed', textAlign: 'center' }}>
                            <Stack align="center" gap="xs">
                                <HistoryIcon size={32} color="var(--mantine-color-gray-4)" />
                                <Text size="sm" color="dimmed">No historical data available for this sector.</Text>
                            </Stack>
                        </Paper>
                    )}
                </Stack>

                {rides?.last_page > 1 && (
                    <Group justify="center" mt="md">
                        <Pagination 
                            total={rides.last_page} 
                            value={rides.current_page} 
                            onChange={(p) => router.get(window.location.pathname, { page: p }, { preserveScroll: true })}
                            size="sm"
                            radius="md"
                        />
                    </Group>
                )}
            </Stack>
        </AppLayout>
    );
}
