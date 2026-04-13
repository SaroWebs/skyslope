import React from 'react';
import { Head, Link } from '@inertiajs/react';
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
    SimpleGrid,
    Button,
    ActionIcon
} from '@mantine/core';
import { 
    User, 
    Phone, 
    Mail, 
    Car, 
    Star, 
    ShieldCheck, 
    Calendar, 
    MapPin, 
    Edit3,
    ArrowLeft,
    CheckCircle2,
    Verified
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';

interface User {
    name: string;
    email: string;
    phone?: string;
    avatar?: string;
}

interface Availability {
    vehicle_type?: string;
    vehicle_number?: string;
    rating?: number;
}

interface Props {
    title?: string;
    user: User;
    availability?: Availability;
}

export default function Profile({ title = 'Driver Profile', user, availability }: Props) {
    return (
        <AppLayout title="Profile" backPath="/driver/dashboard">
            <Head title={title} />
            
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
                            background: 'linear-gradient(135deg, var(--mantine-color-orange-6) 0%, var(--mantine-color-orange-4) 100%)' 
                        }} 
                    />
                    
                    <Stack align="center" gap="sm" mt={rem(40)} pos="relative">
                        <Indicator offset={5} position="bottom-end" label={<Verified size={14} fill="white" color="var(--mantine-color-blue-6)" />} size={20} color="transparent">
                            <Avatar 
                                src={user.avatar} 
                                size={100} 
                                radius={100} 
                                border={rem(4)}
                                style={{ borderColor: '#fff' }}
                                color="orange"
                            >
                                {user.name.charAt(0)}
                            </Avatar>
                        </Indicator>
                        
                        <Stack gap={2} align="center">
                            <Title order={3} fw={900}>{user.name}</Title>
                            <Badge variant="light" color="blue" size="sm">Premium Partner</Badge>
                        </Stack>

                        <Group gap="md" mt="xs">
                            <Stack gap={0} align="center">
                                <Text fw={900} size="sm">{(availability?.rating || 5.0).toFixed(1)}</Text>
                                <Text size={rem(10)} color="dimmed" tt="uppercase">Rating</Text>
                            </Stack>
                            <Divider orientation="vertical" />
                            <Stack gap={0} align="center">
                                <Text fw={900} size="sm">2.4k</Text>
                                <Text size={rem(10)} color="dimmed" tt="uppercase">Trips</Text>
                            </Stack>
                            <Divider orientation="vertical" />
                            <Stack gap={0} align="center">
                                <Text fw={900} size="sm">Exp.</Text>
                                <Text size={rem(10)} color="dimmed" tt="uppercase">Class</Text>
                            </Stack>
                        </Group>
                    </Stack>
                </Paper>

                {/* Professional Portfolio */}
                <Box>
                    <Title order={6} fw={900} mb="md" tt="uppercase" ls={0.5}>Contact Intelligence</Title>
                    <Paper radius="md" withBorder p="md">
                        <Stack gap="md">
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="gray" radius="sm">
                                        <Phone size={16} />
                                    </ThemeIcon>
                                    <Text size="sm" fw={600}>Mobile Protocol</Text>
                                </Group>
                                <Text size="sm" color="dimmed">{user.phone || 'N/A'}</Text>
                            </Group>
                            <Divider dashed />
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="gray" radius="sm">
                                        <Mail size={16} />
                                    </ThemeIcon>
                                    <Text size="sm" fw={600}>Encryption Email</Text>
                                </Group>
                                <Text size="sm" color="dimmed">{user.email}</Text>
                            </Group>
                        </Stack>
                    </Paper>
                </Box>

                {/* Asset Specifications */}
                <Box>
                    <Title order={6} fw={900} mb="md" tt="uppercase" ls={0.5}>Asset Specifications</Title>
                    <Paper radius="md" withBorder p="md">
                        <Stack gap="md">
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="blue" radius="sm">
                                        <Car size={16} />
                                    </ThemeIcon>
                                    <Text size="sm" fw={600}>Transporter ID</Text>
                                </Group>
                                <Text size="sm" fw={700} color="blue">{availability?.vehicle_number || 'UNASSIGNED'}</Text>
                            </Group>
                            <Divider dashed />
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="gray" radius="sm">
                                        <ShieldCheck size={16} />
                                    </ThemeIcon>
                                    <Text size="sm" fw={600}>Vessel Category</Text>
                                </Group>
                                <Text size="sm" color="dimmed">{availability?.vehicle_type || 'Standard Class'}</Text>
                            </Group>
                        </Stack>
                    </Paper>
                </Box>

                {/* Compliance & Verification */}
                <Box>
                    <Alert color="blue" radius="md" icon={<CheckCircle2 size={16} />}>
                        Your account is currently in 'Verified' status. All operational permits are valid through 2026.
                    </Alert>
                </Box>

                <Button variant="light" color="blue" radius="md" fullWidth leftSection={<Edit3 size={16} />}>
                    Update Profile Identity
                </Button>
            </Stack>
        </AppLayout>
    );
}
