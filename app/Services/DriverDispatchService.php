<?php

namespace App\Services;

use App\Models\CarCategory;
use App\Models\Driver;
use App\Models\DriverAvailability;
use App\Models\RideBooking;
use App\Models\RideDispatchAttempt;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class DriverDispatchService
{
    public const DEFAULT_PICKUP_RADIUS_KM = 30.0;

    public function rankedCandidates(
        string $serviceType,
        float $pickupLat,
        float $pickupLng,
        ?string $role = null,
        int $limit = 10,
        ?int $carCategoryId = null,
        bool $preferSharingEnabled = false
    ) {
        return $this->nearbyAvailability($pickupLat, $pickupLng)
            ->filter(function (DriverAvailability $availability) use ($serviceType, $role, $carCategoryId, $pickupLat, $pickupLng) {
                $driver = $availability->driver;

                return $driver
                    && $this->eligibilityFailures(
                        $driver,
                        $serviceType,
                        $role,
                        $carCategoryId,
                        null,
                        $pickupLat,
                        $pickupLng
                    ) === [];
            })
            ->sortByDesc(function (DriverAvailability $availability) use ($serviceType, $role, $preferSharingEnabled) {
                $driver = $availability->driver;
                $rating = (float) ($driver->rating ?? 0);
                $distance = (float) ($availability->distance ?? 999);
                $capabilityFit = $driver->canHandleService($serviceType, $role) ? 25 : 0;
                $workloadPenalty = $this->workloadScore($driver) * 2;
                $acceptanceBonus = $this->acceptanceRate($driver) * 10;
                $sharingPreferenceBonus = $preferSharingEnabled && $availability->sharing_enabled ? 18 : 0;

                return $capabilityFit + ($rating * 10) + $acceptanceBonus + $sharingPreferenceBonus
                    - ($distance * 2) - $workloadPenalty;
            })
            ->take($limit)
            ->values();
    }

    public function createRideAttempts(RideBooking $rideBooking, $candidates, int $ttlSeconds = 90): void
    {
        $expiresAt = now()->addSeconds($ttlSeconds);

        $candidates->values()->each(function (DriverAvailability $availability, int $index) use ($rideBooking, $expiresAt) {
            $driver = $availability->driver;
            if (! $driver) {
                return;
            }

            RideDispatchAttempt::updateOrCreate(
                [
                    'ride_booking_id' => $rideBooking->id,
                    'driver_id' => $driver->id,
                ],
                [
                    'score' => $this->candidateScore($availability, (bool) $rideBooking->sharing_requested),
                    'distance_km' => $availability->distance !== null ? round((float) $availability->distance, 2) : null,
                    'rank' => $index + 1,
                    'status' => 'offered',
                    'offered_at' => now(),
                    'expires_at' => $expiresAt,
                    'responded_at' => null,
                    'decline_reason' => null,
                ]
            );
        });
    }

    public function markAccepted(RideBooking $rideBooking, Driver $driver): void
    {
        RideDispatchAttempt::updateOrCreate(
            [
                'ride_booking_id' => $rideBooking->id,
                'driver_id' => $driver->id,
            ],
            [
                'status' => 'accepted',
                'responded_at' => now(),
                'offered_at' => now(),
            ]
        );

        RideDispatchAttempt::query()
            ->where('ride_booking_id', $rideBooking->id)
            ->where('driver_id', '!=', $driver->id)
            ->where('status', 'offered')
            ->update([
                'status' => 'superseded',
                'responded_at' => now(),
            ]);
    }

    public function markDeclined(RideBooking $rideBooking, Driver $driver, ?string $reason = null): RideDispatchAttempt
    {
        return RideDispatchAttempt::updateOrCreate(
            [
                'ride_booking_id' => $rideBooking->id,
                'driver_id' => $driver->id,
            ],
            [
                'status' => 'declined',
                'responded_at' => now(),
                'decline_reason' => $reason,
                'offered_at' => now(),
            ]
        );
    }

    public function expireOpenAttempts(): int
    {
        return RideDispatchAttempt::query()
            ->where('status', 'offered')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
                'responded_at' => now(),
            ]);
    }

    public function acceptanceRate(Driver $driver): float
    {
        $total = $driver->rideDispatchAttempts()
            ->whereIn('status', ['accepted', 'declined', 'expired'])
            ->count();

        if ($total === 0) {
            return 0.5;
        }

        $accepted = $driver->rideDispatchAttempts()
            ->where('status', 'accepted')
            ->count();

        return $accepted / $total;
    }

    public function rideServiceType(RideBooking $rideBooking): string
    {
        if (in_array($rideBooking->service_type, ['hourly', 'round_trip'], true)) {
            return 'long_ride';
        }

        return (float) $rideBooking->estimated_distance_km < 80
            ? 'short_ride'
            : 'long_ride';
    }

    public function eligibilityFailures(
        Driver $driver,
        string $serviceType,
        ?string $role = null,
        ?int $carCategoryId = null,
        ?int $vehicleId = null,
        ?float $pickupLat = null,
        ?float $pickupLng = null,
        float $radiusKm = self::DEFAULT_PICKUP_RADIUS_KM
    ): array {
        $failures = [];

        if (! $driver->isApproved() || ! $driver->is_active || $driver->status !== 'active') {
            $failures[] = 'Driver is not active and approved.';
        }

        if (! $driver->canHandleService($serviceType, $role)) {
            $failures[] = 'Driver does not have the required service capability.';
        }

        if ($this->hasActiveWorkload($driver)) {
            $failures[] = 'Driver already has an active ride, tour, or rental assignment.';
        }

        if ($vehicleFailure = $this->vehicleEligibilityFailure($driver, $carCategoryId, $vehicleId)) {
            $failures[] = $vehicleFailure;
        }

        if ($pickupLat !== null && $pickupLng !== null) {
            $availability = $driver->driverAvailability;
            if (! $availability || ! $availability->is_available || $availability->status !== 'online') {
                $failures[] = 'Driver is not currently online and available.';
            } elseif ($availability->current_lat === null || $availability->current_lng === null) {
                $failures[] = 'Driver has no current location.';
            } else {
                $distance = $this->distanceKm(
                    $pickupLat,
                    $pickupLng,
                    (float) $availability->current_lat,
                    (float) $availability->current_lng
                );

                if ($distance > $radiusKm) {
                    $failures[] = "Driver is outside the {$radiusKm} km pickup radius.";
                }
            }
        }

        return $failures;
    }

    private function vehicleEligibilityFailure(Driver $driver, ?int $carCategoryId = null, ?int $vehicleId = null): ?string
    {
        if ($vehicleId !== null) {
            $vehicle = Vehicle::find($vehicleId);
            if (! $vehicle || ! $vehicle->is_active) {
                return 'Selected vehicle is not active.';
            }

            if ((int) $vehicle->driver_id !== (int) $driver->id) {
                return 'Selected vehicle is not assigned to this driver.';
            }

            if ($carCategoryId !== null && (int) $vehicle->car_category_id !== (int) $carCategoryId) {
                return 'Selected vehicle does not match the requested car category.';
            }

            return null;
        }

        $vehicle = $driver->vehicle;
        if (! $vehicle || ! $vehicle->isApprovedForService()) {
            return 'Driver does not have an approved, service-ready vehicle.';
        }

        if ($carCategoryId !== null && (int) $vehicle->car_category_id !== (int) $carCategoryId) {
            return 'Driver vehicle does not match the requested car category.';
        }

        return null;
    }

    private function nearbyAvailability(float $pickupLat, float $pickupLng)
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return DriverAvailability::query()
                ->with('driver')
                ->active()
                ->nearLocation($pickupLat, $pickupLng, self::DEFAULT_PICKUP_RADIUS_KM)
                ->get();
        }

        return DriverAvailability::query()
            ->with('driver')
            ->active()
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get()
            ->map(function (DriverAvailability $availability) use ($pickupLat, $pickupLng) {
                $availability->setAttribute('distance', $this->distanceKm(
                    $pickupLat,
                    $pickupLng,
                    (float) $availability->current_lat,
                    (float) $availability->current_lng
                ));

                return $availability;
            })
            ->filter(fn (DriverAvailability $availability) => (float) $availability->distance <= self::DEFAULT_PICKUP_RADIUS_KM);
    }

    public function hasActiveWorkload(Driver $driver): bool
    {
        return RideBooking::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
            ->exists()
            || $driver->carRentals()
                ->whereIn('status', ['driver_assigned', 'in_progress'])
                ->exists()
            || $driver->tourDriverAssignments()
                ->whereIn('status', ['accepted'])
                ->exists();
    }

    public function workloadScore(Driver $driver): int
    {
        return RideBooking::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
            ->count()
            + RideBooking::query()
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('scheduled_at', '>=', now())
                ->count()
            + $driver->carRentals()
                ->whereIn('status', ['driver_assigned', 'in_progress', 'confirmed'])
                ->whereDate('start_date', '>=', today())
                ->count()
            + $driver->tourDriverAssignments()
                ->whereIn('status', ['assigned', 'accepted'])
                ->whereHas('schedule', function ($query) {
                    $query->whereDate('departure_date', '>=', today());
                })
                ->count();
    }

    private function candidateScore(DriverAvailability $availability, bool $preferSharingEnabled = false): float
    {
        $driver = $availability->driver;
        if (! $driver) {
            return 0.0;
        }

        return round(((float) ($driver->rating ?? 0) * 10)
            + ($this->acceptanceRate($driver) * 10)
            + ($preferSharingEnabled && $availability->sharing_enabled ? 18 : 0)
            - ((float) ($availability->distance ?? 999) * 2)
            - ($this->workloadScore($driver) * 2), 2);
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
