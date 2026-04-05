<?php

namespace App\Http\Controllers;

use App\Models\DriverAvailability;
use App\Models\RideBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DriverController extends Controller
{
    public function dashboard(Request $request)
    {
        return Inertia::render('driver/Dashboard', $this->dashboardProps($request));
    }

    public function wallet()
    {
        return Inertia::render('driver/Wallet', [
            'title' => 'Driver Wallet',
            'user' => Auth::user(),
        ]);
    }

    public function history(Request $request)
    {
        $rides = RideBooking::query()
            ->where('driver_id', $request->user()->id)
            ->with([
                'user:id,name,phone,email',
                'reviews:id,ride_booking_id,customer_id,rating,comment,created_at',
                'tips:id,ride_booking_id,customer_id,amount,message,created_at',
            ])
            ->latest()
            ->paginate(20)
            ->through(function (RideBooking $ride) {
                return [
                    'id' => $ride->id,
                    'booking_number' => $ride->booking_number,
                    'status' => $ride->status,
                    'pickup_location' => $ride->pickup_location,
                    'dropoff_location' => $ride->dropoff_location,
                    'pickup_lat' => $ride->pickup_lat ? (float) $ride->pickup_lat : null,
                    'pickup_lng' => $ride->pickup_lng ? (float) $ride->pickup_lng : null,
                    'dropoff_lat' => $ride->dropoff_lat ? (float) $ride->dropoff_lat : null,
                    'dropoff_lng' => $ride->dropoff_lng ? (float) $ride->dropoff_lng : null,
                    'scheduled_at' => $ride->scheduled_at,
                    'completed_at' => $ride->completed_at,
                    'total_fare' => (float) $ride->total_fare,
                    'payment_status' => $ride->payment_status,
                    'payment_method' => $ride->payment_method,
                    'driver_notes' => $ride->driver_notes,
                    'customer_name' => $ride->customer_name ?: ($ride->user?->name ?? 'Customer'),
                    'customer_phone' => $ride->customer_phone ?: ($ride->user?->phone ?? ''),
                    'customer_email' => $ride->customer_email ?: ($ride->user?->email ?? ''),
                    'reviews' => $ride->reviews->map(fn ($review) => [
                        'id' => $review->id,
                        'rating' => (int) $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at,
                    ])->values(),
                    'tips' => $ride->tips->map(fn ($tip) => [
                        'id' => $tip->id,
                        'amount' => (float) $tip->amount,
                        'message' => $tip->message,
                        'created_at' => $tip->created_at,
                    ])->values(),
                    'tips_total' => (float) $ride->tips->sum('amount'),
                ];
            });

        return Inertia::render('driver/History', [
            'title' => 'Ride History',
            'user' => $request->user(),
            'rides' => $rides,
        ]);
    }

    public function settings(Request $request)
    {
        $availability = DriverAvailability::where('driver_id', $request->user()->id)->first();

        return Inertia::render('driver/Settings', [
            'title' => 'Driver Settings',
            'user' => $request->user(),
            'availability' => $availability,
        ]);
    }

    public function profile(Request $request)
    {
        $availability = DriverAvailability::where('driver_id', $request->user()->id)->first();

        return Inertia::render('driver/Profile', [
            'title' => 'Driver Profile',
            'user' => $request->user(),
            'availability' => $availability,
        ]);
    }

    public function tours(Request $request)
    {
        $myTours = $request->user()->tourDrivers()->with('tour')->latest()->get();
        $upcomingTours = \App\Models\Tour::whereHas('drivers', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->where('available_from', '>=', now())->orderBy('available_from')->get();

        return Inertia::render('driver/Tours', [
            'title' => 'My Tours',
            'user' => $request->user(),
            'my_tours' => $myTours,
            'upcoming_tours' => $upcomingTours,
        ]);
    }

    private function dashboardProps(Request $request): array
    {
        $user = $request->user();
        $availability = DriverAvailability::where('driver_id', $user->id)->first();

        $todayCompleted = RideBooking::where('driver_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->get();

        return [
            'title' => 'Driver Dashboard',
            'user' => $user,
            'my_tours' => $user->tourDrivers()->with('tour')->get(),
            'upcoming_tours' => \App\Models\Tour::whereHas('drivers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->where('available_from', '>=', now())->get(),
            'wallet' => [
                'balance' => (float) ($user->wallet?->balance ?? 0),
                'currency' => $user->wallet?->currency ?? 'INR',
            ],
            'stats' => [
                'today_earnings' => (float) $todayCompleted->sum(fn (RideBooking $ride) => $ride->driver_share ?? $ride->total_fare),
                'today_rides' => $todayCompleted->count(),
                'total_rides' => RideBooking::where('driver_id', $user->id)->where('status', 'completed')->count(),
                'rating' => (float) ($availability?->rating ?? 5),
            ],
        ];
    }
}
