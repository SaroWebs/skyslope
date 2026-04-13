<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAvailability extends Model
{
    protected $table = 'driver_availabilities';

    protected $fillable = [
        'driver_id',
        'is_available',
        'status',
        'current_lat',
        'current_lng',
        'last_updated',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'current_lat'  => 'decimal:8',
        'current_lng'  => 'decimal:8',
        'last_updated' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function isOnline(): bool   { return $this->status !== 'offline'; }
    public function isOnRide(): bool   { return $this->status === 'on_ride'; }
    public function isOnTour(): bool   { return $this->status === 'on_tour'; }
    public function isFree(): bool     { return $this->status === 'online' && $this->is_available; }
}
