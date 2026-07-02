import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import {
    Table,
    Text,
    Group,
    Button,
    Paper,
    Pagination,
    ActionIcon,
    Stack,
    Badge,
    Tooltip,
    Modal,
    TextInput,
    NumberInput,
    Select,
    Divider
} from '@mantine/core';
import {
    Plus,
    Edit,
    Trash,
    UserCircle,
    Truck,
    ArrowLeft
} from 'lucide-react';
import { useDisclosure } from '@mantine/hooks';

interface TourSchedule {
    id: number;
    departure_date: string;
    return_date: string;
    total_seats: number;
    booked_seats: number;
    price_override: number | null;
    status: 'open' | 'sold_out' | 'closed' | 'cancelled' | 'completed';
    guideAssignments: { id: number }[];
    driverAssignments: Array<{
        id: number;
        role: 'transport' | 'lead' | 'assistant';
        status: string;
        driver?: {
            id: number;
            name: string;
        };
        vehicle?: {
            id: number;
            registration_number: string;
        };
    }>;
}

interface DriverOption {
    id: number;
    name: string;
    phone?: string;
    rating?: number | null;
    vehicle_number?: string | null;
    can_tour_lead: boolean;
    can_tour_transport: boolean;
}

interface VehicleOption {
    id: number;
    driver_id?: number | null;
    car_category_id?: number | null;
    registration_number: string;
    make?: string | null;
    model?: string | null;
}

interface SchedulesProps {
    title: string;
    tour: {
        id: number;
        title: string;
        slug: string;
    };
    schedules: {
        data: TourSchedule[];
        current_page: number;
        last_page: number;
        total: number;
    };
    drivers: DriverOption[];
    vehicles: VehicleOption[];
}

