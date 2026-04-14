import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Alert,
    Anchor,
    Box,
    Button,
    Checkbox,
    Group,
    Paper,
    PasswordInput,
    Stack,
    Text,
    TextInput,
    ThemeIcon,
    Title,
} from '@mantine/core';
import { ArrowRight, Info, Lock, Mail, ShieldCheck } from 'lucide-react';

interface LoginProps {
    errors?: {
        email?: string;
        password?: string;
    };
}

export default function Login({ errors }: LoginProps) {
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        setLoading(true);

        router.post('/login', formData, {
            onFinish: () => setLoading(false),
        });
    };

    return (
        <Box
            style={{
                minHeight: '100vh',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)',
            }}
        >
            <Head title="Admin Login" />

            <Stack gap="xl" maw={420} w="100%" px="md">
                <Box style={{ textAlign: 'center' }}>
                    <ThemeIcon size={64} radius="xl" variant="gradient" gradient={{ from: 'blue', to: 'indigo' }} mb="md">
                        <ShieldCheck size={32} />
                    </ThemeIcon>
                    <Title order={2} fw={900} style={{ letterSpacing: '-0.5px' }}>
                        Admin Access
                    </Title>
                    <Text color="dimmed" size="sm" mt={4}>
                        Sign in to manage SkySlope operations
                    </Text>
                </Box>

                <Paper radius="md" p="xl" withBorder shadow="md">
                    <form onSubmit={handleSubmit}>
                        <Stack gap="md">
                            <TextInput
                                label="Admin Email"
                                placeholder="admin@skyslope.com"
                                required
                                leftSection={<Mail size={16} color="gray" />}
                                value={formData.email}
                                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                error={errors?.email}
                                radius="md"
                            />

                            <PasswordInput
                                label="Password"
                                placeholder="Enter your password"
                                required
                                leftSection={<Lock size={16} color="gray" />}
                                value={formData.password}
                                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                error={errors?.password}
                                radius="md"
                            />

                            <Group justify="space-between" mt="xs">
                                <Checkbox
                                    label="Remember me"
                                    checked={formData.remember}
                                    onChange={(e) => setFormData({ ...formData, remember: e.currentTarget.checked })}
                                />
                                <Anchor size="sm" component={Link} href="#">
                                    Need help?
                                </Anchor>
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
                                Login to Dashboard
                            </Button>
                        </Stack>
                    </form>
                </Paper>

                <Alert icon={<Info size={16} />} title="Secure Access" color="blue" radius="md">
                    <Text size="xs">This panel is for authenticated administrative users only.</Text>
                </Alert>
            </Stack>
        </Box>
    );
}
