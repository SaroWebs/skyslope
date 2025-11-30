<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CarCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'vehicle_type',
        'seats',
        'has_ac',
        'has_driver',
        'base_price_per_day',
        'price_per_km',
        'features',
        'images',
        'fuel_type',
        'year',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'has_ac' => 'boolean',
        'has_driver' => 'boolean',
        'is_active' => 'boolean',
        'base_price_per_day' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'features' => 'array',
        'images' => 'array',
        'year' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

         static::creating(function ($category) {
             if (empty($category->slug)) {
                 $category->slug = Str::slug($category->name);
             }
         });

         static::updating(function ($category) {
             if (empty($category->slug)) {
                 $category->slug = Str::slug($category->name);
             }
         });
    }

    /**
     * Get the car rentals for this category.
     */
    public function carRentals(): HasMany
    {
        return $this->hasMany(CarRental::class);
    }

    /**
     * Get active categories only.
     */
    public static function active()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get categories by vehicle type.
     */
    public static function byType(string $type)
    {
        return static::where('vehicle_type', $type)->active();
    }

    /**
     * Check if category is available.
     */
    public function isAvailable(): bool
    {
        return $this->is_active;
    }

    /**
     * Calculate total price for given days and distance.
     */
    public function calculatePrice(int $days, float $distanceKm = 0): array
    {
        $basePrice = $this->base_price_per_day * $days;
        $distancePrice = $this->price_per_km * $distanceKm;
        $subtotal = $basePrice + $distancePrice;

        return [
            'base_price' => $basePrice,
            'distance_price' => $distancePrice,
            'subtotal' => $subtotal,
            'days' => $days,
            'distance_km' => $distanceKm,
        ];
    }

    /**
     * Get formatted features list.
     */
    public function getFormattedFeaturesAttribute(): array
    {
        return $this->features ?? [];
    }

    /**
     * Get primary image URL.
     */
    public function getPrimaryImageAttribute(): ?string
    {
        $images = $this->images ?? [];
        return $images[0] ?? null;
    }
}