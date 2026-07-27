import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Alert, Button, Checkbox, Group, Paper, Select, SimpleGrid, Stack, Text, Textarea, TextInput, ThemeIcon } from '@mantine/core';
import { ArrowLeft, CalendarPlus, CircleAlert, Save } from 'lucide-react';
import AdminLayout from '../../../../layouts/AdminLayout';

type Place = { id: number; name: string; city?: string | null; state?: string | null; short_description?: string | null; description?: string | null };
type DayOption = { day_number: number; stops_count: number };
const lines = (value: string) => value.split('\n').map((item) => item.trim()).filter(Boolean);

export default function CreateItinerary({ title, tour, places, nextDay, maxDay, dayOptions }: { title: string; tour: { id: number; title: string }; places: Place[]; nextDay: number; maxDay: number; dayOptions: DayOption[] }) {
    const { data, setData, post, processing, errors, transform } = useForm({
        day_number: String(nextDay), time: '09:00', place_id: '', title: '', details: '', activities_text: '', accommodation: '', meals_included: [] as string[], distance_km: '',
    });
    const selectedDay = Number(data.day_number);
    const existingDay = dayOptions.find((day) => day.day_number === selectedDay);
    const visitNumber = Number(existingDay?.stops_count ?? 0) + 1;
    const nextNewDay = maxDay + 1 || 1;
    const availableDays = [
        ...dayOptions.map((day) => ({ value: String(day.day_number), label: `Day ${day.day_number} · ${day.stops_count} visit${Number(day.stops_count) === 1 ? '' : 's'}` })),
        { value: String(nextNewDay), label: `Day ${nextNewDay} · New day` },
    ];
    const selectedPlace = places.find((place) => String(place.id) === data.place_id);
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        transform((values) => ({ ...values, day_number: Number(values.day_number), activities: lines(values.activities_text) }));
        post(`/admin/tours/${tour.id}/itineraries`);
    };
    const error = (key: string) => (errors as Record<string, string>)[key];

    return <AdminLayout title={title}>
        <Head title={`Add visit to Day ${selectedDay} · ${tour.title}`} />
        <form onSubmit={submit}><Stack gap="lg" maw={920} mx="auto">
            <Group justify="space-between" align="flex-end"><div><Text size="xs" fw={800} c="blue" tt="uppercase">Tour itinerary · Place visit</Text><Text size="xl" fw={900}>Add visit {visitNumber} to Day {selectedDay}</Text><Text c="dimmed">Add another place to an existing day, or start the next sequential day.</Text></div><Button component={Link} href={`/admin/tours/${tour.id}/itineraries`} variant="default" leftSection={<ArrowLeft size={16} />}>Itinerary</Button></Group>
            {Object.keys(errors).length > 0 && <Alert color="red" icon={<CircleAlert size={18} />} title="Complete this visit">Review the highlighted itinerary fields.</Alert>}
            <Paper p="xl" radius="lg" withBorder><Stack gap="lg">
                <Group><ThemeIcon size="xl" radius="xl"><CalendarPlus size={20} /></ThemeIcon><div><Text fw={850}>Day {selectedDay} · Visit {visitNumber}</Text><Text size="sm" c="dimmed">Each record is one ordered place visit. A day may contain several visits.</Text></div></Group>
                <SimpleGrid cols={{ base: 1, md: 3 }}><Select required label="Visit day" description="Existing day or the next new day." data={availableDays} value={data.day_number} onChange={(value) => setData('day_number', value ?? String(nextDay))} error={errors.day_number} /><Select required searchable label="Point of interest" description="Place and approved media." data={places.map((place) => ({ value: String(place.id), label: [place.name, place.city, place.state].filter(Boolean).join(' · ') }))} value={data.place_id} onChange={(value) => setData('place_id', value ?? '')} error={errors.place_id} /><TextInput type="time" label="Arrival time" value={data.time} onChange={(event) => setData('time', event.currentTarget.value)} error={errors.time} /></SimpleGrid>
                {selectedPlace && <Alert variant="light" title={selectedPlace.name}>{selectedPlace.short_description ?? selectedPlace.description ?? 'No place summary has been added yet.'}</Alert>}
                <TextInput label="Visit title" placeholder={selectedPlace ? `Explore ${selectedPlace.name}` : 'Arrival and local exploration'} value={data.title} onChange={(event) => setData('title', event.currentTarget.value)} error={errors.title} />
                <Textarea required minRows={5} label="Visit plan" placeholder="Arrival, time at the place, transfers, breaks, and departure…" value={data.details} onChange={(event) => setData('details', event.currentTarget.value)} error={errors.details} />
                <SimpleGrid cols={{ base: 1, md: 3 }}><Textarea minRows={3} label="Activities" description="One activity per line" value={data.activities_text} onChange={(event) => setData('activities_text', event.currentTarget.value)} error={error('activities')} /><TextInput label="Accommodation" description="Usually set on the final visit" placeholder="Hotel or overnight location" value={data.accommodation} onChange={(event) => setData('accommodation', event.currentTarget.value)} error={errors.accommodation} /><TextInput label="Distance to this visit" placeholder="85 km" value={data.distance_km} onChange={(event) => setData('distance_km', event.currentTarget.value)} error={errors.distance_km} /></SimpleGrid>
                <Checkbox.Group label="Meals included at this visit" value={data.meals_included} onChange={(value) => setData('meals_included', value)}><Group mt="xs"><Checkbox value="breakfast" label="Breakfast" /><Checkbox value="lunch" label="Lunch" /><Checkbox value="dinner" label="Dinner" /></Group></Checkbox.Group>
                <Group justify="space-between" mt="md"><Text size="sm" c="dimmed">After saving, add another place to Day {selectedDay} or start Day {nextNewDay}.</Text><Button type="submit" loading={processing} leftSection={<Save size={17} />}>Save visit</Button></Group>
            </Stack></Paper>
        </Stack></form>
    </AdminLayout>;
}
