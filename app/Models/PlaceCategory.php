<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlaceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function places(): HasMany
    {
        return $this->hasMany(Place::class, 'place_category_id');
    }

    /**
     * Get active categories
     */
    public static function getActiveCategories()
    {
        return self::where('is_active', true)->get();
    }

    /**
     * Get featured categories
     */
    public static function getFeaturedCategories()
    {
        return self::where('is_active', true)
            ->whereHas('places', function ($query) {
                $query->where('is_featured', true);
            })
            ->get();
    }
}