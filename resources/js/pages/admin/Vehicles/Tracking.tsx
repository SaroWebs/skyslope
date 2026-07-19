import React, { useEffect, useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Alert,
    Badge,
    Button,
    Card,
    Grid,
    Group,
    Paper,
    SimpleGrid,
    Stack,
    Table,
    Text,
    ThemeIcon,
    Title,
} from '@mantine/core';
import { AdvancedMarker, APIProvider, Map, Pin, useMap } from '@vis.gl/react-google-maps';
import {
    ArrowLeft,
    Battery,
    ExternalLink,
    Gauge,
    KeyRound,
    MapPinned,
    Navigation,
    Power,
    Radio,
} from 'lucide-react';
import AdminLayout from '@/layouts/AdminLayout';

interface Tracker {
    id: number;
    device_uid: string;
    status: 'unprovisioned' | 'active' | 'suspended' | 'faulty';
    latitude?: string | null;
    longitude?: string | null;
    speed_kmh?: string | null;
    heading?: string | null;
    accuracy_m?: string | null;
    battery_percent?: number | null;
    ignition_on?: boolean | null;
    last_ping_at?: string | null;
    last_recorded_at?: string | null;
}

interface LocationPoint {
    id: number;
    latitude: string;
    longitude: string;
    speed_kmh?: string | null;
    heading?: string | null;
    ignition_on?: boolean | null;
    recorded_at: string;
}

interface Vehicle {
    id: number;
    registration_number: string;
    make: string;
    model: string;
    color: string;
    driver?: { id: number; name: string } | null;
    category?: { id: number; name: string } | null;
}

interface Props {
    title: string;
    vehicle: Vehicle;
    tracker: Tracker | null;
    locations: LocationPoint[];
    google_maps_api_key?: string | null;
}

function TrailPolyline({ points }: { points: LocationPoint[] }) {
    const map = useMap();

    useEffect(() => {
        if (!map || points.length < 2) return;
        const line = new google.maps.Polyline({
            map,
            path: points.map((point) => ({ lat: Number(point.latitude), lng: Number(point.longitude) })),
            strokeColor: '#0284c7',
            strokeOpacity: 0.9,
            strokeWeight: 4,
        });

        return () => line.setMap(null);
    }, [map, points]);

    return null;
}

