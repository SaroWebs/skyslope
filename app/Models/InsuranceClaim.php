<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    protected $table = 'insurance_claims';

    protected $fillable = [
        'insurance_policy_id',
        'customer_id',
        'claim_number',
        'description',
        'claim_amount',
        'approved_amount',
        'status',
        'admin_notes',
        'resolved_at',
    ];

    protected $casts = [
        'claim_amount'    => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'resolved_at'     => 'datetime',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_policy_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }
    public function isPaid(): bool       { return $this->status === 'paid'; }
}
