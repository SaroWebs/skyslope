<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourDriver extends Model
{
    protected $fillable = [
        'tour_id',
        'user_id',
        'guide',
    ];

    protected $casts = [
        'guide' => 'boolean',
    ];

    /**
     * Get the tour that the driver is assigned to.
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /**
     * Get the user (driver) assigned to the tour.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
