<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RideBooking extends Model
{
    protected $fillable = [
        'booking_number',
        'user_id',
        'driver_id',
        'service_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'pickup_location',
        'pickup_lat',
        'pickup_lng',
        'dropoff_location',
        'dropoff_lat',
        'dropoff_lng',
        'scheduled_at',
        'started_at',
        'completed_at',
        'estimated_duration',
        'actual_duration',
        'base_fare',
        'distance_fare',
        'time_fare',
        'waiting_fare',
        'surge_multiplier',
        'total_fare',
        'distance_km',
        'status',
        'payment_status',
        'payment_method',
        'vehicle_number',
        'special_requests',
        'cancellation_reason',
        'current_lat',
        'current_lng',
        'last_location_update',
        'whatsapp_notification',
        'email_notification',
        'sms_notification',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'dropoff_lat' => 'decimal:8',
        'dropoff_lng' => 'decimal:8',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
        'last_location_update' => 'datetime',
        'base_fare' => 'decimal:2',
        'distance_fare' => 'decimal:2',
        'time_fare' => 'decimal:2',
        'waiting_fare' => 'decimal:2',
        'surge_multiplier' => 'decimal:2',
        'total_fare' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'whatsapp_notification' => 'boolean',
        'email_notification' => 'boolean',
        'sms_notification' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = static::generateBookingNumber();
            }
        });
    }

    /**
     * Generate a unique booking number.
     */
    public static function generateBookingNumber()
    {
        do {
            $bookingNumber = 'RIDE' . date('Ymd') . strtoupper(Str::random(4));
        } while (static::where('booking_number', $bookingNumber)->exists());

        return $bookingNumber;
    }

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assigned driver.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Check if booking is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if driver is assigned.
     */
    public function isDriverAssigned(): bool
    {
        return $this->status === 'driver_assigned';
    }

    /**
     * Check if booking is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['driver_arriving', 'pickup', 'in_transit']);
    }

    /**
     * Check if booking is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if payment is completed.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Calculate distance between pickup and dropoff (Haversine formula).
     */
    public function calculateDistance(): float
    {
        if (!$this->pickup_lat || !$this->pickup_lng || !$this->dropoff_lat || !$this->dropoff_lng) {
            return 0;
        }

        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($this->dropoff_lat - $this->pickup_lat);
        $lngDelta = deg2rad($this->dropoff_lng - $this->pickup_lng);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->pickup_lat)) * cos(deg2rad($this->dropoff_lat)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get formatted status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'driver_assigned' => 'bg-purple-100 text-purple-800',
            'driver_arriving' => 'bg-indigo-100 text-indigo-800',
            'pickup' => 'bg-orange-100 text-orange-800',
            'in_transit' => 'bg-green-100 text-green-800',
            'completed' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get formatted payment status badge.
     */
    public function getPaymentStatusBadgeAttribute(): string
    {
        return match($this->payment_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'paid' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
