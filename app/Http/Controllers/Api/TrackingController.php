<?php

namespace App\Http\Controllers\Api;

use App\Events\DriverLocationUpdated;
use App\Events\RentalLocationUpdated;
use App\Events\RideLocationUpdated;
use App\Events\RideStatusUpdated;
use App\Events\TourLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\CarRental;
use App\Models\DriverAvailability;
use App\Models\DriverLocation;
use App\Models\InsurancePolicy;
use App\Models\RideBooking;
use App\Models\TourBooking;
use App\Services\BookingLifecycleNotifier;
use App\Services\BookingStatusService;
use App\Services\CommissionService;
use App\Services\MapsProviderService;
use App\Services\StartVerificationService;
use App\Services\WeatherProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            'service_type' => 'nullable|in:ride,tour,rental',
            'booking_id' => 'nullable|integer|min:1',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0|max:300',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
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
                'is_available' => $request->is_available ?? true,
                'status' => ($request->is_available ?? true) ? 'online' : 'on_ride',
                'last_updated' => now(),
            ]
        );

        $this->updateActiveServiceLocation($request);
        $this->storeDriverLocationHistory($request);

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
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0|max:300',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        Gate::authorize('updateLocation', $booking);

        // Update booking location
        $booking->update([
            'current_lat' => $request->latitude,
            'current_lng' => $request->longitude,
            'last_location_update' => now(),
        ]);
        $this->storeDriverLocationHistory($request, "ride:{$booking->id}");

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
        $isAdmin = method_exists($user, 'isAdmin') && $user->isAdmin();

        Gate::authorize('updateStatus', $booking);

        $previousStatus = $booking->status;
        $statusService = app(BookingStatusService::class);
        $actor = $isCustomer ? 'customer' : ($isDriver ? 'driver' : 'admin');
        $requestedStatus = $statusService->normalize(BookingStatusService::RIDE, $request->status);
        if (! $requestedStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status value',
            ], 422);
        }

        // Validate status transitions
        $allowedTransitions = $statusService->allowedTransitions(BookingStatusService::RIDE, $booking->status, $actor);

        if (! in_array($requestedStatus, $allowedTransitions, true)) {
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
        if ($requestedStatus === 'completed' && $booking->fresh()->payment_status === 'paid') {
            app(CommissionService::class)->settleRide($booking->fresh());
        }

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

        $notificationAction = match ($requestedStatus) {
            'in_transit' => 'booking.started',
            'completed' => 'booking.completed',
            'cancelled' => 'booking.cancelled',
            default => null,
        };

        if ($notificationAction) {
            app(BookingLifecycleNotifier::class)->emit($booking->fresh('customer'), $notificationAction, [
                'previous_status' => $previousStatus,
                'actor' => $actor,
            ]);
        }

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
        Gate::authorize('track', $booking);

        $booking->load(['driver', 'customer']);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking,
                'current_location' => $booking->current_lat && $booking->current_lng ? [
                    'latitude' => $booking->current_lat,
                    'longitude' => $booking->current_lng,
                    'last_update' => $booking->last_location_update,
                ] : null,
                'tracking' => $this->trackingState(
                    $booking->status,
                    $booking->current_lat,
                    $booking->current_lng,
                    $booking->last_location_update,
                    (bool) $booking->driver_id
                ),
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
                'service_type' => 'ride',
                'eta_minutes' => app(MapsProviderService::class)->estimateEtaMinutes($booking->current_lat, $booking->current_lng, $booking->pickup_lat, $booking->pickup_lng),
                'weather' => app(WeatherProviderService::class)->snapshot($booking->current_lat ?? $booking->pickup_lat, $booking->current_lng ?? $booking->pickup_lng),
                'protection' => $this->protectionSnapshot((int) $booking->customer_id),
                'start_verification' => [
                    'required' => true,
                    'code' => app(StartVerificationService::class)->codeFor($booking, 'ride'),
                    'verified_at' => $booking->start_pin_verified_at,
                    'label' => 'Ride start OTP',
                    'instructions' => $booking->start_ride_pin
                        ? 'Share this 4-digit code only after you meet the assigned driver.'
                        : 'Start OTP appears after a driver is assigned.',
                ],
                'status_steps' => $this->statusSteps('ride', $booking->status, app(StartVerificationService::class)->isVerified($booking)),
            ],
        ]);
    }

    public function updateTourLocation(Request $request, TourBooking $booking)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'current_stop_index' => 'nullable|integer|min:0',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0|max:300',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
        ]);

        Gate::authorize('updateLocation', $booking);

        $booking->update([
            'current_lat' => $validated['latitude'],
            'current_lng' => $validated['longitude'],
            'current_stop_index' => $validated['current_stop_index'] ?? $booking->current_stop_index,
            'last_location_update' => now(),
        ]);
        $this->storeDriverLocationHistory($request, "tour:{$booking->id}");

        broadcast(new TourLocationUpdated(
            $booking,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['current_stop_index'] ?? null
        ));

        return response()->json(['success' => true, 'message' => 'Tour location updated']);
    }

    public function updateTourStatus(Request $request, TourBooking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'message' => 'nullable|string|max:255',
            'start_pin' => 'nullable|digits:4',
        ]);

        Gate::authorize('updateLocation', $booking);

        $previousStatus = $booking->status;
        $statusService = app(BookingStatusService::class);
        $requestedStatus = $statusService->normalize(BookingStatusService::TOUR, $validated['status']);

        if (! $requestedStatus) {
            return response()->json(['success' => false, 'message' => 'Invalid status value'], 422);
        }

        if (! in_array($requestedStatus, $statusService->allowedTransitions(BookingStatusService::TOUR, $booking->status, 'driver'), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition',
                'allowed_transitions' => $statusService->allowedTransitions(BookingStatusService::TOUR, $booking->status, 'driver'),
            ], 400);
        }

        if ($requestedStatus === 'in_progress') {
            app(StartVerificationService::class)->verifyStartCode(
                $booking,
                'tour',
                (string) $request->input('start_pin', ''),
                $request->user()
            );
        }

        $booking->update(['status' => $requestedStatus]);

        if ($requestedStatus === 'completed' && $booking->fresh()->payment_status === 'paid') {
            app(CommissionService::class)->settleTour($booking->fresh());
        }

        if (in_array($requestedStatus, ['completed', 'cancelled'], true)) {
            DriverAvailability::where('driver_id', $request->user()->id)->update([
                'is_available' => true,
                'status' => 'online',
                'last_updated' => now(),
            ]);
        } elseif ($requestedStatus === 'in_progress') {
            DriverAvailability::where('driver_id', $request->user()->id)->update([
                'is_available' => false,
                'status' => 'on_tour',
                'last_updated' => now(),
            ]);
        }

        $notificationAction = match ($requestedStatus) {
            'in_progress' => 'booking.started',
            'completed' => 'booking.completed',
            'cancelled' => 'booking.cancelled',
            default => null,
        };

        if ($notificationAction) {
            app(BookingLifecycleNotifier::class)->emit($booking->fresh('customer'), $notificationAction, [
                'previous_status' => $previousStatus,
                'actor' => 'driver',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tour status updated successfully',
            'data' => $booking->fresh(),
        ]);
    }

    public function updateRentalLocation(Request $request, CarRental $rental)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0|max:300',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
        ]);

        Gate::authorize('updateLocation', $rental);

        $rental->update([
            'current_lat' => $validated['latitude'],
            'current_lng' => $validated['longitude'],
            'last_location_update' => now(),
        ]);
        $this->storeDriverLocationHistory($request, "rental:{$rental->id}");

        broadcast(new RentalLocationUpdated($rental, (float) $validated['latitude'], (float) $validated['longitude']));

        return response()->json(['success' => true, 'message' => 'Rental location updated']);
    }

    public function updateRentalStatus(Request $request, CarRental $rental)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'message' => 'nullable|string|max:255',
            'start_pin' => 'nullable|digits:4',
        ]);

        Gate::authorize('updateAssignment', $rental);

        $previousStatus = $rental->status;
        $statusService = app(BookingStatusService::class);
        $requestedStatus = $statusService->normalize(BookingStatusService::RENTAL, $validated['status']);

        if (! $requestedStatus) {
            return response()->json(['success' => false, 'message' => 'Invalid status value'], 422);
        }

        if (! in_array($requestedStatus, $statusService->allowedTransitions(BookingStatusService::RENTAL, $rental->status, 'driver'), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition',
                'allowed_transitions' => $statusService->allowedTransitions(BookingStatusService::RENTAL, $rental->status, 'driver'),
            ], 400);
        }

        if ($requestedStatus === 'in_progress') {
            app(StartVerificationService::class)->verifyStartCode(
                $rental,
                'rental',
                (string) $request->input('start_pin', ''),
                $request->user()
            );
        }

        $rental->update(['status' => $requestedStatus]);

        if ($requestedStatus === 'completed' && $rental->fresh()->payment_status === 'paid') {
            app(CommissionService::class)->settleRental($rental->fresh());
        }

        DriverAvailability::where('driver_id', $request->user()->id)->update([
            'is_available' => in_array($requestedStatus, ['completed', 'cancelled'], true),
            'status' => in_array($requestedStatus, ['completed', 'cancelled'], true) ? 'online' : 'on_ride',
            'last_updated' => now(),
        ]);

        $notificationAction = match ($requestedStatus) {
            'in_progress' => 'booking.started',
            'completed' => 'booking.completed',
            'cancelled' => 'booking.cancelled',
            default => null,
        };

        if ($notificationAction) {
            app(BookingLifecycleNotifier::class)->emit($rental->fresh('customer'), $notificationAction, [
                'previous_status' => $previousStatus,
                'actor' => 'driver',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rental status updated successfully',
            'data' => $rental->fresh(),
        ]);
    }

    public function getTourTrackingInfo(Request $request, TourBooking $booking)
    {
        Gate::authorize('track', $booking);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking->load(['tour.itineraries', 'driverAssignments.driver:id,name,phone']),
                'current_location' => $booking->current_lat && $booking->current_lng ? [
                    'latitude' => $booking->current_lat,
                    'longitude' => $booking->current_lng,
                    'last_update' => $booking->last_location_update,
                    'current_stop_index' => $booking->current_stop_index,
                ] : null,
                'tracking' => $this->trackingState(
                    $booking->status,
                    $booking->current_lat,
                    $booking->current_lng,
                    $booking->last_location_update,
                    $booking->driverAssignments->isNotEmpty()
                ),
                'service_type' => 'tour',
                'eta_minutes' => null,
                'weather' => app(WeatherProviderService::class)->snapshot($booking->current_lat, $booking->current_lng),
                'protection' => $this->protectionSnapshot((int) $booking->customer_id),
                'start_verification' => [
                    'required' => true,
                    'code' => app(StartVerificationService::class)->codeFor($booking, 'tour'),
                    'verified_at' => app(StartVerificationService::class)->isVerified($booking) ? now()->toIso8601String() : null,
                    'label' => 'Tour start OTP',
                    'instructions' => 'Share this 4-digit code at guide/driver check-in, then press customer check-in to confirm readiness.',
                ],
                'status_steps' => $this->statusSteps('tour', $booking->status, app(StartVerificationService::class)->isVerified($booking)),
            ],
        ]);
    }

    public function getRentalTrackingInfo(Request $request, CarRental $rental)
    {
        Gate::authorize('track', $rental);

        return response()->json([
            'success' => true,
            'data' => [
                'rental' => $rental->load(['driver:id,name,phone', 'carCategory']),
                'current_location' => $rental->current_lat && $rental->current_lng ? [
                    'latitude' => $rental->current_lat,
                    'longitude' => $rental->current_lng,
                    'last_update' => $rental->last_location_update,
                ] : null,
                'tracking' => $this->trackingState(
                    $rental->status,
                    $rental->current_lat,
                    $rental->current_lng,
                    $rental->last_location_update,
                    (bool) $rental->driver_id
                ),
                'pickup' => [
                    'latitude' => $rental->pickup_lat,
                    'longitude' => $rental->pickup_lng,
                    'address' => $rental->pickup_location,
                ],
                'dropoff' => [
                    'latitude' => $rental->dropoff_lat,
                    'longitude' => $rental->dropoff_lng,
                    'address' => $rental->dropoff_location,
                ],
                'service_type' => 'rental',
                'eta_minutes' => app(MapsProviderService::class)->estimateEtaMinutes($rental->current_lat, $rental->current_lng, $rental->pickup_lat, $rental->pickup_lng),
                'weather' => app(WeatherProviderService::class)->snapshot($rental->current_lat ?? $rental->pickup_lat, $rental->current_lng ?? $rental->pickup_lng),
                'protection' => $this->protectionSnapshot((int) $rental->customer_id),
                'start_verification' => [
                    'required' => true,
                    'code' => app(StartVerificationService::class)->codeFor($rental, 'rental'),
                    'verified_at' => app(StartVerificationService::class)->isVerified($rental) ? now()->toIso8601String() : null,
                    'label' => 'Rental handover OTP',
                    'instructions' => 'Share this 4-digit code during vehicle handover, then press customer check-in to confirm the rental start.',
                ],
                'status_steps' => $this->statusSteps('rental', $rental->status, app(StartVerificationService::class)->isVerified($rental)),
            ],
        ]);
    }

    private function updateActiveServiceLocation(Request $request): void
    {
        if (! $request->filled(['service_type', 'booking_id'])) {
            return;
        }

        match ($request->service_type) {
            'ride' => optional(RideBooking::where('id', $request->booking_id)
                ->where('driver_id', $request->user()->id)
                ->first())->update([
                    'current_lat' => $request->latitude,
                    'current_lng' => $request->longitude,
                    'last_location_update' => now(),
                ]),
            'tour' => optional(TourBooking::where('id', $request->booking_id)
                ->whereHas('driverAssignments', fn ($query) => $query->where('driver_id', $request->user()->id))
                ->first())->update([
                    'current_lat' => $request->latitude,
                    'current_lng' => $request->longitude,
                    'last_location_update' => now(),
                ]),
            'rental' => optional(CarRental::where('id', $request->booking_id)
                ->where('driver_id', $request->user()->id)
                ->first())->update([
                    'current_lat' => $request->latitude,
                    'current_lng' => $request->longitude,
                    'last_location_update' => now(),
                ]),
            default => null,
        };
    }

    private function storeDriverLocationHistory(Request $request, ?string $context = null): void
    {
        DriverLocation::create([
            'driver_id' => $request->user()->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'heading' => $request->input('heading'),
            'speed' => $request->input('speed'),
            'accuracy' => $request->input('accuracy'),
            'context' => $context ?? $this->driverLocationContext($request),
        ]);
    }

    private function driverLocationContext(Request $request): string
    {
        if ($request->filled(['service_type', 'booking_id'])) {
            return "{$request->service_type}:{$request->booking_id}";
        }

        return 'idle';
    }

    private function protectionSnapshot(int $customerId): array
    {
        $activePolicy = InsurancePolicy::where('customer_id', $customerId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->latest()
            ->first();

        return [
            'status' => $activePolicy ? 'active' : 'optional',
            'label' => $activePolicy ? $activePolicy->policy_type.' active' : 'Optional',
        ];
    }

    private function trackingState(
        string $status,
        mixed $latitude,
        mixed $longitude,
        mixed $lastLocationUpdate,
        bool $driverAssigned
    ): array {
        $terminal = in_array($status, ['completed', 'cancelled'], true);
        $hasLocation = filled($latitude) && filled($longitude) && filled($lastLocationUpdate);
        $lastUpdate = $lastLocationUpdate ? \Illuminate\Support\Carbon::parse($lastLocationUpdate) : null;
        $staleAfterSeconds = 120;
        $ageSeconds = $lastUpdate ? now()->diffInSeconds($lastUpdate, true) : null;

        $state = match (true) {
            $terminal => 'terminal',
            ! $driverAssigned => 'no_driver_yet',
            ! $hasLocation => 'driver_assigned_no_location',
            $ageSeconds !== null && $ageSeconds > $staleAfterSeconds => 'stale',
            default => 'live',
        };

        return [
            'state' => $state,
            'driver_assigned' => $driverAssigned,
            'has_location' => $hasLocation,
            'last_update' => $lastUpdate?->toIso8601String(),
            'age_seconds' => $ageSeconds,
            'stale_after_seconds' => $staleAfterSeconds,
            'poll_after_seconds' => $state === 'live' ? 10 : 30,
            'terminal' => $terminal,
        ];
    }

    private function statusSteps(string $serviceType, string $status, bool $startedVerified): array
    {
        $normalized = app(BookingStatusService::class)->normalize($serviceType, $status) ?? $status;
        $steps = match ($serviceType) {
            'ride' => ['confirmed', 'driver_assigned', 'pickup', 'in_transit', 'completed'],
            'tour' => ['confirmed', 'in_progress', 'completed'],
            'rental' => ['confirmed', 'driver_assigned', 'in_progress', 'completed'],
            default => ['confirmed', 'completed'],
        };
        $activeIndex = array_search($normalized, $steps, true);
        $activeIndex = $activeIndex === false ? -1 : $activeIndex;

        return collect($steps)->map(function (string $step, int $index) use ($activeIndex, $serviceType, $startedVerified) {
            return [
                'key' => $step,
                'label' => $this->statusLabel($serviceType, $step),
                'done' => $index < $activeIndex || ($step === 'in_transit' && $startedVerified) || ($step === 'in_progress' && $startedVerified),
                'active' => $index === $activeIndex,
            ];
        })->values()->all();
    }

    private function statusLabel(string $serviceType, string $step): string
    {
        return match ($step) {
            'confirmed' => 'Booking confirmed',
            'driver_assigned' => $serviceType === 'rental' ? 'Vehicle/driver assigned' : 'Driver assigned',
            'pickup' => 'Pickup in progress',
            'in_transit' => 'OTP verified and ride started',
            'in_progress' => $serviceType === 'tour' ? 'OTP verified and tour started' : 'OTP verified and rental started',
            'completed' => 'Complete service and rate',
            default => ucfirst(str_replace('_', ' ', $step)),
        };
    }
}
