<?php

namespace App\Support\Pricing;

class PricingService
{
    public function applyFees(float $subtotal, array $context = []): array
    {
        $subtotal = max(0, round($subtotal, 2));
        $tax = round($subtotal * (float) setting('fees.tax_percent', 0.0, $context), 2);
        $serviceFee = round(
            $subtotal * (float) setting('fees.service_fee_percent', 0.0, $context)
            + (float) setting('fees.service_fee_flat', 0.0, $context),
            2
        );

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service_fee' => $serviceFee,
            'total' => round($subtotal + $tax + $serviceFee, 2),
        ];
    }
}