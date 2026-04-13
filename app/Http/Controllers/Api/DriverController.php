<?php

namespace App\Http\Controllers\Api;

use App\Events\RideStatusUpdated;
use App\Events\RideAssigned;
use App\Http\Controllers\Controller;
use App\Models\DriverAvailability;
use App\Models\RideBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    public function activeRide(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $ride = RideBooking::query()
            ->where('driver_id', $user->id)
            ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
            ->with('customer:id,name,phone')
            ->orderByDesc('updated_at')
            ->first();

        return response()->json([
            'success' => true,
            'ride' => $ride ? $this->mapRide($ride) : null,
        ]);
    }

    public function pendingRides(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $availability = DriverAvailability::where('driver_id', $user->id)->first();

        $query = RideBooking::query()
            ->whereNull('driver_id')
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('customer:id,name,phone')
            ->orderBy('scheduled_at')
            ->limit(25);

        if ($availability?->current_lat && $availability?->current_lng) {
            $lat = (float) $availability->current_lat;
            $lng = (float) $availability->current_lng;
            $radiusKm = 15.0;

            $latDelta = $radiusKm / 111.32;
            $lngDelta = $radiusKm / (111.32 * cos(deg2rad($lat)));

            $query->whereBetween('pickup_lat', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('pickup_lng', [$lng - $lngDelta, $lng + $lngDelta]);
        }

        $rides = $query->get()->map(fn (RideBooking $ride) => $this->mapRide($ride))->values();

        return response()->json([
            'success' => true,
            'rides' => $rides,
        ]);
    }

    public function acceptRide(Request $request, RideBooking $booking)
    {
        $user = $request->user();
        if (!$user || !$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This ride is not available to accept.',
            ], 409);
        }

        $hasActiveRide = RideBooking::query()
            ->where('driver_id', $user->id)
            ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
            ->exists();

        if ($hasActiveRide) {
            return response()->json([
                'success' => false,
                'message' => 'Complete your active ride before accepting a new one.',
            ], 409);
        }

        $accepted = false;

        DB::transaction(function () use ($booking, $user, &$accepted) {
            $locked = RideBooking::whereKey($booking->id)->lockForUpdate()->first();

            if (!$locked || $locked->driver_id || !in_array($locked->status, ['pending', 'confirmed'], true)) {
                return;
            }

            $vehicleNumber = DriverAvailability::where('driver_id', $user->id)->value('vehicle_number');

            $locked->update([
                'driver_id' => $user->id,
                'status' => 'driver_assigned',
                'vehicle_number' => $locked->vehicle_number ?: $vehicleNumber,
                'start_ride_pin' => $locked->start_ride_pin ?: RideBooking::generateStartRidePin(),
                'start_pin_verified_at' => null,
            ]);

            DriverAvailability::where('driver_id', $user->id)->update([
                'is_online' => true,
                'is_available' => false,
                'last_ping' => now(),
            ]);

            broadcast(new RideStatusUpdated($locked, 'driver_assigned', 'Driver assigned', 'pending'));
            broadcast(new RideAssigned($locked, $user->id));

            $accepted = true;
        });

        if (!$accepted) {
            return response()->json([
                'success' => false,
                'message' => 'Ride already assigned to another driver.',
            ], 409);
        }

        $booking->refresh()->load('customer:id,name,phone');

        return response()->json([
            'success' => true,
            'message' => 'Ride accepted successfully.',
            'ride' => $this->mapRide($booking),
        ]);
    }

    public function updatePaymentStatus(Request $request, RideBooking $booking)
    {
        $user = $request->user();
        if (!$user || !$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ((int) $booking->driver_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update payment for your assigned rides.',
            ], 403);
        }

        $validated = $request->validate([
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string|in:cash,card,wallet,upi,bank_transfer',
        ]);

        if ($booking->payment_status === 'paid' && $validated['payment_status'] === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment is already marked as paid.',
            ], 422);
        }

        if ($validated['payment_status'] === 'paid' && empty($validated['payment_method'])) {
            return response()->json([
                'success' => false,
                'message' => 'Payment mode is required when marking as paid.',
            ], 422);
        }

        $updateData = [
            'payment_status' => $validated['payment_status'],
        ];

        if (!empty($validated['payment_method'])) {
            $updateData['payment_method'] = $validated['payment_method'];
        }

        $booking->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated.',
            'ride' => $this->mapRide($booking->fresh(['customer:id,name,phone'])),
        ]);
    }

    public function updateRideNote(Request $request, RideBooking $booking)
    {
        $user = $request->user();
        if (!$user || !$user->isDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ((int) $booking->driver_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update notes for your assigned rides.',
            ], 403);
        }

        $validated = $request->validate([
            'driver_notes' => 'nullable|string|max:2000',
        ]);

        $booking->update([
            'driver_notes' => $validated['driver_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ride note updated.',
            'ride' => $this->mapRide($booking->fresh(['customer:id,name,phone'])),
        ]);
    }

    private function mapRide(RideBooking $ride): array
    {
        return [
            'id' => $ride->id,
            'booking_number' => $ride->booking_number,
            'status' => $ride->status,
            'pickup_location' => $ride->pickup_location,
            'pickup_lat' => $ride->pickup_lat ? (float) $ride->pickup_lat : null,
            'pickup_lng' => $ride->pickup_lng ? (float) $ride->pickup_lng : null,
            'dropoff_location' => $ride->dropoff_location,
            'dropoff_lat' => $ride->dropoff_lat ? (float) $ride->dropoff_lat : null,
            'dropoff_lng' => $ride->dropoff_lng ? (float) $ride->dropoff_lng : null,
            'scheduled_at' => $ride->scheduled_at,
            'total_fare' => (float) $ride->total_fare,
            'distance_km' => $ride->distance_km ? (float) $ride->distance_km : 0.0,
            'service_type' => $ride->service_type,
            'payment_status' => $ride->payment_status,
            'payment_method' => $ride->payment_method,
            'driver_notes' => $ride->driver_notes,
            'vehicle_number' => $ride->vehicle_number,
            'customer_name' => $ride->customer_name ?: ($ride->customer?->name ?? 'Customer'),
            'customer_phone' => $ride->customer_phone ?: ($ride->customer?->phone ?? ''),
        ];
    }
}
