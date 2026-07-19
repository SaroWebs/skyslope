<?php

namespace App\Http\Controllers\Api;

use App\Events\NewRideRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerApp\BookingReceiptResource;
use App\Http\Resources\CustomerApp\PlaceDetailResource;
use App\Http\Resources\CustomerApp\PlaceMediaResource;
use App\Http\Resources\CustomerApp\PlaceSummaryResource;
use App\Http\Resources\CustomerApp\TourResource;
use App\Models\CarCategory;
use App\Models\CarRental;
use App\Models\CarRentalReview;
use App\Models\Place;
use App\Models\PlaceMedia;
use App\Models\PlaceReview;
use App\Models\RideBooking;
use App\Models\RideBookingReview;
use App\Models\RideBookingTip;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\TourBookingReview;
use App\Models\TourSchedule;
use App\Models\Wallet;
use App\Services\BookingCancellationService;
use App\Services\BookingLifecycleNotifier;
use App\Services\CustomerCouponService;
use App\Services\DriverDispatchService;
use App\Services\RideEstimateService;
use App\Services\StartVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerAppController extends Controller
{
    public function publicBootstrap()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'featured_tours' => Tour::with(['category', 'itineraries.place.media' => fn ($query) => $query->approved(), 'schedules' => fn ($query) => $query
                    ->where('status', 'open')->where('departure_date', '>=', now()->toDateString())])
                    ->active()
                    ->where(fn ($query) => $query->whereNull('available_to')->orWhereDate('available_to', '>=', now()))
                    ->whereHas('schedules', fn ($query) => $query->where('status', 'open')->where('departure_date', '>=', now()->toDateString()))
                    ->orderByDesc('is_featured')
                    ->take(6)
                    ->get()
                    ->map(fn (Tour $tour) => $this->formatPublicTour($tour)),
                'featured_destinations' => Place::with(['media' => fn ($query) => $query->approved()])
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->take(8)
                    ->get()
                    ->map(fn (Place $place) => $this->formatPublicPlaceSummary($place)),
                'car_categories' => CarCategory::active()->take(6)->get(),
            ],
        ]);
    }

    public function publicTours()
    {
        return response()->json([
            'success' => true,
            'data' => Tour::with(['category', 'itineraries.place.media' => fn ($query) => $query->approved(), 'schedules' => function ($q) {
                $q->where('status', 'open')->where('departure_date', '>=', now()->toDateString());
            }])
                ->active()
                ->where(fn ($query) => $query->whereNull('available_from')->orWhereDate('available_from', '<=', now()))
                ->where(fn ($query) => $query->whereNull('available_to')->orWhereDate('available_to', '>=', now()))
                ->whereHas('schedules', fn ($query) => $query
                    ->where('status', 'open')
                    ->where('departure_date', '>=', now()->toDateString())
                    ->whereColumn('total_seats', '>', DB::raw('booked_seats + reserved_seats')))
                ->orderByDesc('is_featured')
                ->orderBy('available_from')
                ->get()
                ->map(fn (Tour $tour) => $this->formatPublicTour($tour)),
        ]);
    }

    public function publicTour(Tour $tour)
    {
        abort_unless(
            $tour->is_active
            && (! $tour->available_from || $tour->available_from->lte(now()))
            && (! $tour->available_to || $tour->available_to->gte(now()->startOfDay())),
            404
        );

        $tour->load(['category', 'itineraries.place.media' => fn ($query) => $query->approved(), 'schedules' => function ($q) {
            $q->where('status', 'open')->where('departure_date', '>=', now());
        }]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPublicTour($tour, true),
        ]);
    }

    public function tourSchedules(Tour $tour)
    {
        abort_unless($tour->is_active, 404);

        $schedules = $tour->schedules()
            ->where('status', 'open')
            ->where('departure_date', '>=', now())
            ->get()
            ->map(fn (TourSchedule $schedule) => [
                'id' => $schedule->id,
                'departure_date' => optional($schedule->departure_date)->toDateString(),
                'return_date' => optional($schedule->return_date)->toDateString(),
                'departure_time' => $schedule->departure_time,
                'departure_point' => $schedule->departure_point,
                'available_seats' => max(0, $schedule->getAvailableSeats()),
                'price' => $schedule->getEffectivePrice(),
                'child_price' => $schedule->getEffectiveChildPrice(),
                'status' => $schedule->getAvailableSeats() > 0 ? $schedule->status : 'sold_out',
            ]);

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function publicDestinations()
    {
        return response()->json([
            'success' => true,
            'data' => Place::with(['media' => fn ($query) => $query->approved()])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Place $place) => $this->formatPublicPlaceSummary($place)),
        ]);
    }

    public function publicPlaces()
    {
        return response()->json([
            'success' => true,
            'data' => Place::with(['media' => fn ($query) => $query->approved()])
                ->where('is_active', true)
                ->orderByDesc('is_featured')
                ->orderByDesc('rating')
                ->get()
                ->map(fn (Place $place) => $this->formatPublicPlaceSummary($place)),
        ]);
    }

    public function publicPopularPlaces()
    {
        return response()->json([
            'success' => true,
            'data' => Place::with(['media' => fn ($query) => $query->approved()])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderByDesc('rating')
                ->take(12)
                ->get()
                ->map(fn (Place $place) => $this->formatPublicPlaceSummary($place)),
        ]);
    }

    public function publicDestination(Place $place)
    {
        $place->load(['media' => fn ($query) => $query->approved(), 'reviews.customer:id,name']);

        return response()->json([
            'success' => true,
            'data' => $this->formatPlaceDetails($place),
        ]);
    }

    public function publicCarCategories()
    {
        $categories = CarCategory::active()->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
            // Kept for older mobile builds that used the original response key.
            'categories' => $categories,
        ]);
    }

    public function uploadPlaceMedia(Request $request, Place $place)
    {
        abort_unless($place->is_active, 404);
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'caption' => 'nullable|string|max:500',
            'is_360' => 'nullable|boolean',
        ]);

        $media = PlaceMedia::create([
            'place_id' => $place->id,
            'uploaded_by_customer_id' => $request->user()->id,
            'path' => $request->file('image')->store('place_media/customer', 'public'),
            'type' => $request->boolean('is_360') ? 'panorama' : 'image',
            'source' => 'customer',
            'approval_status' => 'pending',
            'caption' => $validated['caption'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded and sent to the admin for approval.',
            'data' => (new PlaceMediaResource($media))->resolve($request),
        ], 201);
    }

    public function dashboard(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'customer' => $customer,
            'stats' => [
                'tour_bookings' => TourBooking::where('customer_id', $customer->id)->count(),
                'car_rentals' => CarRental::where('customer_id', $customer->id)->count(),
                'ride_bookings' => RideBooking::where('customer_id', $customer->id)->count(),
                'wallet_balance' => (float) optional(Wallet::forOwner($customer)->first())->balance,
            ],
            'recent_ride_bookings' => RideBooking::with('driver:id,name,phone')
                ->where('customer_id', $customer->id)
                ->latest()
                ->take(5)
                ->get(),
            'recent_bookings' => TourBooking::with('tour:id,title')
                ->where('customer_id', $customer->id)
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function tours()
    {
        $tours = Tour::with('itineraries')
            ->where('available_to', '>=', now())
            ->orderBy('available_from')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tours,
        ]);
    }

    public function tourBookings(Request $request)
    {
        $bookings = TourBooking::with('tour:id,title')
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    public function bookTour(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'tour_schedule_id' => 'required|exists:tour_schedules,id',
            'number_of_adults' => 'required|integer|min:1|max:10',
            'number_of_children' => 'nullable|integer|min:0|max:10',
            'payment_method' => 'required|in:cash,card,wallet,upi',
            'special_requests' => 'nullable|string|max:1000',
            'pickup_option' => 'nullable|string|max:120',
            'insurance_selected' => 'nullable|boolean',
            'coupon_code' => 'nullable|string|max:40',
            'sharing_requested' => 'prohibited',
            'reserved_seats' => 'prohibited',
            'ride_mode' => 'prohibited',
            'car_category_id' => 'prohibited',
            'start_date' => 'prohibited',
            'end_date' => 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $totalPax = (int) $request->number_of_adults + (int) ($request->number_of_children ?? 0);

        try {
            $booking = DB::transaction(function () use ($request, $customer, $totalPax) {
                $schedule = TourSchedule::with('tour')->lockForUpdate()->findOrFail($request->tour_schedule_id);

                if ((int) $schedule->tour_id !== (int) $request->tour_id) {
                    throw new \RuntimeException('The selected departure does not belong to this tour.');
                }

                $tour = $schedule->tour;
                if (! $tour->is_active
                    || ($tour->available_from && $tour->available_from->isFuture())
                    || ($tour->available_to && $tour->available_to->lt(now()->startOfDay()))) {
                    throw new \RuntimeException('This tour is not currently bookable.');
                }

                if (! $schedule->isAvailable()) {
                    throw new \RuntimeException('This departure is no longer available or is sold out.');
                }

                if ($schedule->getAvailableSeats() < $totalPax) {
                    throw new \RuntimeException('Only '.$schedule->getAvailableSeats().' seats remaining.');
                }

                $subtotal = ((int) $request->number_of_adults * $schedule->getEffectivePrice())
                    + ((int) ($request->number_of_children ?? 0) * $schedule->getEffectiveChildPrice());
                $couponResult = app(CustomerCouponService::class)->preview($customer, $request->input('coupon_code'), 'tour', (float) $subtotal);

                if ($request->filled('coupon_code') && ! $couponResult['eligible']) {
                    throw new \RuntimeException($couponResult['message']);
                }

                $discountAmount = (float) ($couponResult['discount_amount'] ?? 0);
                $totalPrice = max(0, round((float) $subtotal - $discountAmount, 2));

                $booking = TourBooking::create([
                    'customer_id' => $customer->id,
                    'tour_id' => $schedule->tour_id,
                    'tour_schedule_id' => $schedule->id,
                    'number_of_adults' => $request->number_of_adults,
                    'number_of_children' => $request->number_of_children ?? 0,
                    'travel_date' => $schedule->departure_date,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'price_per_adult' => $schedule->getEffectivePrice(),
                    'price_per_child' => $schedule->getEffectiveChildPrice(),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,
                    'payment_method' => $request->payment_method,
                    'coupon_code' => $couponResult['eligible'] ? $couponResult['code'] : null,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'special_requests' => $this->appendStructuredNotes($request->special_requests, [
                        'pickup_option' => $request->input('pickup_option'),
                        'insurance_selected' => $request->boolean('insurance_selected'),
                    ]),
                    'whatsapp_notification' => true,
                ]);

                $schedule->increment('reserved_seats', $totalPax);

                if ($couponResult['eligible'] && $couponResult['coupon']) {
                    app(CustomerCouponService::class)->redeem($customer, $couponResult['coupon'], $booking, 'tour', (float) $subtotal, $discountAmount);
                }

                // Handle Wallet Payment
                if ($request->payment_method === 'wallet') {
                    $wallet = Wallet::forOwner($customer)->first();
                    if (! $wallet || ! $wallet->hasSufficientBalance($totalPrice)) {
                        throw new \RuntimeException('Insufficient wallet balance.');
                    }
                    $wallet->debit($totalPrice, "Payment for Tour Booking #{$booking->id}", 'tour_booking', $booking->id);
                    $booking->update(['payment_status' => 'paid', 'status' => 'confirmed']);
                    $schedule->decrement('reserved_seats', $totalPax);
                    $schedule->increment('booked_seats', $totalPax);
                }

                return $booking;
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        app(BookingLifecycleNotifier::class)->emit($booking->fresh('customer'), 'booking.created');
        if ($booking->payment_status === 'paid') {
            app(BookingLifecycleNotifier::class)->emit($booking->fresh('customer'), 'payment.paid');
        }

        return response()->json([
            'success' => true,
            'message' => 'Tour booked successfully.',
            'data' => $booking->load('tour:id,title'),
            'receipt' => $this->formatBookingReceipt($booking, 'tour'),
        ], 201);
    }

    public function carCategories()
    {
        return response()->json([
            'success' => true,
            'data' => CarCategory::active()->get(),
        ]);
    }

    public function carRentals(Request $request)
    {
        $rentals = CarRental::with(['carCategory', 'driver:id,name,phone'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $rentals,
        ]);
    }

    public function bookCar(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'car_category_id' => 'required|exists:car_categories,id',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'required|string|max:255',
            'pickup_lat' => 'nullable|numeric|between:-90,90',
            'pickup_lng' => 'nullable|numeric|between:-180,180',
            'dropoff_location' => 'nullable|string|max:255',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'destination_details' => 'nullable|string',
            'special_requests' => 'nullable|string',
            'distance_km' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,wallet,upi',
            'extras' => 'nullable|array',
            'insurance_selected' => 'nullable|boolean',
            'coupon_code' => 'nullable|string|max:40',
            'sharing_requested' => 'prohibited',
            'reserved_seats' => 'prohibited',
            'ride_mode' => 'prohibited',
            'tour_id' => 'prohibited',
            'tour_schedule_id' => 'prohibited',
            'number_of_adults' => 'prohibited',
            'number_of_children' => 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = CarCategory::findOrFail($request->car_category_id);
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $distanceKm = (float) ($request->distance_km ?? 0);
        $pricing = $category->calculatePrice($numberOfDays, $distanceKm);
        $couponResult = app(CustomerCouponService::class)->preview($customer, $request->input('coupon_code'), 'rental', (float) $pricing['subtotal']);

        if ($request->filled('coupon_code') && ! $couponResult['eligible']) {
            return response()->json(['success' => false, 'message' => $couponResult['message']], 422);
        }

        try {
            $rental = DB::transaction(function () use ($request, $customer, $category, $numberOfDays, $distanceKm, $pricing, $couponResult) {
                $discountAmount = (float) ($couponResult['discount_amount'] ?? 0);
                $totalPrice = max(0, round((float) $pricing['subtotal'] - $discountAmount, 2));

                $rental = CarRental::create([
                    'customer_id' => $customer->id,
                    'car_category_id' => $category->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'start_time' => $request->input('start_time', '09:00'),
                    'end_time' => $request->input('end_time', '18:00'),
                    'pickup_location' => $request->pickup_location,
                    'pickup_lat' => $request->pickup_lat,
                    'pickup_lng' => $request->pickup_lng,
                    'dropoff_location' => $request->dropoff_location,
                    'dropoff_lat' => $request->dropoff_lat,
                    'dropoff_lng' => $request->dropoff_lng,
                    'destination_details' => $request->destination_details,
                    'number_of_days' => $numberOfDays,
                    'base_price' => $pricing['base_price'],
                    'distance_km' => $distanceKm,
                    'distance_price' => $pricing['distance_price'],
                    'extras_price' => 0,
                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => $request->payment_method ?? 'cash',
                    'coupon_code' => $couponResult['eligible'] ? $couponResult['code'] : null,
                    'special_requests' => $this->appendStructuredNotes($request->special_requests, [
                        'extras' => $request->input('extras', []),
                        'insurance_selected' => $request->boolean('insurance_selected'),
                    ]),
                    'whatsapp_notification' => true,
                    'email_notification' => true,
                    'sms_notification' => false,
                ]);

                if ($couponResult['eligible'] && $couponResult['coupon']) {
                    app(CustomerCouponService::class)->redeem($customer, $couponResult['coupon'], $rental, 'rental', (float) $pricing['subtotal'], $discountAmount);
                }

                // Handle Wallet Payment
                if ($request->payment_method === 'wallet') {
                    $wallet = Wallet::forOwner($customer)->first();
                    if (! $wallet || ! $wallet->hasSufficientBalance($totalPrice)) {
                        throw new \RuntimeException('Insufficient wallet balance.');
                    }
                    $wallet->debit($totalPrice, "Payment for Car Rental #{$rental->id}", 'car_rental', $rental->id);
                    $rental->update(['payment_status' => 'paid', 'status' => 'confirmed']);
                }

                return $rental;
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        app(BookingLifecycleNotifier::class)->emit($rental->fresh('customer'), 'booking.created');
        if ($rental->payment_status === 'paid') {
            app(BookingLifecycleNotifier::class)->emit($rental->fresh('customer'), 'payment.paid');
        }

        return response()->json([
            'success' => true,
            'message' => 'Car rental booking created successfully.',
            'data' => $rental->load('carCategory'),
            'receipt' => $this->formatBookingReceipt($rental, 'rental'),
        ], 201);
    }

    public function rides(Request $request)
    {
        $rides = RideBooking::with('driver:id,name,phone')
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $rides,
        ]);
    }

    public function showRide(Request $request, RideBooking $booking)
    {
        Gate::authorize('view', $booking);

        $booking->load([
            'driver:id,name,phone,rating,vehicle_number,vehicle_model,vehicle_color',
            'reviews',
            'tips',
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    public function cancelRide(Request $request, RideBooking $booking)
    {
        Gate::authorize('view', $booking);

        return $this->cancelCustomerBooking($request, $booking, 'ride');
    }

    public function estimateRide(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'service_type' => 'required|in:point_to_point,hourly,hourly_rental,round_trip',
            'scheduled_at' => 'required|date|after:now',
            'coupon_code' => 'nullable|string|max:40',
            'sharing_requested' => 'sometimes|boolean',
            'reserved_seats' => 'sometimes|integer|min:1|max:3',
            'vehicle_class' => 'sometimes|in:mini,comfort,xl',
            'tour_id' => 'prohibited',
            'tour_schedule_id' => 'prohibited',
            'number_of_adults' => 'prohibited',
            'number_of_children' => 'prohibited',
            'car_category_id' => 'prohibited',
            'start_date' => 'prohibited',
            'end_date' => 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->boolean('sharing_requested') && $request->service_type !== 'point_to_point') {
            return response()->json([
                'success' => false,
                'message' => 'Ride sharing is available only for point-to-point rides.',
            ], 422);
        }

        $result = app(RideEstimateService::class)->estimate(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            $request->dropoff_lat ? (float) $request->dropoff_lat : null,
            $request->dropoff_lng ? (float) $request->dropoff_lng : null,
            $request->service_type,
            $request->boolean('sharing_requested'),
            (int) $request->input('reserved_seats', 1),
            $request->input('vehicle_class', 'comfort')
        );

        if ($request->filled('coupon_code')) {
            $couponResult = app(CustomerCouponService::class)->preview(
                $request->user(),
                $request->input('coupon_code'),
                'ride',
                (float) $result['pricing']['subtotal']
            );

            $result['coupon'] = $this->formatCouponResult($couponResult);
            $result['pricing']['discount_amount'] = (float) $couponResult['discount_amount'];
            $result['pricing']['total'] = (float) $couponResult['final_amount'];
        }

        return response()->json($result);
    }

    public function storeRide(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'service_type' => 'required|in:point_to_point,hourly,hourly_rental,round_trip',
            'pickup_location' => 'required|string|max:255',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'dropoff_location' => 'nullable|string|max:255',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'scheduled_at' => 'required|date|after:now',
            'special_requests' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,wallet,upi',
            'coupon_code' => 'nullable|string|max:40',
            'sharing_requested' => 'sometimes|boolean',
            'reserved_seats' => 'sometimes|integer|min:1|max:3',
            'vehicle_class' => 'sometimes|in:mini,comfort,xl',
            'tour_id' => 'prohibited',
            'tour_schedule_id' => 'prohibited',
            'number_of_adults' => 'prohibited',
            'number_of_children' => 'prohibited',
            'car_category_id' => 'prohibited',
            'start_date' => 'prohibited',
            'end_date' => 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->boolean('sharing_requested') && $request->service_type !== 'point_to_point') {
            return response()->json([
                'success' => false,
                'message' => 'Ride sharing is available only for point-to-point rides.',
            ], 422);
        }

        $estimate = app(RideEstimateService::class)->estimate(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            $request->dropoff_lat ? (float) $request->dropoff_lat : null,
            $request->dropoff_lng ? (float) $request->dropoff_lng : null,
            $request->service_type,
            $request->boolean('sharing_requested'),
            (int) $request->input('reserved_seats', 1),
            $request->input('vehicle_class', 'comfort')
        );
        $couponResult = app(CustomerCouponService::class)->preview($customer, $request->input('coupon_code'), 'ride', (float) $estimate['pricing']['subtotal']);

        if ($request->filled('coupon_code') && ! $couponResult['eligible']) {
            return response()->json(['success' => false, 'message' => $couponResult['message']], 422);
        }

        try {
            $ride = DB::transaction(function () use ($request, $customer, $estimate, $couponResult) {
                $discountAmount = (float) ($couponResult['discount_amount'] ?? 0);
                $totalFare = max(0, round((float) $estimate['pricing']['subtotal'] - $discountAmount, 2));

                $ride = RideBooking::create([
                    'booking_number' => RideBooking::generateBookingNumber(),
                    'customer_id' => $customer->id,
                    'service_type' => $estimate['service_type'],
                    'ride_mode' => $estimate['sharing']['requested'] ? 'shared' : 'private',
                    'sharing_requested' => $estimate['sharing']['requested'],
                    'sharing_enabled_by' => $estimate['sharing']['enabled_by'],
                    'reserved_seats' => $estimate['sharing']['reserved_seats'],
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'customer_phone' => $customer->phone,
                    'pickup_location' => $request->pickup_location,
                    'pickup_lat' => $request->pickup_lat,
                    'pickup_lng' => $request->pickup_lng,
                    'dropoff_location' => $request->dropoff_location,
                    'dropoff_lat' => $request->dropoff_lat,
                    'dropoff_lng' => $request->dropoff_lng,
                    'scheduled_at' => $request->scheduled_at,
                    'estimated_distance_km' => $estimate['estimated_distance_km'],
                    'estimated_duration' => $estimate['estimated_duration'],
                    'base_fare' => $estimate['pricing']['base_fare'],
                    'distance_fare' => $estimate['pricing']['distance_fare'],
                    'surge_multiplier' => $estimate['pricing']['surge_multiplier'],
                    'full_car_fare' => $estimate['pricing']['private_subtotal'],
                    'sharing_discount_percent' => $estimate['pricing']['sharing_discount_percent'],
                    'sharing_savings' => $estimate['pricing']['sharing_savings'],
                    'discount_amount' => $discountAmount,
                    'total_fare' => $totalFare,
                    'payment_method' => $request->payment_method,
                    'coupon_code' => $couponResult['eligible'] ? $couponResult['code'] : null,
                    'special_requests' => $request->special_requests,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ]);

                if ($couponResult['eligible'] && $couponResult['coupon']) {
                    app(CustomerCouponService::class)->redeem($customer, $couponResult['coupon'], $ride, 'ride', (float) $estimate['pricing']['subtotal'], $discountAmount);
                }

                // Handle Wallet Payment
                if ($request->payment_method === 'wallet') {
                    $wallet = Wallet::forOwner($customer)->first();
                    if (! $wallet || ! $wallet->hasSufficientBalance($totalFare)) {
                        throw new \RuntimeException('Insufficient wallet balance.');
                    }
                    $wallet->debit($totalFare, "Payment for Ride Booking #{$ride->id}", 'ride_booking', $ride->id);
                    $ride->update(['payment_status' => 'paid']);
                }

                return $ride;
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $dispatchService = app(DriverDispatchService::class);
        $dispatchCandidates = $dispatchService->rankedCandidates(
            $estimate['ride_classification'],
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            null,
            10,
            $ride->car_category_id ? (int) $ride->car_category_id : null,
            (bool) $ride->sharing_requested
        );
        $dispatchService->createRideAttempts($ride, $dispatchCandidates);
        $candidateDriverIds = $dispatchCandidates
            ->pluck('driver_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $ride->update([
            'dispatch_status' => count($candidateDriverIds) > 0 ? 'offered' : 'admin_queue',
            'admin_assignable' => count($candidateDriverIds) === 0,
            'dispatch_failed_at' => count($candidateDriverIds) === 0 ? now() : null,
        ]);

        broadcast(new NewRideRequest($ride, $candidateDriverIds));
        app(BookingLifecycleNotifier::class)->emit($ride->fresh('customer'), 'booking.created');
        if ($ride->payment_status === 'paid') {
            app(BookingLifecycleNotifier::class)->emit($ride->fresh('customer'), 'payment.paid');
        }

        return response()->json([
            'success' => true,
            'data' => $ride->load('driver:id,name,phone'),
            'receipt' => $this->formatBookingReceipt($ride, 'ride'),
            'dispatch' => [
                'classification' => $estimate['ride_classification'],
                'candidate_count' => count($candidateDriverIds),
                'candidate_driver_ids' => $candidateDriverIds,
                'admin_assignable' => (bool) $ride->admin_assignable,
                'dispatch_status' => $ride->dispatch_status,
            ],
            'sharing' => $estimate['sharing'],
        ], 201);
    }

    public function previewCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:40',
            'service_type' => 'required|in:ride,tour,rental',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $result = app(CustomerCouponService::class)->preview(
            $request->user(),
            $validated['coupon_code'],
            $validated['service_type'],
            (float) $validated['subtotal']
        );

        return response()->json([
            'success' => (bool) $result['eligible'],
            'message' => $result['message'],
            'data' => $this->formatCouponResult($result),
        ], $result['eligible'] ? 200 : 422);
    }

    public function availableCoupons(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:ride,tour,rental',
            'subtotal' => 'required|numeric|min:0',
        ]);

        return response()->json([
            'success' => true,
            'data' => app(CustomerCouponService::class)->availableOffers(
                $request->user(),
                $validated['service_type'],
                (float) $validated['subtotal']
            ),
        ]);
    }

    public function submitReview(Request $request, RideBooking $booking)
    {
        Gate::authorize('review', $booking);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = RideBookingReview::updateOrCreate(
            [
                'ride_booking_id' => $booking->id,
                'customer_id' => $request->user()->id,
            ],
            [
                'driver_id' => $booking->driver_id,
                'driver_rating' => $validated['rating'],
                'review' => $validated['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    public function cancelTour(Request $request, TourBooking $booking)
    {
        Gate::authorize('view', $booking);

        return $this->cancelCustomerBooking($request, $booking, 'tour');
    }

    public function submitTip(Request $request, RideBooking $booking)
    {
        Gate::authorize('tip', $booking);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:50000',
        ]);

        $customerWallet = Wallet::forOwner($request->user())->first();
        $driver = $booking->driver;
        $driverWallet = $driver ? Wallet::forOwner($driver)->firstOrCreate([
            'owner_type' => $driver::class,
            'owner_id' => $driver->id,
        ], [
            'balance' => 0,
            'currency' => 'INR',
            'status' => 'active',
        ]) : null;

        if (! $customerWallet || ! $driverWallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not available.',
            ], 422);
        }

        $amount = round((float) $validated['amount'], 2);

        DB::transaction(function () use ($customerWallet, $driverWallet, $booking, $amount, $request) {
            if (! $customerWallet->debit($amount, 'Tip paid for booking #'.$booking->booking_number, 'ride_tip_'.$booking->id)) {
                abort(422, 'Insufficient wallet balance.');
            }

            $driverWallet->credit($amount, 'Tip received for booking #'.$booking->booking_number, 'ride_tip_'.$booking->id);

            RideBookingTip::create([
                'ride_booking_id' => $booking->id,
                'customer_id' => $request->user()->id,
                'driver_id' => $booking->driver_id,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'status' => 'paid',
            ]);
        });

        return response()->json([
            'success' => true,
        ]);
    }

    public function placeReviews(Place $place)
    {
        return response()->json([
            'success' => true,
            'data' => $place->reviews()->with('customer:id,name')->paginate(20),
        ]);
    }

    public function submitPlaceReview(Request $request, Place $place)
    {
        Gate::authorize('create', [PlaceReview::class, $place]);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $review = PlaceReview::updateOrCreate(
            ['place_id' => $place->id, 'customer_id' => $request->user()->id],
            $validated
        );

        $place->update([
            'rating' => round((float) $place->reviews()->avg('rating'), 2),
            'review_count' => $place->reviews()->count(),
        ]);

        return response()->json(['success' => true, 'data' => $review->load('customer:id,name')]);
    }

    public function submitTourReview(Request $request, TourBooking $booking)
    {
        Gate::authorize('review', $booking);

        $validated = $request->validate([
            'tour_rating' => 'nullable|integer|min:1|max:5',
            'driver_rating' => 'nullable|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $review = TourBookingReview::updateOrCreate(
            ['tour_booking_id' => $booking->id, 'customer_id' => $request->user()->id],
            array_merge($validated, ['driver_id' => $booking->assigned_driver_id])
        );

        return response()->json(['success' => true, 'data' => $review]);
    }

    public function submitRentalReview(Request $request, CarRental $rental)
    {
        Gate::authorize('review', $rental);

        $validated = $request->validate([
            'rental_rating' => 'nullable|integer|min:1|max:5',
            'driver_rating' => 'nullable|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $review = CarRentalReview::updateOrCreate(
            ['car_rental_id' => $rental->id, 'customer_id' => $request->user()->id],
            array_merge($validated, ['driver_id' => $rental->driver_id])
        );

        return response()->json(['success' => true, 'data' => $review]);
    }

    public function cancelRental(Request $request, CarRental $rental)
    {
        Gate::authorize('view', $rental);

        return $this->cancelCustomerBooking($request, $rental, 'rental');
    }

    public function storeSupportRequest(Request $request)
    {
        $validated = $request->validate([
            'service_type' => 'required|in:ride,tour,rental',
            'booking_id' => 'required|integer|min:1',
            'topic' => 'required|string|max:120',
            'details' => 'required|string|max:2000',
            'severity' => 'nullable|in:low,medium,high,critical',
        ]);

        $booking = match ($validated['service_type']) {
            'ride' => RideBooking::where('customer_id', $request->user()->id)->findOrFail($validated['booking_id']),
            'tour' => TourBooking::where('customer_id', $request->user()->id)->findOrFail($validated['booking_id']),
            'rental' => CarRental::where('customer_id', $request->user()->id)->findOrFail($validated['booking_id']),
        };

        $topic = strtolower($validated['topic']);
        $type = match (true) {
            str_contains($topic, 'payment') || str_contains($topic, 'wallet') => 'payment',
            str_contains($topic, 'cancel') || str_contains($topic, 'refund') => 'dispute',
            str_contains($topic, 'emergency') || str_contains($topic, 'safety') => 'safety',
            str_contains($topic, 'service') || str_contains($topic, 'active') => 'service_quality',
            default => 'other',
        };

        $incident = $booking->incidents()->create([
            'customer_id' => $request->user()->id,
            'driver_id' => $booking->driver_id ?? $booking->assigned_driver_id ?? null,
            'opened_by_type' => get_class($request->user()),
            'opened_by_id' => $request->user()->id,
            'type' => $type,
            'severity' => $validated['severity'] ?? 'medium',
            'status' => 'open',
            'title' => $validated['topic'],
            'description' => $validated['details'],
            'reported_at' => now(),
        ]);

        app(BookingLifecycleNotifier::class)->emit($booking->fresh('customer'), 'support.updated');

        return response()->json([
            'success' => true,
            'message' => 'Support request submitted successfully.',
            'data' => $incident,
        ], 201);
    }

    public function rideNextSteps(Request $request, int $booking)
    {
        $ride = RideBooking::where('customer_id', $request->user()->id)->findOrFail($booking);

        return $this->bookingNextStepsResponse($ride, 'ride');
    }

    public function tourNextSteps(Request $request, int $booking)
    {
        $tour = TourBooking::with('tour:id,title,start_location,end_location')
            ->where('customer_id', $request->user()->id)
            ->findOrFail($booking);

        return $this->bookingNextStepsResponse($tour, 'tour');
    }

    public function rentalNextSteps(Request $request, int $booking)
    {
        $rental = CarRental::with('carCategory:id,name')
            ->where('customer_id', $request->user()->id)
            ->findOrFail($booking);

        return $this->bookingNextStepsResponse($rental, 'rental');
    }

    private function bookingNextStepsResponse(RideBooking|TourBooking|CarRental $model, string $serviceType)
    {
        $verification = app(StartVerificationService::class);

        return response()->json([
            'success' => true,
            'data' => [
                'service_type' => $serviceType,
                'booking_id' => $model->id,
                'booking_number' => $model->booking_number,
                'status' => $model->status,
                'payment_status' => $model->payment_status,
                'tracking_url' => "/tracking?kind={$serviceType}&id={$model->id}",
                'start_verification' => [
                    'required' => true,
                    'code' => $verification->codeFor($model, $serviceType),
                    'verified' => $verification->isVerified($model),
                ],
                'instructions' => $this->nextStepInstructions($model, $serviceType),
                'actions' => [
                    'can_check_in' => ! in_array($model->status, ['completed', 'cancelled'], true),
                    'can_track' => true,
                    'can_contact_support' => true,
                    'can_review' => $model->status === 'completed',
                ],
            ],
        ]);
    }

    public function rideCheckIn(Request $request, int $booking)
    {
        $ride = RideBooking::where('customer_id', $request->user()->id)->findOrFail($booking);

        return $this->recordBookingCheckIn($request, $ride, 'ride');
    }

    public function tourCheckIn(Request $request, int $booking)
    {
        $tour = TourBooking::where('customer_id', $request->user()->id)->findOrFail($booking);

        return $this->recordBookingCheckIn($request, $tour, 'tour');
    }

    public function rentalCheckIn(Request $request, int $booking)
    {
        $rental = CarRental::where('customer_id', $request->user()->id)->findOrFail($booking);

        return $this->recordBookingCheckIn($request, $rental, 'rental');
    }

    private function recordBookingCheckIn(
        Request $request,
        RideBooking|TourBooking|CarRental $model,
        string $serviceType
    ) {
        if (in_array($model->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Completed or cancelled bookings cannot be checked in.',
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'note' => 'nullable|string|max:500',
        ]);

        $audit = app(StartVerificationService::class)->checkIn($model, $request->user(), $validated);
        app(BookingLifecycleNotifier::class)->emit($model->fresh('customer'), 'booking.started', [
            'actor' => 'customer',
            'check_in_id' => $audit->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer check-in recorded. Share the start OTP with the assigned operator at pickup/handover.',
            'data' => [
                'check_in' => $audit,
                'next_steps' => $this->nextStepInstructions($model->fresh(), $serviceType),
            ],
        ]);
    }

    private function cancelCustomerBooking(Request $request, RideBooking|TourBooking|CarRental $booking, string $serviceType)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Completed or cancelled bookings cannot be cancelled again.',
            ], 422);
        }

        $refund = app(BookingCancellationService::class)->cancel(
            $booking,
            $serviceType,
            $validated['reason'] ?? null
        );

        $booking->refresh();
        app(BookingLifecycleNotifier::class)->emit($booking->fresh('customer'), 'booking.cancelled');
        if ($refund && $refund->status === 'processed') {
            app(BookingLifecycleNotifier::class)->emit($booking->fresh('customer'), 'refund.processed');
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => [
                'booking' => $booking,
                'refund' => $refund,
                'receipt' => $this->formatBookingReceipt($booking, $serviceType, $refund),
            ],
        ]);
    }

    private function nextStepInstructions(RideBooking|TourBooking|CarRental $booking, string $serviceType): array
    {
        if ($booking->status === 'completed') {
            return [
                'Service is completed.',
                'Rate the service and add a tip or review if you want.',
                'Contact support from the booking if any receipt, refund, or claim detail looks wrong.',
            ];
        }

        if ($booking->status === 'cancelled') {
            return [
                'Booking is cancelled.',
                'Track refund state from the receipt or wallet.',
                'Contact support if the cancellation reason or refund amount needs review.',
            ];
        }

        $payment = $booking->payment_status === 'paid'
            ? 'Payment is already recorded.'
            : 'Payment can be completed now or settled later according to the selected method.';

        return match ($serviceType) {
            'ride' => [
                $payment,
                'Keep pickup notes and phone reachable for driver assignment.',
                'When the driver arrives, share the start OTP shown on tracking.',
                'Track driver location and ETA from the live tracking screen.',
            ],
            'tour' => [
                $payment,
                'Review schedule, pickup option, participants, and inclusions before travel.',
                'At guide/driver check-in, share the tour start OTP and press customer check-in.',
                'Track tour status, current stop, weather, support, and rating from tracking.',
            ],
            'rental' => [
                $payment,
                'Carry ID and inspect vehicle condition/features at handover.',
                'At handover, share the rental start OTP and press customer check-in.',
                'Track rental location, roadside care, insurance, and return steps from tracking.',
            ],
            default => [],
        };
    }

    private function appendStructuredNotes(?string $notes, array $metadata): ?string
    {
        $lines = collect([$notes])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value));

        foreach ($metadata as $key => $value) {
            if ($value === null || $value === false || $value === [] || $value === '') {
                continue;
            }

            $formatted = is_array($value) ? implode(', ', array_filter($value)) : (string) $value;
            if ($formatted !== '') {
                $lines->push(str_replace('_', ' ', ucfirst($key)).': '.$formatted);
            }
        }

        return $lines->isEmpty() ? null : $lines->join("\n");
    }

    private function formatCouponResult(array $result): array
    {
        return [
            'eligible' => (bool) ($result['eligible'] ?? false),
            'code' => $result['code'] ?? null,
            'name' => $result['name'] ?? null,
            'description' => $result['description'] ?? null,
            'service_type' => $result['service_type'] ?? null,
            'subtotal' => (float) ($result['subtotal'] ?? 0),
            'discount_amount' => (float) ($result['discount_amount'] ?? 0),
            'final_amount' => (float) ($result['final_amount'] ?? 0),
            'message' => $result['message'] ?? null,
        ];
    }

    private function formatBookingReceipt(RideBooking|TourBooking|CarRental $booking, string $serviceType, mixed $refund = null): array
    {
        return (new BookingReceiptResource($booking, $serviceType, $refund))->resolve(request());
    }

    private function formatPlaceDetails(Place $place): array
    {
        return (new PlaceDetailResource($place, $this->relatedToursForPlace($place)))->resolve(request());
    }

    private function formatPublicPlaceSummary(Place $place): array
    {
        return (new PlaceSummaryResource($place))->resolve(request());
    }

    private function formatPublicTour(Tour $tour, bool $includePlaces = false): array
    {
        return (new TourResource($tour, $includePlaces ? $this->relatedPlacesForTour($tour) : null))->resolve(request());
    }

    private function relatedToursForPlace(Place $place)
    {
        $terms = collect([
            $place->name,
            $place->slug,
            $place->location,
            $place->city,
            $place->state,
            ...($place->tags ?? []),
        ])
            ->filter()
            ->map(fn ($term) => Str::lower((string) $term))
            ->filter(fn ($term) => Str::length($term) > 2)
            ->values();

        if ($terms->isEmpty()) {
            return collect();
        }

        $explicitTours = Tour::with(['itineraries.place.media' => fn ($query) => $query->approved(), 'schedules' => function ($q) {
            $q->where('status', 'open')->where('departure_date', '>=', now());
        }])
            ->active()
            ->where('available_to', '>=', now())
            ->whereHas('itineraries', fn ($query) => $query->where('place_id', $place->id))
            ->get();

        if ($explicitTours->isNotEmpty()) {
            return $explicitTours->values();
        }

        return Tour::with(['itineraries.place.media' => fn ($query) => $query->approved(), 'schedules' => function ($q) {
            $q->where('status', 'open')->where('departure_date', '>=', now());
        }])
            ->active()
            ->where('available_to', '>=', now())
            ->get()
            ->filter(function (Tour $tour) use ($terms) {
                $haystack = Str::lower(collect([
                    $tour->title,
                    $tour->description,
                    $tour->short_description,
                    $tour->start_location,
                    $tour->end_location,
                    $tour->region,
                    ...($tour->highlights ?? []),
                    ...$tour->itineraries->flatMap(fn ($itinerary) => [
                        $itinerary->title,
                        $itinerary->description,
                        ...($itinerary->activities ?? []),
                    ])->all(),
                ])->filter()->join(' '));

                return $terms->contains(fn ($term) => Str::contains($haystack, $term));
            })
            ->values();
    }

    private function relatedPlacesForTour(Tour $tour)
    {
        $terms = collect([
            $tour->title,
            $tour->description,
            $tour->short_description,
            $tour->start_location,
            $tour->end_location,
            $tour->region,
            ...($tour->highlights ?? []),
            ...$tour->itineraries->flatMap(fn ($itinerary) => [
                $itinerary->title,
                $itinerary->description,
                ...($itinerary->activities ?? []),
            ])->all(),
        ])
            ->filter()
            ->map(fn ($term) => Str::lower((string) $term))
            ->join(' ');

        if (! $terms) {
            return collect();
        }

        $explicitPlaceIds = $tour->itineraries
            ->pluck('place_id')
            ->filter()
            ->unique()
            ->values();

        if ($explicitPlaceIds->isNotEmpty()) {
            return Place::with(['media' => fn ($query) => $query->approved()])
                ->whereIn('id', $explicitPlaceIds)
                ->where('is_active', true)
                ->get()
                ->values();
        }

        return Place::with(['media' => fn ($query) => $query->approved()])
            ->where('is_active', true)
            ->get()
            ->filter(function (Place $place) use ($terms) {
                $placeTerms = collect([
                    $place->name,
                    $place->slug,
                    $place->location,
                    $place->city,
                    $place->state,
                    ...($place->tags ?? []),
                ])
                    ->filter()
                    ->map(fn ($term) => Str::lower((string) $term))
                    ->filter(fn ($term) => Str::length($term) > 2);

                return $placeTerms->contains(fn ($term) => Str::contains($terms, $term));
            })
            ->values();
    }
}
