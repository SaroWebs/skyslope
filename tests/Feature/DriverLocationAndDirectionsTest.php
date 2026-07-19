<?php

use App\Models\Driver;
use App\Models\DriverAvailability;
use App\Services\DriverDispatchService;
use App\Services\RideEstimateService;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

it('stores the driver current location used by dispatch eligibility', function () {
    $driver = Driver::create([
        'name' => 'Location Ready Driver',
        'phone' => '8000000099',
        'status' => 'active',
        'is_active' => true,
        'is_approved' => true,
    ]);
    Sanctum::actingAs($driver);

    $this->postJson('/api/driver-app/tracking/location', [
        'latitude' => 26.1445,
        'longitude' => 91.7362,
        'accuracy' => 8.5,
        'is_available' => true,
    ])->assertOk()
        ->assertJsonPath('data.status', 'online')
        ->assertJsonPath('data.is_available', true);

    $this->assertDatabaseHas('driver_availabilities', [
        'driver_id' => $driver->id,
        'current_lat' => 26.1445,
        'current_lng' => 91.7362,
        'status' => 'online',
        'is_available' => true,
    ]);
});

it('shows customers the same pickup radius used by driver dispatch', function () {
    $driver = Driver::create([
        'name' => 'Nearby Online Driver',
        'phone' => '8000000100',
        'status' => 'active',
        'is_active' => true,
        'is_approved' => true,
    ]);

    DriverAvailability::create([
        'driver_id' => $driver->id,
        'status' => 'online',
        'is_available' => true,
        // Roughly 20 km north of the pickup: outside the old 5 km estimate
        // radius, but inside the dispatch radius.
        'current_lat' => 26.3242,
        'current_lng' => 91.7362,
    ]);

    expect(DriverDispatchService::DEFAULT_PICKUP_RADIUS_KM)->toBe(30.0)
        ->and(app(RideEstimateService::class)->nearbyDriverCount(26.1445, 91.7362))->toBe(1);
});

it('returns road route coordinates in the shape expected by react native maps', function () {
    config()->set('services.maps.google_api_key', 'test-routes-key');
    Http::fake([
        'routes.googleapis.com/*' => Http::response([
            'routes' => [[
                'distanceMeters' => 5200,
                'duration' => '840s',
                'polyline' => [
                    'geoJsonLinestring' => [
                        'coordinates' => [
                            [91.7362, 26.1445],
                            [91.7500, 26.1500],
                            [91.7800, 26.1700],
                        ],
                    ],
                ],
            ]],
        ], 200),
    ]);

    $this->postJson('/api/customer-app/public/directions', [
        'origin_lat' => 26.1445,
        'origin_lng' => 91.7362,
        'destination_lat' => 26.1700,
        'destination_lng' => 91.7800,
    ])->assertOk()
        ->assertJsonPath('provider', 'google_routes')
        ->assertJsonPath('distance_meters', 5200)
        ->assertJsonPath('duration_seconds', 840)
        ->assertJsonPath('coordinates.1.latitude', 26.15)
        ->assertJsonPath('coordinates.1.longitude', 91.75);

    Http::assertSent(fn ($request) => $request->url() === 'https://routes.googleapis.com/directions/v2:computeRoutes'
        && $request->hasHeader('X-Goog-Api-Key', 'test-routes-key')
        && $request['polylineEncoding'] === 'GEO_JSON_LINESTRING'
    );
});
