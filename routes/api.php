<?php

use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDriverController;
use App\Http\Controllers\Api\CustomerAppController;
use App\Http\Controllers\Api\CustomerOtpController;
use App\Http\Controllers\Api\DriverAppController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\DriverOtpController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\VehicleTrackerController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::post('/razorpay/webhook', [WalletController::class, 'handleRazorpayWebhook']);
Route::post('/tracker/v1/location', [VehicleTrackerController::class, 'location'])
    ->middleware('throttle:240,1');

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
    Route::get('/public/tours/{tour}/schedules', [CustomerAppController::class, 'tourSchedules']);
    Route::get('/public/destinations', [CustomerAppController::class, 'publicDestinations']);
    Route::get('/public/destinations/{place}', [CustomerAppController::class, 'publicDestination']);
    Route::get('/public/places', [CustomerAppController::class, 'publicPlaces']);
    Route::get('/public/places/popular', [CustomerAppController::class, 'publicPopularPlaces']);
    Route::get('/public/places/{place}', [CustomerAppController::class, 'publicDestination']);
    Route::get('/public/car-categories', [CustomerAppController::class, 'publicCarCategories']);
    Route::get('/public/locations/search', [LocationController::class, 'search']);
    Route::get('/public/locations/popular', [LocationController::class, 'popular']);
    Route::post('/public/locations/validate', [LocationController::class, 'validateLocation']);
    Route::get('/public/locations/place-details', [LocationController::class, 'placeDetails']);
    Route::post('/public/directions', [LocationController::class, 'directions'])->middleware('throttle:60,1');
    Route::match(['get', 'post'], '/public/rides/estimate', [CustomerAppController::class, 'estimateRide']);

    // OTP Auth routes
    Route::post('/otp/send', [CustomerOtpController::class, 'sendOtp'])->middleware('throttle:otp-send');
    Route::post('/otp/verify', [CustomerOtpController::class, 'verifyOtp'])->middleware('throttle:otp-verify');
    Route::post('/otp/register-complete', [CustomerOtpController::class, 'completeRegistration'])->middleware('throttle:otp-verify');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [CustomerOtpController::class, 'logout']);
        Route::get('/me', [CustomerOtpController::class, 'me']);
        Route::get('/dashboard', [CustomerAppController::class, 'dashboard']);
        Route::post('/coupons/preview', [CustomerAppController::class, 'previewCoupon'])->middleware('throttle:customer-write');
        Route::get('/coupons/available', [CustomerAppController::class, 'availableCoupons']);
        Route::get('/tours', [CustomerAppController::class, 'tours']);
        Route::get('/tour-schedules/{tour}', [CustomerAppController::class, 'tourSchedules']);
        Route::get('/tour-bookings', [CustomerAppController::class, 'tourBookings']);
        Route::post('/tours/book', [CustomerAppController::class, 'bookTour'])->middleware('throttle:customer-write');
        Route::get('/car-categories', [CustomerAppController::class, 'carCategories']);
        Route::get('/car-rentals', [CustomerAppController::class, 'carRentals']);
        Route::post('/car-rentals', [CustomerAppController::class, 'bookCar'])->middleware('throttle:customer-write');
        Route::post('/car-rentals/{rental}/cancel', [CustomerAppController::class, 'cancelRental'])->middleware('throttle:customer-write');
        Route::post('/car-rentals/{rental}/review', [CustomerAppController::class, 'submitRentalReview'])->middleware('throttle:customer-write');
        Route::get('/rides', [CustomerAppController::class, 'rides']);
        Route::get('/rides/{booking}', [CustomerAppController::class, 'showRide']);
        Route::post('/rides/estimate', [CustomerAppController::class, 'estimateRide'])->middleware('throttle:customer-write');
        Route::post('/rides', [CustomerAppController::class, 'storeRide'])->middleware('throttle:customer-write');
        Route::post('/rides/{booking}/cancel', [CustomerAppController::class, 'cancelRide'])->middleware('throttle:customer-write');
        Route::post('/rides/{booking}/review', [CustomerAppController::class, 'submitReview'])->middleware('throttle:customer-write');
        Route::post('/rides/{booking}/tip', [CustomerAppController::class, 'submitTip'])->middleware('throttle:payment-write');
        Route::get('/places/{place}/reviews', [CustomerAppController::class, 'placeReviews']);
        Route::post('/places/{place}/reviews', [CustomerAppController::class, 'submitPlaceReview'])->middleware('throttle:customer-write');
        Route::post('/places/{place}/media', [CustomerAppController::class, 'uploadPlaceMedia'])->middleware('throttle:customer-write');
        Route::post('/tour-bookings/{booking}/cancel', [CustomerAppController::class, 'cancelTour'])->middleware('throttle:customer-write');
        Route::post('/tour-bookings/{booking}/review', [CustomerAppController::class, 'submitTourReview'])->middleware('throttle:customer-write');
        Route::post('/support/requests', [CustomerAppController::class, 'storeSupportRequest'])->middleware('throttle:support-write');
        Route::get('/rides/{booking}/next-steps', [CustomerAppController::class, 'rideNextSteps']);
        Route::post('/rides/{booking}/check-in', [CustomerAppController::class, 'rideCheckIn'])->middleware('throttle:customer-write');
        Route::get('/tour-bookings/{booking}/next-steps', [CustomerAppController::class, 'tourNextSteps']);
        Route::post('/tour-bookings/{booking}/check-in', [CustomerAppController::class, 'tourCheckIn'])->middleware('throttle:customer-write');
        Route::get('/car-rentals/{booking}/next-steps', [CustomerAppController::class, 'rentalNextSteps']);
        Route::post('/car-rentals/{booking}/check-in', [CustomerAppController::class, 'rentalCheckIn'])->middleware('throttle:customer-write');
        Route::get('/tracking/ride/{booking}', [TrackingController::class, 'getTrackingInfo']);
        Route::get('/tracking/tour/{booking}', [TrackingController::class, 'getTourTrackingInfo']);
        Route::get('/tracking/rental/{rental}', [TrackingController::class, 'getRentalTrackingInfo']);
        Route::get('/wallet', [WalletController::class, 'getWallet']);
        Route::get('/wallet/transactions', [WalletController::class, 'getTransactions']);
        Route::post('/wallet/topup/order', [WalletController::class, 'createTopUpOrder'])->middleware('throttle:payment-write');
        Route::post('/wallet/topup/verify', [WalletController::class, 'verifyTopUp'])->middleware('throttle:payment-write');
        Route::get('/insurance/policies', [InsuranceController::class, 'getPolicies']);
        Route::get('/insurance/policies/{id}', [InsuranceController::class, 'getPolicy']);
        Route::post('/insurance/policies', [InsuranceController::class, 'createPolicy'])->middleware('throttle:customer-write');
        Route::put('/insurance/policies/{id}', [InsuranceController::class, 'updatePolicy'])->middleware('throttle:customer-write');
        Route::delete('/insurance/policies/{id}', [InsuranceController::class, 'cancelPolicy'])->middleware('throttle:customer-write');
        Route::get('/insurance/claims', [InsuranceController::class, 'getClaims']);
        Route::post('/insurance/claims', [InsuranceController::class, 'createClaim'])->middleware('throttle:support-write');
        Route::get('/insurance/extended-care', [InsuranceController::class, 'getExtendedCare']);
        Route::post('/insurance/extended-care', [InsuranceController::class, 'requestAssistance'])->middleware('throttle:support-write');
        Route::delete('/insurance/extended-care/{id}', [InsuranceController::class, 'cancelAssistance'])->middleware('throttle:customer-write');
    });
});

