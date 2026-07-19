<?php

namespace App\Providers;

use App\Events\BookingLifecycleNotification;
use App\Listeners\QueueBookingLifecycleNotification;
use App\Models\CarRental;
use App\Models\PlaceReview;
use App\Models\RideBooking;
use App\Models\TourBooking;
use App\Policies\CarRentalPolicy;
use App\Policies\PlaceReviewPolicy;
use App\Policies\RideBookingPolicy;
use App\Policies\TourBookingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(125);

        Gate::policy(RideBooking::class, RideBookingPolicy::class);
        Gate::policy(TourBooking::class, TourBookingPolicy::class);
        Gate::policy(CarRental::class, CarRentalPolicy::class);
        Gate::policy(PlaceReview::class, PlaceReviewPolicy::class);

        Event::listen(
            BookingLifecycleNotification::class,
            QueueBookingLifecycleNotification::class,
        );

        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('otp-send', function (Request $request) {
            $phone = (string) $request->input('phone', 'unknown');

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(3)->by($phone.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            $phone = (string) $request->input('phone', 'unknown');

            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by($phone.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('customer-write', fn (Request $request) => Limit::perMinute(30)->by(
            optional($request->user())->getAuthIdentifier() ?: $request->ip()
        ));

        RateLimiter::for('payment-write', fn (Request $request) => Limit::perMinute(10)->by(
            optional($request->user())->getAuthIdentifier() ?: $request->ip()
        ));

        RateLimiter::for('support-write', fn (Request $request) => Limit::perMinute(8)->by(
            optional($request->user())->getAuthIdentifier() ?: $request->ip()
        ));

        RateLimiter::for('admin-financial', fn (Request $request) => Limit::perMinute(20)->by(
            optional($request->user())->getAuthIdentifier() ?: $request->ip()
        ));
    }
}
