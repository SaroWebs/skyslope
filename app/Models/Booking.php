<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'booking_number',
        'user_id',
        'tour_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'travel_date',
        'number_of_people',
        'special_requests',
        'status',
        'total_price',
        'discount_amount',
        'payment_method',
        'payment_status',
        'assigned_guide',
        'assigned_driver',
        'internal_notes',
        'whatsapp_notification',
        'email_notification',
        'sms_notification',
    ];

    protected $casts = [
        'travel_date' => 'date',
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
            $bookingNumber = 'TOUR' . date('Ymd') . strtoupper(Str::random(4));
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
     * Get the tour for the booking.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Get the travel statuses for the booking.
     */
    public function travelStatuses(): HasMany
    {
        return $this->hasMany(TravelStatus::class);
    }

    /**
     * Get the assigned guide for the booking.
     */
    public function guide()
    {
        return $this->belongsTo(User::class, 'assigned_guide');
    }

    /**
     * Get the assigned driver for the booking.
     */
    public function driver()
    {
        return $this->belongsTo(User::class, 'assigned_driver');
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
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
}
