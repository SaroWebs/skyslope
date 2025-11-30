<?php

namespace App\Http\Controllers;

use App\Models\RideBooking;
use App\Models\DriverAvailability;
use App\Models\Destination;
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
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|in:point_to_point,hourly_rental,round_trip',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
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
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
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
            'status' => 'sometimes|in:confirmed,driver_assigned,driver_arriving,pickup,in_transit,completed,cancelled',
            'driver_id' => 'sometimes|nullable|exists:users,id',
            'current_lat' => 'sometimes|nullable|numeric|between:-90,90',
            'current_lng' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rideBooking->update($request->only([
            'status', 'driver_id', 'current_lat', 'current_lng'
        ]));

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

        $rideBooking->delete();

        return response()->json(['message' => 'Ride booking deleted successfully']);
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
}
