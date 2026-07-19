<?php

namespace App\Http\Resources\CustomerApp;

use App\Models\CarRental;
use App\Models\RideBooking;
use App\Models\TourBooking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingReceiptResource extends JsonResource
{
    public function __construct(
        RideBooking|TourBooking|CarRental $resource,
        protected string $serviceType,
        protected mixed $refund = null
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $title = match ($this->serviceType) {
            'ride' => $this->pickup_location ?? 'Ride booking',
            'tour' => $this->tour?->title ?? 'Tour booking',
            'rental' => $this->carCategory?->name ?? 'Car rental',
            default => 'Booking',
        };

        $amount = match ($this->serviceType) {
            'ride' => $this->total_fare,
            default => $this->total_price,
        };

        $travelDate = match ($this->serviceType) {
            'ride' => $this->scheduled_at,
            'tour' => $this->travel_date,
            'rental' => $this->start_date,
            default => $this->created_at,
        };

        return [
            'id' => $this->id,
            'service_type' => $this->serviceType,
            'title' => $title,
            'receipt_number' => $this->booking_number ?? (string) $this->id,
            'booking_number' => $this->booking_number ?? (string) $this->id,
            'transaction_id' => $this->transaction_id ?? $this->payment_reference ?? null,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'amount' => (float) $amount,
            'coupon_code' => $this->coupon_code ?? null,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'currency' => 'INR',
            'travel_date' => optional($travelDate)->toDateString(),
            'refund_status' => $this->refund->status
                ?? $this->refund_status
                ?? ($this->refunded_at ? 'processed' : 'not-applicable'),
            'support_actions' => [
                'can_contact_support' => true,
                'can_cancel' => ! in_array($this->status, ['completed', 'cancelled'], true),
                'can_review' => $this->status === 'completed',
            ],
        ];
    }
}
