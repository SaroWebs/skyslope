<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InsurancePolicy extends Model
{
    protected $table = 'insurance_policies';

    protected $fillable = [
        'policy_number',
        'customer_id',
        'coverable_type',
        'coverable_id',
        'policy_type',
        'premium',
        'coverage_amount',
        'start_date',
        'end_date',
        'status',
        'terms',
    ];

    protected $casts = [
        'premium'         => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'start_date'      => 'date',
        'end_date'        => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** The insured entity: RideBooking or CarRental */
    public function coverable(): MorphTo
    {
        return $this->morphTo();
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'insurance_policy_id');
    }

    public function isActive(): bool  { return $this->status === 'active'; }
    public function isExpired(): bool { return $this->status === 'expired' || $this->end_date->isPast(); }
}
