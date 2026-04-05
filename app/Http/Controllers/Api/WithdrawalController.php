<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\RazorpayService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    protected RazorpayService $razorpay;
    protected NotificationService $notification;

    public function __construct(RazorpayService $razorpay, NotificationService $notification)
    {
        $this->razorpay = $razorpay;
        $this->notification = $notification;
    }

    /**
     * Get user's withdrawal requests
     */
    public function index(Request $request)
    {
        $withdrawals = WithdrawalRequest::forOwner($request->user())
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * Create a new withdrawal request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'bank_account_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|between:9,18',
            'bank_ifsc' => 'required|string|size:11',
            'bank_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $wallet = Wallet::forOwner($user)->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        if (!$wallet->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet is not active',
            ], 400);
        }

        if ($wallet->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance',
            ], 400);
        }

        // Calculate processing fee (if any)
        $processingFee = 0; // Can be configured based on amount
        $netAmount = $request->amount - $processingFee;

        DB::beginTransaction();
        try {
            // Deduct from wallet immediately (pending state)
            $wallet->debit(
                $request->amount,
                'Withdrawal request initiated',
                null
            );

            // Create withdrawal request
            $withdrawal = WithdrawalRequest::create([
                'owner_type' => $user::class,
                'owner_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'processing_fee' => $processingFee,
                'net_amount' => $netAmount,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_ifsc' => $request->bank_ifsc,
                'bank_name' => $request->bank_name,
                'status' => 'pending',
            ]);

            DB::commit();

            // Send notification
            $this->notification->sendWalletNotification($user, [
                'type' => 'Withdrawal Request',
                'amount' => $request->amount,
                'balance' => $wallet->fresh()->balance,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully',
                'data' => $withdrawal,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create withdrawal request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get withdrawal request details
     */
    public function show(Request $request, WithdrawalRequest $withdrawal)
    {
        // Check authorization
        $isOwner = $withdrawal->owner_type === $request->user()::class
            && (int) $withdrawal->owner_id === (int) $request->user()->id;

        if (!$isOwner && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $withdrawal->load(['owner', 'reviewer']),
        ]);
    }

    /**
     * Cancel a pending withdrawal request
     */
    public function cancel(Request $request, WithdrawalRequest $withdrawal)
    {
        // Check authorization
        if ($withdrawal->owner_type !== $request->user()::class || (int) $withdrawal->owner_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!$withdrawal->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be cancelled',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Refund to wallet
            $wallet = $withdrawal->wallet;
            $wallet->credit(
                $withdrawal->amount,
                'Withdrawal request cancelled - ' . $withdrawal->request_number,
                null
            );

            // Update status
            $withdrawal->update(['status' => 'rejected', 'rejection_reason' => 'Cancelled by user']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request cancelled',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel request',
            ], 500);
        }
    }

    /**
     * Admin: Get all withdrawal requests
     */
    public function adminIndex(Request $request)
    {
        $query = WithdrawalRequest::with(['owner', 'reviewer']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $withdrawals = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
        ]);
    }

    /**
     * Admin: Approve withdrawal request
     */
    public function approve(Request $request, WithdrawalRequest $withdrawal)
    {
        if (!$withdrawal->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be approved',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $withdrawal->approve(Auth::id(), $request->admin_notes);

        // Notify user
        $this->notification->sendWalletNotification($withdrawal->owner, [
            'type' => 'Withdrawal Approved',
            'amount' => $withdrawal->amount,
            'balance' => $withdrawal->wallet->balance,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request approved',
            'data' => $withdrawal->fresh(),
        ]);
    }

    /**
     * Admin: Reject withdrawal request
     */
    public function reject(Request $request, WithdrawalRequest $withdrawal)
    {
        if (!$withdrawal->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be rejected',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Refund to wallet
            $wallet = $withdrawal->wallet;
            $wallet->credit(
                $withdrawal->amount,
                'Withdrawal request rejected - ' . $withdrawal->request_number,
                null
            );

            // Update status
            $withdrawal->reject(Auth::id(), $request->rejection_reason, $request->admin_notes);

            DB::commit();

            // Notify user
            $this->notification->sendWalletNotification($withdrawal->owner, [
                'type' => 'Withdrawal Rejected',
                'amount' => $withdrawal->amount,
                'balance' => $wallet->fresh()->balance,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request rejected',
                'data' => $withdrawal->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject request',
            ], 500);
        }
    }

    /**
     * Admin: Process payout via Razorpay
     */
    public function processPayout(Request $request, WithdrawalRequest $withdrawal)
    {
        if (!$withdrawal->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved requests can be processed',
            ], 400);
        }

        try {
            // Create or get fund account
            $fundAccountId = $withdrawal->razorpay_fund_account_id;

            if (!$fundAccountId) {
                // Create contact first
                $contact = $this->razorpay->createContact(
                    $withdrawal->owner->name,
                    $withdrawal->owner->email,
                    $withdrawal->owner->phone,
                    'vendor'
                );

                // Create fund account
                $fundAccount = $this->razorpay->createFundAccount(
                    $contact['id'],
                    $withdrawal->bank_account_name,
                    $withdrawal->bank_ifsc,
                    $withdrawal->bank_account_number
                );

                $fundAccountId = $fundAccount['id'];
                $withdrawal->update(['razorpay_fund_account_id' => $fundAccountId]);
            }

            // Mark as processing
            $withdrawal->markAsProcessing();

            // Create payout
            $payout = $this->razorpay->createPayout(
                $withdrawal->net_amount,
                $fundAccountId,
                'payout',
                $withdrawal->request_number
            );

            // Update payout ID
            $withdrawal->update(['razorpay_payout_id' => $payout['id']]);

            return response()->json([
                'success' => true,
                'message' => 'Payout initiated successfully',
                'data' => [
                    'withdrawal' => $withdrawal->fresh(),
                    'payout' => $payout,
                ],
            ]);

        } catch (\Exception $e) {
            $withdrawal->markAsFailed($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Mark as completed (manual confirmation)
     */
    public function markCompleted(Request $request, WithdrawalRequest $withdrawal)
    {
        $validator = Validator::make($request->all(), [
            'utr_number' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!in_array($withdrawal->status, ['approved', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status for completion',
            ], 400);
        }

        $withdrawal->markAsCompleted($request->utr_number);

        // Notify user
        $this->notification->sendWalletNotification($withdrawal->owner, [
            'type' => 'Withdrawal Completed',
            'amount' => $withdrawal->amount,
            'balance' => $withdrawal->wallet->balance,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal marked as completed',
            'data' => $withdrawal->fresh(),
        ]);
    }

    /**
     * Get withdrawal statistics for admin
     */
    public function stats(Request $request)
    {
        $stats = [
            'total_pending' => WithdrawalRequest::pending()->sum('amount'),
            'total_processing' => WithdrawalRequest::processing()->sum('amount'),
            'total_completed' => WithdrawalRequest::completed()->sum('amount'),
            'pending_count' => WithdrawalRequest::pending()->count(),
            'processing_count' => WithdrawalRequest::processing()->count(),
            'completed_count' => WithdrawalRequest::completed()->count(),
            'today_withdrawals' => WithdrawalRequest::whereDate('created_at', today())->sum('amount'),
            'month_withdrawals' => WithdrawalRequest::whereMonth('created_at', now()->month)->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
