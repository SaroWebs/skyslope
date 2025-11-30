<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarRentalExtra extends Model
{
    protected $fillable = [
        'car_rental_id',
        'extra_type',
        'extra_name',
        'description',
        'price_per_day',
        'quantity',
        'total_price',
        'is_included',
        'is_optional',
    ];

    protected $casts = [
        'price_per_day' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_included' => 'boolean',
        'is_optional' => 'boolean',
    ];

    /**
     * Get the car rental that owns the extra.
     */
    public function carRental(): BelongsTo
    {
        return $this->belongsTo(CarRental::class);
    }

    /**
     * Calculate total price for this extra.
     */
    public function calculateTotal(): void
    {
        $this->total_price = $this->price_per_day * $this->quantity;
        $this->save();
    }

    /**
     * Get formatted extra type badge.
     */
    public function getExtraTypeBadgeAttribute(): string
    {
        return match($this->extra_type) {
            'gps' => 'bg-blue-100 text-blue-800',
            'child_seat' => 'bg-green-100 text-green-800',
            'extra_driver' => 'bg-purple-100 text-purple-800',
            'wifi' => 'bg-orange-100 text-orange-800',
            'snacks' => 'bg-pink-100 text-pink-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get extra type icon.
     */
    public function getExtraTypeIconAttribute(): string
    {
        return match($this->extra_type) {
            'gps' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'child_seat' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z',
            'extra_driver' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            'wifi' => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
            'snacks' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
            default => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }
}