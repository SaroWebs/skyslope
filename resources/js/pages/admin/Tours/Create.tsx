import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '../../../layouts/AdminLayout';
import { 
    Paper, 
    Stack, 
    TextInput, 
    NumberInput, 
    Textarea, 
    Button, 
    Group, 
    Text, 
    SimpleGrid, 
    ThemeIcon, 
    Divider,
    rem
} from '@mantine/core';
import { 
    Plus, 
    ArrowLeft, 
    Navigation, 
    IndianRupee, 
    Tag, 
    Calendar, 
    Save, 
    X,
    FileText,
    Info
} from 'lucide-react';

interface CreateTourProps {
    title: string;
}

export default function Create({ title }: CreateTourProps) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        price: '',
        discount: '',
        available_from: '',
        available_to: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/tours');
    };

    return (
        <AdminLayout title={title}>
            <Head title="Create New Tour" />

            <Stack gap="lg" maw={800} mx="auto">
                <Group justify="space-between" align="flex-end">
                    <Stack gap={2}>
                        <Group gap="xs">
                            <ThemeIcon variant="light" color="blue" size="sm">
                                <Plus size={14} />
                            </ThemeIcon>
                            <Text size="xs" fw={700} color="dimmed" tt="uppercase">Tour Provisioning</Text>
                        </Group>
                        <Text size="h3" fw={800}>Architect New Experience</Text>
                    </Stack>
                    <Button 
                        component={Link} 
                        href="/admin/tours" 
                        variant="subtle" 
                        color="gray" 
                        leftSection={<ArrowLeft size={16} />}
                        radius="md"
                    >
                        Index
                    </Button>
                </Group>

                <Paper p="xl" radius="md" withBorder shadow="sm">
                    <form onSubmit={handleSubmit}>
                        <Stack gap="xl">
                            <Box>
                                <Group gap={8} mb="xs">
                                    <FileText size={16} color="var(--mantine-color-blue-6)" />
                                    <Text fw={700} size="sm">Foundational Information</Text>
                                </Group>
                                <Stack gap="md">
                                    <TextInput
                                        label="Tour Designation"
                                        placeholder="e.g. Majestic Meghalaya 5D4N"
                                        required
                                        value={data.title}
                                        onChange={(e) => setData('title', e.currentTarget.value)}
                                        error={errors.title}
                                        radius="md"
                                    />
                                    <Textarea
                                        label="Package Narrative"
                                        placeholder="Describe the unique elements of this tour..."
                                        required
                                        minRows={4}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.currentTarget.value)}
                                        error={errors.description}
                                        radius="md"
                                    />
                                </Stack>
                            </Box>

                            <Divider variant="dashed" />

                            <Box>
                                <Group gap={8} mb="xs">
                                    <IndianRupee size={16} color="var(--mantine-color-green-6)" />
                                    <Text fw={700} size="sm">Economic Parameters</Text>
                                </Group>
                                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="lg">
                                    <TextInput
                                        label="Market Price (Base)"
                                        type="number"
                                        placeholder="0.00"
                                        leftSection={<IndianRupee size={16} color="gray" />}
                                        required
                                        value={data.price}
                                        onChange={(e) => setData('price', e.currentTarget.value)}
                                        error={errors.price}
                                        radius="md"
                                    />
                                    <TextInput
                                        label="Incentive Discount (%)"
                                        type="number"
                                        placeholder="0"
                                        leftSection={<Tag size={16} color="gray" />}
                                        value={data.discount}
                                        onChange={(e) => setData('discount', e.currentTarget.value)}
                                        error={errors.discount}
                                        radius="md"
                                    />
                                </SimpleGrid>
                            </Box>

                            <Divider variant="dashed" />

                            <Box>
                                <Group gap={8} mb="xs">
                                    <Calendar size={16} color="var(--mantine-color-indigo-6)" />
                                    <Text fw={700} size="sm">Availability Window</Text>
                                </Group>
                                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="lg">
                                    <TextInput
                                        label="Deployment Date (From)"
                                        type="date"
                                        required
                                        value={data.available_from}
                                        onChange={(e) => setData('available_from', e.currentTarget.value)}
                                        error={errors.available_from}
                                        radius="md"
                                    />
                                    <TextInput
                                        label="Retirement Date (To)"
                                        type="date"
                                        required
                                        value={data.available_to}
                                        onChange={(e) => setData('available_to', e.currentTarget.value)}
                                        error={errors.available_to}
                                        radius="md"
                                    />
                                </SimpleGrid>
                            </Box>

                            <Group justify="flex-end" pt="xl">
                                <Button 
                                    component={Link} 
                                    href="/admin/tours" 
                                    variant="subtle" 
                                    color="gray"
                                    radius="md"
                                >
                                    Discard Changes
                                </Button>
                                <Button 
                                    type="submit" 
                                    color="blue" 
                                    size="md" 
                                    radius="md" 
                                    leftSection={<Save size={18} />}
                                    loading={processing}
                                >
                                    Publish Experience
                                </Button>
                            </Group>
                        </Stack>
                    </form>
                </Paper>

                <Paper p="lg" radius="md" bg="blue.0" withBorder>
                    <Group gap="sm" align="flex-start">
                        <ThemeIcon color="blue" variant="light">
                            <Info size={16} />
                        </ThemeIcon>
                        <Box style={{ flex: 1 }}>
                            <Text size="sm" fw={700} color="blue.9">Pro Tip:</Text>
                            <Text size="xs" color="blue.8">After creating the tour, you can add detailed daily itineraries, assign guides, and link preferred drivers from the tour management console.</Text>
                        </Box>
                    </Group>
                </Paper>
            </Stack>
        </AdminLayout>
    );
}