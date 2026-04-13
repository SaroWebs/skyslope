<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * OTP validity in minutes.
     */
    protected int $expiryMinutes = 5;

    /**
     * Throttle: minimum seconds between OTP sends to the same phone.
     */
    protected int $throttleSeconds = 60;

    /**
     * Generate and send an OTP to the given phone number with a specific account type.
     */
    public function send(string $phone, string $type): array
    {
        // Throttle: check if an OTP was sent recently
        $recent = Otp::where('phone', $phone)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subSeconds($this->throttleSeconds))
            ->where('is_used', false)
            ->first();

        if ($recent) {
            return [
                'success' => false,
                'message' => 'Please wait before requesting another OTP.',
                'retry_after' => $this->throttleSeconds,
            ];
        }

        // Invalidate any previous unused OTPs for this phone+type
        Otp::where('phone', $phone)
            ->where('type', $type)
            ->where('is_used', false)
            ->delete();

        // Generate a 6-digit code
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $otp = Otp::create([
            'phone' => $phone,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes($this->expiryMinutes),
        ]);

        // Send via Twilio (or log in dev)
        $this->dispatch($phone, $code);

        return [
            'success' => true,
            'message' => 'OTP sent successfully.',
            'expires_in' => $this->expiryMinutes * 60,
        ];
    }

    /**
     * Verify an OTP code for the given phone number and type.
     */
    public function verify(string $phone, string $code, string $type): array
    {
        $otp = Otp::where('phone', $phone)
            ->where('type', $type)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$otp) {
            return ['success' => false, 'message' => 'No OTP found. Please request a new one.'];
        }

        if ($otp->isExpired()) {
            $otp->delete();
            return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
        }

        if ($otp->code !== $code) {
            return ['success' => false, 'message' => "Invalid OTP code."];
        }

        // Mark as used
        $otp->update(['is_used' => true]);

        return ['success' => true, 'message' => 'OTP verified successfully.'];
    }

    /**
     * Dispatch the OTP via SMS (Twilio) or log in dev.
     */
    protected function dispatch(string $phone, string $code): void
    {
        $sid = config('services.twilio.sid', env('TWILIO_SID'));
        $token = config('services.twilio.token', env('TWILIO_TOKEN'));
        $from = config('services.twilio.from', env('TWILIO_FROM'));

        // In local/testing, just log it
        if (app()->environment('local', 'testing') || empty($sid) || $sid === 'your_twilio_account_sid') {
            Log::info("OTP for {$phone}: {$code}");
            return;
        }

        try {
            $client = new \Twilio\Rest\Client($sid, $token);
            $client->messages->create($phone, [
                'from' => $from,
                'body' => "Your Skyslope verification code is: {$code}. Valid for {$this->expiryMinutes} minutes.",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send OTP to {$phone}: " . $e->getMessage());
            // Don't throw — OTP is still saved, user can retry
        }
    }
}