/*
|--------------------------------------------------------------------------
| Driver App Routes
|--------------------------------------------------------------------------
*/
Route::prefix('driver-app')->group(function () {
    // OTP Auth routes
    Route::post('/otp/send', [DriverOtpController::class, 'sendOtp'])->middleware('throttle:otp-send');
    Route::post('/register', [DriverOtpController::class, 'register'])->middleware('throttle:otp-send');
    Route::post('/otp/verify', [DriverOtpController::class, 'verifyOtp'])->middleware('throttle:otp-verify');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [DriverOtpController::class, 'logout']);
        Route::get('/me', [DriverOtpController::class, 'me']);
        Route::get('/dashboard', [DriverAppController::class, 'dashboard']);
        Route::get('/history', [DriverAppController::class, 'history']);
        Route::get('/history/{kind}/{id}', [DriverAppController::class, 'historyDetail'])
            ->whereIn('kind', ['ride', 'tour', 'rental'])
            ->whereNumber('id');
        Route::get('/vehicle', [DriverAppController::class, 'vehicle']);
        Route::put('/vehicle', [DriverAppController::class, 'upsertVehicle'])->middleware('throttle:customer-write');
        Route::put('/availability', [DriverAppController::class, 'updateAvailability'])->middleware('throttle:customer-write');
        Route::get('/active-ride', [DriverController::class, 'activeRide']);
        Route::post('/directions', [LocationController::class, 'directions'])->middleware('throttle:60,1');
        Route::get('/active-rental', [DriverAppController::class, 'activeRental']);
        Route::get('/pending-rides', [DriverController::class, 'pendingRides']);
        Route::post('/rides/{booking}/accept', [DriverController::class, 'acceptRide'])->middleware('throttle:customer-write');
        Route::post('/rides/{booking}/decline', [DriverController::class, 'declineRide'])->middleware('throttle:customer-write');
        Route::post('/rides/{booking}/payment-status', [DriverController::class, 'updatePaymentStatus'])->middleware('throttle:payment-write');
        Route::post('/rides/{booking}/notes', [DriverController::class, 'updateRideNote'])->middleware('throttle:customer-write');

        // Tour Assignments
        Route::get('/tour-assignments', [DriverAppController::class, 'tourAssignments']);
        Route::post('/tour-assignments/{id}/accept', [DriverAppController::class, 'acceptTourAssignment'])->middleware('throttle:customer-write');
        Route::post('/tour-assignments/{id}/decline', [DriverAppController::class, 'declineTourAssignment'])->middleware('throttle:customer-write');
        Route::post('/tour-assignments/{id}/complete', [DriverAppController::class, 'completeTourAssignment'])->middleware('throttle:customer-write');
        Route::get('/rental-assignments', [DriverAppController::class, 'rentalAssignments']);
        Route::post('/rentals/{rental}/accept', [DriverAppController::class, 'acceptRental'])->middleware('throttle:customer-write');
        Route::post('/rentals/{rental}/decline', [DriverAppController::class, 'declineRental'])->middleware('throttle:customer-write');
        Route::post('/rentals/{rental}/complete', [DriverAppController::class, 'completeRental'])->middleware('throttle:customer-write');

        Route::post('/tracking/location', [TrackingController::class, 'updateDriverLocation'])->middleware('throttle:120,1');
        Route::post('/tracking/ride/{booking}/location', [TrackingController::class, 'updateRideLocation'])->middleware('throttle:120,1');
        Route::post('/tracking/ride/{booking}/status', [TrackingController::class, 'updateRideStatus'])->middleware('throttle:30,1');
        Route::get('/tracking/ride/{booking}', [TrackingController::class, 'getTrackingInfo']);
        Route::post('/tracking/tour/{booking}/location', [TrackingController::class, 'updateTourLocation'])->middleware('throttle:120,1');
        Route::post('/tracking/tour/{booking}/status', [TrackingController::class, 'updateTourStatus'])->middleware('throttle:30,1');
        Route::get('/tracking/tour/{booking}', [TrackingController::class, 'getTourTrackingInfo']);
        Route::post('/tracking/rental/{rental}/location', [TrackingController::class, 'updateRentalLocation'])->middleware('throttle:120,1');
        Route::post('/tracking/rental/{rental}/status', [TrackingController::class, 'updateRentalStatus'])->middleware('throttle:30,1');
        Route::get('/tracking/rental/{rental}', [TrackingController::class, 'getRentalTrackingInfo']);
        Route::get('/wallet', [WalletController::class, 'getWallet']);
        Route::get('/wallet/transactions', [WalletController::class, 'getTransactions']);
        Route::get('/wallet/stats', [WalletController::class, 'getStats']);
        Route::get('/withdrawals', [WithdrawalController::class, 'index']);
        Route::post('/withdrawals', [WithdrawalController::class, 'store'])->middleware('throttle:payment-write');
    });
});

/*
|--------------------------------------------------------------------------
| Guide App Routes
|--------------------------------------------------------------------------
*/
Route::prefix('guide-app')->group(function () {
    Route::any('/{any?}', function () {
        return response()->json([
            'success' => false,
            'message' => 'Guide app APIs are deprecated. Tour guide capabilities now live in the driver app.',
        ], 410);
    })->where('any', '.*');
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
