<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Driver;
use App\Models\Guide;

class Tour extends Model
{
    protected $fillable = [
        'tour_category_id',
        'title',
        'slug',
        'description',
        'short_description',
        'highlights',
        'inclusions',
        'exclusions',
        'faqs',
        'duration_days',
        'duration_nights',
        'min_group_size',
        'max_group_size',
        'price_per_person',
        'child_price',
        'discount',
        'start_location',
        'end_location',
        'region',
        'difficulty',
        'cover_image',
        'gallery',
        'available_from',
        'available_to',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'available_from'   => 'date',
        'available_to'     => 'date',
        'highlights'       => 'array',
        'inclusions'       => 'array',
        'exclusions'       => 'array',
        'faqs'             => 'array',
        'gallery'          => 'array',
        'is_active'        => 'boolean',
        'is_featured'      => 'boolean',
        'price_per_person' => 'decimal:2',
        'child_price'      => 'decimal:2',
        'discount'         => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(TourCategory::class, 'tour_category_id');
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(TourItinerary::class, 'tour_id')->orderBy('day_number');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TourSchedule::class, 'tour_id')->orderBy('departure_date');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class, 'tour_id');
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class, 'tour_driver_assignments', 'tour_schedule_id', 'driver_id')
                    ->join('tour_schedules', 'tour_driver_assignments.tour_schedule_id', '=', 'tour_schedules.id')
                    ->where('tour_schedules.tour_id', $this->getKey())
                    ->using(TourDriverAssignment::class)
                    ->withPivot('vehicle_id', 'status', 'fee', 'notes')
                    ->withTimestamps();
    }

    public function guides(): BelongsToMany
    {
        return $this->belongsToMany(Guide::class, 'tour_guide_assignments', 'tour_schedule_id', 'guide_id')
                    ->join('tour_schedules', 'tour_guide_assignments.tour_schedule_id', '=', 'tour_schedules.id')
                    ->where('tour_schedules.tour_id', $this->getKey())
                    ->using(TourGuideAssignment::class)
                    ->withPivot('role', 'status', 'fee', 'notes')
                    ->withTimestamps();
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function getDiscountedPrice(): float
    {
        return $this->price_per_person * (1 - $this->discount / 100);
    }

    public function getDurationLabelAttribute(): string
    {
        $d = $this->duration_days . 'D';
        $n = $this->duration_nights . 'N';
        return "{$d}/{$n}";
    }

    public function getNextAvailableSchedule(): ?TourSchedule
    {
        return $this->schedules()
            ->where('status', 'open')
            ->where('departure_date', '>=', now()->toDateString())
            ->first();
    }
}
