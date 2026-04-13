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
            'is_online' => $driver->driverAvailability?->is_online ?? false,
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

        return redirect()->back()->with('success', "Driver {$driver->name} has been approved.");
    }

    /**
     * Suspend a driver.
     */
    public function suspend(Driver $driver)
    {
        $driver->update(['status' => 'suspended']);

        // Revoke all tokens
        $driver->tokens()->delete();

        // Set offline
        DriverAvailability::where('driver_id', $driver->id)->update([
            'is_online' => false,
            'is_available' => false,
        ]);

        return redirect()->back()->with('success', "Driver {$driver->name} has been suspended.");
    }

    /**
     * Reactivate a suspended driver.
     */
    public function activate(Driver $driver)
    {
        $driver->update(['status' => 'active']);

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
}
