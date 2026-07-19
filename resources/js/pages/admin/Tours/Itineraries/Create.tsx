import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Alert, Button, Checkbox, Group, Paper, Select, SimpleGrid, Stack, Text, Textarea, TextInput, ThemeIcon } from '@mantine/core';
import { ArrowLeft, CalendarPlus, CircleAlert, Save } from 'lucide-react';
import AdminLayout from '../../../../layouts/AdminLayout';

type Place = { id: number; name: string; city?: string | null; state?: string | null; short_description?: string | null; description?: string | null };
const lines = (value: string) => value.split('\n').map((item) => item.trim()).filter(Boolean);

export default function CreateItinerary({ title, tour, places, nextDay }: { title: string; tour: { id: number; title: string }; places: Place[]; nextDay: number }) {
    const { data, setData, post, processing, errors, transform } = useForm({
        time: '09:00', place_id: '', title: '', details: '', activities_text: '', accommodation: '', meals_included: [] as string[], distance_km: '',
    });
    const selectedPlace = places.find((place) => String(place.id) === data.place_id);
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        transform((values) => ({ ...values, activities: lines(values.activities_text) }));
        post(`/admin/tours/${tour.id}/itineraries`);
    };
    const error = (key: string) => (errors as Record<string, string>)[key];

    return <AdminLayout title={title}>
        <Head title={`Add Day ${nextDay} · ${tour.title}`} />
        <form onSubmit={submit}><Stack gap="lg" maw={920} mx="auto">
            <Group justify="space-between" align="flex-end"><div><Text size="xs" fw={800} c="blue" tt="uppercase">Tour itinerary · Step 2</Text><Text size="xl" fw={900}>Add Day {nextDay}</Text><Text c="dimmed">{tour.title} will become {nextDay} day{nextDay === 1 ? '' : 's'} / {Math.max(0, nextDay - 1)} night{nextDay === 2 ? '' : 's'} after saving.</Text></div><Button component={Link} href={`/admin/tours/${tour.id}/itineraries`} variant="default" leftSection={<ArrowLeft size={16} />}>Itinerary</Button></Group>
            {Object.keys(errors).length > 0 && <Alert color="red" icon={<CircleAlert size={18} />} title="Complete this day">Review the highlighted itinerary fields.</Alert>}
            <Paper p="xl" radius="lg" withBorder><Stack gap="lg">
                <Group><ThemeIcon size="xl" radius="xl"><CalendarPlus size={20} /></ThemeIcon><div><Text fw={850}>Day {nextDay} destination and plan</Text><Text size="sm" c="dimmed">Each saved itinerary record represents one complete tour day.</Text></div></Group>
                <SimpleGrid cols={{ base: 1, md: 2 }}><Select required searchable label="Point of interest" description="Choose an admin-managed place and its approved media." data={places.map((place) => ({ value: String(place.id), label: [place.name, place.city, place.state].filter(Boolean).join(' · ') }))} value={data.place_id} onChange={(value) => setData('place_id', value ?? '')} error={errors.place_id} /><TextInput type="time" label="Day start time" value={data.time} onChange={(e) => setData('time', e.currentTarget.value)} error={errors.time} /></SimpleGrid>
                {selectedPlace && <Alert variant="light" title={selectedPlace.name}>{selectedPlace.short_description ?? selectedPlace.description ?? 'No place summary has been added yet.'}</Alert>}
                <TextInput label="Day title" placeholder={selectedPlace ? `Explore ${selectedPlace.name}` : 'Arrival and local exploration'} value={data.title} onChange={(e) => setData('title', e.currentTarget.value)} error={errors.title} />
                <Textarea required minRows={5} label="Detailed day plan" placeholder="Arrival, transfers, visits, breaks, and expected end time…" value={data.details} onChange={(e) => setData('details', e.currentTarget.value)} error={errors.details} />
                <SimpleGrid cols={{ base: 1, md: 3 }}><Textarea minRows={3} label="Activities" description="One activity per line" value={data.activities_text} onChange={(e) => setData('activities_text', e.currentTarget.value)} error={error('activities')} /><TextInput label="Accommodation" placeholder="Hotel or overnight location" value={data.accommodation} onChange={(e) => setData('accommodation', e.currentTarget.value)} error={errors.accommodation} /><TextInput label="Travel distance" placeholder="85 km" value={data.distance_km} onChange={(e) => setData('distance_km', e.currentTarget.value)} error={errors.distance_km} /></SimpleGrid>
                <Checkbox.Group label="Meals included" value={data.meals_included} onChange={(value) => setData('meals_included', value)}><Group mt="xs"><Checkbox value="breakfast" label="Breakfast" /><Checkbox value="lunch" label="Lunch" /><Checkbox value="dinner" label="Dinner" /></Group></Checkbox.Group>
                <Group justify="space-between" mt="md"><Text size="sm" c="dimmed">After saving, add Day {nextDay + 1} from the itinerary page or continue to departures.</Text><Button type="submit" loading={processing} leftSection={<Save size={17} />}>Save Day {nextDay}</Button></Group>
            </Stack></Paper>
        </Stack></form>
    </AdminLayout>;
}
