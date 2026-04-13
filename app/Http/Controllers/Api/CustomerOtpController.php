<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        // For login, check if customer exists
        if ($action === 'login') {
            $customer = Customer::where('phone', $phone)->first();
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this phone number. Please register first.',
                ], 404);
            }
        }

        $result = $this->otpService->send($phone, 'customer');

        return response()->json($result, $result['success'] ? 200 : 429);
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
            'name' => 'required_if:action,register|string|max:255',
        ]);

        $phone = $request->input('phone');
        $code = $request->input('code');
        $action = $request->input('action', 'login');

        $result = $this->otpService->verify($phone, $code, 'customer');

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        // Find or create customer
        $customer = Customer::where('phone', $phone)->first();

        if (!$customer && $action === 'register') {
            $customer = Customer::create([
                'name' => $request->input('name', 'Customer'),
                'phone' => $phone,
                'email' => $request->input('email'),
                'password' => bcrypt(str()->random(32)), // random password, OTP is primary auth
            ]);
        }

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'No account found. Please register first.',
            ], 404);
        }

        // Revoke old tokens
        $customer->tokens()->delete();

        // Issue new Sanctum token
        $token = $customer->createToken('customer-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'customer' => $customer,
        ]);
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
}
