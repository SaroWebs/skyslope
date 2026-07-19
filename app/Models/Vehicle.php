<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    protected $table = 'vehicles';

    protected $fillable = [
        'car_category_id',
        'driver_id',
        'registration_number',
        'make',
        'model',
        'year',
        'color',
        'vin',
        'fuel_type',
        'seats',
        'is_ac',
        'insurance_expiry',
        'permit_expiry',
        'fitness_expiry',
        'pollution_expiry',
        'odometer_km',
        'is_active',
        'condition',
        'notes',
        'approval_status',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    protected $casts = [
        'insurance_expiry' => 'date',
        'permit_expiry'    => 'date',
        'fitness_expiry'   => 'date',
        'pollution_expiry' => 'date',
        'is_ac'            => 'boolean',
        'is_active'        => 'boolean',
        'year'             => 'integer',
        'seats'            => 'integer',
        'odometer_km'      => 'integer',
        'reviewed_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Vehicle $vehicle) {
            $vehicle->tracker()->firstOrCreate([], [
                'device_uid' => 'SKY-'.str_pad((string) $vehicle->id, 6, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(6)),
                'status' => 'unprovisioned',
            ]);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class, 'car_category_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApprovedForService(): bool
    {
        return $this->is_active
            && $this->approval_status === 'approved'
            && $this->condition !== 'under_maintenance'
            && $this->isDocumentValid();
    }

    public function rideBookings(): HasMany
    {
        return $this->hasMany(RideBooking::class, 'vehicle_id');
    }

    public function carRentals(): HasMany
    {
        return $this->hasMany(CarRental::class, 'vehicle_id');
    }

    public function tourDriverAssignments(): HasMany
    {
        return $this->hasMany(TourDriverAssignment::class, 'vehicle_id');
    }

    public function tracker(): HasOne
    {
        return $this->hasOne(VehicleTracker::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(VehicleLocation::class);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function getDisplayNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model} ({$this->registration_number})";
    }

    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiry && $this->insurance_expiry->isPast();
    }

    public function isDocumentValid(): bool
    {
        return !$this->isInsuranceExpired()
            && (!$this->permit_expiry   || !$this->permit_expiry->isPast())
            && (!$this->fitness_expiry  || !$this->fitness_expiry->isPast())
            && (!$this->pollution_expiry|| !$this->pollution_expiry->isPast());
    }
}
