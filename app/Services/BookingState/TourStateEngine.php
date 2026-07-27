<?php

namespace App\Services\BookingState;

class TourStateEngine implements BookingStateEngine
{
    public function serviceType(): string
    {
        return 'tour';
    }

    public function normalize(string $status): ?string
    {
        return match ($status) {
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'in_progress', 'started' => 'in_progress',
            'completed' => 'completed',
            'cancelled', 'canceled' => 'cancelled',
            default => null,
        };
    }

    public function states(): array
    {
        return ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    }

    public function allowedTransitions(string $currentStatus, string $actor): array
    {
        $currentStatus = $this->normalize($currentStatus) ?? $currentStatus;

        if ($actor === 'customer') {
            return in_array($currentStatus, ['pending', 'confirmed', 'in_progress'], true) ? ['cancelled'] : [];
        }

        if ($actor === 'driver') {
            return match ($currentStatus) {
                'confirmed' => ['in_progress'],
                'in_progress' => ['completed'],
                default => [],
            };
        }

        return match ($currentStatus) {
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            default => [],
        };
    }
}
