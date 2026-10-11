<?php

use App\Services\DocumentVerification\SimulatedProvider;

return [
    // Replace an adapter and its type mapping when a real provider is integrated.
    'providers' => [
        'simulated_transport' => ['label' => 'Transport registry (simulation)', 'adapter' => SimulatedProvider::class, 'simulated' => true],
        'simulated_identity' => ['label' => 'Identity provider (simulation)', 'adapter' => SimulatedProvider::class, 'simulated' => true],
        'simulated_background' => ['label' => 'Background checks (simulation)', 'adapter' => SimulatedProvider::class, 'simulated' => true],
    ],
    'types' => [
        'driving_license' => ['simulated_transport'],
        'government_id' => ['simulated_identity'],
        'vehicle_rc' => ['simulated_transport'],
        'police_verification' => ['simulated_background'],
        'tax_id' => ['simulated_identity'],
    ],
];
