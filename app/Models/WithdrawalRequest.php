<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class WithdrawalRequest extends Model
{
    protected $fillable = [
        'request_number',
        'owner_type',
        'owner_id',
        'wallet_id',
        'amount',
        'processing_fee',
        'net_amount',
        'bank_account_name',
        'bank_account_number',
        'bank_ifsc',
        'bank_name',
        'razorpay_fund_account_id',
        'razorpay_payout_id',
        'razorpay_batch_id',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'admin_notes',
        'processed_at',
        'completed_at',
        'failure_reason',
        'utr_number',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_number)) {
                $request->request_number = static::generateRequestNumber();
            }
        });
    }

    /**
     * Generate a unique request number.
     */
    public static function generateRequestNumber(): string
    {
        do {
            $requestNumber = 'WDR' . date('Ymd') . strtoupper(Str::random(6));
        } while (static::where('request_number', $requestNumber)->exists());

        return $requestNumber;
    }

    /**
     * Get the user who made the withdrawal request.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->owner();
    }

    /**
     * Get the wallet associated with the withdrawal.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the admin who reviewed the request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForOwner($query, Model $owner)
    {
        return $query
            ->where('owner_type', $owner::class)
            ->where('owner_id', $owner->getKey());
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the request is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed requests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for processing requests.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Mark as approved.
     */
    public function approve(int $reviewerId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function reject(int $reviewerId, string $reason, ?string $notes = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(string $utrNumber, ?string $payoutId = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'utr_number' => $utrNumber,
            'razorpay_payout_id' => $payoutId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $reason): bool
    {
        return $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get masked bank account number.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        $number = $this->bank_account_number;
        return 'XXXX' . substr($number, -4);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processing' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'completed' => 'green',
            'failed' => 'red',
            default => 'gray',
        };
    }
}