export default function VehicleTracking({ title, vehicle, tracker: initialTracker, locations: initialLocations, google_maps_api_key }: Props) {
    const [tracker, setTracker] = useState(initialTracker);
    const [locations, setLocations] = useState(initialLocations);
    const [online, setOnline] = useState(false);

    useEffect(() => {
        let mounted = true;
        const refresh = async () => {
            try {
                const response = await fetch(`/admin/vehicles/${vehicle.id}/tracking-data`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok || !mounted) return;
                const data = await response.json();
                setTracker(data.tracker);
                setLocations(data.locations || []);
                setOnline(Boolean(data.is_online));
            } catch {
                // Keep the last known position visible during temporary network interruptions.
            }
        };

        void refresh();
        const timer = window.setInterval(refresh, 15000);
        return () => {
            mounted = false;
            window.clearInterval(timer);
        };
    }, [vehicle.id]);

    const latest = useMemo(() => {
        if (tracker?.latitude && tracker?.longitude) {
            return { lat: Number(tracker.latitude), lng: Number(tracker.longitude) };
        }
        const point = locations.at(-1);
        return point ? { lat: Number(point.latitude), lng: Number(point.longitude) } : null;
    }, [locations, tracker?.latitude, tracker?.longitude]);

    const provision = () => router.post(`/admin/vehicles/${vehicle.id}/tracker/provision`);
    const mapsUrl = latest ? `https://www.google.com/maps?q=${latest.lat},${latest.lng}` : null;

    return (
        <AdminLayout title={title}>
            <Head title={`${vehicle.registration_number} GPS`} />
            <Stack gap="lg">
                <Group justify="space-between" align="flex-start">
                    <Group align="flex-start">
                        <Button component={Link} href="/admin/vehicles" variant="subtle" color="gray" leftSection={<ArrowLeft size={16} />}>
                            Vehicles
                        </Button>
                        <div>
                            <Group gap="sm">
                                <Title order={2}>{vehicle.registration_number}</Title>
                                <Badge color={online ? 'green' : tracker?.status === 'active' ? 'yellow' : 'gray'} variant="light" leftSection={<Radio size={12} />}>
                                    {online ? 'Live' : tracker?.status === 'active' ? 'Signal stale' : 'Not provisioned'}
                                </Badge>
                            </Group>
                            <Text c="dimmed" size="sm">{vehicle.make} {vehicle.model} · {vehicle.category?.name || 'Uncategorized'} · {vehicle.driver?.name || 'No driver assigned'}</Text>
                        </div>
                    </Group>
                    {mapsUrl && (
                        <Button component="a" href={mapsUrl} target="_blank" rel="noreferrer" variant="light" leftSection={<ExternalLink size={16} />}>
                            Open in Google Maps
                        </Button>
                    )}
                </Group>

                {tracker?.status !== 'active' && (
                    <Alert color="orange" title="GPS setup required" icon={<KeyRound size={18} />}>
                        <Group justify="space-between" align="center">
                            <Text size="sm">Provision this vehicle’s tracker to generate its secure device token and begin receiving positions.</Text>
                            <Button size="sm" color="orange" onClick={provision}>Provision tracker</Button>
                        </Group>
                    </Alert>
                )}

                <SimpleGrid cols={{ base: 2, sm: 4 }} spacing="md">
                    <Metric icon={Gauge} label="Speed" value={`${Number(tracker?.speed_kmh || 0).toFixed(0)} km/h`} />
                    <Metric icon={Navigation} label="Heading" value={tracker?.heading ? `${Number(tracker.heading).toFixed(0)}°` : '—'} />
                    <Metric icon={Battery} label="Tracker battery" value={tracker?.battery_percent != null ? `${tracker.battery_percent}%` : '—'} />
                    <Metric icon={Power} label="Ignition" value={tracker?.ignition_on == null ? 'Unknown' : tracker.ignition_on ? 'On' : 'Off'} />
                </SimpleGrid>

                <Grid gutter="lg">
                    <Grid.Col span={{ base: 12, lg: 8 }}>
                        <Card padding={0} radius="lg" withBorder style={{ overflow: 'hidden' }}>
                            <div style={{ height: 480, background: '#e2e8f0' }}>
                                {latest && google_maps_api_key ? (
                                    <APIProvider apiKey={google_maps_api_key}>
                                        <Map defaultCenter={latest} defaultZoom={15} mapId="DEMO_MAP_ID" disableDefaultUI={false}>
                                            <TrailPolyline points={locations} />
                                            <AdvancedMarker position={latest} title={vehicle.registration_number}>
                                                <Pin background="#0f172a" borderColor="#fff" glyphColor="#fff" />
                                            </AdvancedMarker>
                                        </Map>
                                    </APIProvider>
                                ) : (
                                    <Stack h="100%" align="center" justify="center" gap="sm" p="xl">
                                        <ThemeIcon size={64} radius="xl" color="gray" variant="light"><MapPinned size={30} /></ThemeIcon>
                                        <Text fw={700}>{latest ? 'Map key is not configured' : 'Waiting for the first GPS position'}</Text>
                                        <Text size="sm" c="dimmed" ta="center" maw={420}>
                                            {latest ? `${latest.lat.toFixed(6)}, ${latest.lng.toFixed(6)}` : 'The map will appear automatically after the tracker sends its first authenticated ping.'}
                                        </Text>
                                    </Stack>
                                )}
                            </div>
                        </Card>
                    </Grid.Col>

                    <Grid.Col span={{ base: 12, lg: 4 }}>
                        <Paper p="lg" radius="lg" withBorder h="100%">
                            <Stack gap="md">
                                <div>
                                    <Text size="xs" fw={800} tt="uppercase" c="dimmed">Tracker identity</Text>
                                    <Text mt={4} ff="monospace" fw={700}>{tracker?.device_uid || 'Not assigned'}</Text>
                                </div>
                                <div>
                                    <Text size="xs" fw={800} tt="uppercase" c="dimmed">Last signal</Text>
                                    <Text mt={4} fw={600}>{tracker?.last_ping_at ? new Date(tracker.last_ping_at).toLocaleString() : 'Never'}</Text>
                                </div>
                                <div>
                                    <Text size="xs" fw={800} tt="uppercase" c="dimmed">Accuracy</Text>
                                    <Text mt={4} fw={600}>{tracker?.accuracy_m ? `${Number(tracker.accuracy_m).toFixed(0)} metres` : 'Unknown'}</Text>
                                </div>
                                <div>
                                    <Text size="xs" fw={800} tt="uppercase" c="dimmed">Latest coordinates</Text>
                                    <Text mt={4} ff="monospace" size="sm">{latest ? `${latest.lat.toFixed(7)}, ${latest.lng.toFixed(7)}` : 'No location received'}</Text>
                                </div>
                                <Text size="xs" c="dimmed">This view refreshes every 15 seconds. A tracker is considered live when it has reported within five minutes.</Text>
                            </Stack>
                        </Paper>
                    </Grid.Col>
                </Grid>

                <Paper p="lg" radius="lg" withBorder>
                    <Group justify="space-between" mb="md">
                        <div>
                            <Text fw={800}>Recent GPS trail</Text>
                            <Text size="sm" c="dimmed">Most recent 100 hardware-reported positions</Text>
                        </div>
                        <Badge variant="light">{locations.length} points</Badge>
                    </Group>
                    <Table.ScrollContainer minWidth={680}>
                        <Table verticalSpacing="sm" striped highlightOnHover>
                            <Table.Thead><Table.Tr><Table.Th>Recorded</Table.Th><Table.Th>Coordinates</Table.Th><Table.Th>Speed</Table.Th><Table.Th>Heading</Table.Th><Table.Th>Ignition</Table.Th></Table.Tr></Table.Thead>
                            <Table.Tbody>
                                {[...locations].reverse().slice(0, 25).map((point) => (
                                    <Table.Tr key={point.id}>
                                        <Table.Td><Text size="sm">{new Date(point.recorded_at).toLocaleString()}</Text></Table.Td>
                                        <Table.Td><Text size="sm" ff="monospace">{Number(point.latitude).toFixed(6)}, {Number(point.longitude).toFixed(6)}</Text></Table.Td>
                                        <Table.Td>{Number(point.speed_kmh || 0).toFixed(0)} km/h</Table.Td>
                                        <Table.Td>{point.heading ? `${Number(point.heading).toFixed(0)}°` : '—'}</Table.Td>
                                        <Table.Td><Badge size="xs" color={point.ignition_on ? 'green' : 'gray'}>{point.ignition_on ? 'On' : 'Off'}</Badge></Table.Td>
                                    </Table.Tr>
                                ))}
                                {locations.length === 0 && <Table.Tr><Table.Td colSpan={5}><Text ta="center" py="xl" c="dimmed">No GPS positions received yet.</Text></Table.Td></Table.Tr>}
                            </Table.Tbody>
                        </Table>
                    </Table.ScrollContainer>
                </Paper>
            </Stack>
        </AdminLayout>
    );
}

function Metric({ icon: Icon, label, value }: { icon: React.ElementType; label: string; value: string }) {
    return (
        <Card padding="md" radius="lg" withBorder>
            <Group wrap="nowrap">
                <ThemeIcon size={42} radius="md" variant="light" color="blue"><Icon size={20} /></ThemeIcon>
                <div><Text size="xs" c="dimmed" fw={700}>{label}</Text><Text fw={800}>{value}</Text></div>
            </Group>
        </Card>
    );
}
