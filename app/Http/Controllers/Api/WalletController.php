<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRazorpayWebhookEvent;
use App\Models\RazorpayWebhookEvent;
use App\Models\RideBooking;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\CommissionService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        $wallet = $this->walletFor($request);

        $walletData = $wallet->toArray();
        if ($request->user()->isDriver()) {
            $walletData = array_merge($walletData, app(\App\Services\DriverWithdrawalPolicy::class)->summary($wallet));
            $earnings = RideBooking::where('driver_id', $request->user()->id)
                ->where('status', 'completed');
            $walletData['total_earnings'] = (float) (clone $earnings)->sum('driver_share');
            $walletData['weekly_earnings'] = (float) (clone $earnings)
                ->where('completed_at', '>=', now()->subWeek())
                ->sum('driver_share');
        }

        return response()->json([
            'success' => true,
            'data' => $walletData,
        ]);
    }

    /**
     * Get wallet transactions
     */
    public function getTransactions(Request $request)
    {
        $wallet = $this->walletFor($request);

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

        if (! $wallet->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet is not active',
            ], 400);
        }

        try {
            $receipt = 'WALLET_'.$request->user()->id.'_'.Str::random(8);

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
                'message' => 'Failed to create payment order: '.$e->getMessage(),
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
            'idempotency_key' => 'nullable|string|max:255',
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

        if (! $isValid) {
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

            $wallet = Wallet::query()
                ->where('owner_type', $request->user()::class)
                ->where('owner_id', $request->user()->id)
                ->first();

            if (! $wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found',
                ], 404);
            }

            $idempotencyKey = $request->input('idempotency_key')
                ?: "wallet_topup:{$request->razorpay_order_id}:{$request->razorpay_payment_id}";

            $transaction = $wallet->credit(
                $amount,
                'Wallet top-up via Razorpay',
                'razorpay_payment',
                $request->razorpay_payment_id,
                $idempotencyKey
            );

            return response()->json([
                'success' => true,
                'message' => 'Wallet topped up successfully',
                'data' => [
                    'wallet' => $wallet->fresh(),
                    'amount' => $amount,
                    'payment_id' => $request->razorpay_payment_id,
                    'transaction_id' => $transaction->id,
                    'idempotency_key' => $transaction->idempotency_key,
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

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        if (! $wallet->isActive()) {
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
        $commissionService = new CommissionService;
        $success = $commissionService->processDriverWithdrawal(
            $request->user()->id,
            $request->amount,
            'Withdrawal to bank account: '.$request->bank_account
        );

        if (! $success) {
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

        if (! $wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $commissionService = new CommissionService;
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

    public function handleRazorpayWebhook(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        if (! $signature || ! $this->razorpay->verifyWebhook($payload, $signature)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 400);
        }

        $data = json_decode($payload, true) ?: [];
        $eventType = $data['event'] ?? 'unknown';

        // Idempotency key: prefer Razorpay's delivery id header, else derive one
        // deterministically from the event + entity so redeliveries still dedupe.
        $eventId = $request->header('X-Razorpay-Event-Id')
            ?: $this->deriveWebhookEventId($eventType, $data);

        // Persist the raw event before any side effect (§8.7). Unique on
        // (provider, event_id) so a redelivery resolves to the same row.
        $event = RazorpayWebhookEvent::firstOrCreate(
            ['provider' => 'razorpay', 'event_id' => $eventId],
            [
                'event_type' => $eventType,
                'payload' => $data,
                'signature' => $signature,
                'status' => RazorpayWebhookEvent::STATUS_RECEIVED,
            ]
        );

        // Only kick off processing on first receipt; redeliveries are a no-op.
        if ($event->wasRecentlyCreated) {
            ProcessRazorpayWebhookEvent::dispatch($event->id);
        }

        // Return 2xx fast — processing is async (queued) in production.
        return response()->json([
            'success' => true,
            'message' => 'Webhook received.',
            'event' => $eventType,
        ], 200);
    }

    /** Deterministic dedup key when Razorpay omits its X-Razorpay-Event-Id header. */
    private function deriveWebhookEventId(string $eventType, array $data): string
    {
        $entityId = data_get($data, 'payload.payment.entity.id')
            ?? data_get($data, 'payload.refund.entity.id')
            ?? data_get($data, 'payload.payout.entity.id')
            ?? data_get($data, 'payload.order.entity.id')
            ?? '';

        return $eventType.':'.$entityId.':'.substr(hash('sha256', json_encode($data)), 0, 16);
    }

    private function walletFor(Request $request): Wallet
    {
        $owner = $request->user();

        return Wallet::firstOrCreate([
            'owner_type' => $owner::class,
            'owner_id' => $owner->id,
        ], [
            'balance' => 0,
            'currency' => 'INR',
            'is_active' => true,
        ]);
    }
}
