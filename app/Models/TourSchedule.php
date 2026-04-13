<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourSchedule extends Model
{
    protected $table = 'tour_schedules';

    protected $fillable = [
        'tour_id',
        'departure_date',
        'return_date',
        'departure_time',
        'departure_point',
        'total_seats',
        'booked_seats',
        'reserved_seats',
        'price_override',
        'child_price_override',
        'status',
        'notes',
    ];

    protected $casts = [
        'departure_date'       => 'date',
        'return_date'          => 'date',
        'price_override'       => 'decimal:2',
        'child_price_override' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class, 'tour_schedule_id');
    }

    public function guideAssignments(): HasMany
    {
        return $this->hasMany(TourGuideAssignment::class, 'tour_schedule_id');
    }

    public function driverAssignments(): HasMany
    {
        return $this->hasMany(TourDriverAssignment::class, 'tour_schedule_id');
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function getAvailableSeats(): int
    {
        return $this->total_seats - $this->booked_seats - $this->reserved_seats;
    }

    public function isAvailable(): bool
    {
        return $this->status === 'open' && $this->getAvailableSeats() > 0;
    }

    public function getEffectivePrice(): float
    {
        return $this->price_override ?? $this->tour->price_per_person;
    }

    public function getEffectiveChildPrice(): float
    {
        return $this->child_price_override ?? $this->tour->child_price;
    }
}
