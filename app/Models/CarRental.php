<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CarRental extends Model
{
    protected $table = 'car_rentals';

    protected $fillable = [
        'booking_number',
        'customer_id',
        'car_category_id',
        'driver_id',
        'vehicle_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'number_of_days',
        'pickup_location',
        'pickup_lat',
        'pickup_lng',
        'dropoff_location',
        'dropoff_lat',
        'dropoff_lng',
        'destination_details',
        'current_lat',
        'current_lng',
        'last_location_update',
        'base_price',
        'distance_km',
        'distance_price',
        'extras_price',
        'discount_amount',
        'total_price',
        'commission_amount',
        'driver_share',
        'status',
        'payment_status',
        'payment_method',
        'special_requests',
        'internal_notes',
        'cancellation_reason',
        'whatsapp_notification',
        'email_notification',
        'sms_notification',
    ];

    protected $casts = [
        'start_date'            => 'date',
        'end_date'              => 'date',
        'last_location_update'  => 'datetime',
        'base_price'            => 'decimal:2',
        'distance_km'           => 'decimal:2',
        'distance_price'        => 'decimal:2',
        'extras_price'          => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'total_price'           => 'decimal:2',
        'commission_amount'     => 'decimal:2',
        'driver_share'          => 'decimal:2',
        'pickup_lat'            => 'decimal:8',
        'pickup_lng'            => 'decimal:8',
        'dropoff_lat'           => 'decimal:8',
        'dropoff_lng'           => 'decimal:8',
        'current_lat'           => 'decimal:8',
        'current_lng'           => 'decimal:8',
        'whatsapp_notification' => 'boolean',
        'email_notification'    => 'boolean',
        'sms_notification'      => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($rental) {
            if (empty($rental->booking_number)) {
                $rental->booking_number = static::generateBookingNumber();
            }
        });
    }

    public static function generateBookingNumber(): string
    {
        do {
            $num = 'CAR' . date('Ymd') . strtoupper(Str::random(4));
        } while (static::where('booking_number', $num)->exists());
        return $num;
    }

    // ── Relationships ──────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function carCategory(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class, 'car_category_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function extras(): HasMany
    {
        return $this->hasMany(CarRentalExtra::class, 'car_rental_id');
    }

    // ── Status Helpers ─────────────────────────────────────────────

    public function isPending(): bool       { return $this->status === 'pending'; }
    public function isConfirmed(): bool     { return $this->status === 'confirmed'; }
    public function isDriverAssigned(): bool{ return $this->status === 'driver_assigned'; }
    public function isInProgress(): bool    { return $this->status === 'in_progress'; }
    public function isCompleted(): bool     { return $this->status === 'completed'; }
    public function isCancelled(): bool     { return $this->status === 'cancelled'; }
    public function isPaid(): bool          { return $this->payment_status === 'paid'; }

    public function calculateTotalPrice(): float
    {
        $extrasTotal = $this->extras->sum('total_price');
        return max(0, ($this->base_price + $this->distance_price + $extrasTotal) - $this->discount_amount);
    }
}
