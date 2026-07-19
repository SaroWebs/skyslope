import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Alert, Button, Checkbox, Group, Paper, Select, SimpleGrid, Stack, Text, Textarea, TextInput } from '@mantine/core';
import { ArrowLeft, Save } from 'lucide-react';
import AdminLayout from '../../../../layouts/AdminLayout';

type Place = { id: number; name: string; city?: string | null; state?: string | null };
type Itinerary = { id: number; day_index: number; time?: string | null; title?: string | null; details?: string | null; description?: string | null; activities?: string[]; accommodation?: string | null; meals_included?: string[]; distance_km?: string | null; place?: Place };
const lines = (value: string) => value.split('\n').map((item) => item.trim()).filter(Boolean);

export default function EditItinerary({ title, tour, itinerary, places }: { title: string; tour: { id: number; title: string }; itinerary: Itinerary; places: Place[] }) {
    const { data, setData, put, processing, errors, transform } = useForm({
        time: itinerary.time?.slice(0, 5) ?? '', place_id: itinerary.place ? String(itinerary.place.id) : '', title: itinerary.title ?? '',
        details: itinerary.details ?? itinerary.description ?? '', activities_text: (itinerary.activities ?? []).join('\n'), accommodation: itinerary.accommodation ?? '',
        meals_included: itinerary.meals_included ?? [] as string[], distance_km: itinerary.distance_km ?? '',
    });
    const submit = (event: React.FormEvent) => { event.preventDefault(); transform((values) => ({ ...values, activities: lines(values.activities_text) })); put(`/admin/tours/${tour.id}/itineraries/${itinerary.id}`); };

    return <AdminLayout title={title}><Head title={`Edit Day ${itinerary.day_index} · ${tour.title}`} /><form onSubmit={submit}><Stack gap="lg" maw={920} mx="auto">
        <Group justify="space-between"><div><Text size="xs" fw={800} c="blue" tt="uppercase">Itinerary day</Text><Text size="xl" fw={900}>Edit Day {itinerary.day_index}</Text><Text c="dimmed">Day order is sequential; add or remove days to change the calculated tour duration.</Text></div><Button component={Link} href={`/admin/tours/${tour.id}/itineraries`} variant="default" leftSection={<ArrowLeft size={16} />}>Itinerary</Button></Group>
        <Paper p="xl" radius="lg" withBorder><Stack gap="lg">
            <SimpleGrid cols={{ base: 1, md: 2 }}><Select required searchable label="Point of interest" data={places.map((place) => ({ value: String(place.id), label: [place.name, place.city, place.state].filter(Boolean).join(' · ') }))} value={data.place_id} onChange={(value) => setData('place_id', value ?? '')} error={errors.place_id} /><TextInput type="time" label="Day start time" value={data.time} onChange={(e) => setData('time', e.currentTarget.value)} error={errors.time} /></SimpleGrid>
            <TextInput label="Day title" value={data.title} onChange={(e) => setData('title', e.currentTarget.value)} error={errors.title} />
            <Textarea required minRows={5} label="Detailed day plan" value={data.details} onChange={(e) => setData('details', e.currentTarget.value)} error={errors.details} />
            <SimpleGrid cols={{ base: 1, md: 3 }}><Textarea minRows={3} label="Activities" description="One per line" value={data.activities_text} onChange={(e) => setData('activities_text', e.currentTarget.value)} /><TextInput label="Accommodation" value={data.accommodation} onChange={(e) => setData('accommodation', e.currentTarget.value)} error={errors.accommodation} /><TextInput label="Travel distance" value={data.distance_km} onChange={(e) => setData('distance_km', e.currentTarget.value)} error={errors.distance_km} /></SimpleGrid>
            <Checkbox.Group label="Meals included" value={data.meals_included} onChange={(value) => setData('meals_included', value)}><Group mt="xs"><Checkbox value="breakfast" label="Breakfast" /><Checkbox value="lunch" label="Lunch" /><Checkbox value="dinner" label="Dinner" /></Group></Checkbox.Group>
            {Object.keys(errors).length > 0 && <Alert color="red">Review the highlighted fields.</Alert>}
            <Group justify="flex-end"><Button component={Link} href={`/admin/tours/${tour.id}/itineraries`} variant="default">Cancel</Button><Button type="submit" loading={processing} leftSection={<Save size={17} />}>Save Day {itinerary.day_index}</Button></Group>
        </Stack></Paper>
    </Stack></form></AdminLayout>;
}
