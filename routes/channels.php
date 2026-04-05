<?php

use App\Models\RideBooking;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ride.{bookingId}', function ($user, int $bookingId) {
    $booking = RideBooking::find($bookingId);

    if (!$booking) {
        return false;
    }

    return $user->id === $booking->user_id
        || $user->id === $booking->driver_id
        || $user->isAdmin();
});

Broadcast::channel('user.{userId}.rides', function ($user, int $userId) {
    return $user->id === $userId || $user->isAdmin();
});

Broadcast::channel('driver.{driverId}.location', function ($user, int $driverId) {
    return $user->id === $driverId || $user->isAdmin();
});

Broadcast::channel('driver.{driverId}.rides', function ($user, int $driverId) {
    return $user->id === $driverId || $user->isAdmin();
});
