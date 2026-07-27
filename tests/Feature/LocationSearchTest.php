<?php

use App\Models\Destination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('returns and caches matching local destinations without calling external providers', function () {
    Cache::flush();
    Http::fake();

    Destination::create([
        'name' => 'Jaipur Airport',
        'slug' => 'jaipur-airport-test',
        'state' => 'Rajasthan',
        'latitude' => 26.8242,
        'longitude' => 75.8122,
        'is_active' => true,
    ]);

    $this->getJson('/api/customer-app/public/locations/search?query=Jaipur%20Airport')
        ->assertOk()
        ->assertJsonPath('results.0.name', 'Jaipur Airport');

    Destination::query()->delete();

    $this->getJson('/api/customer-app/public/locations/search?query=Jaipur%20Airport')
        ->assertOk()
        ->assertJsonPath('results.0.name', 'Jaipur Airport');

    Http::assertNothingSent();
});
