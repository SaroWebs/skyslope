import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Table,
    Text,
    Group,
    TextInput,
    Button,
    Paper,
    Pagination,
    Select,
    ActionIcon,
    Stack,
    Badge,
    Tooltip,
    Modal,
    NumberInput,
    Checkbox
} from '@mantine/core';
import {
    Search,
    Trash,
    Plus,
    Edit
} from 'lucide-react';
import { useDisclosure } from '@mantine/hooks';
import AdminLayout from '@/layouts/AdminLayout';

interface Vehicle {
    id: number;
    driver_id: number | null;
    car_category_id: number;
    registration_number: string;
    make: string;
    model: string;
    year: number;
    color: string;
    seats: number;
    is_active: boolean;
    condition: string;
    driver?: {
        id: number;
        name: string;
    };
    category?: {
        id: number;
        name: string;
    };
}

interface VehiclesProps {
    title: string;
    vehicles: {
        data: Vehicle[];
        current_page: number;
        last_page: number;
        total: number;
    };
    categories: { id: number, name: string }[];
    drivers: { id: number, name: string }[];
    filters: {
        search: string;
    };
}

export default function Vehicles({ title, vehicles, categories, drivers, filters }: VehiclesProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [opened, { open, close }] = useDisclosure(false);
    const [editingVehicle, setEditingVehicle] = useState<Vehicle | null>(null);

    // Form states
    const [formData, setFormData] = useState({
        car_category_id: '',
        driver_id: '',
        registration_number: '',
        make: '',
        model: '',
        year: 2024,
        color: 'White',
        fuel_type: 'Petrol',
        seats: 4,
        is_ac: true,
        condition: 'good',
        is_active: true
    });

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/admin/vehicles', { search: value }, { preserveState: true, replace: true });
    };

    const handlePageChange = (page: number) => {
        router.get('/admin/vehicles', { search, page }, { preserveState: true });
    };

    const handleEdit = (vehicle: Vehicle) => {
        setEditingVehicle(vehicle);
        setFormData({
            car_category_id: vehicle.car_category_id.toString(),
            driver_id: vehicle.driver_id ? vehicle.driver_id.toString() : '',
            registration_number: vehicle.registration_number,
            make: vehicle.make,
            model: vehicle.model,
            year: vehicle.year,
            color: vehicle.color,
            fuel_type: 'Petrol', // Simplified for demo
            seats: vehicle.seats,
            is_ac: true,
            condition: vehicle.condition,
            is_active: vehicle.is_active
        });
        open();
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this vehicle?')) {
            router.delete(`/admin/vehicles/${id}`);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingVehicle) {
            router.put(`/admin/vehicles/${editingVehicle.id}`, formData, {
                onSuccess: () => close()
            });
        } else {
            router.post(`/admin/vehicles`, formData, {
                onSuccess: () => close()
            });
        }
    };

    const openCreateModal = () => {
        setEditingVehicle(null);
        setFormData({
            car_category_id: '',
            driver_id: '',
            registration_number: '',
            make: '',
            model: '',
            year: new Date().getFullYear(),
            color: '',
            fuel_type: 'Petrol',
            seats: 4,
            is_ac: true,
            condition: 'good',
            is_active: true
        });
        open();
    };

    return (
        <AdminLayout title={title}>
            <Head title="Vehicles" />

            <Stack gap="lg">
                <Paper p="xl" radius="md" withBorder shadow="sm">
                    <Group justify="space-between" mb="xl">
                        <TextInput
                            placeholder="Search registration, make, model..."
                            leftSection={<Search size={16} />}
                            value={search}
                            onChange={(e) => handleSearch(e.currentTarget.value)}
                            style={{ flex: 1, maxWidth: 400 }}
                            radius="md"
                        />
                        <Button onClick={openCreateModal} color="blue" radius="md" leftSection={<Plus size={16} />}>
                            Add Vehicle
                        </Button>
                    </Group>

                    <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Registration</Table.Th>
                                    <Table.Th>Details</Table.Th>
                                    <Table.Th>Category</Table.Th>
                                    <Table.Th>Assigned Driver</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {vehicles.data.map((vehicle) => (
                                    <Table.Tr key={vehicle.id}>
                                        <Table.Td>
                                            <Badge variant="filled" color="dark" size="lg" radius="sm">
                                                {vehicle.registration_number}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            <Text fw={500}>{vehicle.make} {vehicle.model} ({vehicle.year})</Text>
                                            <Text size="xs" color="dimmed">{vehicle.color} • {vehicle.seats} Seats</Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Text size="sm">{vehicle.category?.name}</Text>
                                        </Table.Td>
                                        <Table.Td>
                                            {vehicle.driver ? (
                                                <Badge variant="dot" color="blue">{vehicle.driver.name}</Badge>
                                            ) : (
                                                <Text size="xs" color="dimmed">Unassigned</Text>
                                            )}
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge color={vehicle.is_active ? 'green' : 'red'}>
                                                {vehicle.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={8} justify="flex-end">
                                                <ActionIcon onClick={() => handleEdit(vehicle)} color="blue" variant="light">
                                                    <Edit size={16} />
                                                </ActionIcon>
                                                <ActionIcon onClick={() => handleDelete(vehicle.id)} color="red" variant="light">
                                                    <Trash size={16} />
                                                </ActionIcon>
                                            </Group>
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Table.ScrollContainer>

                    <Group justify="space-between" mt="xl">
                        <Text size="sm" color="dimmed">
                            Showing {vehicles.data.length} of {vehicles.total} vehicles
                        </Text>
                        <Pagination
                            total={vehicles.last_page}
                            value={vehicles.current_page}
                            onChange={handlePageChange}
                        />
                    </Group>
                </Paper>
            </Stack>

            <Modal opened={opened} onClose={close} title={editingVehicle ? "Edit Vehicle" : "Add Vehicle"} size="lg">
                <form onSubmit={handleSubmit}>
                    <Stack>
                        <Select
                            label="Car Category"
                            placeholder="Select category"
                            data={categories.map(c => ({ value: c.id.toString(), label: c.name }))}
                            value={formData.car_category_id}
                            onChange={(val) => setFormData({ ...formData, car_category_id: val || '' })}
                            required
                        />
                        <TextInput
                            label="Registration Number"
                            placeholder="E.g. MH04 AB 1234"
                            value={formData.registration_number}
                            onChange={(e) => setFormData({ ...formData, registration_number: e.target.value })}
                            required
                        />
                        <Group grow>
                            <TextInput
                                label="Make"
                                placeholder="E.g. Toyota"
                                value={formData.make}
                                onChange={(e) => setFormData({ ...formData, make: e.target.value })}
                                required
                            />
                            <TextInput
                                label="Model"
                                placeholder="E.g. Innova Crysta"
                                value={formData.model}
                                onChange={(e) => setFormData({ ...formData, model: e.target.value })}
                                required
                            />
                        </Group>
                        <Group grow>
                            <NumberInput
                                label="Year"
                                value={formData.year}
                                onChange={(val) => setFormData({ ...formData, year: Number(val) })}
                                required
                            />
                            <TextInput
                                label="Color"
                                value={formData.color}
                                onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                                required
                            />
                        </Group>
                        <Group grow>
                            <NumberInput
                                label="Seats"
                                value={formData.seats}
                                onChange={(val) => setFormData({ ...formData, seats: Number(val) })}
                                required
                            />
                            <Select
                                label="Assign Driver (Optional)"
                                placeholder="Select driver"
                                data={drivers.map(d => ({ value: d.id.toString(), label: d.name }))}
                                value={formData.driver_id}
                                onChange={(val) => setFormData({ ...formData, driver_id: val || '' })}
                                clearable
                            />
                        </Group>

                        {editingVehicle && (
                            <Checkbox
                                label="Vehicle is active"
                                checked={formData.is_active}
                                onChange={(e) => setFormData({ ...formData, is_active: e.currentTarget.checked })}
                            />
                        )}

                        <Button type="submit" fullWidth mt="md">
                            {editingVehicle ? "Save Changes" : "Add Vehicle"}
                        </Button>
                    </Stack>
                </form>
            </Modal>
        </AdminLayout>
    );
}
