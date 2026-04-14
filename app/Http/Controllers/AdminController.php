<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\User;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\TourSchedule;
use App\Models\Place;
use App\Models\TourItinerary;
use App\Models\PlaceMedia;
use App\Models\Role;
use App\Models\RideBooking;
use App\Models\DriverAvailability;
use App\Models\CarCategory;
use App\Models\Destination;

class AdminController extends Controller
{
    /**
     * Admin dashboard
     */
    public function dashboard()
    {
        return inertia('admin/Dashboard', [
            'title' => 'Admin Dashboard',
            'user' => Auth::user(),
            'stats' => [
                'total_users' => User::count(),
                'total_customers' => Customer::count(),
                'total_drivers' => Driver::count(),
                'total_tours' => Tour::count(),
                'total_bookings' => TourBooking::count(),
                'total_ride_bookings' => RideBooking::count(),
                'total_places' => Place::count(),
                'active_tours' => Tour::where('available_from', '>=', now())->count(),
                'recent_bookings' => TourBooking::with('customer', 'tour')->latest()->take(5)->get(),
            ],
            'recent_users' => User::with('roles')->latest()->take(5)->get(),
            'upcoming_tours' => Tour::with('guides', 'tourDrivers.driver')->where('available_from', '>=', now())->take(5)->get(),
        ]);
    }

    /**
     * User management
     */
    public function users()
    {
        return inertia('admin/Users/Index', [
            'title' => 'User Management',
            'user' => Auth::user(),
            'users' => User::with('roles')->paginate(15),
            'roles' => Role::active()->whereIn('name', ['admin', 'guide'])->get(),
        ]);
    }

    /**
     * Show user details
     */
    public function showUser(User $user)
    {
        $data = [
            'title' => 'User Details',
            'user' => Auth::user(),
            'target_user' => $user->load('roles', 'bookings.tour'),
        ];

        return view('admin.users.show', $data);
    }

    /**
     * Create user form
     */
    public function createUser()
    {
        $data = [
            'title' => 'Create User',
            'user' => Auth::user(),
            'roles' => Role::active()->whereIn('name', ['admin', 'guide'])->get(),
        ];

        return view('admin.users.create', $data);
    }

    /**
     * Store new user
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,guide',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users')->with('success', 'User created successfully');
    }

    /**
     * Edit user form
     */
    public function editUser(User $user)
    {
        $data = [
            'title' => 'Edit User',
            'user' => Auth::user(),
            'target_user' => $user,
            'roles' => Role::active()->whereIn('name', ['admin', 'guide'])->get(),
        ];

        return view('admin.users.edit', $data);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|unique:users,phone,' . $user->id,
            'role' => 'required|in:admin,guide',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Update user role
        $user->roles()->detach();
        $user->assignRole($request->role);

        return redirect()->route('admin.users')->with('success', 'User updated successfully');
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user)
    {
        // Prevent admin from deleting themselves
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account');
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully');
    }

    /**
     * Tours management
     */
    public function tours()
    {
        $toursPaginator = Tour::with('guides.user', 'drivers.driver')
            ->withCount('itineraries')
            ->paginate(12);

        // Transform data to match frontend expectations
        $toursPaginator->getCollection()->transform(function ($tour) {
            // Keep original field names - frontend expects 'title'
            return $tour;
        });

        return inertia('admin/Tours', [
            'title' => 'Tour Management',
            'user' => Auth::user(),
            'tours' => $toursPaginator,
        ]);
    }

    /**
     * Show create tour form
     */
    public function createTour()
    {
        return inertia('admin/Tours/Create', [
            'title' => 'Create Tour',
            'user' => Auth::user(),
        ]);
    }

