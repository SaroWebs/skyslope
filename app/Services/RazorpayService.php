<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class RazorpayService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $baseUrl;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.razorpay.key');
        $this->apiSecret = config('services.razorpay.secret');
        $this->webhookSecret = config('services.razorpay.webhook_secret');
        $this->baseUrl = 'https://api.razorpay.com/v1';
    }

    /**
     * Create a Razorpay order
     *
     * @param float $amount Amount in INR
     * @param string $receipt Unique receipt ID
     * @param array $notes Additional notes
     * @return array
     * @throws Exception
     */
    public function createOrder(float $amount, string $receipt, array $notes = []): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->baseUrl}/orders", [
                    'amount' => (int) ($amount * 100), // Convert to paise
                    'currency' => 'INR',
                    'receipt' => $receipt,
                    'notes' => array_merge([
                        'purpose' => 'wallet_topup',
                        'created_at' => now()->toIso8601String(),
                    ], $notes),
                    'payment_capture' => 1, // Auto capture
                ]);

            if (!$response->successful()) {
                Log::error('Razorpay order creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to create payment order');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay order creation exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify payment signature
     *
     * @param string $orderId
     * @param string $paymentId
     * @param string $signature
     * @return bool
     */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $data = $orderId . '|' . $paymentId;
        $expectedSignature = hash_hmac('sha256', $data, $this->apiSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Fetch payment details
     *
     * @param string $paymentId
     * @return array
     * @throws Exception
     */
    public function fetchPayment(string $paymentId): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->baseUrl}/payments/{$paymentId}");

            if (!$response->successful()) {
                throw new Exception('Failed to fetch payment details');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay fetch payment exception', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Fetch order details
     *
     * @param string $orderId
     * @return array
     * @throws Exception
     */
    public function fetchOrder(string $orderId): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->baseUrl}/orders/{$orderId}");

            if (!$response->successful()) {
                throw new Exception('Failed to fetch order details');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay fetch order exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process refund
     *
     * @param string $paymentId
     * @param float $amount Amount in INR (optional, defaults to full refund)
     * @param string $reason
     * @return array
     * @throws Exception
     */
    public function refund(string $paymentId, ?float $amount = null, string $reason = ''): array
    {
        try {
            $payload = [
                'notes' => [
                    'reason' => $reason,
                    'refunded_at' => now()->toIso8601String(),
                ],
            ];

            if ($amount !== null) {
                $payload['amount'] = (int) ($amount * 100); // Convert to paise
            }

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->baseUrl}/payments/{$paymentId}/refund", $payload);

            if (!$response->successful()) {
                throw new Exception('Failed to process refund');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay refund exception', [
                'payment_id' => $paymentId,
                'amount' => $amount,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create a payout to bank account (for driver withdrawals)
     *
     * @param float $amount Amount in INR
     * @param string $fundAccountId Razorpay fund account ID
     * @param string $purpose Purpose of payout
     * @param string $referenceId Reference ID for tracking
     * @return array
     * @throws Exception
     */
    public function createPayout(float $amount, string $fundAccountId, string $purpose, string $referenceId): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->baseUrl}/payouts", [
                    'account_number' => config('services.razorpay.merchant_account'),
                    'fund_account_id' => $fundAccountId,
                    'amount' => (int) ($amount * 100), // Convert to paise
                    'currency' => 'INR',
                    'mode' => 'IMPS',
                    'purpose' => $purpose,
                    'queue_if_low_balance' => true,
                    'reference_id' => $referenceId,
                    'narration' => 'SkySlope Driver Payout',
                ]);

            if (!$response->successful()) {
                Log::error('Razorpay payout creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to create payout');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay payout exception', [
                'message' => $e->getMessage(),
                'amount' => $amount,
                'fund_account_id' => $fundAccountId,
            ]);
            throw $e;
        }
    }

    /**
     * Create a fund account for bank transfers
     *
     * @param string $contactId Razorpay contact ID
     * @param string $accountName Bank account holder name
     * @param string $ifsc Bank IFSC code
     * @param string $accountNumber Bank account number
     * @return array
     * @throws Exception
     */
    public function createFundAccount(string $contactId, string $accountName, string $ifsc, string $accountNumber): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->baseUrl}/fund_accounts", [
                    'contact_id' => $contactId,
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name' => $accountName,
                        'ifsc' => $ifsc,
                        'account_number' => $accountNumber,
                    ],
                ]);

            if (!$response->successful()) {
                throw new Exception('Failed to create fund account');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay fund account creation exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a contact for payouts
     *
     * @param string $name Contact name
     * @param string $email Contact email
     * @param string $phone Contact phone
     * @param string $type Contact type (vendor/employee/customer)
     * @return array
     * @throws Exception
     */
    public function createContact(string $name, string $email, string $phone, string $type = 'vendor'): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->baseUrl}/contacts", [
                    'name' => $name,
                    'email' => $email,
                    'contact' => $phone,
                    'type' => $type,
                    'reference_id' => 'contact_' . uniqid(),
                ]);

            if (!$response->successful()) {
                throw new Exception('Failed to create contact');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Razorpay contact creation exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get client configuration for frontend
     *
     * @return array
     */
    public function getClientConfig(): array
    {
        return [
            'key' => $this->apiKey,
            'currency' => 'INR',
            'name' => config('app.name', 'SkySlope'),
            'image' => asset('logo.svg'),
            'prefill' => [
                'name' => auth()->user()?->name ?? '',
                'email' => auth()->user()?->email ?? '',
                'contact' => auth()->user()?->phone ?? '',
            ],
            'theme' => [
                'color' => '#F97316',
            ],
        ];
    }
}
