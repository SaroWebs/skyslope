<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourDriverAssignment extends Model
{
    protected $table = 'tour_driver_assignments';

    protected $fillable = [
        'tour_schedule_id',
        'driver_id',
        'vehicle_id',
        'status',
        'fee',
        'notes',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TourSchedule::class, 'tour_schedule_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function isAccepted(): bool  { return $this->status === 'accepted'; }
    public function isDeclined(): bool  { return $this->status === 'declined'; }
    public function isPending(): bool   { return $this->status === 'assigned'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
}
