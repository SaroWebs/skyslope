<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    protected $fillable = [
        'name',
        'description',
        'lng',
        'lat',
        'status',
    ];

    /**
      * Get the media for the place.
      */
     public function media(): HasMany
     {
         return $this->hasMany(PlaceMedia::class);
     }

    /**
     * Get the itineraries for the place.
     */
    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class);
    }
}
