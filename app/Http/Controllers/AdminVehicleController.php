<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\CarCategory;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminVehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with(['category', 'driver']);

        if ($search = $request->input('search')) {
            $query->where('registration_number', 'like', "%{$search}%")
                  ->orWhere('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
        }

        $vehicles = $query->latest()->paginate(15);
        $categories = CarCategory::all();
        $drivers = Driver::where('status', 'active')->get();

        return inertia('admin/Vehicles/Index', [
            'title' => 'Vehicle Management',
            'user' => Auth::user(),
            'vehicles' => $vehicles,
            'categories' => $categories,
            'drivers' => $drivers,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_category_id' => 'required|exists:car_categories,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_number' => 'required|string|unique:vehicles',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'color' => 'required|string',
            'fuel_type' => 'required|string',
            'seats' => 'required|integer',
            'is_ac' => 'boolean',
            'insurance_expiry' => 'nullable|date',
        ]);

        Vehicle::create($validated);

        return redirect()->back()->with('success', 'Vehicle added successfully.');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'car_category_id' => 'required|exists:car_categories,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'registration_number' => 'required|string|unique:vehicles,registration_number,' . $vehicle->id,
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'required|integer',
            'color' => 'required|string',
            'fuel_type' => 'required|string',
            'seats' => 'required|integer',
            'is_ac' => 'boolean',
            'insurance_expiry' => 'nullable|date',
            'is_active' => 'boolean',
            'condition' => 'string'
        ]);

        $vehicle->update($validated);

        return redirect()->back()->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle removed successfully.');
    }
}
