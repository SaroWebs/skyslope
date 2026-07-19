<?php

namespace App\Services;

use App\Models\DriverAvailability;
use Illuminate\Support\Facades\DB;

class RideEstimateService
{
    /** Base fare in INR */
    public const BASE_FARE = 50;

    /** Rate per km in INR */
    public const PER_KM_RATE = 15;

    /** Surge multiplier threshold (number of nearby drivers) */
    public const SURGE_DRIVER_THRESHOLD = 3;

    /** Surge multiplier applied when drivers < threshold */
    public const SURGE_MULTIPLIER = 1.2;

    /** Assumed average speed in km/h used for duration estimate */
    public const AVERAGE_SPEED_KMH = 30;

    public const SHORT_RIDE_THRESHOLD_KM = 80;

    /** Shared point-to-point fare is 65% for one seat, then +15% per extra seat. */
    public const SHARED_BASE_MULTIPLIER = 0.65;

    public const SHARED_EXTRA_SEAT_MULTIPLIER = 0.15;

    public const MAX_SHARED_SEATS_PER_BOOKING = 3;

    public const VEHICLE_MULTIPLIERS = ['mini' => 0.85, 'comfort' => 1.0, 'xl' => 1.45];

    /**
     * Calculate the Haversine great-circle distance in kilometres between two coordinates.
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Count active drivers within $radiusKm of the given pickup point.
     */
    public function nearbyDriverCount(float $lat, float $lng, float $radiusKm = DriverDispatchService::DEFAULT_PICKUP_RADIUS_KM): int
    {
        return $this->nearbyAvailabilityCount($lat, $lng, $radiusKm);
    }

    public function nearbySharingDriverCount(float $lat, float $lng, float $radiusKm = DriverDispatchService::DEFAULT_PICKUP_RADIUS_KM): int
    {
        return $this->nearbyAvailabilityCount($lat, $lng, $radiusKm, true);
    }

    private function nearbyAvailabilityCount(float $lat, float $lng, float $radiusKm = 5, bool $sharingOnly = false): int
    {
        $query = DriverAvailability::active()
            ->when($sharingOnly, fn ($builder) => $builder->sharing());

        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query
                ->whereNotNull('current_lat')
                ->whereNotNull('current_lng')
                ->get()
                ->filter(fn (DriverAvailability $availability) => $this->distanceKm(
                    $lat,
                    $lng,
                    (float) $availability->current_lat,
                    (float) $availability->current_lng
                ) <= $radiusKm)
                ->count();
        }

        return $query
            ->nearLocation($lat, $lng, $radiusKm)
            ->count();
    }

    /**
     * Compute the full ride estimate.
     *
     * @param  float|null  $dropoffLat  null for hourly/open-ended rides
     * @return array{
     *   distance_km: float,
     *   estimated_duration: int,
     *   nearby_drivers: int,
     *   pricing: array{
     *     base_fare: float,
     *     distance_fare: float,
     *     surge_multiplier: float,
     *     subtotal: float,
     *   }
     * }
     */
    public function estimate(
        float $pickupLat,
        float $pickupLng,
        ?float $dropoffLat = null,
        ?float $dropoffLng = null,
        string $serviceType = 'point_to_point',
        bool $sharingRequested = false,
        int $reservedSeats = 1,
        string $vehicleClass = 'comfort'
    ): array {
        $serviceType = $this->normalizeServiceType($serviceType);
        $sharingEligible = $serviceType === 'point_to_point';
        $sharingRequested = $sharingEligible && $sharingRequested;
        $reservedSeats = max(1, min(self::MAX_SHARED_SEATS_PER_BOOKING, $reservedSeats));
        $distance = ($dropoffLat !== null && $dropoffLng !== null)
            ? $this->distanceKm($pickupLat, $pickupLng, (float) $dropoffLat, (float) $dropoffLng)
            : 0.0;

        $nearbyDrivers = $this->nearbyDriverCount($pickupLat, $pickupLng);
        $nearbySharingDrivers = $sharingEligible
            ? $this->nearbySharingDriverCount($pickupLat, $pickupLng)
            : 0;
        $surgeMultiplier = $nearbyDrivers < self::SURGE_DRIVER_THRESHOLD
            ? self::SURGE_MULTIPLIER
            : 1.0;

        $vehicleClass = array_key_exists($vehicleClass, self::VEHICLE_MULTIPLIERS) ? $vehicleClass : 'comfort';
        $vehicleMultiplier = self::VEHICLE_MULTIPLIERS[$vehicleClass];
        $distanceFare = $distance * self::PER_KM_RATE;
        $privateSubtotal = round((self::BASE_FARE + $distanceFare) * $surgeMultiplier * $vehicleMultiplier, 2);
        $sharedMultiplier = min(
            0.95,
            self::SHARED_BASE_MULTIPLIER + (($reservedSeats - 1) * self::SHARED_EXTRA_SEAT_MULTIPLIER)
        );
        $subtotal = $sharingRequested
            ? round($privateSubtotal * $sharedMultiplier, 2)
            : $privateSubtotal;
        $sharingSavings = $sharingRequested ? round($privateSubtotal - $subtotal, 2) : 0.0;
        $sharingDiscountPercent = $sharingRequested ? round((1 - $sharedMultiplier) * 100, 2) : 0.0;

        return [
            'distance_km' => round($distance, 2),
            'estimated_distance_km' => round($distance, 2),
            'estimated_duration' => $distance > 0
                ? (int) ceil($distance / self::AVERAGE_SPEED_KMH * 60)
                : 30,
            'service_type' => $serviceType,
            'ride_classification' => $this->classify($serviceType, $distance),
            'vehicle_class' => $vehicleClass,
            'nearby_drivers' => $nearbyDrivers,
            'sharing' => [
                'eligible' => $sharingEligible,
                'requested' => $sharingRequested,
                'reserved_seats' => $reservedSeats,
                'nearby_sharing_drivers' => $nearbySharingDrivers,
                'can_customer_enable' => $sharingEligible,
                'enabled_by' => $sharingRequested
                    ? ($nearbySharingDrivers > 0 ? 'driver' : 'customer')
                    : null,
            ],
            'pricing' => [
                'base_fare' => (float) self::BASE_FARE,
                'distance_fare' => round($distanceFare, 2),
                'surge_multiplier' => $surgeMultiplier,
                'vehicle_multiplier' => $vehicleMultiplier,
                'private_subtotal' => $privateSubtotal,
                'sharing_discount_percent' => $sharingDiscountPercent,
                'sharing_savings' => $sharingSavings,
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ],
        ];
    }

    public function normalizeServiceType(string $serviceType): string
    {
        return $serviceType === 'hourly_rental' ? 'hourly' : $serviceType;
    }

    public function classify(string $serviceType, float $distanceKm): string
    {
        $serviceType = $this->normalizeServiceType($serviceType);

        if (in_array($serviceType, ['hourly', 'round_trip'], true)) {
            return 'long_ride';
        }

        return $distanceKm < self::SHORT_RIDE_THRESHOLD_KM ? 'short_ride' : 'long_ride';
    }
}
