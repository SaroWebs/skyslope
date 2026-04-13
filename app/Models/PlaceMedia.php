<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceMedia extends Model
{
    protected $table = 'place_media';

    protected $fillable = [
        'place_id',
        'path',
        'type',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id');
    }

    public function isImage(): bool { return $this->type === 'image'; }
    public function isVideo(): bool { return $this->type === 'video'; }
}
