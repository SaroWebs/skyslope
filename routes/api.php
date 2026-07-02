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
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::post('/razorpay/webhook', [WalletController::class, 'handleRazorpayWebhook']);

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
    Route::match(['get', 'post'], '/public/rides/estimate', [CustomerAppController::class, 'estimateRide']);

    // OTP Auth routes
    Route::post('/otp/send', [CustomerOtpController::class, 'sendOtp']);
    Route::post('/otp/verify', [CustomerOtpController::class, 'verifyOtp']);
    Route::post('/otp/register-complete', [CustomerOtpController::class, 'completeRegistration']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [CustomerOtpController::class, 'logout']);
        Route::get('/me', [CustomerOtpController::class, 'me']);
        Route::get('/dashboard', [CustomerAppController::class, 'dashboard']);
        Route::post('/coupons/preview', [CustomerAppController::class, 'previewCoupon']);
        Route::get('/coupons/available', [CustomerAppController::class, 'availableCoupons']);
        Route::get('/tours', [CustomerAppController::class, 'tours']);
        Route::get('/tour-schedules/{tour}', [CustomerAppController::class, 'tourSchedules']);
        Route::get('/tour-bookings', [CustomerAppController::class, 'tourBookings']);
        Route::post('/tours/book', [CustomerAppController::class, 'bookTour']);
        Route::get('/car-categories', [CustomerAppController::class, 'carCategories']);
        Route::get('/car-rentals', [CustomerAppController::class, 'carRentals']);
        Route::post('/car-rentals', [CustomerAppController::class, 'bookCar']);
        Route::post('/car-rentals/{rental}/cancel', [CustomerAppController::class, 'cancelRental']);
        Route::post('/car-rentals/{rental}/review', [CustomerAppController::class, 'submitRentalReview']);
        Route::get('/rides', [CustomerAppController::class, 'rides']);
        Route::get('/rides/{booking}', [CustomerAppController::class, 'showRide']);
        Route::post('/rides/estimate', [CustomerAppController::class, 'estimateRide']);
        Route::post('/rides', [CustomerAppController::class, 'storeRide']);
        Route::post('/rides/{booking}/cancel', [CustomerAppController::class, 'cancelRide']);
        Route::post('/rides/{booking}/review', [CustomerAppController::class, 'submitReview']);
        Route::post('/rides/{booking}/tip', [CustomerAppController::class, 'submitTip']);
        Route::get('/places/{place}/reviews', [CustomerAppController::class, 'placeReviews']);
        Route::post('/places/{place}/reviews', [CustomerAppController::class, 'submitPlaceReview']);
        Route::post('/tour-bookings/{booking}/cancel', [CustomerAppController::class, 'cancelTour']);
        Route::post('/tour-bookings/{booking}/review', [CustomerAppController::class, 'submitTourReview']);
        Route::post('/support/requests', [CustomerAppController::class, 'storeSupportRequest']);
        Route::get('/bookings/{serviceType}/{booking}/next-steps', [CustomerAppController::class, 'bookingNextSteps']);
        Route::post('/bookings/{serviceType}/{booking}/check-in', [CustomerAppController::class, 'bookingCheckIn']);
        Route::get('/tracking/ride/{booking}', [TrackingController::class, 'getTrackingInfo']);
        Route::get('/tracking/tour/{booking}', [TrackingController::class, 'getTourTrackingInfo']);
        Route::get('/tracking/rental/{rental}', [TrackingController::class, 'getRentalTrackingInfo']);
        Route::get('/wallet', [WalletController::class, 'getWallet']);
        Route::get('/wallet/transactions', [WalletController::class, 'getTransactions']);
        Route::post('/wallet/topup/order', [WalletController::class, 'createTopUpOrder']);
        Route::post('/wallet/topup/verify', [WalletController::class, 'verifyTopUp']);
        Route::get('/insurance/policies', [InsuranceController::class, 'getPolicies']);
        Route::get('/insurance/policies/{id}', [InsuranceController::class, 'getPolicy']);
        Route::post('/insurance/policies', [InsuranceController::class, 'createPolicy']);
        Route::put('/insurance/policies/{id}', [InsuranceController::class, 'updatePolicy']);
        Route::delete('/insurance/policies/{id}', [InsuranceController::class, 'cancelPolicy']);
        Route::get('/insurance/claims', [InsuranceController::class, 'getClaims']);
        Route::post('/insurance/claims', [InsuranceController::class, 'createClaim']);
        Route::get('/insurance/extended-care', [InsuranceController::class, 'getExtendedCare']);
        Route::post('/insurance/extended-care', [InsuranceController::class, 'requestAssistance']);
        Route::delete('/insurance/extended-care/{id}', [InsuranceController::class, 'cancelAssistance']);
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
        Route::get('/active-rental', [DriverAppController::class, 'activeRental']);
        Route::get('/pending-rides', [DriverController::class, 'pendingRides']);
        Route::post('/rides/{booking}/accept', [DriverController::class, 'acceptRide']);
        Route::post('/rides/{booking}/decline', [DriverController::class, 'declineRide']);
        Route::post('/rides/{booking}/payment-status', [DriverController::class, 'updatePaymentStatus']);
        Route::post('/rides/{booking}/notes', [DriverController::class, 'updateRideNote']);

        // Tour Assignments
        Route::get('/tour-assignments', [DriverAppController::class, 'tourAssignments']);
        Route::post('/tour-assignments/{id}/accept', [DriverAppController::class, 'acceptTourAssignment']);
        Route::post('/tour-assignments/{id}/decline', [DriverAppController::class, 'declineTourAssignment']);
        Route::post('/tour-assignments/{id}/complete', [DriverAppController::class, 'completeTourAssignment']);
        Route::get('/rental-assignments', [DriverAppController::class, 'rentalAssignments']);
        Route::post('/rentals/{rental}/accept', [DriverAppController::class, 'acceptRental']);
        Route::post('/rentals/{rental}/decline', [DriverAppController::class, 'declineRental']);
        Route::post('/rentals/{rental}/complete', [DriverAppController::class, 'completeRental']);

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
        Route::post('/withdrawals', [WithdrawalController::class, 'store']);
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
