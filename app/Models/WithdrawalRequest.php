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
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }
}
