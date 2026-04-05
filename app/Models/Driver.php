<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Driver extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'drivers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'license_number',
        'vehicle_type',
        'vehicle_number',
        'status',
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

    public function assignedRideBookings(): HasMany
    {
        return $this->hasMany(RideBooking::class, 'driver_id');
    }

    public function driverAvailability(): HasOne
    {
        return $this->hasOne(DriverAvailability::class, 'driver_id');
    }

    public function tourDrivers(): HasMany
    {
        return $this->hasMany(TourDriver::class, 'user_id');
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
        return true;
    }

    public function isCustomer(): bool
    {
        return false;
    }
}
