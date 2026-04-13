import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
    Paper, 
    TextInput, 
    PasswordInput, 
    Checkbox, 
    Button, 
    Title, 
    Text, 
    Anchor, 
    Stack, 
    Group, 
    Tabs, 
    rem, 
    Box, 
    ThemeIcon, 
    Divider,
    Alert,
    PinInput,
    Transition
} from '@mantine/core';
import { 
    Mail, 
    Lock, 
    Phone, 
    ShieldCheck, 
    Key, 
    ArrowRight, 
    Info, 
    Car, 
    User,
    CheckCircle2,
    AlertCircle
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { useAppNotifications } from '@/app';
import axios from '@/lib/axios';

interface LoginProps {
    errors?: {
        email?: string;
        password?: string;
        phone?: string;
        code?: string;
    };
}

export default function Login({ errors: inertiaErrors }: LoginProps) {
    const { login } = useAuth();
    const addNotification = useAppNotifications();
    const [activeTab, setActiveTab] = useState<string | null>('management');
    const [loading, setLoading] = useState(false);
    
    // Management Login State (Admin/Guide)
    const [mgmtData, setMgmtData] = useState({
        email: '',
        password: '',
        remember: false
    });

    // App Login State (Customer/Driver)
    const [appRole, setAppRole] = useState<'customer' | 'driver'>('customer');
    const [phone, setPhone] = useState('');
    const [otpStep, setOtpStep] = useState<'phone' | 'code'>('phone');
    const [code, setCode] = useState('');

    const handleMgmtLogin = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        router.post('/login', mgmtData, {
            onFinish: () => setLoading(false),
            onError: () => addNotification('Authentication failed. Please check your credentials.', 'error')
        });
    };

    const handleSendOtp = async () => {
        if (!phone) return;
        setLoading(true);
        try {
            const endpoint = appRole === 'driver' ? '/driver-app/otp/send' : '/customer-app/otp/send';
            const response = await axios.post(endpoint, { phone, purpose: 'login' });
            
            if (response.data.success) {
                setOtpStep('code');
                addNotification('OTP sent successfully to your device.', 'success');
            } else {
                addNotification(response.data.message || 'Failed to send OTP.', 'error');
            }
        } catch (error: any) {
            addNotification(error.response?.data?.message || 'Verification service unavailable.', 'error');
        } finally {
            setLoading(false);
        }
    };

    const handleVerifyOtp = async () => {
        if (code.length < 6) return;
        setLoading(true);
        try {
            const endpoint = appRole === 'driver' ? '/driver-app/otp/verify' : '/customer-app/otp/verify';
            const response = await axios.post(endpoint, { phone, code, purpose: 'login' });
            
            if (response.data.success) {
                const userObj = response.data.customer || response.data.driver;
                login(response.data.token, {
                    ...userObj,
                    role: appRole
                });
                addNotification('Identity verified. Accessing dashboard...', 'success');
            } else {
                addNotification(response.data.message || 'Invalid verification code.', 'error');
            }
        } catch (error: any) {
            addNotification(error.response?.data?.message || 'Verification failed.', 'error');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Box 
            style={{ 
                minHeight: '100vh', 
                display: 'flex', 
                alignItems: 'center', 
                justifyContent: 'center',
                background: 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)'
            }}
        >
            <Head title="Access Skyslope" />

            <Stack gap="xl" maw={420} w="100%" px="md">
                <Box style={{ textAlign: 'center' }}>
                    <ThemeIcon size={64} radius="xl" variant="gradient" gradient={{ from: 'blue', to: 'indigo' }} mb="md">
                        <ShieldCheck size={32} />
                    </ThemeIcon>
                    <Title order={2} fw={900} style={{ letterSpacing: '-0.5px' }}>Access Skyslope</Title>
                    <Text color="dimmed" size="sm" mt={4}>Secure gateway for fleet and travel operations</Text>
                </Box>

                <Paper radius="md" p="xl" withBorder shadow="md">
                    <Tabs value={activeTab} onChange={setActiveTab} variant="pills" radius="md">
                        <Tabs.List grow mb="xl">
                            <Tabs.Tab value="management" leftSection={<User size={14} />}>Management</Tabs.Tab>
                            <Tabs.Tab value="app" leftSection={<Car size={14} />}>Mobile App</Tabs.Tab>
                        </Tabs.List>

                        <Tabs.Panel value="management">
                            <form onSubmit={handleMgmtLogin}>
                                <Stack gap="md">
                                    <TextInput
                                        label="Professional Email"
                                        placeholder="admin@skyslope.com"
                                        required
                                        leftSection={<Mail size={16} color="gray" />}
                                        value={mgmtData.email}
                                        onChange={(e) => setMgmtData({ ...mgmtData, email: e.target.value })}
                                        error={inertiaErrors?.email}
                                        radius="md"
                                    />
                                    <PasswordInput
                                        label="Credential Key"
                                        placeholder="Your password"
                                        required
                                        leftSection={<Lock size={16} color="gray" />}
                                        value={mgmtData.password}
                                        onChange={(e) => setMgmtData({ ...mgmtData, password: e.target.value })}
                                        error={inertiaErrors?.password}
                                        radius="md"
                                    />
                                    <Group justify="space-between" mt="xs">
                                        <Checkbox 
                                            label="Remember session" 
                                            checked={mgmtData.remember}
                                            onChange={(e) => setMgmtData({ ...mgmtData, remember: e.currentTarget.checked })}
                                        />
                                        <Anchor size="sm" component={Link} href="#">Forgot Security Key?</Anchor>
                                    </Group>
                                    <Button 
                                        type="submit" 
                                        fullWidth 
                                        mt="md" 
                                        radius="md" 
                                        size="md"
                                        loading={loading}
                                        rightSection={<ArrowRight size={18} />}
                                    >
                                        Establish Connection
                                    </Button>
                                </Stack>
                            </form>
                        </Tabs.Panel>

                        <Tabs.Panel value="app">
                            <Stack gap="md">
                                <Group grow gap="xs">
                                    <Button 
                                        variant={appRole === 'customer' ? 'filled' : 'light'} 
                                        onClick={() => setAppRole('customer')}
                                        radius="md"
                                        size="xs"
                                        leftSection={<User size={14} />}
                                    >
                                        Customer
                                    </Button>
                                    <Button 
                                        variant={appRole === 'driver' ? 'filled' : 'light'} 
                                        color="teal"
                                        onClick={() => setAppRole('driver')}
                                        radius="md"
                                        size="xs"
                                        leftSection={<Car size={14} />}
                                    >
                                        Driver
                                    </Button>
                                </Group>

                                <Transition mounted={otpStep === 'phone'} transition="fade" duration={200}>
                                    {(styles) => (
                                        <Stack style={styles} gap="md">
                                            <TextInput
                                                label="Phone Multi-factor Auth"
                                                placeholder="+91 98765 43210"
                                                required
                                                leftSection={<Phone size={16} color="gray" />}
                                                value={phone}
                                                onChange={(e) => setPhone(e.target.value)}
                                                radius="md"
                                            />
                                            <Button 
                                                fullWidth 
                                                onClick={handleSendOtp} 
                                                loading={loading}
                                                radius="md"
                                                size="md"
                                                leftSection={<Key size={18} />}
                                            >
                                                Request Access Code
                                            </Button>
                                        </Stack>
                                    )}
                                </Transition>

                                <Transition mounted={otpStep === 'code'} transition="fade" duration={200}>
                                    {(styles) => (
                                        <Stack style={styles} gap="md" align="center">
                                            <Text size="sm" fw={700} ta="center">Verify Digital Identity</Text>
                                            <Text size="xs" color="dimmed" ta="center">Enter the 6-digit code sent to {phone}</Text>
                                            <PinInput 
                                                length={6} 
                                                type="number" 
                                                size="md" 
                                                radius="md" 
                                                value={code} 
                                                onChange={setCode}
                                                mask
                                                onComplete={handleVerifyOtp}
                                            />
                                            <Button 
                                                fullWidth 
                                                variant="subtle" 
                                                color="gray" 
                                                size="xs" 
                                                onClick={() => setOtpStep('phone')}
                                            >
                                                Change Phone Number
                                            </Button>
                                        </Stack>
                                    )}
                                </Transition>
                            </Stack>
                        </Tabs.Panel>
                    </Tabs>
                </Paper>

                <Alert icon={<Info size={16} />} title="Operational Awareness" color="blue" radius="md">
                    <Text size="xs">This environment tracks active credentials and session tokens for forensic security audit.</Text>
                </Alert>

                <Group justify="center" gap="xs">
                    <Text size="xs" color="dimmed">Technical issues?</Text>
                    <Anchor size="xs" href="#">Contact System Support</Anchor>
                </Group>
            </Stack>
        </Box>
    );
}