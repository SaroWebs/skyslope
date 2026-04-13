import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../layouts/AdminLayout';
import { 
    Grid, 
    Paper, 
    Text, 
    Group, 
    Stack, 
    RingProgress, 
    ThemeIcon, 
    SimpleGrid,
    Table,
    Badge,
    Avatar,
    ActionIcon,
    Menu,
    Button,
    Box,
    Divider,
    Card
} from '@mantine/core';
import { 
    Users, 
    Briefcase, 
    Activity, 
    MapPin, 
    ArrowUpRight, 
    ArrowDownRight, 
    MoreVertical, 
    ExternalLink,
    Car,
    Map,
    Calendar,
    UserCheck,
    TruckIcon
} from 'lucide-react';

interface DashboardProps {
    title: string;
    user: any;
    stats: {
        total_users: number;
        total_customers: number;
        total_drivers: number;
        total_tours: number;
        total_bookings: number;
        total_ride_bookings: number;
        total_places: number;
        active_tours: number;
        recent_bookings: any[];
    };
    recent_users: any[];
    upcoming_tours: any[];
}

export default function Dashboard({ title, user, stats, recent_users, upcoming_tours }: DashboardProps) {
    const statCards = [
        { title: 'Total Customers', value: stats.total_customers, icon: Users, color: 'blue', diff: 12 },
        { title: 'Available Drivers', value: stats.total_drivers, icon: TruckIcon, color: 'teal', diff: 5 },
        { title: 'Ride Bookings', value: stats.total_ride_bookings, icon: Car, color: 'orange', diff: -3 },
        { title: 'Tour Bookings', value: stats.total_bookings, icon: Map, color: 'grape', diff: 8 },
    ];

    return (
        <AdminLayout title={title}>
            <Head title="Dashboard" />

            <Stack gap="xl">
                {/* Stats Section */}
                <SimpleGrid cols={{ base: 1, sm: 2, lg: 4 }} spacing="lg">
                    {statCards.map((stat) => (
                        <Paper key={stat.title} p="xl" radius="md" withBorder>
                            <Group justify="space-between" mb="xs">
                                <Text size="xs" color="dimmed" fw={700} tt="uppercase">
                                    {stat.title}
                                </Text>
                                <ThemeIcon color="gray" variant="light" radius="md" size="lg">
                                    <stat.icon size={20} strokeWidth={1.5} />
                                </ThemeIcon>
                            </Group>

                            <Group align="flex-end" gap="xs">
                                <Text size="h2" fw={700} style={{ lineHeight: 1 }}>
                                    {stat.value}
                                </Text>
                                <Badge 
                                    color={stat.diff > 0 ? 'teal' : 'red'} 
                                    variant="light" 
                                    leftSection={stat.diff > 0 ? <ArrowUpRight size={12} /> : <ArrowDownRight size={12} />}
                                >
                                    {Math.abs(stat.diff)}%
                                </Badge>
                            </Group>
                            
                            <Text size="xs" color="dimmed" mt={7}>
                                Compared to previous month
                            </Text>
                        </Paper>
                    ))}
                </SimpleGrid>

                <Grid gutter="lg">
                    {/* Recent Ride Bookings / Bookings Table */}
                    <Grid.Col span={{ base: 12, lg: 8 }}>
                        <Paper p="xl" radius="md" withBorder>
                            <Group justify="space-between" mb="xl">
                                <Text fw={700} size="lg">Recent Tour Bookings</Text>
                                <Button variant="light" size="compact-sm" component={Link} href="/admin/tour-bookings">View All</Button>
                            </Group>

                            <Table verticalSpacing="md">
                                <Table.Thead>
                                    <Table.Tr>
                                        <Table.Th>Customer</Table.Th>
                                        <Table.Th>Tour</Table.Th>
                                        <Table.Th>Date</Table.Th>
                                        <Table.Th>Amount</Table.Th>
                                        <Table.Th>Status</Table.Th>
                                        <Table.Th />
                                    </Table.Tr>
                                </Table.Thead>
                                <Table.Tbody>
                                    {stats.recent_bookings.map((booking) => (
                                        <Table.Tr key={booking.id}>
                                            <Table.Td>
                                                <Group gap="sm">
                                                    <Avatar size={30} radius="xl" color="blue">
                                                        {booking.customer?.name?.charAt(0)}
                                                    </Avatar>
                                                    <Stack gap={0}>
                                                        <Text size="sm" fw={500}>{booking.customer?.name}</Text>
                                                        <Text size="xs" color="dimmed">{booking.customer?.phone}</Text>
                                                    </Stack>
                                                </Group>
                                            </Table.Td>
                                            <Table.Td>
                                                <Text size="sm" lineClamp={1}>{booking.tour?.title}</Text>
                                            </Table.Td>
                                            <Table.Td>
                                                <Text size="sm">{new Date(booking.travel_date).toLocaleDateString()}</Text>
                                            </Table.Td>
                                            <Table.Td>
                                                <Text size="sm" fw={600}>₹{parseFloat(booking.total_price).toLocaleString()}</Text>
                                            </Table.Td>
                                            <Table.Td>
                                                <Badge 
                                                    variant="light" 
                                                    color={booking.status === 'confirmed' ? 'green' : booking.status === 'pending' ? 'yellow' : 'gray'}
                                                >
                                                    {booking.status}
                                                </Badge>
                                            </Table.Td>
                                            <Table.Td>
                                                <ActionIcon variant="subtle" color="gray">
                                                    <ExternalLink size={16} />
                                                </ActionIcon>
                                            </Table.Td>
                                        </Table.Tr>
                                    ))}
                                </Table.Tbody>
                            </Table>
                        </Paper>
                    </Grid.Col>

                    {/* Quick Stats / Mini Alerts */}
                    <Grid.Col span={{ base: 12, lg: 4 }}>
                        <Stack gap="lg">
                            <Paper p="xl" radius="md" withBorder>
                                <Text fw={700} size="lg" mb="md">System Overview</Text>
                                <Stack gap="sm">
                                    <Group justify="space-between">
                                        <Text size="sm" color="dimmed">Active Tours</Text>
                                        <Badge variant="filled">{stats.active_tours}</Badge>
                                    </Group>
                                    <Group justify="space-between">
                                        <Text size="sm" color="dimmed">Registered Users</Text>
                                        <Text size="sm" fw={600}>{stats.total_users}</Text>
                                    </Group>
                                     <Group justify="space-between">
                                        <Text size="sm" color="dimmed">Total Service Areas</Text>
                                        <Text size="sm" fw={600}>{stats.total_places}</Text>
                                    </Group>
                                </Stack>
                                
                                <Divider my="xl" />
                                
                                <Text size="sm" fw={600} mb="xs">Activity Index</Text>
                                <Group gap="md">
                                    <RingProgress
                                        size={80}
                                        thickness={8}
                                        roundCaps
                                        sections={[{ value: 65, color: 'blue' }]}
                                        label={
                                            <Text size="xs" ta="center" fw={700}>65%</Text>
                                        }
                                    />
                                    <Box style={{ flex: 1 }}>
                                        <Text size="xs" color="dimmed">Booking conversion rate is currently healthy.</Text>
                                    </Box>
                                </Group>
                            </Paper>

                            <Paper p="xl" radius="md" withBorder bg="blue.9">
                                <Stack gap="xs">
                                    <Text color="white" fw={700}>Pro Tip</Text>
                                    <Text color="blue.1" size="sm">
                                        Check the Ride Bookings section to assign pending rides to your available drivers in real-time.
                                    </Text>
                                    <Button 
                                        variant="white" 
                                        color="blue.9" 
                                        size="compact-sm" 
                                        mt="sm" 
                                        component={Link} 
                                        href="/admin/ride-bookings"
                                    >
                                        Manage Rides
                                    </Button>
                                </Stack>
                            </Paper>
                        </Stack>
                    </Grid.Col>
                </Grid>

                {/* Bottom Row */}
                <Grid gutter="lg">
                     <Grid.Col span={{ base: 12, md: 6 }}>
                        <Paper p="xl" radius="md" withBorder>
                            <Group justify="space-between" mb="xl">
                                <Text fw={700} size="lg">Recent Users</Text>
                                <Button variant="subtle" size="compact-sm" component={Link} href="/admin/users">View All</Button>
                            </Group>
                            <Stack gap="md">
                                {recent_users.map((user) => (
                                    <Group key={user.id} justify="space-between">
                                        <Group gap="sm">
                                            <Avatar color="blue" radius="xl">{user.name.charAt(0)}</Avatar>
                                            <Stack gap={0}>
                                                <Text size="sm" fw={600}>{user.name}</Text>
                                                <Text size="xs" color="dimmed">{user.email}</Text>
                                            </Stack>
                                        </Group>
                                        <Group gap={4}>
                                            {user.roles && user.roles.map((role: any) => (
                                                <Badge key={role.id} size="xs" variant="outline">{role.name}</Badge>
                                            ))}
                                        </Group>
                                    </Group>
                                ))}
                            </Stack>
                        </Paper>
                     </Grid.Col>

                     <Grid.Col span={{ base: 12, md: 6 }}>
                        <Paper p="xl" radius="md" withBorder>
                            <Group justify="space-between" mb="xl">
                                <Text fw={700} size="lg">Upcoming Tours</Text>
                                <Button variant="subtle" size="compact-sm" component={Link} href="/admin/tours">Manage Tours</Button>
                            </Group>
                            <Stack gap="md">
                                {upcoming_tours.map((tour) => (
                                    <Card key={tour.id} withBorder padding="sm" radius="md">
                                        <Group justify="space-between">
                                            <Stack gap={4}>
                                                <Text size="sm" fw={700}>{tour.title}</Text>
                                                <Group gap="xs">
                                                    <Calendar size={14} color="gray" />
                                                    <Text size="xs" color="dimmed">
                                                        {new Date(tour.available_from).toLocaleDateString()} - {new Date(tour.available_to).toLocaleDateString()}
                                                    </Text>
                                                </Group>
                                            </Stack>
                                            <Badge color="cyan" variant="filled">Active</Badge>
                                        </Group>
                                    </Card>
                                ))}
                            </Stack>
                        </Paper>
                     </Grid.Col>
                </Grid>
            </Stack>
        </AdminLayout>
    );
}
