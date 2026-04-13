<?php

use App\Http\Controllers\Api\CustomerAppController;
use App\Http\Controllers\Api\CustomerOtpController;
use App\Http\Controllers\Api\DriverAppController;
use App\Http\Controllers\Api\DriverOtpController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\Api\GuideAppController;
use App\Http\Controllers\Api\GuideOtpController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDriverController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer App Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer-app')->group(function () {
    // Public routes
    Route::get('/public/bootstrap', [CustomerAppController::class, 'publicBootstrap']);
    Route::get('/public/tours', [CustomerAppController::class, 'publicTours']);
    Route::get('/public/tours/{tour}', [CustomerAppController::class, 'publicTour']);
    Route::get('/public/destinations', [CustomerAppController::class, 'publicDestinations']);
    Route::get('/public/destinations/{place}', [CustomerAppController::class, 'publicDestination']);
    Route::get('/public/car-categories', [CustomerAppController::class, 'publicCarCategories']);
    Route::get('/public/locations/search', [LocationController::class, 'search']);
    Route::get('/public/locations/popular', [LocationController::class, 'popular']);
    Route::post('/public/locations/validate', [LocationController::class, 'validateLocation']);
    Route::get('/public/locations/place-details', [LocationController::class, 'placeDetails']);
    Route::match(['get', 'post'], '/public/rides/estimate', [CustomerAppController::class, 'estimateRide']);

    // OTP Auth routes
    Route::post('/otp/send', [CustomerOtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [CustomerOtpController::class, 'verifyOtp']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [CustomerOtpController::class, 'logout']);
        Route::get('/me', [CustomerOtpController::class, 'me']);
        Route::get('/dashboard', [CustomerAppController::class, 'dashboard']);
        Route::get('/tours', [CustomerAppController::class, 'tours']);
        Route::get('/tour-schedules/{tour}', [CustomerAppController::class, 'tourSchedules']);
        Route::get('/tour-bookings', [CustomerAppController::class, 'tourBookings']);
        Route::post('/tours/book', [CustomerAppController::class, 'bookTour']);
        Route::get('/car-categories', [CustomerAppController::class, 'carCategories']);
        Route::get('/car-rentals', [CustomerAppController::class, 'carRentals']);
        Route::post('/car-rentals', [CustomerAppController::class, 'bookCar']);
        Route::get('/rides', [CustomerAppController::class, 'rides']);
        Route::get('/rides/{booking}', [CustomerAppController::class, 'showRide']);
        Route::post('/rides/estimate', [CustomerAppController::class, 'estimateRide']);
        Route::post('/rides', [CustomerAppController::class, 'storeRide']);
        Route::post('/rides/{booking}/review', [CustomerAppController::class, 'submitReview']);
        Route::post('/rides/{booking}/tip', [CustomerAppController::class, 'submitTip']);
        Route::get('/wallet', [WalletController::class, 'getWallet']);
        Route::get('/wallet/transactions', [WalletController::class, 'getTransactions']);
        Route::post('/wallet/topup/order', [WalletController::class, 'createTopUpOrder']);
        Route::post('/wallet/topup/verify', [WalletController::class, 'verifyTopUp']);
        Route::get('/insurance/policies', [InsuranceController::class, 'getPolicies']);
    });
});

/*
|--------------------------------------------------------------------------
| Driver App Routes
|--------------------------------------------------------------------------
*/
Route::prefix('driver-app')->group(function () {
    // OTP Auth routes
    Route::post('/otp/send', [DriverOtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [DriverOtpController::class, 'verifyOtp']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [DriverOtpController::class, 'logout']);
        Route::get('/me', [DriverOtpController::class, 'me']);
        Route::get('/dashboard', [DriverAppController::class, 'dashboard']);
        Route::get('/history', [DriverAppController::class, 'history']);
        Route::put('/availability', [DriverAppController::class, 'updateAvailability']);
        Route::get('/active-ride', [DriverController::class, 'activeRide']);
        Route::get('/pending-rides', [DriverController::class, 'pendingRides']);
        Route::post('/rides/{booking}/accept', [DriverController::class, 'acceptRide']);
        Route::post('/rides/{booking}/payment-status', [DriverController::class, 'updatePaymentStatus']);
        Route::post('/rides/{booking}/notes', [DriverController::class, 'updateRideNote']);
        
        // Tour Assignments
        Route::get('/tour-assignments', [DriverAppController::class, 'tourAssignments']);
        Route::post('/tour-assignments/{id}/accept', [DriverAppController::class, 'acceptTourAssignment']);

        Route::post('/tracking/location', [TrackingController::class, 'updateDriverLocation']);
        Route::post('/tracking/ride/{booking}/location', [TrackingController::class, 'updateRideLocation']);
        Route::post('/tracking/ride/{booking}/status', [TrackingController::class, 'updateRideStatus']);
        Route::get('/tracking/ride/{booking}', [TrackingController::class, 'getTrackingInfo']);
        Route::get('/wallet', [WalletController::class, 'getWallet']);
        Route::get('/wallet/transactions', [WalletController::class, 'getTransactions']);
        Route::get('/wallet/stats', [WalletController::class, 'getStats']);
        Route::get('/withdrawals', [WithdrawalController::class, 'index']);
        Route::post('/withdrawals', [WithdrawalController::class, 'store']);
    });
});

/*
|--------------------------------------------------------------------------
| Guide App Routes
|--------------------------------------------------------------------------
*/
Route::prefix('guide-app')->group(function () {
    // OTP Auth routes
    Route::post('/otp/send', [GuideOtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [GuideOtpController::class, 'verifyOtp']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [GuideOtpController::class, 'logout']);
        Route::get('/me', [GuideOtpController::class, 'me']);
        Route::get('/dashboard', [GuideAppController::class, 'dashboard']);
        Route::get('/assignments', [GuideAppController::class, 'assignments']);
        Route::post('/assignments/{id}/accept', [GuideAppController::class, 'acceptAssignment']);
        Route::post('/assignments/{id}/decline', [GuideAppController::class, 'declineAssignment']);
        Route::post('/assignments/{id}/complete', [GuideAppController::class, 'completeAssignment']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin API Routes (State-modifying actions)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/api')->group(function () {
    Route::post('/customers/{customer}/toggle-status', [AdminCustomerController::class, 'toggleStatus']);
    Route::post('/drivers/{driver}/approve', [AdminDriverController::class, 'approve']);
    Route::post('/drivers/{driver}/suspend', [AdminDriverController::class, 'suspend']);
    Route::post('/drivers/{driver}/activate', [AdminDriverController::class, 'activate']);
});
