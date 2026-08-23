<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Introduces the balanced double-entry ledger in integer minor units
 * (SKY-MRD-001 §8.6, invariants #2 & #7) and integer mirror columns on the
 * existing wallet tables.
 *
 * The decimal `wallets.balance` / `wallet_transactions.*` columns are retained
 * as a mirror for existing read paths; `balance_minor` + `ledger_entries`
 * become the authoritative record and are reconciled by the nightly job.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Chart of accounts ──────────────────────────────────────────
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();           // e.g. wallet:12, system:booking_revenue
            $table->string('name');
            $table->enum('type', ['wallet', 'system'])->index();
            $table->nullableMorphs('owner');            // owner_type, owner_id (wallet accounts)
            $table->string('currency', 3)->default('INR');
            $table->timestamps();
        });

        // ── Double-entry lines ─────────────────────────────────────────
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_ref');            // groups the balanced pair
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('wallet_id')->nullable();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->enum('direction', ['debit', 'credit']);
            $table->unsignedBigInteger('amount_minor'); // positive integer minor units (paise)
            $table->string('currency', 3)->default('INR');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();

            $table->index('transaction_ref');
            $table->index('wallet_id');
            $table->index('wallet_transaction_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['ledger_account_id', 'direction']);
        });

        // ── Integer mirror columns on the existing money tables ─────────
        Schema::table('wallets', function (Blueprint $table) {
            if (! Schema::hasColumn('wallets', 'balance_minor')) {
                $table->bigInteger('balance_minor')->nullable()->after('balance');
            }
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('wallet_transactions', 'amount_minor')) {
                $table->unsignedBigInteger('amount_minor')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('wallet_transactions', 'balance_before_minor')) {
                $table->bigInteger('balance_before_minor')->nullable()->after('balance_before');
            }
            if (! Schema::hasColumn('wallet_transactions', 'balance_after_minor')) {
                $table->bigInteger('balance_after_minor')->nullable()->after('balance_after');
            }
            if (! Schema::hasColumn('wallet_transactions', 'transaction_ref')) {
                $table->uuid('transaction_ref')->nullable();
                $table->index('transaction_ref');
            }
        });

        $this->backfill();
    }

    /**
     * Mirror existing decimal balances into minor units and seed an opening
     * balance entry per funded wallet so the ledger reconciles with prod data.
     * On fresh/test databases every table is empty, so this is a no-op.
     */
    private function backfill(): void
    {
        foreach (DB::table('wallets')->get() as $wallet) {
            $minor = (int) round(((float) $wallet->balance) * 100);
            DB::table('wallets')->where('id', $wallet->id)->update(['balance_minor' => $minor]);

            if ($minor === 0) {
                continue;
            }

            $currency = $wallet->currency ?? 'INR';
            $walletAccountId = $this->accountId(
                'wallet:'.$wallet->id, 'Wallet #'.$wallet->id, 'wallet',
                $wallet->owner_type, $wallet->owner_id, $currency
            );
            $openingAccountId = $this->accountId('system:opening_balance', 'Opening balance', 'system');

            $ref = (string) Str::uuid();
            $now = now();

            DB::table('ledger_entries')->insert([
                [
                    'transaction_ref' => $ref,
                    'ledger_account_id' => $walletAccountId,
                    'wallet_id' => $wallet->id,
                    'wallet_transaction_id' => null,
                    'direction' => 'credit',
                    'amount_minor' => $minor,
                    'currency' => $currency,
                    'reference_type' => 'opening_balance',
                    'reference_id' => (string) $wallet->id,
                    'description' => 'Opening balance carried into ledger',
                    'posted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'transaction_ref' => $ref,
                    'ledger_account_id' => $openingAccountId,
                    'wallet_id' => null,
                    'wallet_transaction_id' => null,
                    'direction' => 'debit',
                    'amount_minor' => $minor,
                    'currency' => $currency,
                    'reference_type' => 'opening_balance',
                    'reference_id' => (string) $wallet->id,
                    'description' => 'Opening balance carried into ledger',
                    'posted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        foreach (DB::table('wallet_transactions')->get() as $txn) {
            DB::table('wallet_transactions')->where('id', $txn->id)->update([
                'amount_minor' => (int) round(((float) $txn->amount) * 100),
                'balance_before_minor' => (int) round(((float) $txn->balance_before) * 100),
                'balance_after_minor' => (int) round(((float) $txn->balance_after) * 100),
            ]);
        }
    }

    private function accountId(string $code, string $name, string $type, ?string $ownerType = null, ?int $ownerId = null, string $currency = 'INR'): int
    {
        $existing = DB::table('ledger_accounts')->where('code', $code)->first();
        if ($existing) {
            return $existing->id;
        }

        return DB::table('ledger_accounts')->insertGetId([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'currency' => $currency,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            foreach (['amount_minor', 'balance_before_minor', 'balance_after_minor', 'transaction_ref'] as $col) {
                if (Schema::hasColumn('wallet_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('wallets', function (Blueprint $table) {
            if (Schema::hasColumn('wallets', 'balance_minor')) {
                $table->dropColumn('balance_minor');
            }
        });

        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};
