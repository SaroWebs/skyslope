<?php

namespace App\Services;

class BookingStatusService
{
    public const RIDE = 'ride';
    public const TOUR = 'tour';
    public const RENTAL = 'rental';

    public function normalize(string $serviceType, string $status): ?string
    {
        return match ($serviceType) {
            self::RIDE => match ($status) {
                'pending' => 'pending',
                'confirmed' => 'confirmed',
                'driver_assigned' => 'driver_assigned',
                'driver_arriving', 'on_the_way' => 'driver_arriving',
                'pickup', 'arrived' => 'pickup',
                'in_transit', 'started' => 'in_transit',
                'completed' => 'completed',
                'cancelled', 'canceled' => 'cancelled',
                default => null,
            },
            self::TOUR => match ($status) {
                'pending' => 'pending',
                'confirmed' => 'confirmed',
                'in_progress', 'started' => 'in_progress',
                'completed' => 'completed',
                'cancelled', 'canceled' => 'cancelled',
                default => null,
            },
            self::RENTAL => match ($status) {
                'pending' => 'pending',
                'confirmed' => 'confirmed',
                'driver_assigned' => 'driver_assigned',
                'in_progress', 'started' => 'in_progress',
                'completed' => 'completed',
                'cancelled', 'canceled' => 'cancelled',
                default => null,
            },
            default => null,
        };
    }

    public function allowedTransitions(string $serviceType, string $currentStatus, string $actor = 'admin'): array
    {
        $currentStatus = $this->normalize($serviceType, $currentStatus) ?? $currentStatus;

        $allowed = match ($serviceType) {
            self::RIDE => [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['driver_assigned', 'cancelled'],
                'driver_assigned' => ['driver_arriving', 'cancelled'],
                'driver_arriving' => ['pickup', 'cancelled'],
                'pickup' => ['in_transit', 'cancelled'],
                'in_transit' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
            ],
            self::TOUR => [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['in_progress', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
            ],
            self::RENTAL => [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['driver_assigned', 'cancelled'],
                'driver_assigned' => ['in_progress', 'completed', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
                'completed' => [],
                'cancelled' => [],
            ],
            default => [],
        };

        $transitions = $allowed[$currentStatus] ?? [];

        if ($actor === 'customer') {
            return in_array('cancelled', $transitions, true) ? ['cancelled'] : [];
        }

        return $transitions;
    }

    public function canTransition(
        string $serviceType,
        string $currentStatus,
        string $requestedStatus,
        string $actor = 'admin'
    ): bool {
        $requestedStatus = $this->normalize($serviceType, $requestedStatus);

        return $requestedStatus !== null
            && in_array($requestedStatus, $this->allowedTransitions($serviceType, $currentStatus, $actor), true);
    }
}
