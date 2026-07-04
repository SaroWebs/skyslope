<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminFinancialController extends Controller
{
    /**
     * List all wallets with transactions and stats.
     */
    public function wallets(Request $request)
    {
        $ownerType = $request->input('owner_type', 'driver'); // 'driver' or 'customer' or 'all'
        
        $query = Wallet::with(['owner', 'transactions' => function ($q) {
            $q->latest()->take(5);
        }]);

        if ($ownerType === 'driver') {
            $query->where('owner_type', 'App\Models\Driver');
        } elseif ($ownerType === 'customer') {
            $query->where('owner_type', 'App\Models\User'); // Customer is User model
        }

        $wallets = $query->paginate(15)->appends($request->all());

        return Inertia::render('admin/Financials/Wallets', [
            'title' => 'Driver & User Wallets',
            'user' => Auth::user(),
            'wallets' => $wallets,
            'filters' => [
                'owner_type' => $ownerType,
            ],
        ]);
    }

    /**
     * Perform a manual balance adjustment (credit or debit).
     */
    public function adjustWallet(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $amount = (float) $validated['amount'];
        $type = $validated['type'];
        $description = '[Admin adjustment] ' . $validated['description'];

        try {
            if ($type === 'credit') {
                $wallet->credit($amount, $description);
            } else {
                if (!$wallet->hasSufficientBalance($amount)) {
                    return back()->with('error', 'Insufficient balance for debit adjustment.');
                }
                $wallet->debit($amount, $description);
            }

            return back()->with('success', 'Wallet balance adjusted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Adjustment failed: ' . $e->getMessage());
        }
    }

    /**
     * List all withdrawal requests.
     */
    public function withdrawals(Request $request)
    {
        $status = $request->input('status', 'all');

        $query = WithdrawalRequest::with('owner');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $withdrawals = $query->latest()->paginate(15)->appends($request->all());

        return Inertia::render('admin/Financials/Withdrawals', [
            'title' => 'Payout Requests',
            'user' => Auth::user(),
            'withdrawals' => $withdrawals,
            'filters' => [
                'status' => $status,
            ],
            'stats' => [
                'pending_count' => WithdrawalRequest::where('status', 'pending')->count(),
                'processing_count' => WithdrawalRequest::where('status', 'processing')->count(),
                'completed_sum' => WithdrawalRequest::where('status', 'completed')->sum('amount'),
            ]
        ]);
    }

    /**
     * Approve a pending withdrawal request.
     */
    public function approveWithdrawal(Request $request, WithdrawalRequest $withdrawal)
    {
        if (!$withdrawal->isPending()) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $withdrawal->approve(Auth::id(), $request->input('admin_notes'));

        return back()->with('success', 'Withdrawal request approved successfully.');
    }

    /**
     * Reject a pending withdrawal request and refund wallet.
     */
    public function rejectWithdrawal(Request $request, WithdrawalRequest $withdrawal)
    {
        if (!$withdrawal->isPending() && !$withdrawal->isProcessing()) {
            return back()->with('error', 'This request cannot be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
            'admin_notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $wallet = Wallet::where('owner_type', $withdrawal->owner_type)
                            ->where('owner_id', $withdrawal->owner_id)
                            ->first();

            if ($wallet) {
                $wallet->credit(
                    (float) $withdrawal->amount,
                    'Refund: Withdrawal request rejected - ID #' . $withdrawal->id
                );
            }

            $withdrawal->reject(Auth::id(), $validated['rejection_reason'], $validated['admin_notes'] ?? null);

            DB::commit();
            return back()->with('success', 'Withdrawal request rejected and amount refunded.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark withdrawal request as completed with a UTR transaction number.
     */
    public function completeWithdrawal(Request $request, WithdrawalRequest $withdrawal)
    {
        if ($withdrawal->status !== 'approved' && $withdrawal->status !== 'processing') {
            return back()->with('error', 'Only approved or processing requests can be completed.');
        }

        $validated = $request->validate([
            'utr_number' => 'required|string|max:100',
        ]);

        $withdrawal->markAsCompleted($validated['utr_number']);

        return back()->with('success', 'Withdrawal marked as completed successfully.');
    }
}
