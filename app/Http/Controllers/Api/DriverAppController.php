<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverAvailability;
use App\Models\RideBooking;
use Illuminate\Http\Request;

class DriverAppController extends Controller
{
    public function dashboard(Request $request)
    {
        $driver = $request->user();
        $availability = DriverAvailability::firstOrCreate(
            ['driver_id' => $driver->id],
            ['is_online' => false, 'is_available' => true]
        );

        return response()->json([
            'success' => true,
            'driver' => $driver,
            'availability' => $availability,
            'stats' => [
                'active_rides' => RideBooking::where('driver_id', $driver->id)
                    ->whereIn('status', ['driver_assigned', 'driver_arriving', 'pickup', 'in_transit'])
                    ->count(),
                'completed_rides' => RideBooking::where('driver_id', $driver->id)
                    ->where('status', 'completed')
                    ->count(),
                'pending_pool' => RideBooking::whereNull('driver_id')
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
            ],
            'recent_rides' => RideBooking::with('customer:id,name,phone')
                ->where('driver_id', $driver->id)
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function updateAvailability(Request $request)
    {
        $validated = $request->validate([
            'is_online' => 'required|boolean',
            'is_available' => 'required|boolean',
            'vehicle_type' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:100',
        ]);

        $availability = DriverAvailability::updateOrCreate(
            ['driver_id' => $request->user()->id],
            array_merge($validated, ['last_ping' => now()])
        );

        return response()->json([
            'success' => true,
            'data' => $availability,
        ]);
    }

    public function history(Request $request)
    {
        $rides = RideBooking::with(['customer:id,name,phone'])
            ->where('driver_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $rides,
        ]);
    }
}
