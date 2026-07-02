<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDriverController extends Controller
{
    /**
     * List all drivers with pagination and search.
     */
    public function index(Request $request)
    {
        $query = Driver::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $drivers = $query->with('driverAvailability')
            ->withCount('assignedRideBookings')
            ->latest()
            ->paginate(15);

        return inertia('admin/Drivers', [
            'title' => 'Driver Management',
            'user' => Auth::user(),
            'drivers' => $drivers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show driver details.
     */
    public function show(Driver $driver)
    {
        $driver->load([
            'assignedRideBookings' => fn($q) => $q->latest()->take(15),
            'driverAvailability',
            'wallet',
            'vehicles',
            'tourDriverAssignments.schedule.tour' => fn($q) => $q->select('id', 'title'),
        ]);

        $stats = [
            'total_rides' => $driver->assignedRideBookings()->count(),
            'completed_rides' => $driver->assignedRideBookings()->where('status', 'completed')->count(),
            'total_earned' => $driver->assignedRideBookings()->where('status', 'completed')->sum('total_fare'),
            'wallet_balance' => $driver->wallet?->balance ?? 0,
            'is_online' => ($driver->driverAvailability?->status ?? 'offline') !== 'offline',
            'is_available' => $driver->driverAvailability?->is_available ?? false,
        ];

        return inertia('admin/Drivers/Show', [
            'title' => 'Driver Details',
            'user' => Auth::user(),
            'driver' => $driver,
            'stats' => $stats,
        ]);
    }

    /**
     * Approve a pending driver.
     */
    public function approve(Driver $driver)
    {
        $driver->update(['status' => 'active']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'message' => "Driver {$driver->name} has been approved.",
                'driver' => $driver->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Driver {$driver->name} has been approved.");
    }

    /**
     * Suspend a driver.
     */
    public function suspend(Driver $driver)
    {
        $driver->update(['status' => 'suspended']);

        $driver->tokens()->delete();

        DriverAvailability::where('driver_id', $driver->id)->update([
            'is_available' => false,
            'status' => 'offline',
            'last_updated' => now(),
        ]);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'message' => "Driver {$driver->name} has been suspended.",
                'driver' => $driver->fresh('driverAvailability'),
            ]);
        }

        return redirect()->back()->with('success', "Driver {$driver->name} has been suspended.");
    }

    /**
     * Reactivate a suspended driver.
     */
    public function activate(Driver $driver)
    {
        $driver->update(['status' => 'active']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'message' => "Driver {$driver->name} has been reactivated.",
                'driver' => $driver->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Driver {$driver->name} has been reactivated.");
    }

    /**
     * Assign a vehicle to this driver
     */
    public function assignVehicle(Request $request, Driver $driver)
    {
        $request->validate(['vehicle_id' => 'required|exists:vehicles,id']);

        \App\Models\Vehicle::where('id', $request->vehicle_id)->update(['driver_id' => $driver->id]);

        return redirect()->back()->with('success', 'Vehicle assigned to driver successfully.');
    }

    public function updateCapabilities(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'can_short_ride' => 'required|boolean',
            'can_long_ride' => 'required|boolean',
            'can_tour_lead' => 'required|boolean',
            'can_tour_transport' => 'required|boolean',
            'can_rental_delivery' => 'required|boolean',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:80',
            'expertise_tags' => 'nullable|array',
            'expertise_tags.*' => 'string|max:80',
            'certification_notes' => 'nullable|string|max:2000',
        ]);

        $driver->update($validated);

        return redirect()->back()->with('success', 'Driver capabilities updated.');
    }
}
