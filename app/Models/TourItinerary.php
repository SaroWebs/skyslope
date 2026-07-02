<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourItinerary extends Model
{
    protected $table = 'tour_itineraries';

    protected $fillable = [
        'tour_id',
        'place_id',
        'day_number',
        'day_index',
        'time',
        'title',
        'description',
        'details',
        'activities',
        'accommodation',
        'meals_included',
        'distance_km',
    ];

    protected $casts = [
        'activities'     => 'array',
        'meals_included' => 'array',
        'day_number'     => 'integer',
        'day_index'      => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id');
    }

    public function getDayIndexAttribute($value): int
    {
        return (int) ($value ?? $this->day_number);
    }

    public function getDetailsAttribute($value): ?string
    {
        return $value ?? $this->description;
    }

    public function hasMeal(string $meal): bool
    {
        return in_array($meal, $this->meals_included ?? []);
    }

    public function getDayLabelAttribute(): string
    {
        return 'Day ' . ($this->day_index ?? $this->day_number);
    }
}
