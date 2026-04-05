<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\CommissionService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    protected RazorpayService $razorpay;

    public function __construct(RazorpayService $razorpay)
    {
        $this->razorpay = $razorpay;
    }

    /**
     * Get user's wallet
     */
    public function getWallet(Request $request)
    {
        $wallet = Wallet::forOwner($request->user())->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $wallet,
        ]);
    }

    /**
     * Get wallet transactions
     */
    public function getTransactions(Request $request)
    {
        $wallet = Wallet::forOwner($request->user())->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Create a payment order for wallet top-up
     */
    public function createTopUpOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $wallet = Wallet::firstOrCreate(
            ['owner_type' => $request->user()::class, 'owner_id' => $request->user()->id],
            [
                'balance' => 0,
                'currency' => 'INR',
                'status' => 'active',
            ]
        );

        if (!$wallet->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet is not active',
            ], 400);
        }

        try {
            $receipt = 'WALLET_' . $request->user()->id . '_' . Str::random(8);
            
            $order = $this->razorpay->createOrder(
                $request->amount,
                $receipt,
                [
                    'user_id' => $request->user()->id,
                    'wallet_id' => $wallet->id,
                    'purpose' => 'wallet_topup',
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'order_id' => $order['id'],
                    'amount' => $request->amount,
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'razorpay_config' => $this->razorpay->getClientConfig(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify and complete wallet top-up after payment
     */
    public function verifyTopUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify signature
        $isValid = $this->razorpay->verifySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment signature',
            ], 400);
        }

        try {
            $payment = $this->razorpay->fetchPayment($request->razorpay_payment_id);
            $order = $this->razorpay->fetchOrder($request->razorpay_order_id);

            if (($payment['order_id'] ?? null) !== $request->razorpay_order_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment does not match order',
                ], 400);
            }

            if (($payment['status'] ?? null) !== 'captured') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is not captured',
                ], 400);
            }

            $amount = ((int) ($payment['amount'] ?? 0)) / 100;
            $orderAmount = ((int) ($order['amount'] ?? 0)) / 100;

            if ($amount <= 0 || $amount !== $orderAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount mismatch',
                ], 400);
            }

            $wallet = DB::transaction(function () use ($request, $amount) {
                $wallet = Wallet::query()
                    ->where('owner_type', $request->user()::class)
                    ->where('owner_id', $request->user()->id)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    return null;
                }

                // Idempotency guard.
                $existingTransaction = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('reference_id', $request->razorpay_payment_id)
                    ->first();

                if ($existingTransaction) {
                    return $wallet->fresh();
                }

                $wallet->credit(
                    $amount,
                    'Wallet top-up via Razorpay',
                    $request->razorpay_payment_id
                );

                return $wallet->fresh();
            });

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Wallet topped up successfully',
                'data' => [
                    'wallet' => $wallet,
                    'amount' => $amount,
                    'payment_id' => $request->razorpay_payment_id,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Wallet top-up verification failed', [
                'user_id' => $request->user()->id,
                'order_id' => $request->razorpay_order_id,
                'payment_id' => $request->razorpay_payment_id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
            ], 500);
        }
    }

    /**
     * Top up wallet (legacy method - kept for backward compatibility)
     */
    public function topUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|in:card,upi,bank_transfer,razorpay',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $wallet = Wallet::forOwner($request->user())->first();

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

        // For Razorpay, redirect to create order
        if ($request->payment_method === 'razorpay') {
            return $this->createTopUpOrder($request);
        }

        // Legacy: Simulate payment processing for other methods
        $paymentSuccess = $this->processPayment($request->amount, $request->payment_method);

        if (!$paymentSuccess) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
            ], 400);
        }

        $wallet->credit(
            $request->amount,
            'Wallet top-up via ' . $request->payment_method,
            null
        );

        return response()->json([
            'success' => true,
            'message' => 'Wallet topped up successfully',
        ]);
    }

    /**
     * Withdraw from wallet
     */
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'bank_account' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $wallet = Wallet::forOwner($request->user())->first();

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

        if ($wallet->getBalance() < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance',
            ], 400);
        }

        // Process withdrawal
        $commissionService = new CommissionService();
        $success = $commissionService->processDriverWithdrawal(
            $request->user()->id,
            $request->amount,
            'Withdrawal to bank account: ' . $request->bank_account
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal failed',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal processed successfully',
        ]);
    }

    /**
     * Get wallet statistics
     */
    public function getStats(Request $request)
    {
        $wallet = Wallet::forOwner($request->user())->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $commissionService = new CommissionService();
        $stats = $request->user()->isDriver()
            ? $commissionService->getDriverCommissionStats($request->user()->id)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_balance' => $wallet->getBalance(),
                'commission_stats' => $stats,
            ],
        ]);
    }

    /**
     * Get Razorpay client configuration
     */
    public function getRazorpayConfig(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->razorpay->getClientConfig(),
        ]);
    }

    /**
     * Simulate payment processing (legacy)
     */
    private function processPayment(float $amount, string $method): bool
    {
        // Simulate payment gateway integration
        // In real implementation, this would integrate with actual payment providers
        sleep(1); // Simulate processing time
        
        // Simulate 95% success rate
        return rand(1, 100) <= 95;
    }
}
