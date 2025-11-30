<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelStatus extends Model
{
    protected $fillable = [
        'booking_id',
        'description',
        'status',
    ];

    /**
     * Get the booking that the travel status belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
