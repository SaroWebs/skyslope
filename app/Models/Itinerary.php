<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Itinerary extends Model
{
    protected $fillable = [
        'tour_id',
        'day_index',
        'time',
        'place_id',
        'details',
    ];

    /**
     * Get the tour that the itinerary belongs to.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Get the place for the itinerary.
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
