<?php

namespace App\Models;

use App\Models\TourGuide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TourDriver;

class Tour extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
        'discount',
        'available_from',
        'available_to',
        'image_path',
    ];

    protected $casts = [
        'available_from' => 'date',
        'available_to' => 'date',
    ];

    /**
     * Get the bookings for the tour.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the itineraries for the tour.
     */
    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class);
    }

    /**
     * Get the drivers for the tour.
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(TourDriver::class)->with('user');
    }

    /**
     * Get the guides for the tour.
     */
    public function guides(): HasMany
    {
        return $this->hasMany(TourGuide::class)->with('user');
    }

    /**
     * Get the duration based on itineraries count.
     */
    public function getDurationAttribute()
    {
        return $this->itineraries_count ?? $this->itineraries()->count();
    }
}
