<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'balance',
        'currency',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForOwner($query, Model $owner)
    {
        return $query
            ->where('owner_type', $owner::class)
            ->where('owner_id', $owner->getKey());
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    /**
     * Credit amount to wallet
     */
    public function credit(float $amount, string $description, string|int|null $referenceId = null): bool
    {
        return $this->applyBalanceChange('credit', $amount, $description, $referenceId);
    }

    /**
     * Debit amount from wallet
     */
    public function debit(float $amount, string $description, string|int|null $referenceId = null): bool
    {
        if ((float) $this->balance < $amount) {
            return false;
        }

        return $this->applyBalanceChange('debit', $amount, $description, $referenceId);
    }

    /**
     * Process commission deduction
     */
    public function processCommission(float $amount, string $description, string|int|null $referenceId = null): bool
    {
        if ((float) $this->balance < $amount) {
            return false;
        }

        return $this->applyBalanceChange('commission', $amount, $description, $referenceId);
    }

    /**
     * Get wallet balance
     */
    public function getBalance(): float
    {
        return $this->balance;
    }

    /**
     * Check if wallet is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    private function applyBalanceChange(
        string $transactionType,
        float $amount,
        string $description,
        string|int|null $referenceId = null
    ): bool {
        if ($amount <= 0) {
            return false;
        }

        if (in_array($transactionType, ['debit', 'commission'], true)) {
            $this->balance = (float) $this->balance - $amount;
        } else {
            $this->balance = (float) $this->balance + $amount;
        }

        $success = $this->save();

        if ($success) {
            $this->transactions()->create([
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'description' => $description,
                'reference_id' => $referenceId,
                'status' => 'completed',
            ]);
        }

        return $success;
    }
}
