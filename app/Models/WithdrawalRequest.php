<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WithdrawalRequest extends Model
{
    protected $table = 'withdrawal_requests';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'amount',
        'method',
        'account_details',
        'status',
        'admin_notes',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'account_details' => 'array',
        'processed_at'    => 'datetime',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isProcessing(): bool { return $this->status === 'processing'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }

    public function approve(int $adminId, ?string $adminNotes = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'processed_by' => $adminId,
            'processed_at' => now(),
            'admin_notes' => $adminNotes,
        ]);
    }

    public function reject(int $adminId, string $rejectionReason, ?string $adminNotes = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'processed_by' => $adminId,
            'processed_at' => now(),
            'rejection_reason' => $rejectionReason,
            'admin_notes' => $adminNotes,
        ]);
    }

    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => 'processing',
        ]);
    }

    public function markAsCompleted(string $utrNumber): bool
    {
        return $this->update([
            'status' => 'completed',
            'utr_number' => $utrNumber,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'status' => 'rejected',
            'admin_notes' => 'Payout failed: ' . $error,
            'processed_at' => now(),
        ]);
    }

    public function scopeForOwner($query, $user)
    {
        return $query->where('owner_type', get_class($user))
                     ->where('owner_id', $user->id);
    }
}
