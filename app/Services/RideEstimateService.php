<?php

namespace App\Services;

use App\Models\DriverAvailability;

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
    public function nearbyDriverCount(float $lat, float $lng, float $radiusKm = 5): int
    {
        return DriverAvailability::active()
            ->nearLocation($lat, $lng, $radiusKm)
            ->count();
    }

    /**
     * Compute the full ride estimate.
     *
     * @param  float       $pickupLat
     * @param  float       $pickupLng
     * @param  float|null  $dropoffLat   null for hourly/open-ended rides
     * @param  float|null  $dropoffLng
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
        ?float $dropoffLng = null
    ): array {
        $distance = ($dropoffLat !== null && $dropoffLng !== null)
            ? $this->distanceKm($pickupLat, $pickupLng, (float) $dropoffLat, (float) $dropoffLng)
            : 0.0;

        $nearbyDrivers   = $this->nearbyDriverCount($pickupLat, $pickupLng);
        $surgeMultiplier = $nearbyDrivers < self::SURGE_DRIVER_THRESHOLD
            ? self::SURGE_MULTIPLIER
            : 1.0;

        $distanceFare = $distance * self::PER_KM_RATE;
        $subtotal     = (self::BASE_FARE + $distanceFare) * $surgeMultiplier;

        return [
            'distance_km'        => round($distance, 2),
            'estimated_duration' => $distance > 0
                ? (int) ceil($distance / self::AVERAGE_SPEED_KMH * 60)
                : 30,
            'nearby_drivers'     => $nearbyDrivers,
            'pricing'            => [
                'base_fare'        => (float) self::BASE_FARE,
                'distance_fare'    => round($distanceFare, 2),
                'surge_multiplier' => $surgeMultiplier,
                'subtotal'         => round($subtotal, 2),
            ],
        ];
    }
}
