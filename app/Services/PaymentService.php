<?php

namespace App\Services;

use App\Models\CarRental;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\Payout;
use App\Models\RideBooking;
use App\Models\TourBooking;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the payment/order/payout state machine (SKY-MRD-001 §6.1) and its
 * ledger consequences. This is the seam the Razorpay webhook processor drives
 * (item #3): it records captures/failures/payouts idempotently and mirrors the
 * booking `payment_status` for legacy clients.
 */
class PaymentService
{
    public function __construct(private LedgerService $ledger) {}

    /** Create our record of an intent to collect money. */
    public function createOrder(int $amountMinor, ?Model $payable = null, ?Model $owner = null, array $attributes = []): PaymentOrder
    {
        return PaymentOrder::create([
            'order_number' => $attributes['order_number'] ?? $this->generateNumber('ORD'),
            'provider' => $attributes['provider'] ?? 'razorpay',
            'provider_order_id' => $attributes['provider_order_id'] ?? null,
            'payable_type' => $payable ? $payable::class : null,
            'payable_id' => $payable?->getKey(),
            'owner_type' => $owner ? $owner::class : null,
            'owner_id' => $owner?->getKey(),
            'amount_minor' => $amountMinor,
            'currency' => $attributes['currency'] ?? 'INR',
            'status' => PaymentOrder::STATUS_CREATED,
            'receipt' => $attributes['receipt'] ?? null,
            'notes' => $attributes['notes'] ?? null,
            'idempotency_key' => $attributes['idempotency_key'] ?? null,
        ]);
    }

    /**
     * Record a captured payment. Idempotent on provider_payment_id: a repeat
     * call for an already-captured payment returns the existing row and posts
     * nothing new. For booking payables it posts a gateway → booking-revenue
     * ledger pair and mirrors payment_status='paid' onto the booking.
     */
    public function recordCapturedPayment(array $data): Payment
    {
        $providerPaymentId = $data['provider_payment_id'] ?? null;
        $amountMinor = (int) ($data['amount_minor'] ?? 0);
        $payable = $data['payable'] ?? null;
        $owner = $data['owner'] ?? null;
        $order = $data['order'] ?? null;

        return DB::transaction(function () use ($data, $providerPaymentId, $amountMinor, $payable, $owner, $order) {
            $payment = $providerPaymentId
                ? Payment::where('provider_payment_id', $providerPaymentId)->lockForUpdate()->first()
                : null;

            if ($payment && $payment->isCaptured()) {
                return $payment; // idempotent replay
            }

            $payment ??= new Payment();
            $payment->fill([
                'payment_order_id' => $order?->id ?? $payment->payment_order_id,
                'provider' => $data['provider'] ?? 'razorpay',
                'provider_payment_id' => $providerPaymentId,
                'provider_order_id' => $data['provider_order_id'] ?? $order?->provider_order_id,
                'payable_type' => $payable ? $payable::class : $payment->payable_type,
                'payable_id' => $payable?->getKey() ?? $payment->payable_id,
                'owner_type' => $owner ? $owner::class : $payment->owner_type,
                'owner_id' => $owner?->getKey() ?? $payment->owner_id,
                'amount_minor' => $amountMinor,
                'currency' => $data['currency'] ?? 'INR',
                'method' => $data['method'] ?? $payment->method,
                'notes' => $data['notes'] ?? $payment->notes,
            ]);
            $payment->markCaptured();

            $order?->markPaid();

            if ($this->isBooking($payable)) {
                $this->mirrorBookingPaid($payable, $data['method'] ?? null);
                $this->ledger->post(
                    'system:gateway_clearing',
                    'system:booking_revenue',
                    $amountMinor,
                    [
                        'reference_type' => $payable::class,
                        'reference_id' => (string) $payable->getKey(),
                        'description' => 'Gateway capture for '.class_basename($payable).' #'.$payable->getKey(),
                        'currency' => $data['currency'] ?? 'INR',
                    ]
                );
            }

            return $payment;
        });
    }

    /** Record a failed payment attempt; never regresses a captured payment. */
    public function markPaymentFailed(array $data): Payment
    {
        $providerPaymentId = $data['provider_payment_id'] ?? null;
        $order = $data['order'] ?? null;

        return DB::transaction(function () use ($data, $providerPaymentId, $order) {
            $payment = $providerPaymentId
                ? Payment::where('provider_payment_id', $providerPaymentId)->lockForUpdate()->first()
                : null;

            if (! $payment) {
                $payable = $data['payable'] ?? null;
                $owner = $data['owner'] ?? null;
                $payment = new Payment();
                $payment->fill([
                    'payment_order_id' => $order?->id,
                    'provider' => $data['provider'] ?? 'razorpay',
                    'provider_payment_id' => $providerPaymentId,
                    'provider_order_id' => $data['provider_order_id'] ?? $order?->provider_order_id,
                    'payable_type' => $payable ? $payable::class : null,
                    'payable_id' => $payable?->getKey(),
                    'owner_type' => $owner ? $owner::class : null,
                    'owner_id' => $owner?->getKey(),
                    'amount_minor' => (int) ($data['amount_minor'] ?? 0),
                    'currency' => $data['currency'] ?? 'INR',
                    'status' => Payment::STATUS_CREATED,
                ]);
                $payment->save();
            }

            $payment->markFailed($data['error_code'] ?? null, $data['error_description'] ?? null);
            $order?->markFailed();

            return $payment;
        });
    }

    /** Create a payout record for a driver withdrawal. */
    public function createPayout(WithdrawalRequest $withdrawal, int $amountMinor, array $attributes = []): Payout
    {
        return Payout::create([
            'payout_number' => $attributes['payout_number'] ?? $this->generateNumber('PO'),
            'provider' => $attributes['provider'] ?? 'razorpay',
            'withdrawal_request_id' => $withdrawal->id,
            'owner_type' => $withdrawal->owner_type,
            'owner_id' => $withdrawal->owner_id,
            'amount_minor' => $amountMinor,
            'currency' => $attributes['currency'] ?? 'INR',
            'status' => Payout::STATUS_CREATED,
            'fund_account_id' => $attributes['fund_account_id'] ?? null,
            'idempotency_key' => $attributes['idempotency_key'] ?? null,
        ]);
    }

    /** Mark a payout processed and move funds from payout clearing to bank settlement. */
    public function markPayoutProcessed(Payout $payout, ?string $utr = null): Payout
    {
        return DB::transaction(function () use ($payout, $utr) {
            if ($payout->isProcessed()) {
                return $payout; // idempotent
            }

            $payout->markProcessed($utr);

            $this->ledger->post(
                'system:payout_clearing',
                'system:bank_settlement',
                (int) $payout->amount_minor,
                [
                    'reference_type' => 'payout',
                    'reference_id' => (string) $payout->id,
                    'description' => 'Driver payout '.$payout->payout_number,
                    'currency' => $payout->currency,
                ]
            );

            $payout->withdrawalRequest?->markAsCompleted($utr ?? '');

            return $payout;
        });
    }

    private function isBooking(?Model $payable): bool
    {
        return $payable instanceof RideBooking
            || $payable instanceof TourBooking
            || $payable instanceof CarRental;
    }

    private function mirrorBookingPaid(Model $booking, ?string $method): void
    {
        $attributes = ['payment_status' => 'paid'];
        if ($method) {
            $attributes['payment_method'] = $method;
        }

        $booking->forceFill($attributes)->save();
    }

    private function generateNumber(string $prefix): string
    {
        return $prefix.'_'.strtoupper(Str::random(12));
    }
}
