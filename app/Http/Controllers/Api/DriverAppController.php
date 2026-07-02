<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarRental;
use App\Models\DriverAvailability;
use App\Models\RideBooking;
use App\Models\TourBooking;
use App\Models\TourDriverAssignment;
use App\Models\Wallet;
use App\Services\BookingLifecycleNotifier;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DriverAppController extends Controller
{
    public function dashboard(Request $request)
    {
        $driver = $request->user();
        $availability = DriverAvailability::firstOrCreate(
            ['driver_id' => $driver->id],
            ['status' => 'offline', 'is_available' => true]
        );

        return response()->json([
            'success' => true,
            'driver' => $driver,
            'availability' => $availability,
            'stats' => [
                'active_rides' => RideBooking::where('driver_id', $driver->id)
                    ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
                    ->count(),
                'completed_rides' => RideBooking::where('driver_id', $driver->id)
                    ->where('status', 'completed')
                    ->count(),
                'pending_pool' => RideBooking::whereNull('driver_id')
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
                'tour_assignments' => TourDriverAssignment::where('driver_id', $driver->id)
                    ->whereIn('status', ['assigned', 'accepted'])
                    ->count(),
                'rental_assignments' => CarRental::where('driver_id', $driver->id)
                    ->whereIn('status', ['driver_assigned', 'in_progress'])
                    ->count(),
                'earnings' => (float) optional(Wallet::forOwner($driver)->first())->balance,
            ],
            'active_ride' => RideBooking::with('customer:id,name,phone')
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
                ->latest()
                ->first(),
            'tour_assignments' => TourDriverAssignment::with(['schedule.tour', 'vehicle'])
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['assigned', 'accepted'])
                ->latest()
                ->take(5)
                ->get(),
            'rental_assignments' => CarRental::with(['customer:id,name,phone', 'carCategory'])
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['driver_assigned', 'in_progress'])
                ->latest()
                ->take(5)
                ->get(),
            'recent_rides' => RideBooking::with('customer:id,name,phone')
                ->where('driver_id', $driver->id)
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $validated = $request->validate([
            'is_online' => 'required|boolean',
            'is_available' => 'required|boolean',
            'vehicle_type' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:100',
        ]);

        $availability = DriverAvailability::updateOrCreate(
            ['driver_id' => $request->user()->id],
            [
                'is_available' => $validated['is_available'],
                'status' => $validated['is_online'] ? 'online' : 'offline',
                'last_updated' => now(),
            ]
        );

        $request->user()->update([
            'is_online' => $validated['is_online'],
            'vehicle_type' => $validated['vehicle_type'] ?? $request->user()->vehicle_type,
            'vehicle_number' => $validated['vehicle_number'] ?? $request->user()->vehicle_number,
        ]);

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    public function history(Request $request)
    {
        $rides = RideBooking::with(['customer:id,name,phone'])
            ->where('driver_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $rides,
        ]);
    }

    public function tourAssignments(Request $request)
    {
        $assignments = TourDriverAssignment::with(['schedule.tour.itineraries', 'vehicle'])
            ->where('driver_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    public function acceptTourAssignment(Request $request, int $id)
    {
        return $this->updateTourAssignment($request, $id, 'accepted');
    }

    public function declineTourAssignment(Request $request, int $id)
    {
        return $this->updateTourAssignment($request, $id, 'declined');
    }

    public function completeTourAssignment(Request $request, int $id)
    {
        return $this->updateTourAssignment($request, $id, 'completed');
    }

    public function rentalAssignments(Request $request)
    {
        $rentals = CarRental::with(['customer:id,name,phone', 'carCategory', 'vehicle'])
            ->where('driver_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $rentals]);
    }

    public function activeRental(Request $request)
    {
        $rental = CarRental::with(['customer:id,name,phone', 'carCategory', 'vehicle'])
            ->where('driver_id', $request->user()->id)
            ->whereIn('status', ['driver_assigned', 'in_progress'])
            ->latest()
            ->first();

        return response()->json(['success' => true, 'data' => $rental]);
    }

    public function acceptRental(Request $request, CarRental $rental)
    {
        return $this->updateRental($request, $rental, 'driver_assigned', 'Rental accepted successfully.');
    }

    public function declineRental(Request $request, CarRental $rental)
    {
        return $this->updateRental($request, $rental, 'pending', 'Rental declined.');
    }

    public function completeRental(Request $request, CarRental $rental)
    {
        return $this->updateRental($request, $rental, 'completed', 'Rental completed.');
    }

    private function updateTourAssignment(Request $request, int $id, string $status)
    {
        $assignment = TourDriverAssignment::with('schedule')
            ->where('driver_id', $request->user()->id)
            ->findOrFail($id);

        $assignment->update(['status' => $status]);

        if ($status === 'accepted') {
            DriverAvailability::where('driver_id', $request->user()->id)->update([
                'status' => 'on_tour',
                'is_available' => false,
                'last_updated' => now(),
            ]);
        } elseif (in_array($status, ['declined', 'completed'], true)) {
            DriverAvailability::where('driver_id', $request->user()->id)->update([
                'status' => 'online',
                'is_available' => true,
                'last_updated' => now(),
            ]);
        }

        $action = match ($status) {
            'accepted' => 'booking.accepted',
            'declined' => 'booking.declined',
            'completed' => 'booking.completed',
            default => null,
        };

        if ($action) {
            TourBooking::with('customer')
                ->where('tour_schedule_id', $assignment->tour_schedule_id)
                ->get()
                ->each(fn (TourBooking $booking) => app(BookingLifecycleNotifier::class)->emit($booking, $action, [
                    'driver_id' => $request->user()->id,
                    'assignment_id' => $assignment->id,
                ]));
        }

        return response()->json(['success' => true, 'data' => $assignment->fresh(['schedule.tour', 'vehicle'])]);
    }

    private function updateRental(Request $request, CarRental $rental, string $status, string $message)
    {
        Gate::authorize('updateAssignment', $rental);

        DB::transaction(function () use ($request, $rental, $status) {
            $updates = ['status' => $status];
            if ($status === 'pending') {
                $updates['driver_id'] = null;
                $updates['vehicle_id'] = null;
            }
            $rental->update($updates);
            if ($status === 'completed' && $rental->fresh()->payment_status === 'paid') {
                app(CommissionService::class)->settleRental($rental->fresh());
            }

            DriverAvailability::where('driver_id', $request->user()->id)->update([
                'status' => $status === 'completed' || $status === 'pending' ? 'online' : 'on_ride',
                'is_available' => $status === 'completed' || $status === 'pending',
                'last_updated' => now(),
            ]);
        });

        $action = match ($status) {
            'driver_assigned' => 'booking.accepted',
            'pending' => 'booking.declined',
            'completed' => 'booking.completed',
            default => null,
        };

        if ($action) {
            app(BookingLifecycleNotifier::class)->emit($rental->fresh('customer'), $action, [
                'driver_id' => $request->user()->id,
            ]);
        }

        return response()->json(['success' => true, 'message' => $message, 'data' => $rental->fresh()]);
    }
}
