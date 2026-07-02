<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletLedgerService
{
    public function credit(
        Wallet $wallet,
        float $amount,
        string $description = '',
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $idempotencyKey = null
    ): WalletTransaction {
        return $this->record($wallet, 'credit', $amount, $description, $referenceType, $referenceId, $idempotencyKey);
    }

    public function debit(
        Wallet $wallet,
        float $amount,
        string $description = '',
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $idempotencyKey = null
    ): WalletTransaction {
        return $this->record($wallet, 'debit', $amount, $description, $referenceType, $referenceId, $idempotencyKey);
    }

    private function record(
        Wallet $wallet,
        string $type,
        float $amount,
        string $description = '',
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $idempotencyKey = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Wallet transaction amount must be greater than zero.');
        }

        return DB::transaction(function () use ($wallet, $type, $amount, $description, $referenceType, $referenceId, $idempotencyKey) {
            $lockedWallet = Wallet::query()
                ->whereKey($wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($idempotencyKey) {
                $existing = WalletTransaction::query()
                    ->where('wallet_id', $lockedWallet->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    $wallet->forceFill(['balance' => $lockedWallet->balance]);
                    return $existing;
                }
            }

            if (!$lockedWallet->is_active) {
                throw new \RuntimeException('Wallet is inactive.');
            }

            $before = (float) $lockedWallet->balance;
            $after = $type === 'credit'
                ? $before + $amount
                : $before - $amount;

            if ($after < 0) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }

            $lockedWallet->forceFill(['balance' => $after])->save();

            $transaction = WalletTransaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'status' => 'completed',
            ]);

            $wallet->forceFill(['balance' => $after]);

            return $transaction;
        });
    }
}
