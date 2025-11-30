<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Tour;
use App\Models\Booking;
use App\Models\Place;
use App\Models\Itinerary;
use App\Models\PlaceMedia;
use App\Models\Role;
use App\Models\RideBooking;

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
                'total_tours' => Tour::count(),
                'total_bookings' => Booking::count(),
                'total_places' => Place::count(),
                'active_tours' => Tour::where('available_from', '>=', now())->count(),
                'recent_bookings' => Booking::with('user', 'tour')->latest()->take(5)->get(),
            ],
            'recent_users' => User::with('roles')->latest()->take(5)->get(),
            'upcoming_tours' => Tour::with('guides', 'drivers')->where('available_from', '>=', now())->take(5)->get(),
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
            'roles' => Role::active()->get(),
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
            'roles' => Role::active()->get(),
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
            'role' => 'required|exists:roles,name',
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
            'roles' => Role::active()->get(),
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
            'role' => 'required|exists:roles,name',
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
        $toursPaginator = Tour::with('guides.user', 'drivers.user')
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
        $existingItinerary = Itinerary::where('tour_id', $tour->id)
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
    public function showTourItinerary(Tour $tour, Itinerary $itinerary)
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
    public function editTourItinerary(Tour $tour, Itinerary $itinerary)
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
    public function updateTourItinerary(Request $request, Tour $tour, Itinerary $itinerary)
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

            $existingItinerary = Itinerary::where('tour_id', $tour->id)
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
    public function deleteTourItinerary(Tour $tour, Itinerary $itinerary)
    {
        if ($itinerary->tour_id !== $tour->id) {
            return back()->with('error', 'Itinerary not found for this tour');
        }

        $itinerary->delete();

        return redirect()->route('admin.tours.itineraries', $tour->id)->with('success', 'Itinerary deleted successfully');
    }

    /**
     * Bookings management
     */
    public function bookings()
    {
        return inertia('admin/Bookings', [
            'title' => 'Booking Management',
            'user' => Auth::user(),
            'bookings' => Booking::with('user', 'tour')->paginate(15),
        ]);
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
     * Car categories management
     */
    public function carCategories()
    {
        return inertia('admin/CarCategories', [
            'title' => 'Car Categories Management',
            'user' => Auth::user(),
            'car_categories' => \App\Models\CarCategory::paginate(12),
        ]);
    }

    /**
     * Destinations management
     */
    public function destinations()
    {
        return inertia('admin/Destinations', [
            'title' => 'Destinations Management',
            'user' => Auth::user(),
            'destinations' => \App\Models\Destination::paginate(12),
        ]);
    }

    /**
     * Ride bookings management
     */
    public function rideBookings(Request $request)
    {
        $query = RideBooking::with(['user', 'driver']);

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
}
