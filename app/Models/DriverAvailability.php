<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAvailability extends Model
{
    protected $fillable = [
        'driver_id',
        'is_online',
        'is_available',
        'current_lat',
        'current_lng',
        'last_ping',
        'service_areas',
        'vehicle_type',
        'vehicle_number',
        'rating',
        'completed_rides',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'is_available' => 'boolean',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
        'last_ping' => 'datetime',
        'service_areas' => 'array',
        'rating' => 'decimal:2',
        'completed_rides' => 'integer',
    ];

    /**
     * Get the driver.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Check if driver is online and available.
     */
    public function isActive(): bool
    {
        return $this->is_online && $this->is_available;
    }

    /**
     * Update driver's current location.
     */
    public function updateLocation(float $lat, float $lng): void
    {
        $this->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
            'last_ping' => now(),
        ]);
    }

    /**
     * Calculate distance from a point (Haversine formula).
     */
    public function distanceFrom(float $lat, float $lng): float
    {
        if (!$this->current_lat || !$this->current_lng) {
            return PHP_FLOAT_MAX; // Return max float if no location
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat - $this->current_lat);
        $lngDelta = deg2rad($lng - $this->current_lng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->current_lat)) * cos(deg2rad($lat)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if driver serves a specific area.
     */
    public function servesArea(int $destinationId): bool
    {
        if (!$this->service_areas) {
            return true; // If no specific areas set, assume all areas
        }

        return in_array($destinationId, $this->service_areas);
    }

    /**
     * Scope for online and available drivers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_online', true)
                    ->where('is_available', true)
                    ->where('last_ping', '>', now()->subMinutes(5)); // Active within last 5 minutes
    }

    /**
     * Scope for drivers near a location.
     */
    public function scopeNearLocation($query, float $lat, float $lng, float $radiusKm = 10)
    {
        // This is a simplified approach. For production, consider using spatial queries
        $latMin = $lat - ($radiusKm / 111.32); // Rough conversion
        $latMax = $lat + ($radiusKm / 111.32);
        $lngMin = $lng - ($radiusKm / (111.32 * cos(deg2rad($lat))));
        $lngMax = $lng + ($radiusKm / (111.32 * cos(deg2rad($lat))));

        return $query->whereBetween('current_lat', [$latMin, $latMax])
                    ->whereBetween('current_lng', [$lngMin, $lngMax]);
    }
}
