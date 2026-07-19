<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCouponController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDriverController;
use App\Http\Controllers\AdminFinancialController;
use App\Http\Controllers\AdminRoleController;
use App\Http\Controllers\AdminVehicleController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| System admin routes and local utilities.
*/

if (app()->isLocal()) {
    Route::get('/link', function () {
        try {
            $output = Artisan::call('storage:link');

            return 'Command output: '.$output;
        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    });
}

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }

    // Keep the unauthenticated entry point deliberately static. Operational data
    // belongs behind the authenticated, role-protected admin routes below.
    return Inertia::render('welcome');
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

    // Profile routes
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::put('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AdminController::class, 'changePassword'])->name('profile.password');

    Route::controller(AdminCouponController::class)->group(function () {
        Route::get('/coupons', 'index')->name('coupons');
        Route::post('/coupons', 'store')->name('coupons.store');
        Route::put('/coupons/{customerCoupon}', 'update')->name('coupons.update');
        Route::patch('/coupons/{customerCoupon}/toggle', 'toggle')->name('coupons.toggle');
    });

    // Admin Account Management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');

    // Role & Permission Management
    Route::controller(AdminRoleController::class)->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{role}', 'show')->name('show');
        Route::get('/{role}/edit', 'edit')->name('edit');
        Route::put('/{role}', 'update')->name('update');
        Route::delete('/{role}', 'destroy')->name('destroy');
    });

    // Financial Management
    Route::controller(AdminFinancialController::class)->prefix('financials')->name('financials.')->group(function () {
        Route::get('/wallets', 'wallets')->name('wallets');
        Route::post('/wallets/{wallet}/adjust', 'adjustWallet')->name('wallets.adjust');
        Route::get('/withdrawals', 'withdrawals')->name('withdrawals');
        Route::post('/withdrawals/{withdrawal}/approve', 'approveWithdrawal')->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', 'rejectWithdrawal')->name('withdrawals.reject');
        Route::post('/withdrawals/{withdrawal}/complete', 'completeWithdrawal')->name('withdrawals.complete');
    });

    // Customer Management (New)
    Route::controller(AdminCustomerController::class)->group(function () {
        Route::get('/customers', 'index')->name('customers');
        Route::get('/customers/{customer}', 'show')->name('customers.show');
        Route::get('/customers/{customer}/edit', 'edit')->name('customers.edit');
        Route::put('/customers/{customer}', 'update')->name('customers.update');
        Route::post('/customers/{customer}/toggle-status', 'toggleStatus')->name('customers.toggle-status');
    });

    // Driver Management (New)
    Route::controller(AdminDriverController::class)->group(function () {
        Route::get('/drivers', 'index')->name('drivers');
        Route::post('/drivers', 'store')->name('drivers.store');
        Route::get('/drivers/{driver}', 'show')->name('drivers.show');
        Route::put('/drivers/{driver}', 'update')->name('drivers.update');
        Route::post('/drivers/{driver}/approve', 'approve')->name('drivers.approve');
        Route::post('/drivers/{driver}/suspend', 'suspend')->name('drivers.suspend');
        Route::post('/drivers/{driver}/activate', 'activate')->name('drivers.activate');
        Route::put('/drivers/{driver}/capabilities', 'updateCapabilities')->name('drivers.capabilities');
        Route::put('/drivers/{driver}/sharing', 'updateSharing')->name('drivers.sharing');
    });

    Route::match(['get', 'post'], '/guides/{any?}', function () {
        return redirect()->route('admin.drivers')
            ->with('info', 'Guide management has moved to driver capabilities.');
    })->where('any', '.*')->name('guides');

    // Vehicle Management (New)
    Route::controller(AdminVehicleController::class)->group(function () {
        Route::get('/vehicles', 'index')->name('vehicles');
        Route::post('/vehicles', 'store')->name('vehicles.store');
        Route::put('/vehicles/{vehicle}', 'update')->name('vehicles.update');
        Route::get('/vehicles/{vehicle}/tracking', 'tracking')->name('vehicles.tracking');
        Route::get('/vehicles/{vehicle}/tracking-data', 'trackingData')->name('vehicles.tracking-data');
        Route::post('/vehicles/{vehicle}/tracker/provision', 'provisionTracker')->name('vehicles.tracker.provision');
        Route::post('/vehicles/{vehicle}/tracker/suspend', 'suspendTracker')->name('vehicles.tracker.suspend');
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
    Route::post('/tours/{tour}/schedules/{schedule}/assign-driver', [AdminController::class, 'assignDriverToSchedule'])->name('tours.schedules.assign-driver');

    // Other Management Routes
    Route::get('/wallet-reconciliation', [AdminController::class, 'walletReconciliation'])->name('wallet-reconciliation');
    Route::get('/bookings', [AdminController::class, 'bookings'])->name('bookings');
    Route::get('/tour-bookings', [AdminController::class, 'tourBookings'])->name('tour-bookings');
    Route::get('/tour-bookings/{tourBooking}', [AdminController::class, 'showTourBooking'])->name('tour-bookings.show');
    Route::post('/tour-bookings/{tourBooking}/update-status', [AdminController::class, 'updateTourBookingStatus'])->name('tour-bookings.update-status');
    Route::post('/tour-bookings/{tourBooking}/confirm-payment', [AdminController::class, 'confirmTourBookingPayment'])->name('tour-bookings.confirm-payment');
    Route::post('/tour-bookings/{tourBooking}/incidents', [AdminController::class, 'storeTourBookingIncident'])->name('tour-bookings.incidents.store');
    Route::get('/ride-bookings', [AdminController::class, 'rideBookings'])->name('ride-bookings');
    Route::get('/ride-bookings/{rideBooking}', [AdminController::class, 'showRideBooking'])->name('ride-bookings.show');
    Route::post('/ride-bookings/{rideBooking}/assign-driver', [AdminController::class, 'assignRideBookingDriver'])->name('ride-bookings.assign-driver');
    Route::post('/ride-bookings/{rideBooking}/update-status', [AdminController::class, 'updateRideBookingStatus'])->name('ride-bookings.update-status');
    Route::post('/ride-bookings/{rideBooking}/confirm-payment', [AdminController::class, 'confirmRideBookingPayment'])->name('ride-bookings.confirm-payment');
    Route::post('/ride-bookings/{rideBooking}/incidents', [AdminController::class, 'storeRideBookingIncident'])->name('ride-bookings.incidents.store');
    Route::post('/booking-refunds/{bookingRefund}/process', [AdminController::class, 'processBookingRefund'])->name('booking-refunds.process');
    Route::patch('/booking-incidents/{bookingIncident}', [AdminController::class, 'updateBookingIncident'])->name('booking-incidents.update');

    // Car Rentals Management Routes
    Route::get('/car-rentals', [AdminController::class, 'carRentals'])->name('car-rentals');
    Route::get('/car-rentals/create', [AdminController::class, 'createCarRental'])->name('car-rentals.create');
    Route::post('/car-rentals', [AdminController::class, 'storeCarRental'])->name('car-rentals.store');
    Route::get('/car-rentals/{carRental}', [AdminController::class, 'showCarRental'])->name('car-rentals.show');
    Route::get('/car-rentals/{carRental}/edit', [AdminController::class, 'editCarRental'])->name('car-rentals.edit');
    Route::put('/car-rentals/{carRental}', [AdminController::class, 'updateCarRental'])->name('car-rentals.update');
    Route::post('/car-rentals/{carRental}/assign-driver', [AdminController::class, 'assignCarRentalDriver'])->name('car-rentals.assign-driver');
    Route::post('/car-rentals/{carRental}/update-status', [AdminController::class, 'updateCarRentalStatus'])->name('car-rentals.update-status');
    Route::post('/car-rentals/{carRental}/confirm-payment', [AdminController::class, 'confirmCarRentalPayment'])->name('car-rentals.confirm-payment');
    Route::post('/car-rentals/{carRental}/incidents', [AdminController::class, 'storeCarRentalIncident'])->name('car-rentals.incidents.store');
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
    Route::post('/places/{place}/sync-google', [AdminController::class, 'syncPlaceGoogleData'])->name('places.sync-google');
    Route::delete('/places/{place}', [AdminController::class, 'deletePlace'])->name('places.delete');
    Route::post('/places/{place}/media', [AdminController::class, 'storeMedia'])->name('places.media.store');
    Route::patch('/media/{media}/approve', [AdminController::class, 'approveMedia'])->name('media.approve');
    Route::patch('/media/{media}/reject', [AdminController::class, 'rejectMedia'])->name('media.reject');
    Route::delete('/media/{media}', [AdminController::class, 'deleteMedia'])->name('media.delete');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});
