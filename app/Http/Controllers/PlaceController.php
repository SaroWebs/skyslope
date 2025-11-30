<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $places = Place::with(['placeMedia', 'itineraries'])
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        return response()->json($places);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'status' => 'required|in:available,unavailable,restricted',
        ]);

        $place = Place::create($validated);

        return response()->json($place->load('media'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $place = Place::with('media', 'itineraries.tour')
            ->findOrFail($id);

        return response()->json($place);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $place = Place::findOrFail($id);

        return response()->json($place);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $place = Place::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'lng' => 'nullable|numeric',
            'lat' => 'nullable|numeric',
            'status' => 'sometimes|required|in:available,unavailable,restricted',
        ]);

        $place->update($validated);

        return response()->json($place->load('media'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $place = Place::findOrFail($id);
        
        // Check if place is used in any itineraries
        if ($place->itineraries()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete place that is used in itineraries'
            ], 422);
        }

        $place->delete();

        return response()->json(['message' => 'Place deleted successfully']);
    }
}
