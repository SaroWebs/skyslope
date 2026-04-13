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
    Box,
    rem
} from '@mantine/core';
import { 
    Pencil, 
    ArrowLeft, 
    IndianRupee, 
    Tag, 
    Calendar, 
    Save, 
    FileText,
    Info,
    History
} from 'lucide-react';

interface Tour {
    id: number;
    title: string;
    description: string;
    price: number | string;
    discount: number | string;
    available_from: string;
    available_to: string;
}

interface EditTourProps {
    title: string;
    tour: Tour;
}

export default function Edit({ title, tour }: EditTourProps) {
    const { data, setData, put, processing, errors } = useForm({
        title: tour.title,
        description: tour.description,
        price: tour.price,
        discount: tour.discount,
        available_from: tour.available_from ? new Date(tour.available_from).toISOString().split('T')[0] : '',
        available_to: tour.available_to ? new Date(tour.available_to).toISOString().split('T')[0] : '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/tours/${tour.id}`);
    };

    return (
        <AdminLayout title={title}>
            <Head title={`Edit Tour: ${tour.title}`} />

            <Stack gap="lg" maw={800} mx="auto">
                <Group justify="space-between" align="flex-end">
                    <Stack gap={2}>
                        <Group gap="xs">
                            <ThemeIcon variant="light" color="yellow" size="sm">
                                <Pencil size={14} />
                            </ThemeIcon>
                            <Text size="xs" fw={700} color="dimmed" tt="uppercase">Experience Refining</Text>
                        </Group>
                        <Text size="h3" fw={800}>Update Package Details</Text>
                    </Stack>
                    <Button 
                        component={Link} 
                        href={`/admin/tours/${tour.id}`} 
                        variant="subtle" 
                        color="gray" 
                        leftSection={<ArrowLeft size={16} />}
                        radius="md"
                    >
                        Package Details
                    </Button>
                </Group>

                <Paper p="xl" radius="md" withBorder shadow="sm">
                    <form onSubmit={handleSubmit}>
                        <Stack gap="xl">
                            <Box>
                                <Group gap={8} mb="xs">
                                    <FileText size={16} color="var(--mantine-color-blue-6)" />
                                    <Text fw={700} size="sm">Modify Foundational Data</Text>
                                </Group>
                                <Stack gap="md">
                                    <TextInput
                                        label="Marketable Package Title"
                                        placeholder="e.g. Majestic Meghalaya 5D4N"
                                        required
                                        value={data.title}
                                        onChange={(e) => setData('title', e.currentTarget.value)}
                                        error={errors.title}
                                        radius="md"
                                    />
                                    <Textarea
                                        label="Detailed Narrative"
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
                                    <Text fw={700} size="sm">Economic Readjustment</Text>
                                </Group>
                                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="lg">
                                    <TextInput
                                        label="Adjusted Price"
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
                                        label="Seasonal Incentive (%)"
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
                                    <Text fw={700} size="sm">Update Temporal Window</Text>
                                </Group>
                                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="lg">
                                    <TextInput
                                        label="Valid From"
                                        type="date"
                                        required
                                        value={data.available_from}
                                        onChange={(e) => setData('available_from', e.currentTarget.value)}
                                        error={errors.available_from}
                                        radius="md"
                                    />
                                    <TextInput
                                        label="Valid To"
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
                                    href={`/admin/tours/${tour.id}`} 
                                    variant="subtle" 
                                    color="gray"
                                    radius="md"
                                >
                                    Cancel Changes
                                </Button>
                                <Button 
                                    type="submit" 
                                    color="yellow" 
                                    size="md" 
                                    radius="md" 
                                    leftSection={<Save size={18} />}
                                    loading={processing}
                                >
                                    Commit Redeployment
                                </Button>
                            </Group>
                        </Stack>
                    </form>
                </Paper>

                <Paper p="lg" radius="md" bg="yellow.0" withBorder>
                    <Group gap="sm" align="flex-start">
                        <ThemeIcon color="yellow" variant="light">
                            <History size={16} />
                        </ThemeIcon>
                        <Box style={{ flex: 1 }}>
                            <Text size="sm" fw={700} color="yellow.9">Audit Note:</Text>
                            <Text size="xs" color="yellow.9">System overrides for active bookings will not occur. Significant price adjustments should be communicated to currently scheduled guests.</Text>
                        </Box>
                    </Group>
                </Paper>
            </Stack>
        </AdminLayout>
    );
}