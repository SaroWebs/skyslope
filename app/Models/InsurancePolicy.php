<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'policy_number',
        'insurance_type',
        'coverage_amount',
        'premium_amount',
        'start_date',
        'end_date',
        'status',
        'payment_status',
        'policy_document_url',
        'terms_accepted',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terms_accepted' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'insurance_id');
    }

    public function extendedCare(): HasMany
    {
        return $this->hasMany(ExtendedCare::class, 'insurance_id');
    }

    /**
     * Generate a unique policy number
     */
    public static function generatePolicyNumber(): string
    {
        $prefix = 'POL-' . date('Y');
        $lastPolicy = self::where('policy_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPolicy ? (int) substr($lastPolicy->policy_number, -6) + 1 : 1;
        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if policy is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    /**
     * Check if policy is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date < now() || $this->status === 'expired';
    }
}
