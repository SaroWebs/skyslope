<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function carRentals(): HasMany
    {
        return $this->hasMany(CarRental::class, 'user_id');
    }

    public function rideBookings(): HasMany
    {
        return $this->hasMany(RideBooking::class, 'user_id');
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'user_id');
    }

    public function extendedCareRequests(): HasMany
    {
        return $this->hasMany(ExtendedCare::class, 'user_id');
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function withdrawalRequests(): MorphMany
    {
        return $this->morphMany(WithdrawalRequest::class, 'owner');
    }

    public function isAdmin(): bool
    {
        return false;
    }

    public function isDriver(): bool
    {
        return false;
    }

    public function isCustomer(): bool
    {
        return true;
    }
}
