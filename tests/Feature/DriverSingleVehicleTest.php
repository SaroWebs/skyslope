<?php

use App\Models\CarCategory;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverAvailability;
use App\Models\RideBooking;
use App\Models\Vehicle;
use Illuminate\Database\QueryException;
use Laravel\Sanctum\Sanctum;

function singleVehicleCategory(): CarCategory
{
    return CarCategory::create([
        'name' => 'Driver Sedan',
        'slug' => 'driver-sedan-'.uniqid(),
        'vehicle_type' => 'sedan',
        'seats' => 4,
        'base_price_per_day' => 1800,
        'is_active' => true,
    ]);
}

function singleVehicleDriver(): Driver
{
    return Driver::create([
        'name' => 'One Car Driver',
        'phone' => '88'.random_int(10000000, 99999999),
        'status' => 'active',
        'is_active' => true,
        'is_approved' => true,
        'can_short_ride' => true,
    ]);
}

function vehicleData(CarCategory $category, array $overrides = []): array
{
    return array_merge([
        'car_category_id' => $category->id,
        'registration_number' => 'KA'.random_int(10000000, 99999999),
        'make' => 'Toyota',
        'model' => 'Etios',
        'year' => 2024,
        'color' => 'White',
        'fuel_type' => 'petrol',
        'seats' => 4,
        'is_ac' => true,
    ], $overrides);
}

it('enforces one vehicle row per driver at database level', function () {
    $driver = singleVehicleDriver();
    $category = singleVehicleCategory();

    Vehicle::create(vehicleData($category, [
        'driver_id' => $driver->id,
        'is_active' => true,
        'approval_status' => 'approved',
    ]));

    expect(fn () => Vehicle::create(vehicleData($category, [
        'driver_id' => $driver->id,
        'is_active' => true,
        'approval_status' => 'approved',
    ])))->toThrow(QueryException::class);
});

it('uses a single upsert endpoint and sends every driver edit for approval', function () {
    $driver = singleVehicleDriver();
    $category = singleVehicleCategory();
    Sanctum::actingAs($driver);

    $this->putJson('/api/driver-app/vehicle', vehicleData($category, [
        'registration_number' => 'ka 01 ab 1234',
    ]))->assertOk()
        ->assertJsonPath('vehicle.registration_number', 'KA01AB1234')
        ->assertJsonPath('vehicle.approval_status', 'pending')
        ->assertJsonPath('vehicle_readiness.can_go_online', false);

    $vehicleId = Vehicle::where('driver_id', $driver->id)->value('id');

    $this->putJson('/api/driver-app/vehicle', vehicleData($category, [
        'registration_number' => 'KA01AB1234',
        'color' => 'Blue',
    ]))->assertOk()
        ->assertJsonPath('vehicle.id', $vehicleId)
        ->assertJsonPath('vehicle.color', 'Blue');

    expect(Vehicle::where('driver_id', $driver->id)->count())->toBe(1);
});

it('blocks online mode until the one car is approved and service ready', function () {
    $driver = singleVehicleDriver();
    $category = singleVehicleCategory();
    $vehicle = Vehicle::create(vehicleData($category, [
        'driver_id' => $driver->id,
        'is_active' => false,
        'approval_status' => 'pending',
    ]));
    Sanctum::actingAs($driver);

    $payload = ['is_online' => true, 'is_available' => true];
    $this->putJson('/api/driver-app/availability', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('vehicle_readiness.status', 'pending');

    $vehicle->update(['is_active' => true, 'approval_status' => 'approved']);

    $this->putJson('/api/driver-app/availability', $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'online');
});

it('assigns the drivers approved car when accepting a compatible ride', function () {
    $driver = singleVehicleDriver();
    $category = singleVehicleCategory();
    $vehicle = Vehicle::create(vehicleData($category, [
        'driver_id' => $driver->id,
        'is_active' => true,
        'approval_status' => 'approved',
    ]));
    DriverAvailability::create([
        'driver_id' => $driver->id,
        'is_available' => true,
        'status' => 'online',
        'current_lat' => 12.9716,
        'current_lng' => 77.5946,
    ]);
    $customer = Customer::create(['name' => 'One Car Rider', 'phone' => '9000000991']);
    $ride = RideBooking::create([
        'customer_id' => $customer->id,
        'car_category_id' => $category->id,
        'service_type' => 'point_to_point',
        'customer_name' => $customer->name,
        'customer_phone' => $customer->phone,
        'pickup_location' => 'MG Road',
        'pickup_lat' => 12.9716,
        'pickup_lng' => 77.5946,
        'dropoff_location' => 'Indiranagar',
        'dropoff_lat' => 12.9784,
        'dropoff_lng' => 77.6408,
        'scheduled_at' => now(),
        'estimated_distance_km' => 7,
        'total_fare' => 350,
        'status' => 'confirmed',
        'payment_status' => 'pending',
        'payment_method' => 'cash',
    ]);
    Sanctum::actingAs($driver);

    $this->postJson("/api/driver-app/rides/{$ride->id}/accept")
        ->assertOk();

    $this->assertDatabaseHas('ride_bookings', [
        'id' => $ride->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'status' => 'driver_assigned',
    ]);
});
