<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDriverController;
use App\Http\Controllers\AdminGuideController;
use App\Http\Controllers\AdminVehicleController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\CarRentalController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\RideBookingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| All public facing web pages.
*/

if (app()->isLocal()) {
    Route::get('/link', function () {
        try {
            $output = Artisan::call('storage:link');
            return 'Command output: ' . $output;
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    });
}

Route::get('/', [PagesController::class, 'index'])->name('home');
Route::get('/about', [PagesController::class, 'about'])->name('about');
Route::get('/contact', [PagesController::class, 'contact'])->name('contact');

// Public browse routes (Inertia based)
Route::controller(PagesController::class)->group(function () {
    Route::get('/tours', 'tours')->name('tours');
    Route::get('/tours/{id}', 'tourView')->name('tours.show');
    Route::get('/destinations', 'destinations')->name('destinations');
    Route::get('/destinations/{id}', 'destinationView')->name('destinations.show');
    Route::get('/car-rental', 'carRental')->name('car-rental');
    Route::get('/ride-booking', 'rideBooking')->name('ride-booking');
    Route::get('/book-now', 'bookNow')->name('book-now');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Inertia based)
|--------------------------------------------------------------------------
| Redesigned admin dashboard with Mantine UI.
*/

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Admin Account Management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');

    // Customer Management (New)
    Route::controller(AdminCustomerController::class)->group(function () {
        Route::get('/customers', 'index')->name('customers');
        Route::get('/customers/{customer}', 'show')->name('customers.show');
        Route::post('/customers/{customer}/toggle-status', 'toggleStatus')->name('customers.toggle-status');
    });

    // Driver Management (New)
    Route::controller(AdminDriverController::class)->group(function () {
        Route::get('/drivers', 'index')->name('drivers');
        Route::get('/drivers/{driver}', 'show')->name('drivers.show');
        Route::post('/drivers/{driver}/approve', 'approve')->name('drivers.approve');
        Route::post('/drivers/{driver}/suspend', 'suspend')->name('drivers.suspend');
        Route::post('/drivers/{driver}/activate', 'activate')->name('drivers.activate');
    });

    // Guide Management (New)
    Route::controller(AdminGuideController::class)->group(function () {
        Route::get('/guides', 'index')->name('guides');
        Route::get('/guides/{guide}', 'show')->name('guides.show');
        Route::post('/guides/{guide}/approve', 'approve')->name('guides.approve');
        Route::post('/guides/{guide}/suspend', 'suspend')->name('guides.suspend');
        Route::post('/guides/{guide}/activate', 'activate')->name('guides.activate');
    });

    // Vehicle Management (New)
    Route::controller(AdminVehicleController::class)->group(function () {
        Route::get('/vehicles', 'index')->name('vehicles');
        Route::post('/vehicles', 'store')->name('vehicles.store');
        Route::put('/vehicles/{vehicle}', 'update')->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', 'destroy')->name('vehicles.destroy');
    });

    // Tour Management Routes
    Route::get('/tours', [AdminController::class, 'tours'])->name('tours');
    Route::get('/tours/create', [AdminController::class, 'createTour'])->name('tours.create');
    Route::post('/tours', [AdminController::class, 'storeTour'])->name('tours.store');
    Route::get('/tours/{tour}', [AdminController::class, 'showTour'])->name('tours.show');
    Route::get('/tours/{tour}/edit', [AdminController::class, 'editTour'])->name('tours.edit');
    Route::put('/tours/{tour}', [AdminController::class, 'updateTour'])->name('tours.update');
    Route::delete('/tours/{tour}', [AdminController::class, 'deleteTour'])->name('tours.delete');
    
    // Itinerary Management Routes
    Route::get('/tours/{tour}/itineraries', [AdminController::class, 'tourItineraries'])->name('tours.itineraries');
    Route::get('/tours/{tour}/itineraries/create', [AdminController::class, 'createTourItinerary'])->name('tours.itineraries.create');
    Route::post('/tours/{tour}/itineraries', [AdminController::class, 'storeTourItinerary'])->name('tours.itineraries.store');
    Route::get('/tours/{tour}/itineraries/{itinerary}', [AdminController::class, 'showTourItinerary'])->name('tours.itineraries.show');
    Route::get('/tours/{tour}/itineraries/{itinerary}/edit', [AdminController::class, 'editTourItinerary'])->name('tours.itineraries.edit');
    Route::put('/tours/{tour}/itineraries/{itinerary}', [AdminController::class, 'updateTourItinerary'])->name('tours.itineraries.update');
    Route::delete('/tours/{tour}/itineraries/{itinerary}', [AdminController::class, 'deleteTourItinerary'])->name('tours.itineraries.delete');

    // Tour Schedules Management Routes
    Route::get('/tours/{tour}/schedules', [AdminController::class, 'tourSchedules'])->name('tours.schedules');
    Route::get('/tours/{tour}/schedules/create', [AdminController::class, 'createTourSchedule'])->name('tours.schedules.create');
    Route::post('/tours/{tour}/schedules', [AdminController::class, 'storeTourSchedule'])->name('tours.schedules.store');
    Route::put('/tours/{tour}/schedules/{schedule}', [AdminController::class, 'updateTourSchedule'])->name('tours.schedules.update');
    Route::delete('/tours/{tour}/schedules/{schedule}', [AdminController::class, 'deleteTourSchedule'])->name('tours.schedules.delete');
    Route::post('/tours/{tour}/schedules/{schedule}/assign-guide', [AdminController::class, 'assignGuideToSchedule'])->name('tours.schedules.assign-guide');
    Route::post('/tours/{tour}/schedules/{schedule}/assign-driver', [AdminController::class, 'assignDriverToSchedule'])->name('tours.schedules.assign-driver');

    // Other Management Routes
    Route::get('/tour-bookings', [AdminController::class, 'tourBookings'])->name('tour-bookings');
    Route::get('/ride-bookings', [AdminController::class, 'rideBookings'])->name('ride-bookings');
    Route::get('/ride-bookings/{rideBooking}', [AdminController::class, 'showRideBooking'])->name('ride-bookings.show');
    Route::post('/ride-bookings/{rideBooking}/assign-driver', [AdminController::class, 'assignRideBookingDriver'])->name('ride-bookings.assign-driver');
     Route::post('/ride-bookings/{rideBooking}/update-status', [AdminController::class, 'updateRideBookingStatus'])->name('ride-bookings.update-status');
    
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
