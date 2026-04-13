<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TourBooking extends Model
{
    protected $table = 'tour_bookings';

    protected $fillable = [
        'booking_number',
        'customer_id',
        'tour_id',
        'tour_schedule_id',
        'number_of_adults',
        'number_of_children',
        'travel_date',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'price_per_adult',
        'price_per_child',
        'subtotal',
        'discount_amount',
        'total_price',
        'status',
        'payment_status',
        'payment_method',
        'assigned_guide_id',
        'assigned_driver_id',
        'assigned_vehicle_id',
        'special_requests',
        'internal_notes',
        'cancellation_reason',
        'cancelled_at',
        'whatsapp_notification',
        'email_notification',
        'sms_notification',
    ];

    protected $casts = [
        'travel_date'            => 'date',
        'cancelled_at'           => 'datetime',
        'whatsapp_notification'  => 'boolean',
        'email_notification'     => 'boolean',
        'sms_notification'       => 'boolean',
        'price_per_adult'        => 'decimal:2',
        'price_per_child'        => 'decimal:2',
        'subtotal'               => 'decimal:2',
        'discount_amount'        => 'decimal:2',
        'total_price'            => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = static::generateBookingNumber();
            }
        });
    }

    public static function generateBookingNumber(): string
    {
        do {
            $num = 'TOUR' . date('Ymd') . strtoupper(Str::random(4));
        } while (static::where('booking_number', $num)->exists());
        return $num;
    }

    // ── Relationships ──────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TourSchedule::class, 'tour_schedule_id');
    }

    public function assignedGuide(): BelongsTo
    {
        return $this->belongsTo(Guide::class, 'assigned_guide_id');
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function assignedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function travelStatuses(): HasMany
    {
        return $this->hasMany(TravelStatus::class, 'tour_booking_id');
    }

    // ── Status Helpers ─────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isConfirmed(): bool  { return $this->status === 'confirmed'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isPaid(): bool       { return $this->payment_status === 'paid'; }

    public function getTotalPax(): int
    {
        return $this->number_of_adults + $this->number_of_children;
    }
}
