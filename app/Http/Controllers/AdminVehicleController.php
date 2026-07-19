<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\CarCategory;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminVehicleController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehicle::with(['category', 'driver', 'tracker']);

        if ($search = $request->input('search')) {
            $query->where('registration_number', 'like', "%{$search}%")
                  ->orWhere('make', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
        }

        $vehicles = $query->latest()->paginate(15);
        $categories = CarCategory::all();
        $drivers = Driver::where('status', 'active')
            ->with('vehicle:id,driver_id,registration_number')
            ->get();

        return inertia('admin/Vehicles/Index', [
            'title' => 'Vehicle Management',
            'user' => Auth::user(),
            'vehicles' => $vehicles,
            'categories' => $categories,
            'drivers' => $drivers,
            'filters' => $request->only('search'),
            'tracker_credentials' => session('tracker_credentials'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_category_id' => 'required|exists:car_categories,id',
            'driver_id' => ['nullable', 'exists:drivers,id', Rule::unique('vehicles', 'driver_id')],
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
            'driver_id' => ['nullable', 'exists:drivers,id', Rule::unique('vehicles', 'driver_id')->ignore($vehicle->id)],
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
            'condition' => 'string',
            'approval_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $validated['is_active'] = $validated['approval_status'] === 'approved';
        $validated['reviewed_at'] = now();
        $validated['reviewed_by'] = Auth::id();
        if ($validated['approval_status'] !== 'rejected') {
            $validated['rejection_reason'] = null;
        }

        $vehicle->update($validated);

        return redirect()->back()->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->back()->with('success', 'Vehicle removed successfully.');
    }

    public function tracking(Vehicle $vehicle)
    {
        $vehicle->load(['category', 'driver', 'tracker']);
        $locations = $vehicle->locations()
            ->latest('recorded_at')
            ->limit(100)
            ->get()
            ->sortBy('recorded_at')
            ->values();

        return inertia('admin/Vehicles/Tracking', [
            'title' => 'Vehicle GPS Tracking',
            'vehicle' => $vehicle,
            'tracker' => $vehicle->tracker,
            'locations' => $locations,
            'google_maps_api_key' => config('services.google_maps.api_key'),
        ]);
    }

    public function trackingData(Vehicle $vehicle)
    {
        $tracker = $vehicle->tracker;

        return response()->json([
            'tracker' => $tracker,
            'is_online' => (bool) $tracker?->isOnline(),
            'locations' => $vehicle->locations()
                ->latest('recorded_at')
                ->limit(100)
                ->get()
                ->sortBy('recorded_at')
                ->values(),
        ]);
    }

    public function provisionTracker(Request $request, Vehicle $vehicle)
    {
        $tracker = $vehicle->tracker()->firstOrCreate([], [
            'device_uid' => 'SKY-'.str_pad((string) $vehicle->id, 6, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(6)),
        ]);

        $validated = $request->validate([
            'device_uid' => [
                'nullable', 'string', 'max:100',
                Rule::unique('vehicle_trackers', 'device_uid')->ignore($tracker->id),
            ],
        ]);

        $plainToken = 'skytrk_'.Str::random(48);
        $tracker->update([
            'device_uid' => strtoupper($validated['device_uid'] ?? $tracker->device_uid),
            'token_hash' => hash('sha256', $plainToken),
            'status' => 'active',
            'installed_at' => $tracker->installed_at ?? now(),
        ]);

        return redirect()->route('admin.vehicles')->with('tracker_credentials', [
            'vehicle_id' => $vehicle->id,
            'registration_number' => $vehicle->registration_number,
            'device_uid' => $tracker->device_uid,
            'api_token' => $plainToken,
            'endpoint' => url('/api/tracker/v1/location'),
        ])->with('success', 'GPS tracker provisioned. Copy the token now; it will not be shown again.');
    }

    public function suspendTracker(Vehicle $vehicle)
    {
        $vehicle->tracker?->update(['status' => 'suspended']);

        return redirect()->back()->with('success', 'GPS tracker suspended.');
    }
}
