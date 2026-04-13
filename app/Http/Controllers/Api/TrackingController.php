<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RideBooking;
use App\Models\DriverAvailability;
use App\Events\RideLocationUpdated;
use App\Events\RideStatusUpdated;
use App\Events\DriverLocationUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    /**
     * Update driver's current location
     */
    public function updateDriverLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_available' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Update driver availability
        $availability = DriverAvailability::updateOrCreate(
            ['driver_id' => $user->id],
            [
                'current_lat' => $request->latitude,
                'current_lng' => $request->longitude,
                'is_online' => true,
                'is_available' => $request->is_available ?? true,
                'last_ping' => now(),
            ]
        );

        // Broadcast location update
        broadcast(new DriverLocationUpdated(
            $user,
            $request->latitude,
            $request->longitude,
            $request->is_available ?? true
        ));

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $availability,
        ]);
    }

    /**
     * Update ride location (for tracking)
     */
    public function updateRideLocation(Request $request, RideBooking $booking)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'eta' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify the user is the driver of this booking
        if ($booking->driver_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Update booking location
        $booking->update([
            'current_lat' => $request->latitude,
            'current_lng' => $request->longitude,
            'last_location_update' => now(),
        ]);

        // Broadcast location update
        broadcast(new RideLocationUpdated(
            $booking,
            $request->latitude,
            $request->longitude,
            null,
            $request->eta
        ));

        return response()->json([
            'success' => true,
            'message' => 'Ride location updated',
        ]);
    }

    /**
     * Update ride status
     */
    public function updateRideStatus(Request $request, RideBooking $booking)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'message' => 'nullable|string|max:255',
            'start_pin' => 'nullable|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify authorization
        $user = $request->user();
        $isDriver = $booking->driver_id === $user->id;
        $isCustomer = $booking->customer_id === $user->id;
        $isAdmin = $user->isAdmin();

        if (!$isDriver && !$isCustomer && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $previousStatus = $booking->status;
        $requestedStatus = $this->normalizeStatus($request->status);
        if (!$requestedStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status value',
            ], 422);
        }

        // Validate status transitions
        $allowedTransitions = $this->getAllowedTransitions($booking->status, $isDriver, $isCustomer);

        if (!in_array($requestedStatus, $allowedTransitions, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition',
                'allowed_transitions' => $allowedTransitions,
            ], 400);
        }

        // Update booking status
        $updateData = ['status' => $requestedStatus];

        if ($requestedStatus === 'driver_assigned' && empty($booking->start_ride_pin)) {
            $updateData['start_ride_pin'] = RideBooking::generateStartRidePin();
            $updateData['start_pin_verified_at'] = null;
        }

        if ($requestedStatus === 'in_transit' && $isDriver) {
            if (empty($booking->start_ride_pin)) {
                $booking->update([
                    'start_ride_pin' => RideBooking::generateStartRidePin(),
                    'start_pin_verified_at' => null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Ride PIN has been generated. Ask customer to share it from booking details.',
                ], 422);
            }

            if ((string) $request->input('start_pin') !== (string) $booking->start_ride_pin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid 4-digit PIN. Please verify with customer.',
                ], 422);
            }

            $updateData['start_pin_verified_at'] = now();
        }

        if ($requestedStatus === 'in_transit') {
            $updateData['started_at'] = now();
        } elseif ($requestedStatus === 'completed') {
            $updateData['completed_at'] = now();
        } elseif ($requestedStatus === 'cancelled') {
            $updateData['cancellation_reason'] = $request->message;
        }

        $booking->update($updateData);

        if (in_array($requestedStatus, ['completed', 'cancelled'], true) && $booking->driver_id) {
            DriverAvailability::where('driver_id', $booking->driver_id)->update([
                'is_available' => true,
            ]);
        }

        // Broadcast status update
        broadcast(new RideStatusUpdated(
            $booking,
            $requestedStatus,
            $request->message,
            $previousStatus
        ));

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'data' => $booking->fresh(),
        ]);
    }

    /**
     * Get ride tracking info
     */
    public function getTrackingInfo(Request $request, RideBooking $booking)
    {
        // Verify authorization
        $user = $request->user();
        $isDriver = $booking->driver_id === $user->id;
        $isCustomer = $booking->customer_id === $user->id;
        $isAdmin = $user->isAdmin();

        if (!$isDriver && !$isCustomer && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $booking->load(['driver', 'user']);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'current_location' => $booking->current_lat && $booking->current_lng ? [
                    'latitude' => $booking->current_lat,
                    'longitude' => $booking->current_lng,
                    'last_update' => $booking->last_location_update,
                ] : null,
                'pickup' => [
                    'latitude' => $booking->pickup_lat,
                    'longitude' => $booking->pickup_lng,
                    'address' => $booking->pickup_location,
                ],
                'dropoff' => [
                    'latitude' => $booking->dropoff_lat,
                    'longitude' => $booking->dropoff_lng,
                    'address' => $booking->dropoff_location,
                ],
            ],
        ]);
    }

    /**
     * Get allowed status transitions
     */
    private function getAllowedTransitions(string $currentStatus, bool $isDriver, bool $isCustomer): array
    {
        $currentStatus = $this->normalizeStatus($currentStatus) ?? $currentStatus;

        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['driver_assigned', 'cancelled'],
            'driver_assigned' => ['driver_arriving', 'cancelled'],
            'driver_arriving' => ['pickup', 'cancelled'],
            'pickup' => ['in_transit'],
            'in_transit' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        $allowed = $transitions[$currentStatus] ?? [];

        // Customers can only cancel
        if ($isCustomer) {
            return in_array('cancelled', $allowed, true) ? ['cancelled'] : [];
        }

        return $allowed;
    }

    private function normalizeStatus(string $status): ?string
    {
        return match ($status) {
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'driver_assigned' => 'driver_assigned',
            'driver_arriving', 'on_the_way' => 'driver_arriving',
            'pickup', 'arrived' => 'pickup',
            'in_transit', 'started' => 'in_transit',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => null,
        };
    }
}
