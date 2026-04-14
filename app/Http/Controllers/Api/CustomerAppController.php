<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CarCategory;
use App\Models\CarRental;
use App\Models\Place;
use App\Models\Tour;
use App\Models\TourSchedule;
use App\Models\TourBooking;
use App\Models\RideBooking;
use App\Models\RideBookingReview;
use App\Models\RideBookingTip;
use App\Models\Wallet;
use App\Services\RideEstimateService;
use App\Events\NewRideRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerAppController extends Controller
{
    public function publicBootstrap()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'featured_tours' => Tour::with('itineraries')
                    ->where('available_to', '>=', now())
                    ->orderBy('available_from')
                    ->take(6)
                    ->get(),
                'featured_destinations' => Place::with('media')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->take(8)
                    ->get(),
                'car_categories' => CarCategory::active()->take(6)->get(),
            ],
        ]);
    }

    public function publicTours()
    {
        return response()->json([
            'success' => true,
            'data' => Tour::with('itineraries')
                ->where('available_to', '>=', now())
                ->orderBy('available_from')
                ->get(),
        ]);
    }

    public function publicTour(Tour $tour)
    {
        $tour->load(['itineraries.place.media', 'schedules' => function ($q) {
            $q->where('status', 'open')->where('departure_date', '>=', now());
        }]);

        return response()->json([
            'success' => true,
            'data' => $tour,
        ]);
    }

    public function tourSchedules(Tour $tour)
    {
        $schedules = $tour->schedules()
            ->where('status', 'open')
            ->where('departure_date', '>=', now())
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    public function publicDestinations()
    {
        return response()->json([
            'success' => true,
            'data' => Place::with('media')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function publicDestination(Place $place)
    {
        $place->load(['media', 'itineraries.tour']);

        return response()->json([
            'success' => true,
            'data' => $place,
        ]);
    }

    public function publicCarCategories()
    {
        return response()->json([
            'success' => true,
            'data' => CarCategory::active()->get(),
        ]);
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule = TourSchedule::with('tour')->findOrFail($request->tour_schedule_id);

        if (!$schedule->isAvailable()) {
            return response()->json(['success' => false, 'message' => 'This schedule is no longer available or is sold out.'], 400);
        }

        $totalPax = (int)$request->number_of_adults + (int)($request->number_of_children ?? 0);
        
        if ($schedule->getAvailableSeats() < $totalPax) {
            return response()->json(['success' => false, 'message' => 'Only ' . $schedule->getAvailableSeats() . ' seats remaining.'], 400);
        }

        $priceAdult = $schedule->getEffectivePrice();
        $priceChild = $schedule->getEffectiveChildPrice();
        $subtotal = ($request->number_of_adults * $priceAdult) + ($request->number_of_children * $priceChild);

        $booking = DB::transaction(function () use ($request, $customer, $schedule, $subtotal, $totalPax) {
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
                'total_price' => $subtotal,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'payment_status' => 'pending',
                'special_requests' => $request->special_requests,
                'whatsapp_notification' => true,
            ]);

            // Handle Wallet Payment
            if ($request->payment_method === 'wallet') {
                $wallet = Wallet::forOwner($customer)->first();
                if (!$wallet || !$wallet->hasSufficientBalance($subtotal)) {
                    throw new \RuntimeException('Insufficient wallet balance.');
                }
                $wallet->debit($subtotal, "Payment for Tour Booking #{$booking->id}", 'tour_booking', $booking->id);
                $booking->update(['payment_status' => 'paid', 'status' => 'confirmed']);
            }

            // Update inventory
            $schedule->increment('booked_seats', $totalPax);

            return $booking;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tour booked successfully.',
            'data' => $booking->load('tour:id,title'),
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
            'dropoff_location' => 'nullable|string|max:255',
            'destination_details' => 'nullable|string',
            'special_requests' => 'nullable|string',
            'distance_km' => 'nullable|numeric|min:0',
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

        $rental = DB::transaction(function () use ($request, $customer, $category, $numberOfDays, $distanceKm, $pricing) {
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
                'dropoff_location' => $request->dropoff_location,
                'destination_details' => $request->destination_details,
                'number_of_days' => $numberOfDays,
                'base_price' => $pricing['base_price'],
                'distance_km' => $distanceKm,
                'distance_price' => $pricing['distance_price'],
                'extras_price' => 0,
                'discount_amount' => 0,
                'total_price' => $pricing['subtotal'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method ?? 'cash',
                'special_requests' => $request->special_requests,
                'whatsapp_notification' => true,
                'email_notification' => true,
                'sms_notification' => false,
            ]);

            // Handle Wallet Payment
            if ($request->payment_method === 'wallet') {
                $wallet = Wallet::forOwner($customer)->first();
                $totalPrice = (float) $pricing['subtotal'];
                if (!$wallet || !$wallet->hasSufficientBalance($totalPrice)) {
                    throw new \RuntimeException('Insufficient wallet balance.');
                }
                $wallet->debit($totalPrice, "Payment for Car Rental #{$rental->id}", 'car_rental', $rental->id);
                $rental->update(['payment_status' => 'paid', 'status' => 'confirmed']);
            }

            return $rental;
        });

        return response()->json([
            'success' => true,
            'message' => 'Car rental booking created successfully.',
            'data' => $rental->load('carCategory'),
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
        if ((int) $booking->customer_id !== (int) $request->user()->id) {
            abort(403);
        }

        $booking->load([
            'driver:id,name,phone',
            'reviews',
            'tips',
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    public function estimateRide(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pickup_lat'   => 'required|numeric|between:-90,90',
            'pickup_lng'   => 'required|numeric|between:-180,180',
            'dropoff_lat'  => 'nullable|numeric|between:-90,90',
            'dropoff_lng'  => 'nullable|numeric|between:-180,180',
            'service_type' => 'required|in:point_to_point,hourly_rental,round_trip',
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = app(RideEstimateService::class)->estimate(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            $request->dropoff_lat  ? (float) $request->dropoff_lat  : null,
            $request->dropoff_lng ? (float) $request->dropoff_lng : null
        );

        return response()->json($result);
    }

    public function storeRide(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'service_type'     => 'required|in:point_to_point,hourly_rental,round_trip',
            'pickup_location'  => 'required|string|max:255',
            'pickup_lat'       => 'required|numeric|between:-90,90',
            'pickup_lng'       => 'required|numeric|between:-180,180',
            'dropoff_location' => 'nullable|string|max:255',
            'dropoff_lat'      => 'nullable|numeric|between:-90,90',
            'dropoff_lng'      => 'nullable|numeric|between:-180,180',
            'scheduled_at'     => 'required|date|after:now',
            'special_requests' => 'nullable|string',
            'payment_method'   => 'required|in:cash,card,wallet,upi',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estimate = app(RideEstimateService::class)->estimate(
            (float) $request->pickup_lat,
            (float) $request->pickup_lng,
            $request->dropoff_lat ? (float) $request->dropoff_lat : null,
            $request->dropoff_lng ? (float) $request->dropoff_lng : null
        );

        $ride = DB::transaction(function () use ($request, $customer, $estimate) {
            $ride = RideBooking::create([
                'customer_id'      => $customer->id,
                'service_type'     => $request->service_type,
                'customer_name'    => $customer->name,
                'customer_email'   => $customer->email,
                'customer_phone'   => $customer->phone,
                'pickup_location'  => $request->pickup_location,
                'pickup_lat'       => $request->pickup_lat,
                'pickup_lng'       => $request->pickup_lng,
                'dropoff_location' => $request->dropoff_location,
                'dropoff_lat'      => $request->dropoff_lat,
                'dropoff_lng'      => $request->dropoff_lng,
                'scheduled_at'     => $request->scheduled_at,
                'distance_km'      => $estimate['distance_km'],
                'estimated_duration' => $estimate['estimated_duration'],
                'base_fare'        => $estimate['pricing']['base_fare'],
                'distance_fare'    => $estimate['pricing']['distance_fare'],
                'surge_multiplier' => $estimate['pricing']['surge_multiplier'],
                'total_fare'       => $estimate['pricing']['subtotal'],
                'payment_method'   => $request->payment_method,
                'special_requests' => $request->special_requests,
                'status'           => 'pending',
                'payment_status'   => 'pending',
            ]);

            // Handle Wallet Payment
            if ($request->payment_method === 'wallet') {
                $wallet = Wallet::forOwner($customer)->first();
                $totalFare = (float) $estimate['pricing']['subtotal'];
                if (!$wallet || !$wallet->hasSufficientBalance($totalFare)) {
                    throw new \RuntimeException('Insufficient wallet balance.');
                }
                $wallet->debit($totalFare, "Payment for Ride Booking #{$ride->id}", 'ride_booking', $ride->id);
                $ride->update(['payment_status' => 'paid']);
            }

            return $ride;
        });

        broadcast(new NewRideRequest($ride));

        return response()->json([
            'success' => true,
            'data'    => $ride->load('driver:id,name,phone'),
        ], 201);
    }

    public function submitReview(Request $request, RideBooking $booking)
    {
        if ((int) $booking->customer_id !== (int) $request->user()->id) {
            abort(403);
        }

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
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    public function submitTip(Request $request, RideBooking $booking)
    {
        if ((int) $booking->customer_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:50000',
            'message' => 'nullable|string|max:500',
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

        if (!$customerWallet || !$driverWallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not available.',
            ], 422);
        }

        $amount = round((float) $validated['amount'], 2);

        DB::transaction(function () use ($customerWallet, $driverWallet, $booking, $amount, $request, $validated) {
            if (!$customerWallet->debit($amount, 'Tip paid for booking #' . $booking->booking_number, 'ride_tip_' . $booking->id)) {
                abort(422, 'Insufficient wallet balance.');
            }

            $driverWallet->credit($amount, 'Tip received for booking #' . $booking->booking_number, 'ride_tip_' . $booking->id);

            RideBookingTip::create([
                'ride_booking_id' => $booking->id,
                'customer_id' => $request->user()->id,
                'driver_id' => $booking->driver_id,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'message' => $validated['message'] ?? null,
            ]);
        });

        return response()->json([
            'success' => true,
        ]);
    }
}