    /**
     * Store new tour
     */
    public function storeTour(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'available_from' => 'required|date|after_or_equal:today',
            'available_to' => 'required|date|after:available_from',
        ]);

        $tour = Tour::create([
            'title' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'discount' => $request->discount ?? 0,
            'available_from' => $request->available_from,
            'available_to' => $request->available_to,
        ]);

        return redirect()->route('admin.tours')->with('success', 'Tour created successfully');
    }

    /**
     * Show tour details
     */
    public function showTour(Tour $tour)
    {
        $tour->load('guides.user', 'drivers.user', 'itineraries.place.media', 'bookings.user');
        
        return inertia('admin/Tours/Show', [
            'title' => 'Tour Details',
            'user' => Auth::user(),
            'tour' => $tour,
        ]);
    }

    /**
     * Show edit tour form
     */
    public function editTour(Tour $tour)
    {
        $tour->load('guides.user', 'drivers.user');
        
        return inertia('admin/Tours/Edit', [
            'title' => 'Edit Tour',
            'user' => Auth::user(),
            'tour' => $tour,
        ]);
    }

    /**
     * Update tour
     */
    public function updateTour(Request $request, Tour $tour)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'available_from' => 'required|date',
            'available_to' => 'required|date|after:available_from',
        ]);

        $tour->update([
            'title' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'discount' => $request->discount ?? 0,
            'available_from' => $request->available_from,
            'available_to' => $request->available_to,
        ]);

        return redirect()->route('admin.tours')->with('success', 'Tour updated successfully');
    }

    /**
     * Delete tour
     */
    public function deleteTour(Tour $tour)
    {
        $tour->delete();

        return redirect()->route('admin.tours')->with('success', 'Tour deleted successfully');
    }

    /**
     * Tour Itineraries management
     */
    public function tourItineraries(Tour $tour)
    {
        $itineraries = $tour->itineraries()
            ->with('place.media')
            ->orderBy('day_index')
            ->orderBy('time')
            ->get();

        return inertia('admin/Tours/Itineraries', [
            'title' => 'Tour Itineraries Management',
            'user' => Auth::user(),
            'tour' => $tour,
            'itineraries' => $itineraries,
        ]);
    }

    /**
     * Show create tour itinerary form
     */
    public function createTourItinerary(Tour $tour)
    {
        $places = Place::where('status', 'available')->orderBy('name')->get();

        return inertia('admin/Tours/Itineraries/Create', [
            'title' => 'Create Tour Itinerary',
            'user' => Auth::user(),
            'tour' => $tour,
            'places' => $places,
        ]);
    }

    /**
     * Store new tour itinerary
     */
    public function storeTourItinerary(Request $request, Tour $tour)
    {
        $request->validate([
            'day_index' => 'required|integer|min:1',
            'time' => 'nullable|date_format:H:i',
            'place_id' => 'required|exists:places,id',
            'details' => 'nullable|string',
        ]);

        // Check for duplicate day_index and time
        $existingItinerary = TourItinerary::where('tour_id', $tour->id)
            ->where('day_index', $request->day_index)
            ->where('time', $request->time)
            ->first();

        if ($existingItinerary) {
            return back()->with('error', 'An itinerary already exists for this day and time');
        }

        $tour->itineraries()->create($request->all());

        return redirect()->route('admin.tours.itineraries', $tour->id)->with('success', 'Itinerary created successfully');
    }

    /**
     * Show tour itinerary details
     */
    public function showTourItinerary(Tour $tour, TourItinerary $itinerary)
    {
        if ($itinerary->tour_id !== $tour->id) {
            return back()->with('error', 'Itinerary not found for this tour');
        }

        return inertia('admin/Tours/Itineraries/Show', [
            'title' => 'Tour Itinerary Details',
            'user' => Auth::user(),
            'tour' => $tour,
            'itinerary' => $itinerary->load('place.media'),
        ]);
    }

    /**
     * Show edit tour itinerary form
     */
    public function editTourItinerary(Tour $tour, TourItinerary $itinerary)
    {
        if ($itinerary->tour_id !== $tour->id) {
            return back()->with('error', 'Itinerary not found for this tour');
        }

        $places = Place::where('status', 'available')->orderBy('name')->get();

        return inertia('admin/Tours/Itineraries/Edit', [
            'title' => 'Edit Tour Itinerary',
            'user' => Auth::user(),
            'tour' => $tour,
            'itinerary' => $itinerary->load('place.media'),
            'places' => $places,
        ]);
    }

    /**
     * Update tour itinerary
     */
    public function updateTourItinerary(Request $request, Tour $tour, TourItinerary $itinerary)
    {
        if ($itinerary->tour_id !== $tour->id) {
            return back()->with('error', 'Itinerary not found for this tour');
        }

        $request->validate([
            'day_index' => 'sometimes|required|integer|min:1',
            'time' => 'nullable|date_format:H:i',
            'place_id' => 'sometimes|required|exists:places,id',
            'details' => 'nullable|string',
        ]);

        // Check for duplicate day_index and time (excluding current)
        if (isset($request->day_index) || isset($request->time)) {
            $dayIndex = $request->day_index ?? $itinerary->day_index;
            $time = $request->time ?? $itinerary->time;

            $existingItinerary = TourItinerary::where('tour_id', $tour->id)
                ->where('day_index', $dayIndex)
                ->where('time', $time)
                ->where('id', '!=', $itinerary->id)
                ->first();

            if ($existingItinerary) {
                return back()->with('error', 'An itinerary already exists for this day and time');
            }
        }

        $itinerary->update($request->all());

        return redirect()->route('admin.tours.itineraries', $tour->id)->with('success', 'Itinerary updated successfully');
    }

    /**
     * Delete tour itinerary
     */
    public function deleteTourItinerary(Tour $tour, TourItinerary $itinerary)
    {
        if ($itinerary->tour_id !== $tour->id) {
            return back()->with('error', 'Itinerary not found for this tour');
        }

        $itinerary->delete();

        return redirect()->route('admin.tours.itineraries', $tour->id)->with('success', 'Itinerary deleted successfully');
    }

    /**
     * Tour Bookings management
     */
    public function tourBookings()
    {
        return inertia('admin/TourBookings/Index', [
            'title' => 'Tour Booking Management',
            'user' => Auth::user(),
            'bookings' => TourBooking::with(['customer', 'tourSchedule.tour'])->latest()->paginate(15),
        ]);
    }

    /**
     * Tour Schedules Management
     */
    public function tourSchedules(Tour $tour)
    {
        return inertia('admin/Tours/Schedules', [
            'title' => 'Tour Schedules',
            'user' => Auth::user(),
            'tour' => $tour,
            'schedules' => $tour->schedules()->with(['guideAssignments.guide', 'driverAssignments.driver'])->latest('departure_date')->paginate(15),
        ]);
    }

    public function createTourSchedule(Tour $tour)
    {
        return inertia('admin/Tours/Schedules/Create', [
            'title' => 'Create Tour Schedule',
            'user' => Auth::user(),
            'tour' => $tour,
        ]);
    }

    public function storeTourSchedule(Request $request, Tour $tour)
    {
        $validated = $request->validate([
            'departure_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'total_seats' => 'required|integer|min:1',
            'price_override' => 'nullable|numeric|min:0',
            'status' => 'required|in:open,sold_out,closed,cancelled,completed',
        ]);

        $tour->schedules()->create($validated);

        return redirect()->route('admin.tours.schedules', $tour)->with('success', 'Tour schedule created.');
    }

    public function updateTourSchedule(Request $request, Tour $tour, TourSchedule $schedule)
    {
        $validated = $request->validate([
            'departure_date' => 'required|date',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'total_seats' => 'required|integer|min:1',
            'price_override' => 'nullable|numeric|min:0',
            'status' => 'required|in:open,sold_out,closed,cancelled,completed',
        ]);

        $schedule->update($validated);

        return redirect()->back()->with('success', 'Tour schedule updated.');
    }

    public function deleteTourSchedule(Tour $tour, TourSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'Tour schedule deleted.');
    }

    public function assignGuideToSchedule(Request $request, Tour $tour, TourSchedule $schedule)
    {
        $request->validate(['guide_id' => 'required|exists:guides,id']);
        $schedule->guideAssignments()->create(['guide_id' => $request->guide_id]);
        return redirect()->back()->with('success', 'Guide assigned.');
    }

    public function assignDriverToSchedule(Request $request, Tour $tour, TourSchedule $schedule)
    {
        $request->validate(['driver_id' => 'required|exists:drivers,id']);
        $schedule->driverAssignments()->create(['driver_id' => $request->driver_id]);
        return redirect()->back()->with('success', 'Driver assigned.');
    }

    /**
      * Places management
      */
     public function places()
     {
         return inertia('admin/Places', [
             'title' => 'Place Management',
             'user' => Auth::user(),
             'places' => Place::with('media')->paginate(12),
         ]);
     }

     /**
      * Show create place form
      */
     public function createPlace()
     {
         return inertia('admin/Places/Create', [
             'title' => 'Create Place',
             'user' => Auth::user(),
         ]);
     }

     /**
      * Store new place
      */
     public function storePlace(Request $request)
     {
         $request->validate([
             'name' => 'required|string|max:255',
             'description' => 'required|string',
             'lng' => 'nullable|numeric',
             'lat' => 'nullable|numeric',
             'status' => 'required|in:available,unavailable,restricted',
         ]);

         Place::create($request->all());

         return redirect()->route('admin.places')->with('success', 'Place created successfully');
     }

     /**
      * Show place details
      */
     public function showPlace(Place $place)
     {
         return inertia('admin/Places/Show', [
             'title' => 'Place Details',
             'user' => Auth::user(),
             'place' => $place->load('media'),
         ]);
     }

     /**
      * Show edit place form
      */
     public function editPlace(Place $place)
     {
         return inertia('admin/Places/Edit', [
             'title' => 'Edit Place',
             'user' => Auth::user(),
             'place' => $place,
         ]);
     }

     /**
      * Update place
      */
     public function updatePlace(Request $request, Place $place)
     {
         $request->validate([
             'name' => 'required|string|max:255',
             'description' => 'required|string',
             'lng' => 'nullable|numeric',
             'lat' => 'nullable|numeric',
             'status' => 'required|in:available,unavailable,restricted',
         ]);

         $place->update($request->all());

         return redirect()->route('admin.places')->with('success', 'Place updated successfully');
     }

     /**
      * Delete place
      */
     public function deletePlace(Place $place)
     {
         $place->delete();

         return redirect()->route('admin.places')->with('success', 'Place deleted successfully');
     }

     /**
      * Store new media for a place
      */
     public function storeMedia(Request $request, Place $place)
     {
         $request->validate([
             'file' => 'required|file|mimes:jpeg,png,jpg,gif,svg,mp4,avi,mov|max:20480',
             'description' => 'nullable|string',
         ]);

         $filePath = $request->file('file')->store('place_media', 'public');

         PlaceMedia::create([
             'place_id' => $place->id,
             'file_path' => $filePath,
             'file_type' => str_starts_with($request->file('file')->getMimeType(), 'image/') ? 'image' : 'video',
             'description' => $request->description,
         ]);

         return redirect()->route('admin.places.show', $place->id)->with('success', 'Media added successfully');
     }

     /**
      * Delete media
      */
     public function deleteMedia(PlaceMedia $media)
     {
         Storage::disk('public')->delete($media->file_path);
         $media->delete();

         return redirect()->route('admin.places.show', $media->place_id)->with('success', 'Media deleted successfully');
     }

    /**
     * System settings
     */
    public function settings()
    {
        return inertia('admin/Settings', [
            'title' => 'System Settings',
            'user' => Auth::user(),
            'settings' => [
                'site_name' => 'Travel Agency',
                'site_description' => 'Your trusted travel partner',
                'contact_email' => 'info@travelagency.com',
                'contact_phone' => '+1 (555) 123-4567',
                'address' => '123 Main St, City, State',
                'social_links' => [
                    'facebook' => 'https://facebook.com/travelagency',
                    'twitter' => 'https://twitter.com/travelagency',
                    'instagram' => 'https://instagram.com/travelagency',
                ],
            ],
        ]);
    }

    /**
     * Car rentals management
     */
    public function carRentals(Request $request)
    {
        $query = \App\Models\CarRental::with('carCategory', 'user');

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $carRentals = $query->orderBy('created_at', 'desc')->paginate(12);

        // Return JSON if it's an API request
        if ($request->expectsJson()) {
            return response()->json($carRentals);
        }

        return inertia('admin/CarRentals', [
            'title' => 'Car Rentals Management',
            'user' => Auth::user(),
            'car_rentals' => $carRentals,
        ]);
    }

    /**
     * Show create car rental form
     */
    public function createCarRental()
    {
        $carCategories = CarCategory::where('is_active', true)->orderBy('name')->get();
        $destinations = Destination::where('is_active', true)->orderBy('name')->get();
        $drivers = User::whereHas('roles', function($query) {
            $query->where('name', 'driver');
        })->orderBy('name')->get();

        return inertia('admin/CarRentals/Create', [
            'title' => 'Create Car Rental',
            'user' => Auth::user(),
            'car_categories' => $carCategories,
            'destinations' => $destinations,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Store new car rental
     */
    public function storeCarRental(Request $request)
    {
        $validated = $request->validate([
            'car_category_id' => 'required|exists:car_categories,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'nullable|string',
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
            'destination_details' => 'nullable|string',
            'distance_km' => 'nullable|numeric|min:0',
            'special_requests' => 'nullable|string',
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'required|in:cash,card,bank_transfer,upi',
            'assigned_driver' => 'nullable|exists:drivers,id',
            'vehicle_number' => 'nullable|string|max:50',
            'internal_notes' => 'nullable|string',
            'whatsapp_notification' => 'boolean',
            'email_notification' => 'boolean',
            'sms_notification' => 'boolean',
        ]);

        // Calculate pricing
        $carCategory = CarCategory::findOrFail($validated['car_category_id']);
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);
        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $distanceKm = $validated['distance_km'] ?? 0;

        $pricing = $carCategory->calculatePrice($numberOfDays, $distanceKm);

        $carRental = \App\Models\CarRental::create([
            'user_id' => Auth::id(),
            'car_category_id' => $validated['car_category_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'customer_address' => $validated['customer_address'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $validated['start_time'] ?? '09:00',
            'end_time' => $validated['end_time'] ?? '18:00',
            'pickup_location' => $validated['pickup_location'],
            'dropoff_location' => $validated['dropoff_location'],
            'destination_details' => $validated['destination_details'],
            'number_of_days' => $numberOfDays,
            'base_price' => $pricing['base_price'],
            'distance_km' => $distanceKm,
            'distance_price' => $pricing['distance_price'],
            'extras_price' => 0,
            'discount_amount' => 0,
            'total_price' => $pricing['subtotal'],
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'],
            'special_requests' => $validated['special_requests'],
            'assigned_driver' => $validated['assigned_driver'],
            'vehicle_number' => $validated['vehicle_number'],
            'internal_notes' => $validated['internal_notes'],
            'whatsapp_notification' => $request->boolean('whatsapp_notification', true),
            'email_notification' => $request->boolean('email_notification', true),
            'sms_notification' => $request->boolean('sms_notification', false),
        ]);

        return redirect()->route('admin.car-rentals')->with('success', 'Car rental created successfully');
    }

    /**
     * Show car rental details
     */
    public function showCarRental(\App\Models\CarRental $carRental)
    {
        $carRental->load(['carCategory', 'user', 'driver', 'extras']);

        return inertia('admin/CarRentals/Show', [
            'title' => 'Car Rental Details',
            'user' => Auth::user(),
            'car_rental' => $carRental,
        ]);
    }

    /**
     * Show edit car rental form
     */
    public function editCarRental(\App\Models\CarRental $carRental)
    {
        $carRental->load(['carCategory', 'user', 'driver']);
        $carCategories = CarCategory::where('is_active', true)->orderBy('name')->get();
        $destinations = Destination::where('is_active', true)->orderBy('name')->get();
        $drivers = User::whereHas('roles', function($query) {
            $query->where('name', 'driver');
        })->orderBy('name')->get();

        return inertia('admin/CarRentals/Edit', [
            'title' => 'Edit Car Rental',
            'user' => Auth::user(),
            'car_rental' => $carRental,
            'car_categories' => $carCategories,
            'destinations' => $destinations,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Update car rental
     */
    public function updateCarRental(Request $request, \App\Models\CarRental $carRental)
    {
        $validated = $request->validate([
            'car_category_id' => 'required|exists:car_categories,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'pickup_location' => 'required|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
            'destination_details' => 'nullable|string',
            'distance_km' => 'nullable|numeric|min:0',
            'special_requests' => 'nullable|string',
            'status' => 'required|in:pending,confirmed,in_progress,completed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'required|in:cash,card,bank_transfer,upi',
            'assigned_driver' => 'nullable|exists:drivers,id',
            'vehicle_number' => 'nullable|string|max:50',
            'internal_notes' => 'nullable|string',
            'whatsapp_notification' => 'boolean',
            'email_notification' => 'boolean',
            'sms_notification' => 'boolean',
        ]);

        // Recalculate pricing if dates or category changed
        $carCategory = CarCategory::findOrFail($validated['car_category_id']);
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate = \Carbon\Carbon::parse($validated['end_date']);
        $numberOfDays = $startDate->diffInDays($endDate) + 1;
        $distanceKm = $validated['distance_km'] ?? 0;

        $pricing = $carCategory->calculatePrice($numberOfDays, $distanceKm);

        $carRental->update([
            'car_category_id' => $validated['car_category_id'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'customer_address' => $validated['customer_address'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'pickup_location' => $validated['pickup_location'],
            'dropoff_location' => $validated['dropoff_location'],
            'destination_details' => $validated['destination_details'],
            'number_of_days' => $numberOfDays,
            'base_price' => $pricing['base_price'],
            'distance_km' => $distanceKm,
            'distance_price' => $pricing['distance_price'],
            'total_price' => $pricing['subtotal'],
            'special_requests' => $validated['special_requests'],
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'],
            'assigned_driver' => $validated['assigned_driver'],
            'vehicle_number' => $validated['vehicle_number'],
            'internal_notes' => $validated['internal_notes'],
            'whatsapp_notification' => $request->boolean('whatsapp_notification', $carRental->whatsapp_notification),
            'email_notification' => $request->boolean('email_notification', $carRental->email_notification),
            'sms_notification' => $request->boolean('sms_notification', $carRental->sms_notification),
        ]);

        return redirect()->route('admin.car-rentals')->with('success', 'Car rental updated successfully');
    }

    /**
     * Delete car rental
     */
    public function deleteCarRental(\App\Models\CarRental $carRental)
    {
        $carRental->delete();

        return redirect()->route('admin.car-rentals')->with('success', 'Car rental deleted successfully');
    }

    /**
     * Car categories management
     */
    public function carCategories(Request $request)
    {
        $query = CarCategory::query();

        // Filter by vehicle type if provided
        if ($request->has('type') && !empty($request->type)) {
            $query->where('vehicle_type', $request->type);
        }

        // Filter by active status
        if ($request->has('active') && $request->boolean('active')) {
            $query->where('is_active', true);
        } elseif ($request->has('active') && !$request->boolean('active')) {
            $query->where('is_active', false);
        }

        $carCategories = $query->orderBy('sort_order')->orderBy('name')->paginate(12);

        return inertia('admin/CarCategories', [
            'title' => 'Car Categories Management',
            'user' => Auth::user(),
            'car_categories' => $carCategories,
        ]);
    }

    /**
     * Show create car category form
     */
    public function createCarCategory()
    {
        return inertia('admin/CarCategories/Create', [
            'title' => 'Create Car Category',
            'user' => Auth::user(),
        ]);
    }

    /**
     * Store new car category
     */
    public function storeCarCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_categories,name',
            'description' => 'nullable|string',
            'vehicle_type' => 'required|string|in:sedan,suv,hatchback,convertible,van,truck',
            'seats' => 'required|integer|min:1|max:20',
            'has_ac' => 'boolean',
            'has_driver' => 'boolean',
            'base_price_per_day' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'fuel_type' => 'nullable|string|in:petrol,diesel,electric,hybrid',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        CarCategory::create($validated);

        return redirect()->route('admin.car-categories')->with('success', 'Car category created successfully');
    }

    /**
     * Show car category details
     */
    public function showCarCategory(CarCategory $carCategory)
    {
        return inertia('admin/CarCategories/Show', [
            'title' => 'Car Category Details',
            'user' => Auth::user(),
            'car_category' => $carCategory->load('carRentals'),
        ]);
    }

    /**
     * Show edit car category form
     */
    public function editCarCategory(CarCategory $carCategory)
    {
        return inertia('admin/CarCategories/Edit', [
            'title' => 'Edit Car Category',
            'user' => Auth::user(),
            'car_category' => $carCategory,
        ]);
    }

    /**
     * Update car category
     */
    public function updateCarCategory(Request $request, CarCategory $carCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_categories,name,' . $carCategory->id,
            'description' => 'nullable|string',
            'vehicle_type' => 'required|string|in:sedan,suv,hatchback,convertible,van,truck',
            'seats' => 'required|integer|min:1|max:20',
            'has_ac' => 'boolean',
            'has_driver' => 'boolean',
            'base_price_per_day' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'fuel_type' => 'nullable|string|in:petrol,diesel,electric,hybrid',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $carCategory->update($validated);

        return redirect()->route('admin.car-categories')->with('success', 'Car category updated successfully');
    }

    /**
     * Delete car category
     */
    public function deleteCarCategory(CarCategory $carCategory)
    {
        // Check if there are any car rentals using this category
        if ($carCategory->carRentals()->count() > 0) {
            return back()->with('error', 'Cannot delete car category as it has associated car rentals');
        }

        $carCategory->delete();

        return redirect()->route('admin.car-categories')->with('success', 'Car category deleted successfully');
    }

    /**
     * Destinations management
     */
    public function destinations(Request $request)
    {
        $query = Destination::query();

        // Filter by state if provided
        if ($request->has('state') && !empty($request->state)) {
            $query->where('state', $request->state);
        }

        // Filter by type if provided
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Filter by region if provided
        if ($request->has('region') && !empty($request->region)) {
            $query->where('region', $request->region);
        }

        // Filter by active status
        if ($request->has('active') && $request->boolean('active')) {
            $query->where('is_active', true);
        } elseif ($request->has('active') && !$request->boolean('active')) {
            $query->where('is_active', false);
        }

        $destinations = $query->orderBy('sort_order')->orderBy('name')->paginate(12);

        return inertia('admin/Destinations', [
            'title' => 'Destinations Management',
            'user' => Auth::user(),
            'destinations' => $destinations,
        ]);
    }

    /**
     * Show create destination form
     */
    public function createDestination()
    {
        return inertia('admin/Destinations/Create', [
            'title' => 'Create Destination',
            'user' => Auth::user(),
        ]);
    }

    /**
     * Store new destination
     */
    public function storeDestination(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:destinations,name',
            'description' => 'nullable|string',
            'state' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'type' => 'required|string|in:city,hill_station,beach,historical,cultural,nature,adventure,religious',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'popular_routes' => 'nullable|array',
            'popular_routes.*' => 'string',
            'distance_from_guwahati' => 'nullable|numeric|min:0',
            'estimated_travel_time' => 'nullable|integer|min:0',
            'best_time_to_visit' => 'nullable|array',
            'best_time_to_visit.*' => 'string',
            'attractions' => 'nullable|array',
            'attractions.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Destination::create($validated);

        return redirect()->route('admin.destinations')->with('success', 'Destination created successfully');
    }

    /**
     * Show destination details
     */
    public function showDestination(Destination $destination)
    {
        return inertia('admin/Destinations/Show', [
            'title' => 'Destination Details',
            'user' => Auth::user(),
            'destination' => $destination,
        ]);
    }

    /**
     * Show edit destination form
     */
    public function editDestination(Destination $destination)
    {
        return inertia('admin/Destinations/Edit', [
            'title' => 'Edit Destination',
            'user' => Auth::user(),
            'destination' => $destination,
        ]);
    }

    /**
     * Update destination
     */
    public function updateDestination(Request $request, Destination $destination)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:destinations,name,' . $destination->id,
            'description' => 'nullable|string',
            'state' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
            'type' => 'required|string|in:city,hill_station,beach,historical,cultural,nature,adventure,religious',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'popular_routes' => 'nullable|array',
            'popular_routes.*' => 'string',
            'distance_from_guwahati' => 'nullable|numeric|min:0',
            'estimated_travel_time' => 'nullable|integer|min:0',
            'best_time_to_visit' => 'nullable|array',
            'best_time_to_visit.*' => 'string',
            'attractions' => 'nullable|array',
            'attractions.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $destination->update($validated);

        return redirect()->route('admin.destinations')->with('success', 'Destination updated successfully');
    }

    /**
     * Delete destination
     */
    public function deleteDestination(Destination $destination)
    {
        // Check if there are any tours using this destination
        if ($destination->tours()->count() > 0) {
            return back()->with('error', 'Cannot delete destination as it has associated tours');
        }

        $destination->delete();

        return redirect()->route('admin.destinations')->with('success', 'Destination deleted successfully');
    }

    /**
     * Ride bookings management
     */
    public function rideBookings(Request $request)
    {
        $query = RideBooking::with(['customer', 'driver']);

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $rideBookings = $query->orderBy('created_at', 'desc')->paginate(12);

        // Return JSON if it's an API request
        if ($request->expectsJson()) {
            return response()->json($rideBookings);
        }

        return inertia('admin/RideBookings', [
            'title' => 'Ride Bookings Management',
            'user' => Auth::user(),
            'ride_bookings' => $rideBookings,
        ]);
    }

    /**
     * Show ride booking details for admin dashboard.
     */
    public function showRideBooking(RideBooking $rideBooking)
    {
        $rideBooking->load([
            'user:id,name,email,phone',
            'driver:id,name,email,phone',
        ]);

        $drivers = Driver::query()
            ->select('id', 'name', 'email', 'phone')
            ->with('driverAvailability')
            ->orderBy('name')
            ->get()
            ->map(function ($driver) {
                $availability = $driver->driverAvailability;

                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'is_online' => (bool) ($availability?->is_online ?? false),
                    'is_available' => (bool) ($availability?->is_available ?? false),
                    'rating' => $availability?->rating,
                    'vehicle_number' => $availability?->vehicle_number,
                ];
            })->values();

        return inertia('admin/RideBookingDetails', [
            'title' => 'Ride Booking Details',
            'user' => Auth::user(),
            'booking' => $rideBooking,
            'drivers' => $drivers,
            'can_undo_last_change' => $this->canUndoLastChange($rideBooking),
        ]);
    }

    /**
     * Assign or reassign driver for ride booking.
     */
    public function assignRideBookingDriver(Request $request, RideBooking $rideBooking)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $driver = Driver::findOrFail($validated['driver_id']);

        $previousDriverId = $rideBooking->driver_id;
        $newDriverId = (int) $validated['driver_id'];

        if ($previousDriverId && $previousDriverId !== $newDriverId) {
            DriverAvailability::where('driver_id', $previousDriverId)->update([
                'is_available' => true,
            ]);
        }

        $this->captureAdminSnapshot($rideBooking);

        $rideBooking->update([
            'driver_id' => $newDriverId,
            'status' => in_array($rideBooking->status, ['completed', 'cancelled'], true)
                ? $rideBooking->status
                : 'driver_assigned',
            'start_ride_pin' => $rideBooking->start_ride_pin ?: RideBooking::generateStartRidePin(),
            'start_pin_verified_at' => null,
            'last_admin_changed_at' => now(),
            'last_admin_changed_by' => Auth::id(),
        ]);

        DriverAvailability::where('driver_id', $newDriverId)->update([
            'is_available' => false,
        ]);

        return response()->json([
            'message' => 'Driver assigned successfully.',
            'ride_booking' => $rideBooking->fresh(['driver:id,name,email,phone']),
        ]);
    }

    public function updateRideBookingStatus(Request $request, RideBooking $rideBooking)
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,confirmed,driver_assigned,driver_arriving,pickup,in_transit,completed,cancelled',
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',
        ]);

        if (!isset($validated['status']) && !isset($validated['payment_status'])) {
            return response()->json([
                'message' => 'No changes requested.',
            ], 422);
        }

        $this->captureAdminSnapshot($rideBooking);

        $updateData = [
            'last_admin_changed_at' => now(),
            'last_admin_changed_by' => Auth::id(),
        ];

        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if (isset($validated['payment_status'])) {
            $updateData['payment_status'] = $validated['payment_status'];
        }

        $rideBooking->update($updateData);

        return response()->json([
            'message' => 'Ride booking updated successfully.',
            'ride_booking' => $rideBooking->fresh(['driver:id,name,email,phone']),
        ]);
    }

    public function undoLastRideBookingChange(RideBooking $rideBooking)
    {
        if (!$this->canUndoLastChange($rideBooking)) {
            return response()->json([
                'message' => 'Undo window has expired or there is no change to undo.',
            ], 422);
        }

        $snapshot = $rideBooking->last_admin_change_snapshot ?? [];
        if (!is_array($snapshot) || empty($snapshot)) {
            return response()->json([
                'message' => 'No undo snapshot available.',
            ], 422);
        }

        $oldDriverId = $rideBooking->driver_id;
        $newDriverId = $snapshot['driver_id'] ?? null;

        if ($oldDriverId && $oldDriverId !== $newDriverId) {
            DriverAvailability::where('driver_id', $oldDriverId)->update(['is_available' => true]);
        }

        if ($newDriverId) {
            DriverAvailability::where('driver_id', $newDriverId)->update(['is_available' => false]);
        }

        $rideBooking->update([
            'driver_id' => $snapshot['driver_id'] ?? null,
            'status' => $snapshot['status'] ?? $rideBooking->status,
            'payment_status' => $snapshot['payment_status'] ?? $rideBooking->payment_status,
            'start_ride_pin' => $snapshot['start_ride_pin'] ?? null,
            'start_pin_verified_at' => $snapshot['start_pin_verified_at'] ?? null,
            'last_admin_change_snapshot' => null,
            'last_admin_changed_at' => null,
            'last_admin_changed_by' => null,
        ]);

        return response()->json([
            'message' => 'Last change undone successfully.',
            'ride_booking' => $rideBooking->fresh(['driver:id,name,email,phone']),
        ]);
    }

    private function captureAdminSnapshot(RideBooking $rideBooking): void
    {
        $rideBooking->last_admin_change_snapshot = [
            'driver_id' => $rideBooking->driver_id,
            'status' => $rideBooking->status,
            'payment_status' => $rideBooking->payment_status,
            'start_ride_pin' => $rideBooking->start_ride_pin,
            'start_pin_verified_at' => $rideBooking->start_pin_verified_at?->toDateTimeString(),
        ];
        $rideBooking->save();
    }

    private function canUndoLastChange(RideBooking $rideBooking): bool
    {
        if (!$rideBooking->last_admin_changed_at || empty($rideBooking->last_admin_change_snapshot)) {
            return false;
        }

        return $rideBooking->last_admin_changed_at->greaterThan(now()->subMinutes(10));
    }
}
