<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'drivers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'profile_photo',
        'date_of_birth',
        'gender',
        'license_number',
        'license_expiry',
        'vehicle_type',
        'vehicle_number',
        'vehicle_model',
        'vehicle_color',
        'vehicle_year',
        'status',
        'is_online',
        'is_active',
        'is_approved',
        'approved_at',
        'approved_by',
        'rating',
        'total_rides',
        'total_tours',
        'bank_account_number',
        'bank_account_name',
        'bank_name',
        'ifsc_code',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'bank_account_number',
        'ifsc_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'phone_verified_at'  => 'datetime',
            'license_expiry'     => 'date',
            'date_of_birth'      => 'date',
            'approved_at'        => 'datetime',
            'is_online'          => 'boolean',
            'is_active'          => 'boolean',
            'is_approved'        => 'boolean',
            'rating'             => 'decimal:2',
            'password'           => 'hashed',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    public function availability(): HasOne
    {
        return $this->hasOne(DriverAvailability::class, 'driver_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class, 'driver_id');
    }

    public function rideBookings(): HasMany
    {
        return $this->hasMany(RideBooking::class, 'driver_id');
    }

    public function carRentals(): HasMany
    {
        return $this->hasMany(CarRental::class, 'driver_id');
    }

    public function tourDriverAssignments(): HasMany
    {
        return $this->hasMany(TourDriverAssignment::class, 'driver_id');
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function withdrawalRequests(): MorphMany
    {
        return $this->morphMany(WithdrawalRequest::class, 'owner');
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isOnline(): bool   { return (bool) $this->is_online; }
    public function isApproved(): bool { return (bool) $this->is_approved; }
    public function isAdmin(): bool    { return false; }
    public function isDriver(): bool   { return true; }
    public function isCustomer(): bool { return false; }
    public function isGuide(): bool    { return false; }
}
