<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $table = 'wallets';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'balance'   => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id')->latest();
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function credit(float $amount, string $description = '', ?string $refType = null, ?string $refId = null): WalletTransaction
    {
        $before = (float) $this->balance;
        $after  = $before + $amount;

        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type'           => 'credit',
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $after,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'description'    => $description,
        ]);
    }

    public function debit(float $amount, string $description = '', ?string $refType = null, ?string $refId = null): WalletTransaction
    {
        if ((float) $this->balance < $amount) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }

        $before = (float) $this->balance;
        $after  = $before - $amount;

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type'           => 'debit',
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $after,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'description'    => $description,
        ]);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return (float) $this->balance >= $amount;
    }

    public function getBalance(): float
    {
        return (float) $this->balance;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope to find wallet for a specific owner.
     */
    public function scopeForOwner($query, $user)
    {
        return $query->where('owner_type', get_class($user))
                     ->where('owner_id', $user->id);
    }
}
