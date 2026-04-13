import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    Stack, 
    Group, 
    Text, 
    Title, 
    Paper, 
    Avatar, 
    Box, 
    rem, 
    Divider, 
    Badge, 
    ThemeIcon, 
    Button,
    ActionIcon,
    SimpleGrid
} from '@mantine/core';
import { 
    User, 
    Mail, 
    Phone, 
    ShieldCheck, 
    Star, 
    Settings, 
    CreditCard, 
    History, 
    MapPin, 
    Edit3,
    ArrowLeft,
    Verified,
    CheckCircle2
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useAuth } from '@/context/AuthContext';

interface UserData {
    name: string;
    email: string;
    phone?: string;
    avatar?: string;
}

interface Props {
    user: UserData;
    stats?: {
        total_rides: number;
        total_spent: number;
        loyalty_points: number;
    }
}

export default function Profile({ user, stats = { total_rides: 24, total_spent: 480, loyalty_points: 1200 } }: Props) {
    const { logout } = useAuth();

    return (
        <AppLayout title="Profile" backPath="/customer/dashboard">
            <Head title="My Profile" />
            
            <Stack gap="xl">
                {/* Identity Card */}
                <Paper radius="md" p="xl" withBorder shadow="sm" pos="relative" style={{ overflow: 'hidden' }}>
                    <Box 
                        style={{ 
                            position: 'absolute', 
                            top: 0, 
                            left: 0, 
                            right: 0, 
                            height: rem(80), 
                            background: 'linear-gradient(135deg, var(--mantine-color-blue-6) 0%, var(--mantine-color-blue-4) 100%)' 
                        }} 
                    />
                    
                    <Stack align="center" gap="sm" mt={rem(40)} pos="relative">
                        <Avatar 
                            src={user.avatar} 
                            size={100} 
                            radius={100} 
                            border={rem(4)}
                            style={{ borderColor: '#fff' }}
                            color="blue"
                        >
                            {user.name.charAt(0)}
                        </Avatar>
                        
                        <Stack gap={2} align="center">
                            <Title order={3} fw={900}>{user.name}</Title>
                            <Group gap={4} align="center">
                                <Star fill="var(--mantine-color-yellow-5)" color="var(--mantine-color-yellow-5)" size={14} />
                                <Text size="sm" fw={700}>Explorer Tier</Text>
                            </Group>
                        </Stack>

                        <Group gap="md" mt="xs">
                            <Stack gap={0} align="center">
                                <Text fw={900} size="sm">{stats.total_rides}</Text>
                                <Text size={rem(10)} color="dimmed" tt="uppercase">Trips</Text>
                            </Stack>
                            <Divider orientation="vertical" />
                            <Stack gap={0} align="center">
                                <Text fw={900} size="sm">${stats.total_spent}</Text>
                                <Text size={rem(10)} color="dimmed" tt="uppercase">Spent</Text>
                            </Stack>
                            <Divider orientation="vertical" />
                            <Stack gap={0} align="center">
                                <Text fw={900} size="sm">{stats.loyalty_points}</Text>
                                <Text size={rem(10)} color="dimmed" tt="uppercase">Points</Text>
                            </Stack>
                        </Group>
                    </Stack>
                </Paper>

                {/* Profile Grid Actions */}
                <SimpleGrid cols={2} spacing="md">
                    <Card withBorder radius="md" p="md" style={{ cursor: 'pointer' }} component={Link} href="/customer/rides">
                        <ThemeIcon color="green" variant="light" size="lg" radius="md">
                            <History size={20} />
                        </ThemeIcon>
                        <Text fw={700} size="sm" mt="sm">Trip History</Text>
                        <Text size={rem(10)} color="dimmed">Past assignments</Text>
                    </Card>
                    <Card withBorder radius="md" p="md" style={{ cursor: 'pointer' }}>
                        <ThemeIcon color="indigo" variant="light" size="lg" radius="md">
                            <CreditCard size={20} />
                        </ThemeIcon>
                        <Text fw={700} size="sm" mt="sm">Payment Ledger</Text>
                        <Text size={rem(10)} color="dimmed">Financial records</Text>
                    </Card>
                </SimpleGrid>

                {/* Secure Contact Details */}
                <Box>
                    <Title order={6} fw={900} mb="md" tt="uppercase" ls={0.5}>Secure Identity Intelligence</Title>
                    <Paper radius="md" withBorder p="md">
                        <Stack gap="md">
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="gray" radius="sm">
                                        <Mail size={16} />
                                    </ThemeIcon>
                                    <Text size="sm" fw={600}>Encryption Email</Text>
                                </Group>
                                <Text size="sm" color="dimmed">{user.email}</Text>
                            </Group>
                            <Divider dashed />
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="gray" radius="sm">
                                        <Phone size={16} />
                                    </ThemeIcon>
                                    <Text size="sm" fw={600}>Mobile Protocol</Text>
                                </Group>
                                <Text size="sm" color="dimmed">{user.phone || 'Not Configured'}</Text>
                            </Group>
                        </Stack>
                    </Paper>
                </Box>

                {/* Compliance & Security */}
                <Box>
                    <Alert color="green" radius="md" icon={<ShieldCheck size={16} />}>
                        Your account identity is verified and high-priority encryption is active.
                    </Alert>
                </Box>

                <Stack gap="sm">
                    <Button variant="light" color="blue" radius="md" fullWidth leftSection={<Edit3 size={16} />}>
                        Update Profile Preferences
                    </Button>
                    <Button variant="subtle" color="red" radius="md" fullWidth leftSection={<LogOut size={16} />} onClick={() => logout()}>
                        Terminate Session
                    </Button>
                </Stack>
            </Stack>
        </AppLayout>
    );
}
