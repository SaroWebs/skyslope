<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Place extends Model
{
    protected $table = 'places';

    protected $fillable = [
        'place_category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'location',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'rating',
        'review_count',
        'cover_image',
        'tags',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'latitude'     => 'decimal:8',
        'longitude'    => 'decimal:8',
        'rating'       => 'decimal:2',
        'review_count' => 'integer',
        'tags'         => 'array',
        'is_active'    => 'boolean',
        'is_featured'  => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PlaceCategory::class, 'place_category_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PlaceMedia::class, 'place_id')->orderBy('sort_order');
    }
}
