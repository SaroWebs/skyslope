<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CarRental extends Model
{
    protected $fillable = [
        'booking_number',
        'user_id',
        'car_category_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'pickup_location',
        'dropoff_location',
        'destination_details',
        'number_of_days',
        'base_price',
        'distance_km',
        'distance_price',
        'extras_price',
        'discount_amount',
        'total_price',
        'status',
        'payment_status',
        'payment_method',
        'special_requests',
        'internal_notes',
        'assigned_driver',
        'vehicle_number',
        'whatsapp_notification',
        'email_notification',
        'sms_notification',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'base_price' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'distance_price' => 'decimal:2',
        'extras_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
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

         static::creating(function ($rental) {
             if (empty($rental->booking_number)) {
                 $rental->booking_number = static::generateBookingNumber();
             }
         });
    }

    /**
     * Generate a unique booking number.
     */
     public static function generateBookingNumber()
     {
         do {
             $bookingNumber = 'CAR' . date('Ymd') . strtoupper(Str::random(4));
         } while (static::where('booking_number', $bookingNumber)->exists());

         return $bookingNumber;
     }

    /**
     * Get the user that owns the car rental.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the car category for the rental.
     */
    public function carCategory(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class);
    }

    /**
     * Get the extras for the rental.
     */
    public function extras(): HasMany
    {
        return $this->hasMany(CarRentalExtra::class);
    }

    /**
     * Get the assigned driver for the rental.
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'assigned_driver');
    }

    /**
     * Check if rental is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if rental is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if rental is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if rental is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if rental is cancelled.
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
     * Check if payment is pending.
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Calculate total price including extras.
     */
    public function calculateTotalPrice(): float
    {
        $extrasTotal = $this->extras->sum('total_price');
        return ($this->base_price + $this->distance_price + $extrasTotal) - $this->discount_amount;
    }

    /**
     * Get formatted status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-green-100 text-green-800',
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
