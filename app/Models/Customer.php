<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'profile_photo',
        'date_of_birth',
        'gender',
        'emergency_contact_name',
        'emergency_contact_phone',
        'is_active',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'phone_verified_at'  => 'datetime',
            'date_of_birth'      => 'date',
            'is_active'          => 'boolean',
            'password'           => 'hashed',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function tourBookings(): HasMany
    {
        return $this->hasMany(TourBooking::class, 'customer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class, 'customer_id');
    }

    public function carRentals(): HasMany
    {
        return $this->hasMany(CarRental::class, 'customer_id');
    }

    public function rideBookings(): HasMany
    {
        return $this->hasMany(RideBooking::class, 'customer_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'customer_id');
    }

    public function extendedCareRequests(): HasMany
    {
        return $this->hasMany(ExtendedCare::class, 'customer_id');
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

    public function isAdmin(): bool    { return false; }
    public function isDriver(): bool   { return false; }
    public function isCustomer(): bool { return true; }
    public function isGuide(): bool    { return false; }
}
