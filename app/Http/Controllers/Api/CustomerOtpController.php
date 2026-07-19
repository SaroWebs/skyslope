<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Wallet;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CustomerOtpController extends Controller
{
    public function __construct(protected OtpService $otpService) {}

    /**
     * Send OTP to phone number for login/register as customer.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:20',
            'action' => 'in:login,register',
        ]);

        $phone = $request->input('phone');
        $action = $request->input('action', 'login');

        $result = $this->otpService->send($phone, 'customer');
        $customer = Customer::where('phone', $phone)->first();
        $result['customer_exists'] = (bool) $customer;
        $result['next_step'] = $customer ? 'verify_login' : 'verify_registration';

        return response()->json($result, $result['success'] ? 200 : ($result['status_code'] ?? 429));
    }

    /**
     * Verify OTP and issue Sanctum token.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:20',
            'code' => 'required|string|size:6',
            'action' => 'in:login,register',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $phone = $request->input('phone');
        $code = $request->input('code');
        $action = $request->input('action', 'login');

        $result = $this->otpService->verify($phone, $code, 'customer');

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        $customer = Customer::where('phone', $phone)->first();

        if (! $customer) {
            return response()->json([
                'success' => true,
                'message' => 'OTP verified. Complete your customer profile to register.',
                'requires_registration' => true,
                'registration_token' => $this->registrationToken($phone),
            ]);
        }

        $token = $this->issueToken($customer);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'requires_registration' => false,
            'token' => $token,
            'customer' => $customer,
        ]);
    }

    public function completeRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:10|max:20',
            'registration_token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        if (! $this->validRegistrationToken($validated['registration_token'], $validated['phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Registration session expired. Please request a new OTP.',
            ], 422);
        }

        $customer = Customer::firstOrCreate(
            ['phone' => $validated['phone']],
            [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'password' => bcrypt(str()->random(32)),
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (! $customer->wasRecentlyCreated) {
            $customer->forceFill([
                'name' => $customer->name ?: $validated['name'],
                'email' => $customer->email ?: ($validated['email'] ?? null),
                'phone_verified_at' => $customer->phone_verified_at ?? now(),
            ])->save();
        }

        Wallet::firstOrCreate([
            'owner_type' => get_class($customer),
            'owner_id' => $customer->id,
        ], [
            'balance' => 0,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $token = $this->issueToken($customer);

        return response()->json([
            'success' => true,
            'message' => $customer->wasRecentlyCreated ? 'Registration successful.' : 'Customer already exists. Logged in successfully.',
            'requires_registration' => false,
            'token' => $token,
            'customer' => $customer,
        ], $customer->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Get current authenticated customer.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'customer' => $request->user(),
        ]);
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    private function issueToken(Customer $customer): string
    {
        $customer->tokens()->delete();

        return $customer->createToken(
            'customer-app',
            ['*'],
            now()->addMinutes((int) config('services.otp.token_expiration_minutes', 60 * 24 * 30))
        )->plainTextToken;
    }

    private function registrationToken(string $phone): string
    {
        return Crypt::encryptString(json_encode([
            'phone' => $phone,
            'expires_at' => now()->addMinutes(15)->timestamp,
        ]));
    }

    private function validRegistrationToken(string $token, string $phone): bool
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }

        return ($payload['phone'] ?? null) === $phone
            && (int) ($payload['expires_at'] ?? 0) >= now()->timestamp;
    }
}
