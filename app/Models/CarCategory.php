<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarCategory extends Model
{
    protected $table = 'car_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'vehicle_type',
        'seats',
        'has_ac',
        'has_driver',
        // Ride (Ola/Uber) fare fields
        'base_fare',
        'price_per_km',
        'price_per_minute',
        'waiting_charge_per_min',
        'min_fare',
        // Car Rental daily fare fields
        'base_price_per_day',
        'extra_km_charge',
        // Metadata
        'fuel_type',
        'year',
        'features',
        'images',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'has_ac'                 => 'boolean',
        'has_driver'             => 'boolean',
        'is_active'              => 'boolean',
        'features'               => 'array',
        'images'                 => 'array',
        'base_fare'              => 'decimal:2',
        'price_per_km'           => 'decimal:2',
        'price_per_minute'       => 'decimal:2',
        'waiting_charge_per_min' => 'decimal:2',
        'min_fare'               => 'decimal:2',
        'base_price_per_day'     => 'decimal:2',
        'extra_km_charge'        => 'decimal:2',
        'seats'                  => 'integer',
        'sort_order'             => 'integer',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'car_category_id');
    }

    public function carRentals(): HasMany
    {
        return $this->hasMany(CarRental::class, 'car_category_id');
    }

    public function rideBookings(): HasMany
    {
        return $this->hasMany(RideBooking::class, 'car_category_id');
    }

    /**
     * Estimate ride fare given distance (km) and duration (minutes).
     */
    public function estimateRideFare(float $distanceKm, float $durationMin, float $surge = 1.0): float
    {
        $fare = $this->base_fare
            + ($distanceKm * $this->price_per_km)
            + ($durationMin * $this->price_per_minute);

        $fare = $fare * $surge;

        return max((float) $this->min_fare, round($fare, 2));
    }

    /**
     * Estimate rental price for N days + extra km.
     */
    public function estimateRentalPrice(int $days, float $extraKm = 0): float
    {
        return ($this->base_price_per_day * $days)
            + ($extraKm * $this->extra_km_charge);
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}