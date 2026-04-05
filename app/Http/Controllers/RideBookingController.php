<?php

namespace App\Http\Controllers;

use App\Models\RideBooking;
use App\Models\DriverAvailability;
use App\Models\Destination;
use App\Models\RideBookingReview;
use App\Models\RideBookingTip;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RideBookingController extends Controller
{
    /**
     * Display a listing of ride bookings.
     */
    public function index(Request $request)
    {
        $query = RideBooking::with(['user', 'driver']);

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by user if not admin
        if (!Auth::user()->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        $rideBookings = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($rideBookings);
        }

        return view('ride-bookings.index', compact('rideBookings'));
    }

    /**
     * Get price estimate for a ride.
     */
    public function estimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'service_type' => 'required|in:point_to_point,hourly_rental,round_trip',
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate distance if dropoff provided
        $distance = 0;
        if ($request->dropoff_lat && $request->dropoff_lng) {
            $earthRadius = 6371;
            $latDelta = deg2rad($request->dropoff_lat - $request->pickup_lat);
            $lngDelta = deg2rad($request->dropoff_lng - $request->pickup_lng);

            $a = sin($latDelta / 2) * sin($latDelta / 2) +
                 cos(deg2rad($request->pickup_lat)) * cos(deg2rad($request->dropoff_lat)) *
                 sin($lngDelta / 2) * sin($lngDelta / 2);

            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earthRadius * $c;
        }

        // Base pricing (you can make this configurable)
        $baseFare = 50; // Base fare
        $perKmRate = 15; // Rate per kilometer
        $distanceFare = $distance * $perKmRate;

        // Surge pricing (simplified - you can implement time-based surge)
        $surgeMultiplier = 1.0;

        // Check for nearby drivers
        $nearbyDrivers = DriverAvailability::active()
            ->nearLocation($request->pickup_lat, $request->pickup_lng, 5)
            ->count();

        if ($nearbyDrivers < 3) {
            $surgeMultiplier = 1.2; // 20% surge if fewer than 3 drivers
        }

        $subtotal = ($baseFare + $distanceFare) * $surgeMultiplier;
        $estimatedDuration = $distance > 0 ? ceil($distance / 30 * 60) : 30; // Rough estimate: 30 km/h

        return response()->json([
            'distance_km' => round($distance, 2),
            'estimated_duration' => $estimatedDuration,
            'pricing' => [
                'base_fare' => $baseFare,
                'distance_fare' => round($distanceFare, 2),
                'surge_multiplier' => $surgeMultiplier,
                'subtotal' => round($subtotal, 2),
            ],
            'nearby_drivers' => $nearbyDrivers,
        ]);
    }

    /**
     * Store a newly created ride booking.
     */
    public function store(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Please login to book a ride.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'service_type' => 'required|in:point_to_point,hourly_rental,round_trip',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'pickup_location' => 'required|string|max:255',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'dropoff_location' => 'nullable|string|max:255',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'scheduled_at' => 'required|date|after:now',
            'special_requests' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,wallet',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate pricing
        $user = Auth::user();
        $customerName = trim((string) ($request->input('customer_name') ?: ($user->name ?? '')));
        $customerEmail = trim((string) ($request->input('customer_email') ?: ($user->email ?? '')));
        $customerPhone = trim((string) ($request->input('customer_phone')
            ?: ($user->phone ?? $user->phone_number ?? $user->mobile ?? '')));

        if ($customerName === '' || $customerEmail === '') {
            return response()->json([
                'message' => 'Missing customer identity details.',
                'errors' => [
                    'customer_name' => $customerName === '' ? ['Customer name is required.'] : [],
                    'customer_email' => $customerEmail === '' ? ['Customer email is required.'] : [],
                ],
            ], 422);
        }

        $estimateRequest = new Request([
            'pickup_lat' => $request->pickup_lat,
            'pickup_lng' => $request->pickup_lng,
            'dropoff_lat' => $request->dropoff_lat,
            'dropoff_lng' => $request->dropoff_lng,
            'service_type' => $request->service_type,
            'scheduled_at' => $request->scheduled_at,
        ]);

        $estimate = $this->estimate($estimateRequest)->getData();

        DB::beginTransaction();
        try {
            $rideBooking = RideBooking::create([
                'user_id' => Auth::id(),
                'service_type' => $request->service_type,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'pickup_location' => $request->pickup_location,
                'pickup_lat' => $request->pickup_lat,
                'pickup_lng' => $request->pickup_lng,
                'dropoff_location' => $request->dropoff_location,
                'dropoff_lat' => $request->dropoff_lat,
                'dropoff_lng' => $request->dropoff_lng,
                'scheduled_at' => $request->scheduled_at,
                'distance_km' => $estimate->distance_km ?? 0,
                'estimated_duration' => $estimate->estimated_duration ?? 30,
                'base_fare' => $estimate->pricing->base_fare ?? 50,
                'distance_fare' => $estimate->pricing->distance_fare ?? 0,
                'surge_multiplier' => $estimate->pricing->surge_multiplier ?? 1.0,
                'total_fare' => $estimate->pricing->subtotal ?? 50,
                'payment_method' => $request->payment_method,
                'special_requests' => $request->special_requests,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Try to assign driver immediately
            $this->assignDriver($rideBooking);

            DB::commit();

            return response()->json([
                'message' => 'Ride booking created successfully',
                'ride_booking' => $rideBooking->load(['driver']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Ride booking creation failed', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to create booking'], 500);
        }
    }

    /**
     * Display the specified ride booking.
     */
    public function show(RideBooking $rideBooking)
    {
        // Check if user can view this booking
        if (!Auth::user()->isAdmin() && $rideBooking->user_id !== Auth::id()) {
            abort(403);
        }

        $rideBooking->load(['user', 'driver']);

        if (request()->expectsJson()) {
            return response()->json($rideBooking);
        }

        return view('ride-bookings.show', compact('rideBooking'));
    }

    /**
     * Update the specified ride booking.
     */
    public function update(Request $request, RideBooking $rideBooking)
    {
        // Check if user can update this booking
        if (!Auth::user()->isAdmin() && $rideBooking->user_id !== Auth::id()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:confirmed,driver_assigned,driver_arriving,pickup,in_transit,completed,cancelled,on_the_way,arrived,started',
            'driver_id' => 'sometimes|nullable|exists:drivers,id',
            'current_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'current_lng' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only([
            'status', 'driver_id', 'current_lat', 'current_lng'
        ]);

        if (!empty($updateData['status'])) {
            $updateData['status'] = $this->normalizeStatus($updateData['status']) ?? $rideBooking->status;
        }

        $rideBooking->update($updateData);

        // Update last location update timestamp
        if ($request->has(['current_lat', 'current_lng'])) {
            $rideBooking->update(['last_location_update' => now()]);
        }

        return response()->json([
            'message' => 'Ride booking updated successfully',
            'ride_booking' => $rideBooking->load(['driver'])
        ]);
    }

    /**
     * Remove the specified ride booking.
     */
    public function destroy(RideBooking $rideBooking)
    {
        // Check if user can delete this booking
        if (!Auth::user()->isAdmin() && $rideBooking->user_id !== Auth::id()) {
            abort(403);
        }

        if (in_array($rideBooking->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'message' => 'This booking can no longer be cancelled.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            if ($rideBooking->driver_id) {
                DriverAvailability::where('driver_id', $rideBooking->driver_id)
                    ->update(['is_available' => true]);
            }

            $rideBooking->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Cancelled by user',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Ride booking cancelled successfully',
                'ride_booking' => $rideBooking->fresh(['driver']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to cancel booking',
            ], 500);
        }
    }

    /**
     * Assign a driver to the booking.
     */
    private function assignDriver(RideBooking $rideBooking)
    {
        // Find nearby active drivers
        $nearbyDrivers = DriverAvailability::active()
            ->nearLocation($rideBooking->pickup_lat, $rideBooking->pickup_lng, 5)
            ->with('driver')
            ->get();

        if ($nearbyDrivers->isEmpty()) {
            return; // No drivers available
        }

        // Sort by distance and rating
        $sortedDrivers = $nearbyDrivers->sortBy(function ($driverAvailability) use ($rideBooking) {
            $distance = $driverAvailability->distanceFrom($rideBooking->pickup_lat, $rideBooking->pickup_lng);
            $rating = 6 - $driverAvailability->rating; // Invert rating so higher rating = lower score
            return $distance + ($rating * 1000); // Weight rating heavily
        });

        $assignedDriver = $sortedDrivers->first();

        if ($assignedDriver) {
            $rideBooking->update([
                'driver_id' => $assignedDriver->driver_id,
                'status' => 'driver_assigned',
                'vehicle_number' => $assignedDriver->vehicle_number,
                'start_ride_pin' => $rideBooking->start_ride_pin ?: RideBooking::generateStartRidePin(),
                'start_pin_verified_at' => null,
            ]);

            // Mark driver as unavailable
            $assignedDriver->update(['is_available' => false]);
        }
    }

    /**
     * Get nearby drivers for a location.
     */
    public function nearbyDrivers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $radius = $request->radius ?? 5; // Default 5km radius

        $drivers = DriverAvailability::active()
            ->nearLocation($request->lat, $request->lng, $radius)
            ->with('driver:id,name,email,phone')
            ->get()
            ->map(function ($driverAvailability) use ($request) {
                return [
                    'id' => $driverAvailability->driver_id,
                    'name' => $driverAvailability->driver->name,
                    'rating' => $driverAvailability->rating,
                    'vehicle_type' => $driverAvailability->vehicle_type,
                    'distance' => round($driverAvailability->distanceFrom($request->lat, $request->lng), 2),
                    'eta' => ceil($driverAvailability->distanceFrom($request->lat, $request->lng) / 30 * 60), // Rough ETA in minutes
                ];
            });

        return response()->json(['drivers' => $drivers]);
    }

    private function normalizeStatus(string $status): ?string
    {
        return match ($status) {
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

    public function submitReview(Request $request, RideBooking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'Review allowed only after ride completion.'], 422);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = RideBookingReview::updateOrCreate(
            [
                'ride_booking_id' => $booking->id,
                'customer_id' => Auth::id(),
            ],
            [
                'driver_id' => $booking->driver_id,
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'review' => $review,
        ]);
    }

    public function submitTip(Request $request, RideBooking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'completed') {
            return response()->json(['message' => 'Tip allowed only after ride completion.'], 422);
        }

        if (!$booking->driver_id) {
            return response()->json(['message' => 'No driver assigned for this booking.'], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:50000',
            'message' => 'nullable|string|max:500',
        ]);

        $amount = round((float) $validated['amount'], 2);

        $customerWallet = Wallet::where('user_id', Auth::id())->first();
        if (!$customerWallet || !$customerWallet->isActive()) {
            return response()->json(['message' => 'Customer wallet not available.'], 422);
        }

        $driverWallet = Wallet::firstOrCreate(
            ['user_id' => $booking->driver_id],
            [
                'balance' => 0,
                'currency' => 'INR',
                'status' => 'active',
            ]
        );

        if (!$driverWallet->isActive()) {
            return response()->json(['message' => 'Driver wallet is not active.'], 422);
        }

        DB::beginTransaction();
        try {
            $debited = $customerWallet->debit(
                $amount,
                'Tip paid for booking #' . $booking->booking_number,
                'ride_tip_' . $booking->id . '_' . now()->timestamp
            );

            if (!$debited) {
                DB::rollBack();
                return response()->json(['message' => 'Insufficient wallet balance for tip.'], 422);
            }

            $credited = $driverWallet->credit(
                $amount,
                'Tip received for booking #' . $booking->booking_number,
                'ride_tip_' . $booking->id . '_' . now()->timestamp
            );

            if (!$credited) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to transfer tip to driver.'], 500);
            }

            $tip = RideBookingTip::create([
                'ride_booking_id' => $booking->id,
                'customer_id' => Auth::id(),
                'driver_id' => $booking->driver_id,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'message' => $validated['message'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tip sent successfully.',
                'tip' => $tip,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to send tip.',
            ], 500);
        }
    }
}
