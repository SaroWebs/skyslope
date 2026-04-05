<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\CarRentalController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\RideBookingController;
use App\Http\Controllers\Api\DriverController as ApiDriverController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\CarCategoryController as ApiCarCategoryController;
use App\Http\Controllers\Api\DestinationController as ApiDestinationController;

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

Route::match(['get', 'post'], '/api/ride-bookings/estimate', [RideBookingController::class, 'estimate'])->name('api.ride-bookings.estimate');
Route::get('/api/ride-bookings/nearby-drivers', [RideBookingController::class, 'nearbyDrivers'])->name('api.ride-bookings.nearby-drivers');

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    // Car Rental Routes
    Route::post('/api/book-car', [CarRentalController::class, 'bookCar'])->name('api.book-car');
    Route::resource('car-rentals', CarRentalController::class);

    // Ride Booking Routes (Ola/Uber-like)
    Route::post('/api/ride-bookings', [RideBookingController::class, 'store'])->name('api.ride-bookings.store');
    Route::post('/api/ride-bookings/{booking}/review', [RideBookingController::class, 'submitReview'])->name('api.ride-bookings.review');
    Route::post('/api/ride-bookings/{booking}/tip', [RideBookingController::class, 'submitTip'])->name('api.ride-bookings.tip');
    Route::resource('ride-bookings', RideBookingController::class);

    // Tour Routes
    Route::post('/api/book-tour', [TourController::class, 'bookTour'])->name('api.book-tour');

    // Wallet Routes
    Route::prefix('api/wallet')->name('api.wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'getWallet'])->name('index');
        Route::get('/transactions', [WalletController::class, 'getTransactions'])->name('transactions');
        Route::post('/topup', [WalletController::class, 'topUp'])->name('topup');
        Route::post('/topup/order', [WalletController::class, 'createTopUpOrder'])->name('topup.order');
        Route::post('/topup/verify', [WalletController::class, 'verifyTopUp'])->name('topup.verify');
        Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
        Route::get('/stats', [WalletController::class, 'getStats'])->name('stats');
        Route::get('/razorpay-config', [WalletController::class, 'getRazorpayConfig'])->name('razorpay-config');
    });

    // Tracking Routes (Real-time location and status)
    Route::prefix('api/tracking')->name('api.tracking.')->group(function () {
        Route::post('/driver-location', [TrackingController::class, 'updateDriverLocation'])->name('driver-location');
        Route::post('/ride/{booking}/location', [TrackingController::class, 'updateRideLocation'])->name('ride-location');
        Route::post('/ride/{booking}/status', [TrackingController::class, 'updateRideStatus'])->name('ride-status');
        Route::get('/ride/{booking}', [TrackingController::class, 'getTrackingInfo'])->name('ride-info');
    });

    // Withdrawal Routes (User)
    Route::prefix('api/withdrawals')->name('api.withdrawals.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\WithdrawalController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Api\WithdrawalController::class, 'store'])->name('store');
        Route::get('/{withdrawal}', [App\Http\Controllers\Api\WithdrawalController::class, 'show'])->name('show');
        Route::post('/{withdrawal}/cancel', [App\Http\Controllers\Api\WithdrawalController::class, 'cancel'])->name('cancel');
    });
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
    Route::get('/ride-bookings/{rideBooking}', [AdminController::class, 'showRideBooking'])->name('ride-bookings.show');
    Route::post('/ride-bookings/{rideBooking}/assign-driver', [AdminController::class, 'assignRideBookingDriver'])->name('ride-bookings.assign-driver');
    Route::post('/ride-bookings/{rideBooking}/update-status', [AdminController::class, 'updateRideBookingStatus'])->name('ride-bookings.update-status');
    Route::post('/ride-bookings/{rideBooking}/undo-last-change', [AdminController::class, 'undoLastRideBookingChange'])->name('ride-bookings.undo-last-change');
    
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

    // Commission Analytics API Routes
    Route::get('/api/commission-stats', [App\Http\Controllers\Api\CommissionController::class, 'getStats'])->name('api.commission-stats');
    Route::get('/api/commission-transactions', [App\Http\Controllers\Api\CommissionController::class, 'getTransactions'])->name('api.commission-transactions');
    Route::get('/api/driver/{driverId}/commission-stats', [App\Http\Controllers\Api\CommissionController::class, 'getDriverStats'])->name('api.driver-commission-stats');

    // Withdrawal Management API Routes
    Route::get('/api/withdrawals', [App\Http\Controllers\Api\WithdrawalController::class, 'adminIndex'])->name('api.withdrawals');
    Route::get('/api/withdrawals/stats', [App\Http\Controllers\Api\WithdrawalController::class, 'stats'])->name('api.withdrawals.stats');
    Route::post('/api/withdrawals/{withdrawal}/approve', [App\Http\Controllers\Api\WithdrawalController::class, 'approve'])->name('api.withdrawals.approve');
    Route::post('/api/withdrawals/{withdrawal}/reject', [App\Http\Controllers\Api\WithdrawalController::class, 'reject'])->name('api.withdrawals.reject');
    Route::post('/api/withdrawals/{withdrawal}/process', [App\Http\Controllers\Api\WithdrawalController::class, 'processPayout'])->name('api.withdrawals.process');
    Route::post('/api/withdrawals/{withdrawal}/complete', [App\Http\Controllers\Api\WithdrawalController::class, 'markCompleted'])->name('api.withdrawals.complete');
});
