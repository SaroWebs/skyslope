<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarRental;
use App\Models\CarCategory;
use App\Models\DriverAvailability;
use App\Models\RideBooking;
use App\Models\TourBooking;
use App\Models\TourDriverAssignment;
use App\Models\Wallet;
use App\Models\Vehicle;
use App\Services\BookingLifecycleNotifier;
use App\Services\CommissionService;
use App\Services\DriverDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DriverAppController extends Controller
{
    public function dashboard(Request $request)
    {
        $driver = $request->user();
        $availability = DriverAvailability::firstOrCreate(
            ['driver_id' => $driver->id],
            ['status' => 'offline', 'is_available' => true]
        );
        $vehicle = $driver->vehicle()->with('category:id,name,vehicle_type,seats')->first();

        return response()->json([
            'success' => true,
            'driver' => $driver,
            'availability' => $availability,
            'vehicle' => $vehicle,
            'vehicle_readiness' => $this->vehicleReadiness($vehicle),
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
            'tour_assignments' => TourDriverAssignment::with(['schedule.tour.itineraries', 'vehicle', 'bookings.customer:id,name,phone'])
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
            'sharing_enabled' => 'sometimes|boolean',
            'sharing_seat_capacity' => 'sometimes|integer|min:2|max:6',
        ]);

        if ($validated['is_online']) {
            $vehicle = $request->user()->vehicle()->first();
            $readiness = $this->vehicleReadiness($vehicle);

            if (! $readiness['can_go_online']) {
                return response()->json([
                    'success' => false,
                    'message' => $readiness['message'],
                    'vehicle_readiness' => $readiness,
                ], 422);
            }
        }

        $availability = DriverAvailability::updateOrCreate(
            ['driver_id' => $request->user()->id],
            [
                'is_available' => $validated['is_available'],
                'status' => $validated['is_online'] ? 'online' : 'offline',
                'sharing_enabled' => $validated['sharing_enabled']
                    ?? $request->user()->driverAvailability?->sharing_enabled
                    ?? false,
                'sharing_seat_capacity' => $validated['sharing_seat_capacity']
                    ?? $request->user()->driverAvailability?->sharing_seat_capacity
                    ?? 3,
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

    public function vehicle(Request $request)
    {
        $vehicle = $request->user()->vehicle()->with('category:id,name,vehicle_type,seats')->first();

        return response()->json([
            'success' => true,
            'vehicle' => $vehicle,
            'vehicle_readiness' => $this->vehicleReadiness($vehicle),
            'categories' => CarCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'vehicle_type', 'seats']),
        ]);
    }

    public function upsertVehicle(Request $request)
    {
        $driver = $request->user();
        $vehicle = $driver->vehicle()->first();

        $hasActiveWork = RideBooking::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
            ->exists();

        if ($hasActiveWork) {
            return response()->json([
                'success' => false,
                'message' => 'Finish your active ride before editing your car.',
            ], 409);
        }

        $validated = $request->validate([
            'car_category_id' => ['required', 'exists:car_categories,id'],
            'registration_number' => [
                'required', 'string', 'max:30',
                Rule::unique('vehicles', 'registration_number')->ignore($vehicle?->id),
            ],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            'color' => ['required', 'string', 'max:50'],
            'fuel_type' => ['required', Rule::in(['petrol', 'diesel', 'cng', 'electric', 'hybrid'])],
            'seats' => ['required', 'integer', 'min:2', 'max:12'],
            'is_ac' => ['required', 'boolean'],
            'insurance_expiry' => ['nullable', 'date'],
            'permit_expiry' => ['nullable', 'date'],
            'fitness_expiry' => ['nullable', 'date'],
            'pollution_expiry' => ['nullable', 'date'],
        ]);

        $validated['registration_number'] = strtoupper(preg_replace('/\s+/', '', $validated['registration_number']));
        $validated['driver_id'] = $driver->id;
        $validated['is_active'] = false;
        $validated['approval_status'] = 'pending';
        $validated['reviewed_at'] = null;
        $validated['reviewed_by'] = null;
        $validated['rejection_reason'] = null;

        $vehicle = Vehicle::updateOrCreate(['driver_id' => $driver->id], $validated);
        $category = CarCategory::find($validated['car_category_id']);

        $driver->update([
            'vehicle_type' => $category?->vehicle_type,
            'vehicle_number' => $vehicle->registration_number,
            'vehicle_model' => trim($vehicle->make.' '.$vehicle->model),
            'vehicle_color' => $vehicle->color,
            'vehicle_year' => $vehicle->year,
            'is_online' => false,
        ]);

        DriverAvailability::where('driver_id', $driver->id)->update([
            'status' => 'offline',
            'is_available' => false,
            'last_updated' => now(),
        ]);

        $vehicle->load('category:id,name,vehicle_type,seats');

        return response()->json([
            'success' => true,
            'message' => 'Your car was submitted for admin approval.',
            'vehicle' => $vehicle,
            'vehicle_readiness' => $this->vehicleReadiness($vehicle),
        ]);
    }

    private function vehicleReadiness(?Vehicle $vehicle): array
    {
        if (! $vehicle) {
            return [
                'status' => 'missing',
                'can_go_online' => false,
                'message' => 'Add your car before going online.',
            ];
        }

        if ($vehicle->approval_status === 'rejected') {
            return [
                'status' => 'rejected',
                'can_go_online' => false,
                'message' => $vehicle->rejection_reason ?: 'Your car was rejected. Update its details and submit again.',
            ];
        }

        if ($vehicle->approval_status !== 'approved' || ! $vehicle->is_active) {
            return [
                'status' => 'pending',
                'can_go_online' => false,
                'message' => 'Your car is waiting for admin approval.',
            ];
        }

        if (! $vehicle->isDocumentValid() || $vehicle->condition === 'under_maintenance') {
            return [
                'status' => 'unavailable',
                'can_go_online' => false,
                'message' => 'Your car is not service-ready. Check documents and maintenance status.',
            ];
        }

        return [
            'status' => 'approved',
            'can_go_online' => true,
            'message' => 'Your car is approved and ready for trips.',
        ];
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

    public function historyDetail(Request $request, string $kind, int $id)
    {
        $driverId = $request->user()->id;

        $record = match ($kind) {
            'ride' => RideBooking::with(['customer:id,name,phone'])
                ->where('driver_id', $driverId)
                ->findOrFail($id),
            'tour' => TourDriverAssignment::with([
                'schedule.tour.itineraries',
                'vehicle',
                'bookings.customer:id,name,phone',
            ])->where('driver_id', $driverId)->findOrFail($id),
            'rental' => CarRental::with(['customer:id,name,phone', 'carCategory', 'vehicle'])
                ->where('driver_id', $driverId)
                ->findOrFail($id),
        };

        return response()->json([
            'success' => true,
            'kind' => $kind,
            'data' => $record,
        ]);
    }

    public function tourAssignments(Request $request)
    {
        $assignments = TourDriverAssignment::with(['schedule.tour.itineraries', 'vehicle', 'bookings.customer:id,name,phone'])
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

        if ($status === 'accepted') {
            $failures = app(DriverDispatchService::class)->eligibilityFailures(
                $request->user(),
                'tour',
                $assignment->role ?? 'transport',
                null,
                $assignment->vehicle_id ? (int) $assignment->vehicle_id : null
            );

            if ($failures !== []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Complete your current ride, rental, or tour before accepting this tour.',
                    'errors' => ['engagement' => $failures],
                ], 409);
            }
        }

        $assignment->update(['status' => $status]);

        if ($status === 'accepted') {
            DriverAvailability::where('driver_id', $request->user()->id)->update([
                'status' => 'on_tour',
                'is_available' => false,
                'last_updated' => now(),
            ]);
        } elseif (in_array($status, ['declined', 'completed'], true)) {
            $stillEngaged = app(DriverDispatchService::class)->hasActiveWorkload($request->user());
            DriverAvailability::where('driver_id', $request->user()->id)->update([
                'status' => $stillEngaged ? 'on_ride' : 'online',
                'is_available' => ! $stillEngaged,
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

        return response()->json(['success' => true, 'data' => $assignment->fresh(['schedule.tour.itineraries', 'vehicle', 'bookings.customer:id,name,phone'])]);
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
