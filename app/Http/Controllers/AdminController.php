<?php

namespace App\Http\Controllers;

use App\Models\BookingAuditLog;
use App\Models\BookingIncident;
use App\Models\BookingRefund;
use App\Models\CarCategory;
use App\Models\CarRental;
use App\Models\Customer;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\DriverAvailability;
use App\Models\Place;
use App\Models\PlaceMedia;
use App\Models\RideBooking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\TourItinerary;
use App\Models\TourSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\BookingCancellationService;
use App\Services\BookingLifecycleNotifier;
use App\Services\BookingStatusService;
use App\Services\CommissionService;
use App\Services\DriverDispatchService;
use App\Services\GooglePlaceDetailsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
            'upcoming_tours' => Tour::with(['schedules.driverAssignments.driver'])->where('available_from', '>=', now())->take(5)->get(),
        ]);
    }

    public function walletReconciliation(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'owner_type' => 'nullable|in:customer,driver',
        ]);

        $transactions = WalletTransaction::query()
            ->when(isset($validated['from']), fn ($query) => $query->whereDate('created_at', '>=', $validated['from']))
            ->when(isset($validated['to']), fn ($query) => $query->whereDate('created_at', '<=', $validated['to']))
            ->when(isset($validated['owner_type']), function ($query) use ($validated) {
                $ownerClass = $validated['owner_type'] === 'driver' ? Driver::class : Customer::class;

                $query->whereHas('wallet', fn ($walletQuery) => $walletQuery->where('owner_type', $ownerClass));
            });

        $credits = (clone $transactions)->where('type', 'credit')->sum('amount');
        $debits = (clone $transactions)->where('type', 'debit')->sum('amount');
        $wallets = Wallet::query()
            ->when(isset($validated['owner_type']), function ($query) use ($validated) {
                $ownerClass = $validated['owner_type'] === 'driver' ? Driver::class : Customer::class;

                $query->where('owner_type', $ownerClass);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'filters' => [
                    'from' => $validated['from'] ?? null,
                    'to' => $validated['to'] ?? null,
                    'owner_type' => $validated['owner_type'] ?? null,
                ],
                'wallet_count' => (clone $wallets)->count(),
                'active_wallet_count' => (clone $wallets)->where('is_active', true)->count(),
                'wallet_balance_total' => round((float) (clone $wallets)->sum('balance'), 2),
                'credit_total' => round((float) $credits, 2),
                'debit_total' => round((float) $debits, 2),
                'net_movement' => round((float) $credits - (float) $debits, 2),
                'transaction_count' => (clone $transactions)->count(),
                'recent_transactions' => (clone $transactions)
                    ->with('wallet:id,owner_type,owner_id,balance,currency,is_active')
                    ->latest()
                    ->take(20)
                    ->get(),
            ],
        ]);
    }

    /**
     * Admin profile page
     */
    public function profile()
    {
        return Inertia::render('admin/Profile', [
            'title'       => 'My Profile',
            'user'        => Auth::user(),
            'target_user' => Auth::user()->load('roles'),
        ]);
    }

    /**
     * Update admin's own profile info
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required|string|unique:users,phone,' . $user->id,
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Change admin's own password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string|current_password',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        Auth::user()->update([
            'password' => bcrypt($request->password),
        ]);

        return back()->with('success', 'Password changed successfully.');
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
            'roles' => Role::active()->orderBy('name')->get(),
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
            'target_user' => $user->load(['roles']),
        ];

        return Inertia::render('admin/Users/Show', $data);
    }

    /**
     * Create user form
     */
    public function createUser()
    {
        $data = [
            'title' => 'Create User',
            'user' => Auth::user(),
            'roles' => Role::active()->orderBy('name')->get(),
        ];

        return Inertia::render('admin/Users/Create', $data);
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
            'role' => 'required|string|exists:roles,name',
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
            'target_user' => $user->load(['roles']),
            'roles' => Role::active()->orderBy('name')->get(),
        ];

        return Inertia::render('admin/Users/Edit', $data);
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
            'role' => 'required|string|exists:roles,name',
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
        $toursPaginator = Tour::with(['schedules.driverAssignments.driver'])
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
        $tour->load(['schedules.driverAssignments.driver', 'itineraries', 'bookings.customer']);
        
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
        $tour->load(['schedules.driverAssignments.driver']);
        
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
        $places = Place::where('is_active', true)->orderBy('name')->get();

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
        $validated = $request->validate([
            'day_index' => 'required|integer|min:1',
            'time' => 'nullable|date_format:H:i',
            'place_id' => 'required|exists:places,id',
            'details' => 'nullable|string',
        ]);
        $place = Place::findOrFail($validated['place_id']);

        // Check for duplicate day_index and time
        $existingItinerary = TourItinerary::where('tour_id', $tour->id)
            ->where('day_index', $validated['day_index'])
            ->where('time', $validated['time'] ?? null)
            ->first();

        if ($existingItinerary) {
            return back()->with('error', 'An itinerary already exists for this day and time');
        }

        $tour->itineraries()->create([
            'place_id' => $place->id,
            'day_index' => $validated['day_index'],
            'day_number' => $validated['day_index'],
            'time' => $validated['time'] ?? null,
            'title' => $place->name,
            'description' => $validated['details'] ?? $place->short_description ?? $place->description,
            'details' => $validated['details'] ?? null,
        ]);

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

        $places = Place::where('is_active', true)->orderBy('name')->get();

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

        $validated = $request->validate([
            'day_index' => 'sometimes|required|integer|min:1',
            'time' => 'nullable|date_format:H:i',
            'place_id' => 'sometimes|required|exists:places,id',
            'details' => 'nullable|string',
        ]);

        // Check for duplicate day_index and time (excluding current)
        if (array_key_exists('day_index', $validated) || array_key_exists('time', $validated)) {
            $dayIndex = $validated['day_index'] ?? $itinerary->day_index;
            $time = $validated['time'] ?? $itinerary->time;

            $existingItinerary = TourItinerary::where('tour_id', $tour->id)
                ->where('day_index', $dayIndex)
                ->where('time', $time)
                ->where('id', '!=', $itinerary->id)
                ->first();

            if ($existingItinerary) {
                return back()->with('error', 'An itinerary already exists for this day and time');
            }
        }

        $place = isset($validated['place_id']) ? Place::findOrFail($validated['place_id']) : $itinerary->place;

        $itinerary->update([
            'place_id' => $place?->id,
            'day_index' => $validated['day_index'] ?? $itinerary->day_index,
            'day_number' => $validated['day_index'] ?? $itinerary->day_number,
            'time' => $validated['time'] ?? null,
            'title' => $place?->name ?? $itinerary->title,
            'description' => $validated['details'] ?? $itinerary->description,
            'details' => $validated['details'] ?? null,
        ]);

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
            'bookings' => TourBooking::with(['customer', 'schedule.tour', 'driverAssignments.driver'])->latest()->paginate(15),
        ]);
    }

    public function showTourBooking(TourBooking $tourBooking)
    {
        $tourBooking->load([
            'customer',
            'tour',
            'schedule.tour',
            'assignedDriver:id,name,email,phone',
            'assignedVehicle',
            'driverAssignments.driver:id,name,email,phone,rating,vehicle_number',
            'driverAssignments.vehicle',
            'refunds.customer:id,name,phone',
            'refunds.walletTransaction',
            'incidents.customer:id,name,phone',
            'incidents.driver:id,name,phone',
            'auditLogs.admin:id,name,email',
            'reviews.customer:id,name',
            'reviews.driver:id,name',
        ]);

        return inertia('admin/TourBookings/Show', [
            'title' => 'Tour Booking Details',
            'user' => Auth::user(),
            'booking' => $tourBooking,
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
            'schedules' => $tour->schedules()->with(['guideAssignments.guide', 'driverAssignments.driver', 'driverAssignments.vehicle'])->latest('departure_date')->paginate(15),
            'drivers' => Driver::query()
                ->where('status', 'active')
                ->where('is_active', true)
                ->where('is_approved', true)
                ->where(function ($query) {
                    $query->where('can_tour_lead', true)
                        ->orWhere('can_tour_transport', true);
                })
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'rating', 'vehicle_number', 'can_tour_lead', 'can_tour_transport']),
            'vehicles' => Vehicle::query()
                ->where('is_active', true)
                ->orderBy('registration_number')
                ->get(['id', 'driver_id', 'car_category_id', 'registration_number', 'make', 'model']),
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

    public function assignDriverToSchedule(Request $request, Tour $tour, TourSchedule $schedule)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'role' => 'nullable|in:transport,lead,assistant',
        ]);

        $driver = Driver::findOrFail($validated['driver_id']);
        $role = $validated['role'] ?? 'transport';

        $eligibilityFailures = app(DriverDispatchService::class)->eligibilityFailures(
            $driver,
            'tour',
            $role,
            null,
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null
        );

        if ($eligibilityFailures !== []) {
            return redirect()->back()->with('error', implode(' ', $eligibilityFailures));
        }

        $schedule->driverAssignments()->updateOrCreate(
            ['driver_id' => $driver->id],
            [
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'role' => $role,
                'status' => 'assigned',
            ]
        );

        return redirect()->back()->with('success', 'Driver assigned to tour.');
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
         $validated = $request->validate([
             'name' => 'required|string|max:255',
             'description' => 'nullable|string',
             'short_description' => 'nullable|string|max:500',
             'location' => 'nullable|string|max:255',
             'city' => 'nullable|string|max:100',
             'state' => 'nullable|string|max:100',
             'country' => 'nullable|string|max:100',
             'latitude' => 'nullable|numeric|between:-90,90',
             'longitude' => 'nullable|numeric|between:-180,180',
             'tags' => 'nullable|array',
             'tags.*' => 'string|max:80',
             'google_place_id' => 'nullable|string|max:255',
             'google_rating' => 'nullable|numeric|min:0|max:5',
             'google_review_count' => 'nullable|integer|min:0',
             'is_active' => 'required|boolean',
             'is_featured' => 'required|boolean',
         ]);

         Place::create([
             ...$validated,
             'slug' => $this->uniquePlaceSlug($validated['name']),
             'country' => ($validated['country'] ?? null) ?: 'India',
             'tags' => $validated['tags'] ?? [],
             'google_review_count' => $validated['google_review_count'] ?? 0,
         ]);

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
             'place' => $place->load(['media', 'reviews.customer:id,name']),
         ]);
     }

     public function syncPlaceGoogleData(Place $place, GooglePlaceDetailsService $googlePlaces)
     {
         $result = $googlePlaces->sync($place, true);

         $statusCode = $result['status'] === 'failed' ? 422 : 200;

         if (request()->expectsJson()) {
             return response()->json([
                 'success' => $result['status'] !== 'failed',
                 'status' => $result['status'],
                 'message' => $result['message'],
                 'place' => $place->fresh(['media', 'reviews.customer:id,name']),
             ], $statusCode);
         }

         return redirect()->route('admin.places.show', $place)
             ->with($result['status'] === 'failed' ? 'error' : 'success', $result['message']);
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
         $validated = $request->validate([
             'name' => 'required|string|max:255',
             'description' => 'nullable|string',
             'short_description' => 'nullable|string|max:500',
             'location' => 'nullable|string|max:255',
             'city' => 'nullable|string|max:100',
             'state' => 'nullable|string|max:100',
             'country' => 'nullable|string|max:100',
             'latitude' => 'nullable|numeric|between:-90,90',
             'longitude' => 'nullable|numeric|between:-180,180',
             'tags' => 'nullable|array',
             'tags.*' => 'string|max:80',
             'google_place_id' => 'nullable|string|max:255',
             'google_rating' => 'nullable|numeric|min:0|max:5',
             'google_review_count' => 'nullable|integer|min:0',
             'is_active' => 'required|boolean',
             'is_featured' => 'required|boolean',
         ]);

         $place->update([
             ...$validated,
             'slug' => $place->name !== $validated['name']
                 ? $this->uniquePlaceSlug($validated['name'], $place->id)
                 : $place->slug,
             'country' => ($validated['country'] ?? null) ?: 'India',
             'tags' => $validated['tags'] ?? [],
             'google_review_count' => $validated['google_review_count'] ?? 0,
         ]);

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
             'caption' => 'nullable|string',
         ]);

         $filePath = $request->file('file')->store('place_media', 'public');

         PlaceMedia::create([
             'place_id' => $place->id,
             'path' => $filePath,
             'type' => str_starts_with($request->file('file')->getMimeType(), 'image/') ? 'image' : 'video',
             'caption' => $request->caption,
         ]);

         return redirect()->route('admin.places.show', $place->id)->with('success', 'Media added successfully');
     }

     /**
      * Delete media
      */
     public function deleteMedia(PlaceMedia $media)
     {
         Storage::disk('public')->delete($media->path);
         $media->delete();

         return redirect()->route('admin.places.show', $media->place_id)->with('success', 'Media deleted successfully');
     }

     private function uniquePlaceSlug(string $name, ?int $ignoreId = null): string
     {
         $base = Str::slug($name) ?: 'place';
         $slug = $base;
         $counter = 2;

         while (Place::where('slug', $slug)
             ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
             ->exists()) {
             $slug = "{$base}-{$counter}";
             $counter++;
         }

         return $slug;
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
        $query = CarRental::with(['carCategory', 'customer', 'driver', 'vehicle']);

        // Filter by status if provided
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('dispatch_status') && !empty($request->dispatch_status)) {
            $query->where('dispatch_status', $request->dispatch_status);
        }

        if ($request->boolean('admin_assignable')) {
            $query->where('admin_assignable', true);
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
        $drivers = Driver::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->where('is_approved', true)
            ->where('can_rental_delivery', true)
            ->orderBy('name')
            ->get();
        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->orderBy('registration_number')
            ->get();

        return inertia('admin/CarRentals/Create', [
            'title' => 'Create Car Rental',
            'user' => Auth::user(),
            'car_categories' => $carCategories,
            'destinations' => $destinations,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
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
            'status' => 'required|in:pending,confirmed,driver_assigned,in_progress,completed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'required|in:cash,card,bank_transfer,upi',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
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

        $customer = Customer::firstOrCreate(
            ['phone' => $validated['customer_phone']],
            [
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'is_active' => true,
            ]
        );

        $carRental = CarRental::create([
            'booking_number' => CarRental::generateBookingNumber(),
            'customer_id' => $customer->id,
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
            'status' => $validated['driver_id'] && $validated['status'] === 'confirmed'
                ? 'driver_assigned'
                : $validated['status'],
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'],
            'special_requests' => $validated['special_requests'],
            'driver_id' => $validated['driver_id'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'internal_notes' => $validated['internal_notes'],
            'whatsapp_notification' => $request->boolean('whatsapp_notification', true),
            'email_notification' => $request->boolean('email_notification', true),
            'sms_notification' => $request->boolean('sms_notification', false),
        ]);

        if (!empty($validated['driver_id'])) {
            DriverAvailability::where('driver_id', $validated['driver_id'])->update([
                'status' => 'on_ride',
                'is_available' => false,
                'last_updated' => now(),
            ]);
        }

        return redirect()->route('admin.car-rentals')->with('success', 'Car rental created successfully');
    }

    /**
     * Show car rental details
     */
    public function showCarRental(CarRental $carRental)
    {
        $carRental->load([
            'carCategory',
            'customer',
            'driver',
            'vehicle',
            'extras',
            'refunds.customer:id,name,phone',
            'refunds.walletTransaction',
            'incidents.customer:id,name,phone',
            'incidents.driver:id,name,phone',
            'auditLogs.admin:id,name,email',
            'reviews.customer:id,name',
            'reviews.driver:id,name',
        ]);

        return inertia('admin/CarRentals/Show', [
            'title' => 'Car Rental Details',
            'user' => Auth::user(),
            'car_rental' => $carRental,
            'drivers' => Driver::query()
                ->where('status', 'active')
                ->where('is_active', true)
                ->where('is_approved', true)
                ->where('can_rental_delivery', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'rating', 'vehicle_number']),
            'vehicles' => Vehicle::query()
                ->where('is_active', true)
                ->orderBy('registration_number')
                ->get(['id', 'driver_id', 'car_category_id', 'registration_number', 'make', 'model']),
        ]);
    }

    /**
     * Show edit car rental form
     */
    public function editCarRental(CarRental $carRental)
    {
        $carRental->load(['carCategory', 'customer', 'driver', 'vehicle']);
        $carCategories = CarCategory::where('is_active', true)->orderBy('name')->get();
        $destinations = Destination::where('is_active', true)->orderBy('name')->get();
        $drivers = Driver::query()
            ->where('status', 'active')
            ->where('is_active', true)
            ->where('is_approved', true)
            ->where('can_rental_delivery', true)
            ->orderBy('name')
            ->get();
        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->orderBy('registration_number')
            ->get();

        return inertia('admin/CarRentals/Edit', [
            'title' => 'Edit Car Rental',
            'user' => Auth::user(),
            'car_rental' => $carRental,
            'car_categories' => $carCategories,
            'destinations' => $destinations,
            'drivers' => $drivers,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Update car rental
     */
    public function updateCarRental(Request $request, CarRental $carRental)
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
            'status' => 'required|in:pending,confirmed,driver_assigned,in_progress,completed,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'required|in:cash,card,bank_transfer,upi',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
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

        $customer = Customer::updateOrCreate(
            ['phone' => $validated['customer_phone']],
            [
                'name' => $validated['customer_name'],
                'email' => $validated['customer_email'],
                'is_active' => true,
            ]
        );

        $previousDriverId = $carRental->driver_id;

        $carRental->update([
            'customer_id' => $customer->id,
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
            'driver_id' => $validated['driver_id'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'internal_notes' => $validated['internal_notes'],
            'whatsapp_notification' => $request->boolean('whatsapp_notification', $carRental->whatsapp_notification),
            'email_notification' => $request->boolean('email_notification', $carRental->email_notification),
            'sms_notification' => $request->boolean('sms_notification', $carRental->sms_notification),
        ]);

        $this->syncDriverAvailabilityForRental($carRental, $previousDriverId);

        return redirect()->route('admin.car-rentals')->with('success', 'Car rental updated successfully');
    }

    /**
     * Delete car rental
     */
    public function deleteCarRental(CarRental $carRental)
    {
        $carRental->delete();

        return redirect()->route('admin.car-rentals')->with('success', 'Car rental deleted successfully');
    }

    public function assignCarRentalDriver(Request $request, CarRental $carRental)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        $driver = Driver::findOrFail($validated['driver_id']);

        $eligibilityFailures = app(DriverDispatchService::class)->eligibilityFailures(
            $driver,
            'rental',
            null,
            $carRental->car_category_id ? (int) $carRental->car_category_id : null,
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
            $carRental->pickup_lat !== null ? (float) $carRental->pickup_lat : null,
            $carRental->pickup_lng !== null ? (float) $carRental->pickup_lng : null
        );

        if ($eligibilityFailures !== []) {
            return response()->json([
                'message' => implode(' ', $eligibilityFailures),
                'errors' => ['driver_id' => $eligibilityFailures],
            ], 422);
        }

        $previousDriverId = $carRental->driver_id;

        $carRental->update([
            'driver_id' => $driver->id,
            'vehicle_id' => $validated['vehicle_id'] ?? $carRental->vehicle_id,
            'status' => in_array($carRental->status, ['completed', 'cancelled'], true)
                ? $carRental->status
                : 'driver_assigned',
        ]);

        $this->syncDriverAvailabilityForRental($carRental, $previousDriverId);
        $this->notifyBookingLifecycle($carRental->fresh('customer'), 'driver.assigned', [
            'driver_id' => $driver->id,
            'vehicle_id' => $validated['vehicle_id'] ?? $carRental->vehicle_id,
        ]);

        return response()->json([
            'message' => 'Rental driver assigned successfully.',
            'car_rental' => $carRental->fresh(['driver:id,name,email,phone', 'vehicle']),
        ]);
    }

    public function updateCarRentalStatus(Request $request, CarRental $carRental)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,confirmed,driver_assigned,in_progress,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        if (!isset($validated['status']) && !isset($validated['payment_status'])) {
            return response()->json(['message' => 'No changes requested.'], 422);
        }

        $previousDriverId = $carRental->driver_id;
        $before = $this->bookingAuditSnapshot($carRental);
        $statusService = app(BookingStatusService::class);
        $cancellationService = app(BookingCancellationService::class);

        $updates = [];
        if (isset($validated['status'])) {
            $requestedStatus = $statusService->normalize(BookingStatusService::RENTAL, $validated['status']);
            if (!$requestedStatus || !$statusService->canTransition(BookingStatusService::RENTAL, $carRental->status, $requestedStatus, 'admin')) {
                return response()->json([
                    'message' => 'Invalid rental status transition.',
                    'allowed_transitions' => $statusService->allowedTransitions(BookingStatusService::RENTAL, $carRental->status, 'admin'),
                ], 422);
            }

            if ($requestedStatus === 'cancelled') {
                $refund = $cancellationService->cancel(
                    $carRental,
                    BookingStatusService::RENTAL,
                    $validated['cancellation_reason'] ?? null,
                    Auth::id()
                );
                $this->recordBookingAudit($carRental->fresh(), 'cancelled', $before, $this->bookingAuditSnapshot($carRental->fresh()), $validated['cancellation_reason'] ?? null);
                $this->syncDriverAvailabilityForRental($carRental->fresh(), $previousDriverId);
                $this->notifyBookingLifecycle($carRental->fresh('customer'), 'booking.cancelled');
                if ($refund?->status === 'processed') {
                    $this->notifyBookingLifecycle($carRental->fresh('customer'), 'refund.processed', ['refund_id' => $refund->id]);
                }

                return response()->json([
                    'message' => 'Car rental cancelled successfully.',
                    'car_rental' => $carRental->fresh(['driver:id,name,email,phone', 'vehicle', 'refunds']),
                ]);
            }

            $updates['status'] = $requestedStatus;
        }
        if (isset($validated['payment_status'])) {
            $updates['payment_status'] = $validated['payment_status'];
        }

        $carRental->update($updates);
        if (($updates['status'] ?? null) === 'completed' && $carRental->fresh()->payment_status === 'paid') {
            app(CommissionService::class)->settleRental($carRental->fresh());
        }
        $this->recordBookingAudit($carRental->fresh(), 'status_updated', $before, $this->bookingAuditSnapshot($carRental->fresh()));
        $this->syncDriverAvailabilityForRental($carRental, $previousDriverId);
        $this->notifyStatusAndPaymentLifecycle($carRental->fresh('customer'), $updates);

        return response()->json([
            'message' => 'Car rental updated successfully.',
            'car_rental' => $carRental->fresh(['driver:id,name,email,phone', 'vehicle']),
        ]);
    }

    public function confirmCarRentalPayment(Request $request, CarRental $carRental)
    {
        return $this->confirmBookingPayment($request, $carRental, 'car_rental');
    }

    public function updateTourBookingStatus(Request $request, TourBooking $tourBooking)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
            'payment_status' => 'nullable|in:pending,partial,paid,refunded',
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        if (!isset($validated['status']) && !isset($validated['payment_status'])) {
            return response()->json(['message' => 'No changes requested.'], 422);
        }

        $statusService = app(BookingStatusService::class);

        DB::transaction(function () use ($tourBooking, $validated) {
            $tourBooking->refresh();
            $previousStatus = $tourBooking->status;
            $previousPaymentStatus = $tourBooking->payment_status;
            $before = $this->bookingAuditSnapshot($tourBooking);

            $updates = [];
            if (isset($validated['status'])) {
                $statusService = app(BookingStatusService::class);
                $requestedStatus = $statusService->normalize(BookingStatusService::TOUR, $validated['status']);
                if (!$requestedStatus || !$statusService->canTransition(BookingStatusService::TOUR, $tourBooking->status, $requestedStatus, 'admin')) {
                    abort(response()->json([
                        'message' => 'Invalid tour booking status transition.',
                        'allowed_transitions' => $statusService->allowedTransitions(BookingStatusService::TOUR, $tourBooking->status, 'admin'),
                    ], 422));
                }

                if ($requestedStatus === 'cancelled') {
                    $refund = app(BookingCancellationService::class)->cancel(
                        $tourBooking,
                        BookingStatusService::TOUR,
                        $validated['cancellation_reason'] ?? null,
                        Auth::id()
                    );
                    $this->recordBookingAudit($tourBooking->fresh(), 'cancelled', $before, $this->bookingAuditSnapshot($tourBooking->fresh()), $validated['cancellation_reason'] ?? null);
                    $this->syncTourSeatInventory($tourBooking->fresh(), $previousStatus, $previousPaymentStatus);
                    $this->notifyBookingLifecycle($tourBooking->fresh('customer'), 'booking.cancelled');
                    if ($refund?->status === 'processed') {
                        $this->notifyBookingLifecycle($tourBooking->fresh('customer'), 'refund.processed', ['refund_id' => $refund->id]);
                    }

                    return;
                }

                $updates['status'] = $requestedStatus;
            }
            if (isset($validated['payment_status'])) {
                $updates['payment_status'] = $validated['payment_status'];
            }

            $tourBooking->update($updates);
            if (($updates['status'] ?? null) === 'completed' && $tourBooking->fresh()->payment_status === 'paid') {
                app(CommissionService::class)->settleTour($tourBooking->fresh());
            }
            $this->recordBookingAudit($tourBooking->fresh(), 'status_updated', $before, $this->bookingAuditSnapshot($tourBooking->fresh()));
            $this->syncTourSeatInventory($tourBooking->fresh(), $previousStatus, $previousPaymentStatus);
            $this->notifyStatusAndPaymentLifecycle($tourBooking->fresh('customer'), $updates);
        });

        return response()->json([
            'message' => 'Tour booking updated successfully.',
            'tour_booking' => $tourBooking->fresh(['customer', 'tour', 'schedule', 'refunds']),
        ]);
    }

    public function confirmTourBookingPayment(Request $request, TourBooking $tourBooking)
    {
        return $this->confirmBookingPayment($request, $tourBooking, 'tour_booking');
    }

    private function syncDriverAvailabilityForRental(CarRental $carRental, ?int $previousDriverId = null): void
    {
        if ($previousDriverId && $previousDriverId !== $carRental->driver_id) {
            DriverAvailability::where('driver_id', $previousDriverId)->update([
                'status' => 'online',
                'is_available' => true,
                'last_updated' => now(),
            ]);
        }

        if (!$carRental->driver_id) {
            return;
        }

        $isActiveRental = in_array($carRental->status, ['driver_assigned', 'in_progress'], true);

        DriverAvailability::where('driver_id', $carRental->driver_id)->update([
            'status' => $isActiveRental ? 'on_ride' : 'online',
            'is_available' => !$isActiveRental,
            'last_updated' => now(),
        ]);
    }

    private function syncTourSeatInventory(TourBooking $booking, string $previousStatus, string $previousPaymentStatus): void
    {
        $schedule = $booking->schedule()->lockForUpdate()->first();
        if (!$schedule) {
            return;
        }

        $seats = $booking->getTotalPax();
        $wasConfirmed = $previousStatus === 'confirmed' || $previousPaymentStatus === 'paid';
        $isConfirmed = $booking->status === 'confirmed' || $booking->payment_status === 'paid';
        $isCancelled = $booking->status === 'cancelled';

        if (!$wasConfirmed && $isConfirmed && !$isCancelled) {
            $schedule->decrement('reserved_seats', min($schedule->reserved_seats, $seats));
            $schedule->increment('booked_seats', $seats);
            return;
        }

        if ($previousStatus === 'pending' && $isCancelled) {
            $schedule->decrement('reserved_seats', min($schedule->reserved_seats, $seats));
            return;
        }

        if ($wasConfirmed && $isCancelled) {
            $schedule->decrement('booked_seats', min($schedule->booked_seats, $seats));
        }
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
            'customer:id,name,email,phone',
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
                    'is_online' => ($availability?->status ?? 'offline') !== 'offline',
                    'is_available' => (bool) ($availability?->is_available ?? false),
                    'rating' => $driver->rating,
                    'vehicle_number' => $driver->vehicle_number,
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
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $driver = Driver::findOrFail($validated['driver_id']);
        $dispatchService = app(DriverDispatchService::class);
        $eligibilityFailures = $dispatchService->eligibilityFailures(
            $driver,
            $dispatchService->rideServiceType($rideBooking),
            null,
            $rideBooking->car_category_id ? (int) $rideBooking->car_category_id : null,
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
            $rideBooking->pickup_lat !== null ? (float) $rideBooking->pickup_lat : null,
            $rideBooking->pickup_lng !== null ? (float) $rideBooking->pickup_lng : null
        );

        if ($eligibilityFailures !== []) {
            return response()->json([
                'message' => implode(' ', $eligibilityFailures),
                'errors' => ['driver_id' => $eligibilityFailures],
            ], 422);
        }

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
            'vehicle_id' => $validated['vehicle_id'] ?? $rideBooking->vehicle_id,
            'status' => in_array($rideBooking->status, ['completed', 'cancelled'], true)
                ? $rideBooking->status
                : 'driver_assigned',
            'dispatch_status' => 'assigned',
            'admin_assignable' => false,
            'start_ride_pin' => $rideBooking->start_ride_pin ?: RideBooking::generateStartRidePin(),
            'start_pin_verified_at' => null,
            'last_admin_changed_at' => now(),
            'last_admin_changed_by' => Auth::id(),
        ]);

        DriverAvailability::where('driver_id', $newDriverId)->update([
            'is_available' => false,
        ]);
        $this->notifyBookingLifecycle($rideBooking->fresh('customer'), 'driver.assigned', [
            'driver_id' => $newDriverId,
            'vehicle_id' => $validated['vehicle_id'] ?? $rideBooking->vehicle_id,
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
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        if (!isset($validated['status']) && !isset($validated['payment_status'])) {
            return response()->json([
                'message' => 'No changes requested.',
            ], 422);
        }

        $this->captureAdminSnapshot($rideBooking);
        $before = $this->bookingAuditSnapshot($rideBooking);
        $statusService = app(BookingStatusService::class);

        $updateData = [
            'last_admin_changed_at' => now(),
            'last_admin_changed_by' => Auth::id(),
        ];

        if (isset($validated['status'])) {
            $requestedStatus = $statusService->normalize(BookingStatusService::RIDE, $validated['status']);
            if (!$requestedStatus || !$statusService->canTransition(BookingStatusService::RIDE, $rideBooking->status, $requestedStatus, 'admin')) {
                return response()->json([
                    'message' => 'Invalid ride booking status transition.',
                    'allowed_transitions' => $statusService->allowedTransitions(BookingStatusService::RIDE, $rideBooking->status, 'admin'),
                ], 422);
            }

            if ($requestedStatus === 'cancelled') {
                $refund = app(BookingCancellationService::class)->cancel(
                    $rideBooking,
                    BookingStatusService::RIDE,
                    $validated['cancellation_reason'] ?? null,
                    Auth::id()
                );

                $rideBooking->update($updateData);
                $this->recordBookingAudit($rideBooking->fresh(), 'cancelled', $before, $this->bookingAuditSnapshot($rideBooking->fresh()), $validated['cancellation_reason'] ?? null);
                $this->notifyBookingLifecycle($rideBooking->fresh('customer'), 'booking.cancelled');
                if ($refund?->status === 'processed') {
                    $this->notifyBookingLifecycle($rideBooking->fresh('customer'), 'refund.processed', ['refund_id' => $refund->id]);
                }

                return response()->json([
                    'message' => 'Ride booking cancelled successfully.',
                    'ride_booking' => $rideBooking->fresh(['driver:id,name,email,phone', 'refunds']),
                ]);
            }

            $updateData['status'] = $requestedStatus;
        }

        if (isset($validated['payment_status'])) {
            $updateData['payment_status'] = $validated['payment_status'];
        }

        $rideBooking->update($updateData);
        if (($updateData['status'] ?? null) === 'completed' && $rideBooking->fresh()->payment_status === 'paid') {
            app(CommissionService::class)->settleRide($rideBooking->fresh());
        }
        $this->recordBookingAudit($rideBooking->fresh(), 'status_updated', $before, $this->bookingAuditSnapshot($rideBooking->fresh()));
        $this->notifyStatusAndPaymentLifecycle($rideBooking->fresh('customer'), $updateData);

        return response()->json([
            'message' => 'Ride booking updated successfully.',
            'ride_booking' => $rideBooking->fresh(['driver:id,name,email,phone']),
        ]);
    }

    public function confirmRideBookingPayment(Request $request, RideBooking $rideBooking)
    {
        return $this->confirmBookingPayment($request, $rideBooking, 'ride_booking');
    }

    public function processBookingRefund(BookingRefund $bookingRefund)
    {
        $refund = app(BookingCancellationService::class)->processRefund($bookingRefund, Auth::id());
        if ($refund->status === 'processed' && $refund->refundable) {
            $this->notifyBookingLifecycle($refund->refundable->fresh('customer'), 'refund.processed', ['refund_id' => $refund->id]);
        }

        return response()->json([
            'message' => $refund->status === 'processed'
                ? 'Refund processed successfully.'
                : 'Refund could not be processed.',
            'refund' => $refund->fresh(['refundable', 'customer', 'walletTransaction']),
        ], $refund->status === 'processed' ? 200 : 422);
    }

    public function storeRideBookingIncident(Request $request, RideBooking $rideBooking)
    {
        return $this->storeBookingIncident($request, $rideBooking);
    }

    public function storeTourBookingIncident(Request $request, TourBooking $tourBooking)
    {
        return $this->storeBookingIncident($request, $tourBooking);
    }

    public function storeCarRentalIncident(Request $request, CarRental $carRental)
    {
        return $this->storeBookingIncident($request, $carRental);
    }

    public function updateBookingIncident(Request $request, BookingIncident $bookingIncident)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,under_review,resolved,dismissed',
            'resolution' => 'nullable|string|max:2000',
        ]);

        $bookingIncident->update([
            'status' => $validated['status'],
            'resolution' => $validated['resolution'] ?? $bookingIncident->resolution,
            'resolved_at' => in_array($validated['status'], ['resolved', 'dismissed'], true) ? now() : null,
            'resolved_by' => in_array($validated['status'], ['resolved', 'dismissed'], true) ? Auth::id() : null,
        ]);

        return response()->json([
            'message' => 'Booking incident updated successfully.',
            'incident' => $bookingIncident->fresh(['incidentable', 'customer', 'driver']),
        ]);
    }

    private function storeBookingIncident(Request $request, \Illuminate\Database\Eloquent\Model $booking)
    {
        $validated = $request->validate([
            'type' => 'required|in:no_show,dispute,safety,payment,service_quality,other',
            'severity' => 'nullable|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'status' => 'nullable|in:open,under_review',
        ]);

        $incident = $booking->incidents()->create([
            'customer_id' => $booking->customer_id,
            'driver_id' => $booking->driver_id ?? $booking->assigned_driver_id ?? null,
            'opened_by_type' => Auth::user() ? get_class(Auth::user()) : null,
            'opened_by_id' => Auth::id(),
            'type' => $validated['type'],
            'severity' => $validated['severity'] ?? 'medium',
            'status' => $validated['status'] ?? 'open',
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'reported_at' => now(),
        ]);

        $this->recordBookingAudit($booking, 'incident_created', [], [
            'incident_id' => $incident->id,
            'type' => $incident->type,
            'severity' => $incident->severity,
            'status' => $incident->status,
        ], $incident->title);

        return response()->json([
            'message' => 'Booking incident recorded successfully.',
            'incident' => $incident->fresh(['incidentable', 'customer', 'driver']),
        ], 201);
    }

    private function recordBookingAudit(
        \Illuminate\Database\Eloquent\Model $booking,
        string $action,
        array $before = [],
        array $after = [],
        ?string $note = null
    ): BookingAuditLog {
        return $booking->auditLogs()->create([
            'admin_id' => Auth::id(),
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'note' => $note,
        ]);
    }

    private function confirmBookingPayment(Request $request, \Illuminate\Database\Eloquent\Model $booking, string $type)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,upi,bank_transfer,razorpay,wallet',
            'payment_reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($booking->payment_status === 'refunded') {
            return response()->json([
                'message' => 'Refunded bookings cannot be marked as paid.',
            ], 422);
        }

        $before = $this->bookingAuditSnapshot($booking);

        DB::transaction(function () use ($booking, $validated, $before, $type) {
            $booking->refresh();
            $updates = [
                'payment_status' => 'paid',
                'payment_method' => $validated['payment_method'],
            ];

            if ($booking instanceof TourBooking && $booking->status === 'pending') {
                $updates['status'] = 'confirmed';
            }

            $previousStatus = $booking->status;
            $previousPaymentStatus = $booking->payment_status;
            $booking->update($updates);

            if ($booking instanceof TourBooking) {
                $this->syncTourSeatInventory($booking->fresh(), $previousStatus, $previousPaymentStatus);
            }

            $this->recordBookingAudit(
                $booking->fresh(),
                'payment_confirmed',
                $before,
                array_merge($this->bookingAuditSnapshot($booking->fresh()), [
                    'payment_reference' => $validated['payment_reference'] ?? null,
                    'confirmation_type' => $type,
                ]),
                $validated['note'] ?? null
            );
            $this->notifyBookingLifecycle($booking->fresh('customer'), 'payment.paid', [
                'confirmation_type' => $type,
                'payment_reference' => $validated['payment_reference'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Payment confirmed successfully.',
            'booking' => $booking->fresh(),
        ]);
    }

    private function bookingAuditSnapshot(\Illuminate\Database\Eloquent\Model $booking): array
    {
        return [
            'status' => $booking->status ?? null,
            'payment_status' => $booking->payment_status ?? null,
            'driver_id' => $booking->driver_id ?? $booking->assigned_driver_id ?? null,
            'vehicle_id' => $booking->vehicle_id ?? $booking->assigned_vehicle_id ?? null,
            'cancellation_reason' => $booking->cancellation_reason ?? null,
            'cancellation_fee' => $booking->cancellation_fee ?? null,
            'refund_amount' => $booking->refund_amount ?? null,
        ];
    }

    private function notifyStatusAndPaymentLifecycle(\Illuminate\Database\Eloquent\Model $booking, array $updates): void
    {
        if (isset($updates['status']) && ($action = $this->statusNotificationAction($updates['status']))) {
            $this->notifyBookingLifecycle($booking, $action);
        }

        if (isset($updates['payment_status']) && ($action = $this->paymentNotificationAction($updates['payment_status']))) {
            $this->notifyBookingLifecycle($booking, $action);
        }
    }

    private function notifyBookingLifecycle(\Illuminate\Database\Eloquent\Model $booking, string $action, array $metadata = []): void
    {
        app(BookingLifecycleNotifier::class)->emit($booking, $action, $metadata);
    }

    private function statusNotificationAction(string $status): ?string
    {
        return match ($status) {
            'driver_assigned' => 'driver.assigned',
            'in_progress', 'in_transit' => 'booking.started',
            'completed' => 'booking.completed',
            'cancelled' => 'booking.cancelled',
            default => null,
        };
    }

    private function paymentNotificationAction(string $paymentStatus): ?string
    {
        return match ($paymentStatus) {
            'paid' => 'payment.paid',
            'failed' => 'payment.failed',
            'refunded' => 'refund.processed',
            default => null,
        };
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
