<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RideBooking extends Model
{
    protected $table = 'ride_bookings';

    protected $fillable = [
        'booking_number',
        'customer_id',
        'driver_id',
        'car_category_id',
        'vehicle_id',
        'service_type',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'pickup_location',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_location',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'scheduled_at',
        'driver_assigned_at',
        'driver_arrived_at',
        'started_at',
        'completed_at',
        'estimated_duration',
        'actual_duration',
        'estimated_distance_km',
        'actual_distance_km',
        'base_fare',
        'distance_fare',
        'time_fare',
        'waiting_fare',
        'surge_multiplier',
        'total_fare',
        'commission_amount',
        'driver_share',
        'status',
        'payment_status',
        'payment_method',
        'start_ride_pin',
        'start_pin_verified_at',
        'current_lat',
        'current_lng',
        'last_location_update',
        'last_admin_change_snapshot',
        'last_admin_changed_at',
        'last_admin_changed_by',
        'special_requests',
        'driver_notes',
        'cancellation_reason',
        'cancelled_at',
        'whatsapp_notification',
        'email_notification',
        'sms_notification',
    ];

    protected $casts = [
        'scheduled_at'              => 'datetime',
        'driver_assigned_at'        => 'datetime',
        'driver_arrived_at'         => 'datetime',
        'started_at'                => 'datetime',
        'completed_at'              => 'datetime',
        'cancelled_at'              => 'datetime',
        'start_pin_verified_at'     => 'datetime',
        'last_location_update'      => 'datetime',
        'last_admin_changed_at'     => 'datetime',
        'last_admin_change_snapshot'=> 'array',
        'pickup_lat'                => 'decimal:8',
        'pickup_lng'                => 'decimal:8',
        'dropoff_lat'               => 'decimal:8',
        'dropoff_lng'               => 'decimal:8',
        'current_lat'               => 'decimal:8',
        'current_lng'               => 'decimal:8',
        'base_fare'                 => 'decimal:2',
        'distance_fare'             => 'decimal:2',
        'time_fare'                 => 'decimal:2',
        'waiting_fare'              => 'decimal:2',
        'surge_multiplier'          => 'decimal:2',
        'total_fare'                => 'decimal:2',
        'commission_amount'         => 'decimal:2',
        'driver_share'              => 'decimal:2',
        'estimated_distance_km'     => 'decimal:2',
        'actual_distance_km'        => 'decimal:2',
        'whatsapp_notification'     => 'boolean',
        'email_notification'        => 'boolean',
        'sms_notification'          => 'boolean',
    ];

    protected $hidden = ['start_ride_pin'];

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
            $num = 'RIDE' . date('Ymd') . strtoupper(Str::random(4));
        } while (static::where('booking_number', $num)->exists());
        return $num;
    }

    public static function generateStartRidePin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ──────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function carCategory(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class, 'car_category_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(RideBookingReview::class, 'ride_booking_id');
    }

    public function tips(): HasMany
    {
        return $this->hasMany(RideBookingTip::class, 'ride_booking_id');
    }

    // ── Status Helpers ─────────────────────────────────────────────

    public function isPending(): bool        { return $this->status === 'pending'; }
    public function isConfirmed(): bool      { return $this->status === 'confirmed'; }
    public function isDriverAssigned(): bool { return $this->status === 'driver_assigned'; }
    public function isInProgress(): bool     { return in_array($this->status, ['driver_arriving', 'pickup', 'in_transit']); }
    public function isCompleted(): bool      { return $this->status === 'completed'; }
    public function isCancelled(): bool      { return $this->status === 'cancelled'; }
    public function isPaid(): bool           { return $this->payment_status === 'paid'; }

    public function calculateDistance(): float
    {
        if (!$this->pickup_lat || !$this->dropoff_lat) return 0;
        $R = 6371;
        $dLat = deg2rad((float)$this->dropoff_lat - (float)$this->pickup_lat);
        $dLng = deg2rad((float)$this->dropoff_lng - (float)$this->pickup_lng);
        $a = sin($dLat/2)**2 + cos(deg2rad((float)$this->pickup_lat)) * cos(deg2rad((float)$this->dropoff_lat)) * sin($dLng/2)**2;
        return round($R * 2 * atan2(sqrt($a), sqrt(1-$a)), 2);
    }
}
