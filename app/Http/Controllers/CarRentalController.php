<?php

namespace App\Http\Controllers;

use App\Models\CarRental;
use App\Models\CarCategory;
use App\Models\CarRentalExtra;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CarRentalController extends Controller
{
    /**
     * Display a listing of car rentals.
     */
    public function index(Request $request)
    {
        $query = CarRental::with(['carCategory', 'user']);

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by user if not admin
        if (!Auth::user()->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        $carRentals = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($carRentals);
        }

        return view('car-rentals.index', compact('carRentals'));
    }

    /**
     * Show the form for creating a new car rental.
     */
    public function create()
    {
        $carCategories = CarCategory::active()->get();
        $destinations = Destination::active()->get();

        return view('car-rentals.create', compact('carCategories', 'destinations'));
    }

    /**
     * Store a newly created car rental.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_category_id' => 'required|exists:car_categories,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
            'destination_details' => 'nullable|string',
            'special_requests' => 'nullable|string',
            'distance_km' => 'nullable|numeric|min:0',
            'whatsapp_notification' => 'boolean',
            'email_notification' => 'boolean',
            'sms_notification' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        // Calculate pricing
        $carCategory = CarCategory::findOrFail($request->car_category_id);
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $distanceKm = $request->distance_km ?? 0;

        $pricing = $carCategory->calculatePrice($numberOfDays, $distanceKm);

        $carRental = CarRental::create([
            'user_id' => Auth::id(),
            'car_category_id' => $request->car_category_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time ?? '09:00',
            'end_time' => $request->end_time ?? '18:00',
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
            'whatsapp_notification' => $request->boolean('whatsapp_notification', true),
            'email_notification' => $request->boolean('email_notification', true),
            'sms_notification' => $request->boolean('sms_notification', false),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Car rental booking created successfully',
                'car_rental' => $carRental->load(['carCategory'])
            ], 201);
        }

        return redirect()->route('car-rentals.show', $carRental)
                         ->with('success', 'Car rental booking created successfully!');
    }

    /**
     * Display the specified car rental.
     */
    public function show(CarRental $carRental)
    {
        // Check if user can view this rental
        if (!Auth::user()->isAdmin() && $carRental->user_id !== Auth::id()) {
            abort(403);
        }

        $carRental->load(['carCategory', 'user']);

        if (request()->expectsJson()) {
            return response()->json($carRental);
        }

        return view('car-rentals.show', compact('carRental'));
    }

    /**
     * Show the form for editing the specified car rental.
     */
    public function edit(CarRental $carRental)
    {
        // Check if user can edit this rental
        if (!Auth::user()->isAdmin() && $carRental->user_id !== Auth::id()) {
            abort(403);
        }

        $carCategories = CarCategory::active()->get();
        $destinations = Destination::active()->get();

        return view('car-rentals.edit', compact('carRental', 'carCategories', 'destinations'));
    }

    /**
     * Update the specified car rental.
     */
    public function update(Request $request, CarRental $carRental)
    {
        // Check if user can update this rental
        if (!Auth::user()->isAdmin() && $carRental->user_id !== Auth::id()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'car_category_id' => 'required|exists:car_categories,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
            'destination_details' => 'nullable|string',
            'special_requests' => 'nullable|string',
            'status' => 'in:pending,confirmed,in_progress,completed,cancelled',
            'payment_status' => 'in:pending,paid,failed,refunded',
            'internal_notes' => 'nullable|string',
            'assigned_driver' => 'nullable|exists:users,id',
            'vehicle_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        // Recalculate pricing if dates or category changed
        $carCategory = CarCategory::findOrFail($request->car_category_id);
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $numberOfDays = $startDate->diffInDays($endDate) + 1;

        $pricing = $carCategory->calculatePrice($numberOfDays, $carRental->distance_km);

        $carRental->update([
            'car_category_id' => $request->car_category_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'pickup_location' => $request->pickup_location,
            'dropoff_location' => $request->dropoff_location,
            'destination_details' => $request->destination_details,
            'number_of_days' => $numberOfDays,
            'base_price' => $pricing['base_price'],
            'total_price' => $pricing['subtotal'] - $carRental->discount_amount,
            'special_requests' => $request->special_requests,
            'status' => $request->status ?? $carRental->status,
            'payment_status' => $request->payment_status ?? $carRental->payment_status,
            'internal_notes' => $request->internal_notes,
            'assigned_driver' => $request->assigned_driver,
            'vehicle_number' => $request->vehicle_number,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Car rental updated successfully',
                'car_rental' => $carRental->load(['carCategory'])
            ]);
        }

        return redirect()->route('car-rentals.show', $carRental)
                         ->with('success', 'Car rental updated successfully!');
    }

    /**
     * Remove the specified car rental.
     */
    public function destroy(CarRental $carRental)
    {
        // Check if user can delete this rental
        if (!Auth::user()->isAdmin() && $carRental->user_id !== Auth::id()) {
            abort(403);
        }

        $carRental->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Car rental deleted successfully']);
        }

        return redirect()->route('car-rentals.index')
                         ->with('success', 'Car rental deleted successfully!');
    }

    public function bookCar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_category_id' => 'required|exists:car_categories,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'pickup_location' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate pricing
        $carCategory = CarCategory::findOrFail($request->car_category_id);
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $numberOfDays = $startDate->diffInDays($endDate) + 1;

        $pricing = $carCategory->calculatePrice($numberOfDays, 0);

        $carRental = CarRental::create([
            'user_id' => Auth::id(),
            'car_category_id' => $request->car_category_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'pickup_location' => $request->pickup_location,
            'number_of_days' => $numberOfDays,
            'base_price' => $pricing['base_price'],
            'distance_km' => 0,
            'distance_price' => 0,
            'extras_price' => 0,
            'discount_amount' => 0,
            'total_price' => $pricing['subtotal'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash',
            'whatsapp_notification' => true,
            'email_notification' => true,
            'sms_notification' => false,
        ]);

        return response()->json([
            'message' => 'Car rental booking created successfully',
            'car_rental' => $carRental->load(['carCategory'])
        ], 201);
    }
}