export default function Schedules({ title, tour, schedules, drivers, vehicles }: SchedulesProps) {
    const [opened, { open, close }] = useDisclosure(false);
    const [assignmentOpened, { open: openAssignment, close: closeAssignment }] = useDisclosure(false);
    const [editingSchedule, setEditingSchedule] = useState<TourSchedule | null>(null);
    const [assignmentSchedule, setAssignmentSchedule] = useState<TourSchedule | null>(null);

    const [formData, setFormData] = useState({
        departure_date: '',
        return_date: '',
        total_seats: 20,
        price_override: '',
        status: 'open'
    });
    const [assignmentData, setAssignmentData] = useState({
        driver_id: '',
        vehicle_id: '',
        role: 'transport',
    });

    const handlePageChange = (page: number) => {
        router.get(`/admin/tours/${tour.id}/schedules`, { page }, { preserveState: true });
    };

    const handleEdit = (schedule: TourSchedule) => {
        setEditingSchedule(schedule);
        setFormData({
            departure_date: schedule.departure_date,
            return_date: schedule.return_date,
            total_seats: schedule.total_seats,
            price_override: schedule.price_override?.toString() || '',
            status: schedule.status
        });
        open();
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this schedule?')) {
            router.delete(`/admin/tours/${tour.id}/schedules/${id}`);
        }
    };

    const openCreateModal = () => {
        setEditingSchedule(null);
        setFormData({
            departure_date: '',
            return_date: '',
            total_seats: 20,
            price_override: '',
            status: 'open'
        });
        open();
    };

    const openAssignmentModal = (schedule: TourSchedule) => {
        setAssignmentSchedule(schedule);
        setAssignmentData({
            driver_id: '',
            vehicle_id: '',
            role: 'transport',
        });
        openAssignment();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const payload = {
            ...formData,
            price_override: formData.price_override ? Number(formData.price_override) : null
        };

        if (editingSchedule) {
            router.put(`/admin/tours/${tour.id}/schedules/${editingSchedule.id}`, payload, {
                onSuccess: () => close()
            });
        } else {
            router.post(`/admin/tours/${tour.id}/schedules`, payload, {
                onSuccess: () => close()
            });
        }
    };

    const handleAssignmentSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!assignmentSchedule || !assignmentData.driver_id) return;

        router.post(`/admin/tours/${tour.id}/schedules/${assignmentSchedule.id}/assign-driver`, {
            driver_id: Number(assignmentData.driver_id),
            vehicle_id: assignmentData.vehicle_id ? Number(assignmentData.vehicle_id) : null,
            role: assignmentData.role,
        }, {
            preserveScroll: true,
            onSuccess: () => closeAssignment(),
        });
    };

    return (
        <AdminLayout title={title}>
            <Head title={`Schedules: ${tour.title}`} />

            <Stack gap="lg">
                <Paper p="xl" radius="md" withBorder shadow="sm">
                    <Group justify="space-between" mb="xl">
                        <Group gap="md">
                            <ActionIcon component={Link} href="/admin/tours" variant="light" size="lg">
                                <ArrowLeft size={18} />
                            </ActionIcon>
                            <div>
                                <Text size="lg" fw={600}>Tour Schedules</Text>
                                <Text size="sm" color="dimmed">{tour.title}</Text>
                            </div>
                        </Group>
                        <Button onClick={openCreateModal} color="blue" radius="md" leftSection={<Plus size={16} />}>
                            Add Schedule
                        </Button>
                    </Group>

                    <Table.ScrollContainer minWidth={800}>
                        <Table verticalSpacing="md" highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Dates</Table.Th>
                                    <Table.Th>Seats (Booked/Total)</Table.Th>
                                    <Table.Th>Price Override</Table.Th>
                                    <Table.Th>Assignments</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th />
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {schedules.data.map((schedule) => (
                                    <Table.Tr key={schedule.id}>
                                        <Table.Td>
                                            <Text size="sm" fw={500}>{schedule.departure_date}</Text>
                                            <Text size="xs" color="dimmed">to {schedule.return_date}</Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge variant="light" color={schedule.booked_seats === schedule.total_seats ? 'red' : 'blue'}>
                                                {schedule.booked_seats} / {schedule.total_seats}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            {schedule.price_override ? `₹${schedule.price_override}` : '-'}
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap="xs">
                                                <Tooltip label={`${schedule.guideAssignments.length} Guides Assigned`}>
                                                    <Badge variant="dot" color="violet" leftSection={<UserCircle size={10} />}>
                                                        {schedule.guideAssignments.length}
                                                    </Badge>
                                                </Tooltip>
                                                <Tooltip label={`${schedule.driverAssignments.length} Drivers Assigned`}>
                                                    <Badge variant="dot" color="orange" leftSection={<Truck size={10} />}>
                                                        {schedule.driverAssignments.length}
                                                    </Badge>
                                                </Tooltip>
                                            </Group>
                                            {schedule.driverAssignments.length > 0 && (
                                                <Text size="xs" color="dimmed" mt={4}>
                                                    {schedule.driverAssignments.map((assignment) => `${assignment.driver?.name || 'Driver'} (${assignment.role})`).join(', ')}
                                                </Text>
                                            )}
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge color={schedule.status === 'open' ? 'green' : 'gray'}>
                                                {schedule.status}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td>
                                            <Group gap={8} justify="flex-end">
                                                <Tooltip label="Assign tour driver role">
                                                    <ActionIcon onClick={() => openAssignmentModal(schedule)} color="orange" variant="light">
                                                        <Truck size={16} />
                                                    </ActionIcon>
                                                </Tooltip>
                                                <ActionIcon onClick={() => handleEdit(schedule)} color="blue" variant="light">
                                                    <Edit size={16} />
                                                </ActionIcon>
                                                <ActionIcon onClick={() => handleDelete(schedule.id)} color="red" variant="light">
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
                            Showing {schedules.data.length} of {schedules.total} schedules
                        </Text>
                        <Pagination
                            total={schedules.last_page}
                            value={schedules.current_page}
                            onChange={handlePageChange}
                        />
                    </Group>
                </Paper>
            </Stack>

            <Modal opened={opened} onClose={close} title={editingSchedule ? "Edit Schedule" : "Add Schedule"} size="md">
                <form onSubmit={handleSubmit}>
                    <Stack>
                        <TextInput
                            label="Departure Date"
                            type="date"
                            value={formData.departure_date}
                            onChange={(e) => setFormData({ ...formData, departure_date: e.target.value })}
                            required
                        />
                        <TextInput
                            label="Return Date"
                            type="date"
                            value={formData.return_date}
                            onChange={(e) => setFormData({ ...formData, return_date: e.target.value })}
                            required
                        />
                        <NumberInput
                            label="Total Seats"
                            value={formData.total_seats}
                            onChange={(val) => setFormData({ ...formData, total_seats: Number(val) })}
                            required
                        />
                        <TextInput
                            label="Price Override (Optional)"
                            placeholder="Leave blank to use tour base price"
                            type="number"
                            value={formData.price_override}
                            onChange={(e) => setFormData({ ...formData, price_override: e.target.value })}
                        />
                        <Select
                            label="Status"
                            data={[
                                { value: 'open', label: 'Open' },
                                { value: 'sold_out', label: 'Sold Out' },
                                { value: 'closed', label: 'Closed' },
                                { value: 'cancelled', label: 'Cancelled' }
                            ]}
                            value={formData.status}
                            onChange={(val) => setFormData({ ...formData, status: val || 'open' })}
                        />
                        <Button type="submit" fullWidth mt="md">
                            {editingSchedule ? "Save Changes" : "Create Schedule"}
                        </Button>
                    </Stack>
                </form>
            </Modal>

            <Modal opened={assignmentOpened} onClose={closeAssignment} title="Assign Tour Driver Role" size="md">
                <form onSubmit={handleAssignmentSubmit}>
                    <Stack>
                        <Text size="sm" color="dimmed">
                            {assignmentSchedule ? `${tour.title} · ${assignmentSchedule.departure_date}` : 'Select a driver for this schedule.'}
                        </Text>
                        <Select
                            label="Role"
                            data={[
                                { value: 'transport', label: 'Transport' },
                                { value: 'lead', label: 'Lead' },
                                { value: 'assistant', label: 'Assistant' },
                            ]}
                            value={assignmentData.role}
                            onChange={(val) => setAssignmentData({ ...assignmentData, role: val || 'transport' })}
                            required
                        />
                        <Select
                            label="Driver"
                            data={drivers.map((driver) => ({
                                value: String(driver.id),
                                label: `${driver.name}${driver.vehicle_number ? ` · ${driver.vehicle_number}` : ''}${driver.can_tour_lead ? ' · lead' : ''}${driver.can_tour_transport ? ' · transport' : ''}`,
                                disabled: assignmentData.role === 'lead' ? !driver.can_tour_lead : !driver.can_tour_transport && !driver.can_tour_lead,
                            }))}
                            value={assignmentData.driver_id}
                            onChange={(val) => setAssignmentData({ ...assignmentData, driver_id: val || '' })}
                            searchable
                            required
                        />
                        <Select
                            label="Vehicle"
                            data={vehicles.map((vehicle) => ({
                                value: String(vehicle.id),
                                label: `${vehicle.registration_number} · ${vehicle.make || ''} ${vehicle.model || ''}`,
                            }))}
                            value={assignmentData.vehicle_id}
                            onChange={(val) => setAssignmentData({ ...assignmentData, vehicle_id: val || '' })}
                            searchable
                            clearable
                        />
                        <Divider />
                        <Button type="submit" fullWidth leftSection={<Truck size={16} />} disabled={!assignmentData.driver_id}>
                            Assign Driver Role
                        </Button>
                    </Stack>
                </form>
            </Modal>
        </AdminLayout>
    );
}
