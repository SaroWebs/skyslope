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

        $phone = trim((string) $request->input('phone'));
        $driver = Driver::where('phone', $phone)->first();

        if (! $driver) {
            return response()->json([
                'success' => true,
                'driver_exists' => false,
                'message' => 'No driver account was found. Please complete registration.',
            ]);
        }

        $result = $this->otpService->send($phone, 'driver');

        if ($result['success']) {
            $result['driver_exists'] = true;
            $result['driver_status'] = $driver->status;
        }

        return response()->json($result, $result['success'] ? 200 : ($result['status_code'] ?? 429));
    }

    /**
     * Register a new driver and issue the first OTP.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string|min:10|max:20|unique:drivers,phone',
            'name' => 'required|string|min:2|max:255',
            'email' => 'nullable|email|max:191|unique:drivers,email',
            'license_number' => 'required|string|max:100',
            'license_expiry' => 'nullable|date|after:today',
            'vehicle_type' => 'required|string|in:hatchback,sedan,suv,van,other',
            'vehicle_number' => 'required|string|max:50',
            'vehicle_model' => 'nullable|string|max:100',
            'service_types' => 'required|array|min:1',
            'service_types.*' => 'required|string|in:ride,tour,rental',
        ]);

        $serviceTypes = array_values(array_unique($validated['service_types']));
        unset($validated['service_types']);

        $driver = Driver::create([
            ...$validated,
            'status' => 'pending',
            'is_active' => true,
            'is_approved' => false,
            'is_online' => false,
            'can_short_ride' => in_array('ride', $serviceTypes, true),
            'can_long_ride' => in_array('ride', $serviceTypes, true),
            'can_tour_lead' => false,
            'can_tour_transport' => in_array('tour', $serviceTypes, true),
            'can_rental_delivery' => in_array('rental', $serviceTypes, true),
        ]);

        $result = $this->otpService->send($driver->phone, 'driver');

        if (! $result['success']) {
            $driver->delete();

            return response()->json($result, $result['status_code'] ?? 503);
        }

        return response()->json([
            ...$result,
            'driver_created' => true,
            'driver_status' => $driver->status,
            'message' => 'Registration submitted. Verify your phone while your account awaits approval.',
        ], 201);
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

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        $driver = Driver::where('phone', $phone)->first();

        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver account not found.',
            ], 404);
        }

        if (! $driver->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Driver account is inactive. Please contact admin.',
            ], 403);
        }

        if (! $driver->phone_verified_at) {
            $driver->forceFill(['phone_verified_at' => now()])->save();
        }

        if (! $driver->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Phone verified. Your driver registration is pending admin approval.',
                'phone_verified' => true,
                'driver_status' => $driver->status,
            ], 403);
        }

        // Revoke old tokens
        $driver->tokens()->delete();

        // Issue new Sanctum token
        $token = $driver->createToken(
            'driver-app',
            ['*'],
            now()->addMinutes((int) config('services.otp.token_expiration_minutes', 60 * 24 * 30))
        )->plainTextToken;

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
            'driver' => $request->user()->load('vehicle.category'),
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
