<?php

use App\Http\Controllers\Api\CustomerAppController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\DriverAppController;
use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer-app')->middleware('web')->group(function () {
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

    Route::post('/login', [CustomerAuthController::class, 'login']);

    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/me', [CustomerAuthController::class, 'me']);
        Route::get('/dashboard', [CustomerAppController::class, 'dashboard']);
        Route::get('/tours', [CustomerAppController::class, 'tours']);
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

Route::prefix('driver-app')->middleware('web')->group(function () {
    Route::post('/login', [DriverAuthController::class, 'login']);

    Route::middleware('auth:driver')->group(function () {
        Route::post('/logout', [DriverAuthController::class, 'logout']);
        Route::get('/me', [DriverAuthController::class, 'me']);
        Route::get('/dashboard', [DriverAppController::class, 'dashboard']);
        Route::put('/availability', [DriverAppController::class, 'updateAvailability']);
        Route::get('/active-ride', [DriverController::class, 'activeRide']);
        Route::get('/pending-rides', [DriverController::class, 'pendingRides']);
        Route::post('/rides/{booking}/accept', [DriverController::class, 'acceptRide']);
        Route::post('/rides/{booking}/payment-status', [DriverController::class, 'updatePaymentStatus']);
        Route::post('/rides/{booking}/notes', [DriverController::class, 'updateRideNote']);
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
