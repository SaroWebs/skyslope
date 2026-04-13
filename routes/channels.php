<?php

use App\Models\RideBooking;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Individual Ride Channel
Broadcast::channel('ride.{bookingId}', function ($user, int $bookingId) {
    $booking = RideBooking::find($bookingId);

    if (!$booking) {
        return false;
    }

    // Allow if it's the customer, the driver, or an admin
    if ($user instanceof Customer) {
        return $user->id === $booking->customer_id;
    }

    if ($user instanceof Driver) {
        return $user->id === $booking->driver_id;
    }

    if ($user instanceof User) {
        return $user->id === 1; // Simple admin check or use role
    }

    return false;
});

// Private Customer Channel
Broadcast::channel('customer.{customerId}', function ($user, int $customerId) {
    return ($user instanceof Customer) && ($user->id === $customerId);
});

// Private Driver Channel
Broadcast::channel('driver.{driverId}', function ($user, int $driverId) {
    return ($user instanceof Driver) && ($user->id === $driverId);
});

// Available Drivers Channel (for new ride requests)
Broadcast::channel('drivers.available', function ($user) {
    return ($user instanceof Driver) && ($user->status === 'active');
});

// Legacy channels (for backward compatibility if needed)
Broadcast::channel('user.{userId}.rides', function ($user, int $userId) {
    return ($user instanceof User) && ($user->id === $userId);
});
