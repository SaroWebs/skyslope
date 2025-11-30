<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceMedia extends Model
{
    protected $fillable = [
        'place_id',
        'file_path',
        'file_type',
        'description',
    ];

    /**
     * Get the place that owns the media.
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
