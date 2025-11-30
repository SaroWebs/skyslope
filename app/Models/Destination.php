<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Destination extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'state',
        'region',
        'type',
        'latitude',
        'longitude',
        'popular_routes',
        'distance_from_guwahati',
        'estimated_travel_time',
        'best_time_to_visit',
        'attractions',
        'images',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance_from_guwahati' => 'decimal:2',
        'estimated_travel_time' => 'integer',
        'is_active' => 'boolean',
        'popular_routes' => 'array',
        'best_time_to_visit' => 'array',
        'attractions' => 'array',
        'images' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

         static::creating(function ($destination) {
             if (empty($destination->slug)) {
                 $destination->slug = Str::slug($destination->name);
             }
         });

         static::updating(function ($destination) {
             if (empty($destination->slug)) {
                 $destination->slug = Str::slug($destination->name);
             }
         });
    }

    /**
     * Get active destinations only.
     */
    public static function active()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get destinations by state.
     */
    public static function byState(string $state)
    {
        return static::where('state', $state)->active();
    }

    /**
     * Get destinations by type.
     */
    public static function byType(string $type)
    {
        return static::where('type', $type)->active();
    }

    /**
     * Get destinations by region.
     */
    public static function byRegion(string $region = 'northeast_india')
    {
        return static::where('region', $region)->active();
    }

    /**
     * Get popular destinations.
     */
    public static function popular()
    {
        return static::where('is_active', true)
                    ->orderBy('sort_order')
                    ->limit(10);
    }

    /**
     * Get formatted attractions list.
     */
    public function getFormattedAttractionsAttribute(): array
    {
        return $this->attractions ?? [];
    }

    /**
     * Get primary image URL.
     */
    public function getPrimaryImageAttribute(): ?string
    {
        $images = $this->images ?? [];
        return $images[0] ?? null;
    }

    /**
     * Get formatted best time to visit.
     */
    public function getFormattedBestTimeAttribute(): array
    {
        return $this->best_time_to_visit ?? [];
    }

    /**
     * Calculate distance and time from another destination.
     */
    public function calculateDistanceFrom(Destination $from): array
    {
        // Simplified distance calculation - in real app, use proper geospatial queries
        $distance = abs($this->distance_from_guwahati - $from->distance_from_guwahati);

        return [
            'distance_km' => round($distance, 2),
            'estimated_time_hours' => ceil($distance / 50), // Assuming 50km/h average
        ];
    }
}