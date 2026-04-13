<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverOtpController extends Controller
{
    public function __construct(protected OtpService $otpService) {}

    /**
     * Send OTP to phone number for login.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:20',
        ]);

        $phone = $request->input('phone');

        // Only pre-approved existing drivers can log in
        $driver = Driver::where('phone', $phone)->first();
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'No driver account found with this phone number. Please contact admin.',
            ], 404);
        }

        $result = $this->otpService->send($phone, 'driver');

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
        ]);

        $phone = $request->input('phone');
        $code = $request->input('code');

        $result = $this->otpService->verify($phone, $code, 'driver');

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        $driver = Driver::where('phone', $phone)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver account not found.',
            ], 404);
        }

        if (!$driver->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Driver account is inactive. Please contact admin.',
            ], 403);
        }

        if (!$driver->is_approved) {
             return response()->json([
                 'success' => false,
                 'message' => 'Driver account is pending approval.',
             ], 403);
        }

        // Revoke old tokens
        $driver->tokens()->delete();

        // Issue new Sanctum token
        $token = $driver->createToken('driver-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'driver' => $driver,
        ]);
    }

    /**
     * Get current authenticated driver.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'driver' => $request->user()->load('vehicles'),
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
