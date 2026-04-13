<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourItinerary extends Model
{
    protected $table = 'tour_itineraries';

    protected $fillable = [
        'tour_id',
        'day_number',
        'title',
        'description',
        'activities',
        'accommodation',
        'meals_included',
        'distance_km',
    ];

    protected $casts = [
        'activities'     => 'array',
        'meals_included' => 'array',
        'day_number'     => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function hasMeal(string $meal): bool
    {
        return in_array($meal, $this->meals_included ?? []);
    }

    public function getDayLabelAttribute(): string
    {
        return 'Day ' . $this->day_number;
    }
}
