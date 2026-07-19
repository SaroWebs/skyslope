<?php

use App\Models\Customer;
use App\Models\Place;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\TourSchedule;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates a tour first and derives duration from sequential itinerary days', function () {
    $admin = User::create(['name' => 'Tour Admin', 'email' => 'tour-admin@example.com', 'password' => 'password']);
    $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
    $admin->roles()->attach($role);
    $category = TourCategory::create(['name' => 'Nature', 'slug' => 'nature', 'is_active' => true]);
    $firstPlace = Place::create(['name' => 'Living Root Bridge', 'slug' => 'living-root-bridge', 'is_active' => true]);
    $secondPlace = Place::create(['name' => 'Shillong Peak', 'slug' => 'shillong-peak', 'is_active' => true]);

    $this->actingAs($admin)->post('/admin/tours', [
        'tour_category_id' => $category->id,
        'title' => 'Meghalaya Living Roots',
        'short_description' => 'A guided rainforest and living-root bridge experience.',
        'description' => 'A complete three day tour with local transport and guided destination experiences.',
        'highlights' => ['Living root bridge', 'Local village walk'],
        'inclusions' => ['Transport', 'Breakfast'],
        'exclusions' => ['Flights'],
        'min_group_size' => 2, 'max_group_size' => 6,
        'price_per_person' => 12500, 'child_price' => 8500, 'discount' => 10,
        'start_location' => 'Shillong', 'end_location' => 'Shillong', 'region' => 'Meghalaya', 'difficulty' => 'moderate',
        'available_from' => now()->toDateString(), 'available_to' => now()->addMonths(3)->toDateString(),
        'is_active' => true, 'is_featured' => true,
    ])->assertRedirect();

    $tour = Tour::where('title', 'Meghalaya Living Roots')->firstOrFail();
    expect($tour->slug)->toBe('meghalaya-living-roots')
        ->and((float) $tour->price_per_person)->toBe(12500.0)
        ->and($tour->duration_days)->toBe(0)
        ->and($tour->itineraries)->toHaveCount(0);

    foreach ([$firstPlace, $secondPlace] as $index => $place) {
        $this->actingAs($admin)->post("/admin/tours/{$tour->id}/itineraries", [
            'place_id' => $place->id,
            'time' => '09:00',
            'title' => $place->name,
            'details' => "Plan for day ".($index + 1),
            'activities' => ['Guided visit'],
            'meals_included' => ['breakfast'],
        ])->assertRedirect();
    }

    expect($tour->fresh()->duration_days)->toBe(2)
        ->and($tour->fresh()->duration_nights)->toBe(1)
        ->and($tour->itineraries()->pluck('day_number')->all())->toBe([1, 2]);

    $this->actingAs($admin)->delete("/admin/tours/{$tour->id}/itineraries/{$tour->itineraries()->first()->id}")->assertRedirect();
    expect($tour->fresh()->duration_days)->toBe(1)
        ->and($tour->fresh()->duration_nights)->toBe(0)
        ->and($tour->itineraries()->first()->day_number)->toBe(1);
});

it('rejects booking a departure that belongs to a different tour', function () {
    $customer = Customer::create(['name' => 'Tour Customer', 'phone' => '9000000042']);
    $first = Tour::create([
        'title' => 'First Tour', 'slug' => 'first-tour', 'price_per_person' => 1000, 'child_price' => 500,
        'available_from' => now(), 'available_to' => now()->addMonth(), 'is_active' => true,
    ]);
    $second = Tour::create([
        'title' => 'Second Tour', 'slug' => 'second-tour', 'price_per_person' => 2000, 'child_price' => 1000,
        'available_from' => now(), 'available_to' => now()->addMonth(), 'is_active' => true,
    ]);
    $schedule = TourSchedule::create([
        'tour_id' => $second->id, 'departure_date' => now()->addWeek(), 'return_date' => now()->addDays(8),
        'total_seats' => 5, 'status' => 'open',
    ]);
    Sanctum::actingAs($customer);

    $this->postJson('/api/customer-app/tours/book', [
        'tour_id' => $first->id, 'tour_schedule_id' => $schedule->id,
        'number_of_adults' => 1, 'number_of_children' => 0, 'payment_method' => 'cash',
    ])->assertStatus(422)->assertJsonPath('message', 'The selected departure does not belong to this tour.');

    $this->assertDatabaseCount('tour_bookings', 0);
    expect($schedule->fresh()->reserved_seats)->toBe(0);
});
