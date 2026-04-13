import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { 
    Stack, 
    Group, 
    Text, 
    Title, 
    Paper, 
    Switch, 
    Box, 
    rem, 
    Divider, 
    ThemeIcon, 
    Button,
    ActionIcon,
    Alert,
    Anchor
} from '@mantine/core';
import { 
    Settings as SettingsIcon, 
    Bell, 
    ShieldCheck, 
    Map, 
    Globe, 
    HelpCircle, 
    LogOut, 
    ChevronRight, 
    Wifi, 
    Volume2, 
    Moon, 
    UserMinus,
    Headset,
    Info,
    Smartphone
} from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';
import { useAuth } from '@/context/AuthContext';

interface Availability {
    is_online?: boolean;
    is_available?: boolean;
    last_ping?: string;
}

interface Props {
    title?: string;
    availability?: Availability;
}

export default function Settings({ title = 'Control Configuration', availability }: Props) {
    const { logout } = useAuth();

    return (
        <AppLayout title="Settings" backPath="/driver/dashboard">
            <Head title={title} />
            
            <Stack gap="xl">
                {/* Operational Toggles */}
                <Box>
                    <Title order={6} fw={900} mb="md" tt="uppercase" ls={0.5}>Mission Configuration</Title>
                    <Paper radius="md" withBorder p="md">
                        <Stack gap="md">
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="blue" radius="sm">
                                        <Bell size={16} />
                                    </ThemeIcon>
                                    <Stack gap={0}>
                                        <Text size="sm" fw={600}>Dispatch Notifications</Text>
                                        <Text size={rem(10)} color="dimmed">Critical alerts for new ride requests</Text>
                                    </Stack>
                                </Group>
                                <Switch defaultChecked color="blue" size="md" />
                            </Group>
                            <Divider dashed />
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="orange" radius="sm">
                                        <Volume2 size={16} />
                                    </ThemeIcon>
                                    <Stack gap={0}>
                                        <Text size="sm" fw={600}>Audible Telemetry</Text>
                                        <Text size={rem(10)} color="dimmed">Sound effects for state changes</Text>
                                    </Stack>
                                </Group>
                                <Switch defaultChecked color="orange" size="md" />
                            </Group>
                            <Divider dashed />
                            <Group justify="space-between">
                                <Group gap="sm">
                                    <ThemeIcon variant="light" color="indigo" radius="sm">
                                        <Map size={16} />
                                    </ThemeIcon>
                                    <Stack gap={0}>
                                        <Text size="sm" fw={600}>Auto-Navigation</Text>
                                        <Text size={rem(10)} color="dimmed">Automated triggers for external maps</Text>
                                    </Stack>
                                </Group>
                                <Switch color="indigo" size="md" />
                            </Group>
                        </Stack>
                    </Paper>
                </Box>

                {/* System & Security */}
                <Box>
                    <Title order={6} fw={900} mb="md" tt="uppercase" ls={0.5}>System & Security</Title>
                    <Paper radius="md" withBorder p="md">
                        <Stack gap="sm">
                            <UnstyledButton p="sm" className="hover-bg" style={{ borderRadius: rem(8), width: '100%' }}>
                                <Group justify="space-between">
                                    <Group gap="sm">
                                        <ThemeIcon variant="light" color="gray" radius="sm">
                                            <ShieldCheck size={16} />
                                        </ThemeIcon>
                                        <Text size="sm" fw={600}>Encryption Protocols</Text>
                                    </Group>
                                    <ChevronRight size={16} color="var(--mantine-color-gray-4)" />
                                </Group>
                            </UnstyledButton>
                            <UnstyledButton p="sm" className="hover-bg" style={{ borderRadius: rem(8), width: '100%' }}>
                                <Group justify="space-between">
                                    <Group gap="sm">
                                        <ThemeIcon variant="light" color="gray" radius="sm">
                                            <Moon size={16} />
                                        </ThemeIcon>
                                        <Text size="sm" fw={600}>Night Mode Interface</Text>
                                    </Group>
                                    <Switch size="sm" />
                                </Group>
                            </UnstyledButton>
                            <UnstyledButton p="sm" className="hover-bg" style={{ borderRadius: rem(8), width: '100%' }}>
                                <Group justify="space-between">
                                    <Group gap="sm">
                                        <ThemeIcon variant="light" color="gray" radius="sm">
                                            <Globe size={16} />
                                        </ThemeIcon>
                                        <Text size="sm" fw={600}>Operational Language</Text>
                                    </Group>
                                    <Text size="xs" color="dimmed">English (US)</Text>
                                </Group>
                            </UnstyledButton>
                        </Stack>
                    </Paper>
                </Box>

                {/* Support & Logistics */}
                <Box>
                    <Title order={6} fw={900} mb="md" tt="uppercase" ls={0.5}>Support & Logistics</Title>
                    <Paper radius="md" withBorder p="md">
                        <Stack gap="sm">
                            <UnstyledButton p="sm" className="hover-bg" style={{ borderRadius: rem(8), width: '100%' }} component={Link} href="/driver/help">
                                <Group justify="space-between">
                                    <Group gap="sm">
                                        <ThemeIcon variant="light" color="teal" radius="sm">
                                            <Headset size={16} />
                                        </ThemeIcon>
                                        <Text size="sm" fw={600}>Tactical Support</Text>
                                    </Group>
                                    <ChevronRight size={16} color="var(--mantine-color-gray-4)" />
                                </Group>
                            </UnstyledButton>
                            <UnstyledButton p="sm" className="hover-bg" style={{ borderRadius: rem(8), width: '100%' }}>
                                <Group justify="space-between">
                                    <Group gap="sm">
                                        <ThemeIcon variant="light" color="teal" radius="sm">
                                            <HelpCircle size={16} />
                                        </ThemeIcon>
                                        <Text size="sm" fw={600}>Operating Manual</Text>
                                    </Group>
                                    <ChevronRight size={16} color="var(--mantine-color-gray-4)" />
                                </Group>
                            </UnstyledButton>
                        </Stack>
                    </Paper>
                </Box>

                {/* Device Intel */}
                <Box>
                    <Paper radius="md" p="md" style={{ background: 'var(--mantine-color-gray-0)', border: '1px solid var(--mantine-color-gray-2)' }}>
                        <Group justify="space-between">
                            <Group gap="xs">
                                <Smartphone size={14} color="var(--mantine-color-gray-5)" />
                                <Text size={rem(10)} color="dimmed">Client Version 2.4.12-Operational</Text>
                            </Group>
                            <Group gap="xs">
                                <Wifi size={14} color="var(--mantine-color-green-5)" />
                                <Text size={rem(10)} color="dimmed">Stable Connection</Text>
                            </Group>
                        </Group>
                    </Paper>
                </Box>

                {/* Terminate Session */}
                <Stack gap="sm">
                    <Button 
                        variant="light" 
                        color="red" 
                        radius="md" 
                        fullWidth 
                        leftSection={<LogOut size={16} />}
                        onClick={() => logout()}
                    >
                        Terminate Operational Session
                    </Button>
                    <Anchor ta="center" size="xs" color="dimmed" underline="always" onClick={() => confirm('Delete account?')}>
                        Inhibit Driver Profile (Request Deletion)
                    </Anchor>
                </Stack>
            </Stack>
        </AppLayout>
    );
}

// Simple UnstyledButton to avoid repetition
function UnstyledButton({ children, ...others }: any) {
    return (
        <Box 
            component="div" 
            {...others} 
            style={{ 
                cursor: 'pointer', 
                ...others.style 
            }}
        >
            {children}
        </Box>
    );
}
