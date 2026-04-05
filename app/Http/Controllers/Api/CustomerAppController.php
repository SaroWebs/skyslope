<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CarCategory;
use App\Models\CarRental;
use App\Models\Place;
use App\Models\Tour;
use App\Models\DriverAvailability;
use App\Models\RideBooking;
use App\Models\RideBookingReview;
use App\Models\RideBookingTip;
use App\Models\Wallet;
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
                    ->where('status', 'available')
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
        $tour->load(['itineraries.place.media', 'guides', 'drivers']);

        return response()->json([
            'success' => true,
            'data' => $tour,
        ]);
    }

    public function publicDestinations()
    {
        return response()->json([
            'success' => true,
            'data' => Place::with(['media', 'itineraries'])
                ->where('status', 'available')
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
                'tour_bookings' => Booking::where('user_id', $customer->id)->count(),
                'car_rentals' => CarRental::where('user_id', $customer->id)->count(),
                'ride_bookings' => RideBooking::where('user_id', $customer->id)->count(),
                'wallet_balance' => (float) optional(Wallet::forOwner($customer)->first())->balance,
            ],
            'recent_ride_bookings' => RideBooking::with('driver:id,name,phone')
                ->where('user_id', $customer->id)
                ->latest()
                ->take(5)
                ->get(),
            'recent_bookings' => Booking::with('tour:id,title')
                ->where('user_id', $customer->id)
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

    public function bookTour(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'tour_id' => 'required|exists:tours,id',
            'travel_date' => 'required|date',
            'number_of_people' => 'required|integer|min:1|max:20',
            'special_requests' => 'nullable|string',
            'payment_method' => 'nullable|in:cash,card,wallet,upi',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tour = Tour::findOrFail($request->tour_id);
        $peopleCount = (int) $request->number_of_people;
        $price = (float) $tour->price * $peopleCount;

        $booking = Booking::create([
            'user_id' => $customer->id,
            'tour_id' => $tour->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'travel_date' => $request->travel_date,
            'number_of_people' => $peopleCount,
            'special_requests' => $request->special_requests,
            'status' => 'pending',
            'total_price' => $price,
            'discount_amount' => 0,
            'payment_method' => $request->input('payment_method', 'cash'),
            'payment_status' => 'pending',
            'whatsapp_notification' => true,
            'email_notification' => true,
            'sms_notification' => false,
        ]);

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
            ->where('user_id', $request->user()->id)
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

        $rental = CarRental::create([
            'user_id' => $customer->id,
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
            'payment_method' => 'cash',
            'special_requests' => $request->special_requests,
            'whatsapp_notification' => true,
            'email_notification' => true,
            'sms_notification' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Car rental booking created successfully.',
            'data' => $rental->load('carCategory'),
        ], 201);
    }

    public function rides(Request $request)
    {
        $rides = RideBooking::with('driver:id,name,phone')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $rides,
        ]);
    }

    public function showRide(Request $request, RideBooking $booking)
    {
        if ((int) $booking->user_id !== (int) $request->user()->id) {
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

        $distance = 0;
        if ($request->dropoff_lat && $request->dropoff_lng) {
            $earthRadius = 6371;
            $latDelta = deg2rad($request->dropoff_lat - $request->pickup_lat);
            $lngDelta = deg2rad($request->dropoff_lng - $request->pickup_lng);

            $a = sin($latDelta / 2) * sin($latDelta / 2) +
                cos(deg2rad($request->pickup_lat)) * cos(deg2rad($request->dropoff_lat)) *
                sin($lngDelta / 2) * sin($lngDelta / 2);

            $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
        }

        $baseFare = 50;
        $distanceFare = $distance * 15;
        $nearbyDrivers = DriverAvailability::active()
            ->nearLocation((float) $request->pickup_lat, (float) $request->pickup_lng, 5)
            ->count();
        $surgeMultiplier = $nearbyDrivers < 3 ? 1.2 : 1.0;
        $subtotal = ($baseFare + $distanceFare) * $surgeMultiplier;

        return response()->json([
            'distance_km' => round($distance, 2),
            'estimated_duration' => $distance > 0 ? ceil($distance / 30 * 60) : 30,
            'pricing' => [
                'base_fare' => $baseFare,
                'distance_fare' => round($distanceFare, 2),
                'surge_multiplier' => $surgeMultiplier,
                'subtotal' => round($subtotal, 2),
            ],
            'nearby_drivers' => $nearbyDrivers,
        ]);
    }

    public function storeRide(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'service_type' => 'required|in:point_to_point,hourly_rental,round_trip',
            'pickup_location' => 'required|string|max:255',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'dropoff_location' => 'nullable|string|max:255',
            'dropoff_lat' => 'nullable|numeric|between:-90,90',
            'dropoff_lng' => 'nullable|numeric|between:-180,180',
            'scheduled_at' => 'required|date|after:now',
            'special_requests' => 'nullable|string',
            'payment_method' => 'required|in:cash,card,wallet,upi',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $estimate = $this->estimateRide(new Request($request->all()))->getData();

        $ride = DB::transaction(function () use ($request, $customer, $estimate) {
            return RideBooking::create([
                'user_id' => $customer->id,
                'service_type' => $request->service_type,
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
                'distance_km' => $estimate->distance_km ?? 0,
                'estimated_duration' => $estimate->estimated_duration ?? 30,
                'base_fare' => $estimate->pricing->base_fare ?? 50,
                'distance_fare' => $estimate->pricing->distance_fare ?? 0,
                'surge_multiplier' => $estimate->pricing->surge_multiplier ?? 1,
                'total_fare' => $estimate->pricing->subtotal ?? 50,
                'payment_method' => $request->payment_method,
                'special_requests' => $request->special_requests,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => $ride->load('driver:id,name,phone'),
        ], 201);
    }

    public function submitReview(Request $request, RideBooking $booking)
    {
        if ((int) $booking->user_id !== (int) $request->user()->id) {
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
        if ((int) $booking->user_id !== (int) $request->user()->id) {
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
