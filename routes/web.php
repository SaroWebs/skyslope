<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\CarRentalController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\RideBookingController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\CarCategoryController as ApiCarCategoryController;
use App\Http\Controllers\Api\DestinationController as ApiDestinationController;

Route::get('/link', function () {
    try {
        $output = Artisan::call('storage:link');
        return 'Command output: ' . $output;
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

Route::get('/', [PagesController::class, 'index'])->name('home');
Route::get('/about', [PagesController::class, 'about'])->name('about');
Route::get('/contact', [PagesController::class, 'contact'])->name('contact');
Route::get('/tours', [PagesController::class, 'tours'])->name('tours');
Route::get('/tours/{id}', [PagesController::class, 'tourView'])->name('tours.show');
Route::get('/destinations', [PagesController::class, 'destinations'])->name('destinations');
Route::get('/destinations/{id}', [PagesController::class, 'destinationView'])->name('destinations.show');
Route::get('/car-rental', [PagesController::class, 'carRental'])->name('car-rental');
Route::get('/ride-booking', [PagesController::class, 'rideBooking'])->name('ride-booking');
Route::get('/book-now', [PagesController::class, 'bookNow'])->name('book-now');

// API Routes for dynamic data
Route::get('/api/tours', [TourController::class, 'index'])->name('api.tours');
Route::get('/api/tours/{id}', [TourController::class, 'show'])->name('api.tours.show');
Route::get('/api/places', [PlaceController::class, 'index'])->name('api.places');
Route::get('/api/car-categories', [ApiCarCategoryController::class, 'index'])->name('api.car-categories');
Route::get('/api/car-categories/active', [ApiCarCategoryController::class, 'getActiveCategories'])->name('api.car-categories.active');
Route::get('/api/destinations', [ApiDestinationController::class, 'index'])->name('api.destinations');

// Location and Ride Booking APIs
Route::get('/api/locations/search', [LocationController::class, 'search'])->name('api.locations.search');
Route::get('/api/locations/popular', [LocationController::class, 'popular'])->name('api.locations.popular');
Route::post('/api/locations/validate', [LocationController::class, 'validateLocation'])->name('api.locations.validate');
Route::get('/api/locations/place-details', [LocationController::class, 'placeDetails'])->name('api.locations.place-details');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // Car Rental Routes
    Route::post('/api/book-car', [CarRentalController::class, 'bookCar'])->name('api.book-car');
    Route::resource('car-rentals', CarRentalController::class);

    // Ride Booking Routes (Ola/Uber-like)
    Route::get('/api/ride-bookings/estimate', [RideBookingController::class, 'estimate'])->name('api.ride-bookings.estimate');
    Route::post('/api/ride-bookings', [RideBookingController::class, 'store'])->name('api.ride-bookings.store');
    Route::get('/api/ride-bookings/nearby-drivers', [RideBookingController::class, 'nearbyDrivers'])->name('api.ride-bookings.nearby-drivers');
    Route::resource('ride-bookings', RideBookingController::class);

    // Tour Routes
    Route::post('/api/book-tour', [TourController::class, 'bookTour'])->name('api.book-tour');
});

// Admin Routes (require admin role)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // User Management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');

    // Tour Management Routes
    Route::get('/tours', [AdminController::class, 'tours'])->name('tours');
    Route::get('/tours/create', [AdminController::class, 'createTour'])->name('tours.create');
    Route::post('/tours', [AdminController::class, 'storeTour'])->name('tours.store');
    Route::get('/tours/{tour}', [AdminController::class, 'showTour'])->name('tours.show');
    Route::get('/tours/{tour}/edit', [AdminController::class, 'editTour'])->name('tours.edit');
    Route::put('/tours/{tour}', [AdminController::class, 'updateTour'])->name('tours.update');
    Route::delete('/tours/{tour}', [AdminController::class, 'deleteTour'])->name('tours.delete');
    
    // Itinerary Management Routes (nested under tours)
    Route::get('/tours/{tour}/itineraries', [AdminController::class, 'tourItineraries'])->name('tours.itineraries');
    Route::get('/tours/{tour}/itineraries/create', [AdminController::class, 'createTourItinerary'])->name('tours.itineraries.create');
    Route::post('/tours/{tour}/itineraries', [AdminController::class, 'storeTourItinerary'])->name('tours.itineraries.store');
    Route::get('/tours/{tour}/itineraries/{itinerary}', [AdminController::class, 'showTourItinerary'])->name('tours.itineraries.show');
    Route::get('/tours/{tour}/itineraries/{itinerary}/edit', [AdminController::class, 'editTourItinerary'])->name('tours.itineraries.edit');
    Route::put('/tours/{tour}/itineraries/{itinerary}', [AdminController::class, 'updateTourItinerary'])->name('tours.itineraries.update');
    Route::delete('/tours/{tour}/itineraries/{itinerary}', [AdminController::class, 'deleteTourItinerary'])->name('tours.itineraries.delete');

    // Other Management Routes
    Route::get('/bookings', [AdminController::class, 'bookings'])->name('bookings');
    Route::get('/ride-bookings', [AdminController::class, 'rideBookings'])->name('ride-bookings');
    
    // Car Rentals Management Routes
    Route::get('/car-rentals', [AdminController::class, 'carRentals'])->name('car-rentals');
    Route::get('/car-rentals/create', [AdminController::class, 'createCarRental'])->name('car-rentals.create');
    Route::post('/car-rentals', [AdminController::class, 'storeCarRental'])->name('car-rentals.store');
    Route::get('/car-rentals/{carRental}', [AdminController::class, 'showCarRental'])->name('car-rentals.show');
    Route::get('/car-rentals/{carRental}/edit', [AdminController::class, 'editCarRental'])->name('car-rentals.edit');
    Route::put('/car-rentals/{carRental}', [AdminController::class, 'updateCarRental'])->name('car-rentals.update');
    Route::delete('/car-rentals/{carRental}', [AdminController::class, 'deleteCarRental'])->name('car-rentals.delete');
    // Car Categories Management Routes
    Route::get('/car-categories', [AdminController::class, 'carCategories'])->name('car-categories');
    Route::get('/car-categories/create', [AdminController::class, 'createCarCategory'])->name('car-categories.create');
    Route::post('/car-categories', [AdminController::class, 'storeCarCategory'])->name('car-categories.store');
    Route::get('/car-categories/{carCategory}', [AdminController::class, 'showCarCategory'])->name('car-categories.show');
    Route::get('/car-categories/{carCategory}/edit', [AdminController::class, 'editCarCategory'])->name('car-categories.edit');
    Route::put('/car-categories/{carCategory}', [AdminController::class, 'updateCarCategory'])->name('car-categories.update');
    Route::delete('/car-categories/{carCategory}', [AdminController::class, 'deleteCarCategory'])->name('car-categories.delete');

    // Destinations Management Routes
    Route::get('/destinations', [AdminController::class, 'destinations'])->name('destinations');
    Route::get('/destinations/create', [AdminController::class, 'createDestination'])->name('destinations.create');
    Route::post('/destinations', [AdminController::class, 'storeDestination'])->name('destinations.store');
    Route::get('/destinations/{destination}', [AdminController::class, 'showDestination'])->name('destinations.show');
    Route::get('/destinations/{destination}/edit', [AdminController::class, 'editDestination'])->name('destinations.edit');
    Route::put('/destinations/{destination}', [AdminController::class, 'updateDestination'])->name('destinations.update');
    Route::delete('/destinations/{destination}', [AdminController::class, 'deleteDestination'])->name('destinations.delete');
    Route::get('/places', [AdminController::class, 'places'])->name('places');
    Route::get('/places/create', [AdminController::class, 'createPlace'])->name('places.create');
    Route::post('/places', [AdminController::class, 'storePlace'])->name('places.store');
    Route::get('/places/{place}', [AdminController::class, 'showPlace'])->name('places.show');
    Route::get('/places/{place}/edit', [AdminController::class, 'editPlace'])->name('places.edit');
    Route::put('/places/{place}', [AdminController::class, 'updatePlace'])->name('places.update');
    Route::delete('/places/{place}', [AdminController::class, 'deletePlace'])->name('places.delete');
    Route::post('/places/{place}/media', [AdminController::class, 'storeMedia'])->name('places.media.store');
    Route::delete('/media/{media}', [AdminController::class, 'deleteMedia'])->name('media.delete');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});
