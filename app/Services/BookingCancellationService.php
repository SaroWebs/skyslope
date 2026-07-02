<?php

namespace App\Services;

use App\Models\BookingRefund;
use App\Models\CarRental;
use App\Models\RideBooking;
use App\Models\TourBooking;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BookingCancellationService
{
    public function cancel(
        Model $booking,
        string $serviceType,
        ?string $reason = null,
        ?int $processedBy = null
    ): ?BookingRefund {
        return DB::transaction(function () use ($booking, $serviceType, $reason, $processedBy) {
            $booking->refresh();

            if ($booking->status === 'cancelled') {
                return $booking->refunds()->latest()->first();
            }

            $totalAmount = $this->bookingTotal($booking);
            $isPaid = $booking->payment_status === 'paid';
            $cancellationFee = $isPaid ? $this->calculateFee($booking, $serviceType) : 0.0;
            $refundAmount = $isPaid ? max(0, $totalAmount - $cancellationFee) : 0.0;

            $booking->forceFill([
                'status' => 'cancelled',
                'cancellation_reason' => $reason ?: $booking->cancellation_reason,
                'cancelled_at' => now(),
                'cancellation_fee' => $cancellationFee,
                'refund_amount' => $refundAmount,
            ])->save();

            if (!$isPaid || $refundAmount <= 0) {
                return null;
            }

            $refund = $booking->refunds()->create([
                'customer_id' => $booking->customer_id,
                'amount' => $refundAmount,
                'cancellation_fee' => $cancellationFee,
                'method' => $booking->payment_method ?: 'manual',
                'status' => $booking->payment_method === 'wallet' ? 'processed' : 'pending',
                'reason' => $reason,
                'processed_at' => $booking->payment_method === 'wallet' ? now() : null,
                'processed_by' => $booking->payment_method === 'wallet' ? $processedBy : null,
            ]);

            if ($booking->payment_method === 'wallet') {
                $transaction = $this->refundToWallet($booking, $refundAmount, $serviceType);
                $refund->update(['wallet_transaction_id' => $transaction->id]);
                $booking->forceFill([
                    'payment_status' => 'refunded',
                    'refunded_at' => now(),
                ])->save();
            }

            return $refund->fresh();
        });
    }

    public function processRefund(BookingRefund $refund, ?int $processedBy = null): BookingRefund
    {
        return DB::transaction(function () use ($refund, $processedBy) {
            $refund->refresh();

            if ($refund->status === 'processed') {
                return $refund;
            }

            $booking = $refund->refundable;
            if (!$booking) {
                $refund->update(['status' => 'failed']);
                return $refund->fresh();
            }

            $transaction = $this->refundToWallet($booking, (float) $refund->amount, $this->serviceTypeFor($booking));

            $refund->update([
                'wallet_transaction_id' => $transaction->id,
                'method' => 'wallet',
                'status' => 'processed',
                'processed_at' => now(),
                'processed_by' => $processedBy,
            ]);

            $booking->forceFill([
                'payment_status' => 'refunded',
                'refunded_at' => now(),
            ])->save();

            return $refund->fresh();
        });
    }

    public function calculateFee(Model $booking, string $serviceType): float
    {
        $totalAmount = $this->bookingTotal($booking);
        if ($totalAmount <= 0) {
            return 0.0;
        }

        if ($this->isAlreadyActive($booking, $serviceType)) {
            return round($totalAmount * 0.2, 2);
        }

        $startAt = $this->bookingStartAt($booking);
        if ($startAt && now()->diffInHours($startAt, false) < 24) {
            return round($totalAmount * 0.1, 2);
        }

        return 0.0;
    }

    private function refundToWallet(Model $booking, float $amount, string $serviceType)
    {
        $wallet = Wallet::query()->firstOrCreate([
            'owner_type' => get_class($booking->customer),
            'owner_id' => $booking->customer_id,
        ], [
            'balance' => 0,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        return $wallet->credit(
            $amount,
            ucfirst($serviceType) . ' booking refund',
            $this->serviceTypeFor($booking),
            (string) $booking->id
        );
    }

    private function bookingTotal(Model $booking): float
    {
        return match (true) {
            $booking instanceof RideBooking => (float) $booking->total_fare,
            $booking instanceof TourBooking => (float) $booking->total_price,
            $booking instanceof CarRental => (float) $booking->total_price,
            default => 0.0,
        };
    }

    private function bookingStartAt(Model $booking): mixed
    {
        return match (true) {
            $booking instanceof RideBooking => $booking->scheduled_at,
            $booking instanceof TourBooking => $booking->travel_date?->startOfDay(),
            $booking instanceof CarRental => $booking->start_date?->startOfDay(),
            default => null,
        };
    }

    private function isAlreadyActive(Model $booking, string $serviceType): bool
    {
        return match ($serviceType) {
            BookingStatusService::RIDE => in_array($booking->status, ['driver_arriving', 'pickup', 'in_transit'], true),
            BookingStatusService::TOUR => $booking->status === 'in_progress',
            BookingStatusService::RENTAL => $booking->status === 'in_progress',
            default => false,
        };
    }

    private function serviceTypeFor(Model $booking): string
    {
        return match (true) {
            $booking instanceof RideBooking => BookingStatusService::RIDE,
            $booking instanceof TourBooking => BookingStatusService::TOUR,
            $booking instanceof CarRental => BookingStatusService::RENTAL,
            default => 'booking',
        };
    }
}
