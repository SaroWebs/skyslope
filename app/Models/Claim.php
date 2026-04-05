<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'claim_number',
        'incident_date',
        'incident_description',
        'claim_amount',
        'status',
        'documents',
        'approved_by',
        'approved_at',
        'paid_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'documents' => 'array',
    ];

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate a unique claim number
     */
    public static function generateClaimNumber(): string
    {
        $prefix = 'CLM-' . date('Y');
        $lastClaim = self::where('claim_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastClaim ? (int) substr($lastClaim->claim_number, -6) + 1 : 1;
        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Approve the claim
     */
    public function approve(int $approvedBy): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the claim
     */
    public function reject(): bool
    {
        return $this->update([
            'status' => 'rejected',
        ]);
    }

    /**
     * Mark claim as paid
     */
    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Check if claim can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'pending' && $this->insurance->isActive();
    }
}