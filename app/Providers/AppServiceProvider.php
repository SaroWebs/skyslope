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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
    }
}
