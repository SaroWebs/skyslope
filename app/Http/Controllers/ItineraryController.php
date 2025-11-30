<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\Tour;
use App\Models\Place;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Tour $tour)
    {
        $itineraries = $tour->itineraries()
            ->with('place.media')
            ->orderBy('day_index')
            ->orderBy('time')
            ->get();

        return response()->json([
            'tour' => $tour,
            'itineraries' => $itineraries
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Tour $tour)
    {
        $places = Place::where('status', 'available')->orderBy('name')->get();
        
        return response()->json([
            'tour' => $tour,
            'places' => $places
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Tour $tour)
    {
        $validated = $request->validate([
            'day_index' => 'required|integer|min:1',
            'time' => 'nullable|date_format:H:i',
            'place_id' => 'required|exists:places,id',
            'details' => 'nullable|string',
        ]);

        // Check if day_index already exists for this tour
        $existingItinerary = Itinerary::where('tour_id', $tour->id)
            ->where('day_index', $validated['day_index'])
            ->where('time', $validated['time'])
            ->first();

        if ($existingItinerary) {
            return response()->json([
                'message' => 'An itinerary already exists for this day and time'
            ], 422);
        }

        $itinerary = $tour->itineraries()->create($validated);

        return response()->json($itinerary->load('place.media'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tour $tour, Itinerary $itinerary)
    {
        // Ensure the itinerary belongs to the tour
        if ($itinerary->tour_id !== $tour->id) {
            return response()->json(['message' => 'Itinerary not found for this tour'], 404);
        }

        return response()->json($itinerary->load('place.media'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tour $tour, Itinerary $itinerary)
    {
        // Ensure the itinerary belongs to the tour
        if ($itinerary->tour_id !== $tour->id) {
            return response()->json(['message' => 'Itinerary not found for this tour'], 404);
        }

        $places = Place::where('status', 'available')->orderBy('name')->get();
        
        return response()->json([
            'tour' => $tour,
            'itinerary' => $itinerary->load('place.media'),
            'places' => $places
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tour $tour, Itinerary $itinerary)
    {
        // Ensure the itinerary belongs to the tour
        if ($itinerary->tour_id !== $tour->id) {
            return response()->json(['message' => 'Itinerary not found for this tour'], 404);
        }

        $validated = $request->validate([
            'day_index' => 'sometimes|required|integer|min:1',
            'time' => 'nullable|date_format:H:i',
            'place_id' => 'sometimes|required|exists:places,id',
            'details' => 'nullable|string',
        ]);

        // Check for duplicate day_index and time (excluding current itinerary)
        if (isset($validated['day_index']) || isset($validated['time'])) {
            $dayIndex = $validated['day_index'] ?? $itinerary->day_index;
            $time = $validated['time'] ?? $itinerary->time;

            $existingItinerary = Itinerary::where('tour_id', $tour->id)
                ->where('day_index', $dayIndex)
                ->where('time', $time)
                ->where('id', '!=', $itinerary->id)
                ->first();

            if ($existingItinerary) {
                return response()->json([
                    'message' => 'An itinerary already exists for this day and time'
                ], 422);
            }
        }

        $itinerary->update($validated);

        return response()->json($itinerary->load('place.media'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tour $tour, Itinerary $itinerary)
    {
        // Ensure the itinerary belongs to the tour
        if ($itinerary->tour_id !== $tour->id) {
            return response()->json(['message' => 'Itinerary not found for this tour'], 404);
        }

        $itinerary->delete();

        return response()->json(['message' => 'Itinerary deleted successfully']);
    }

    /**
     * Get itineraries by day for a tour
     */
    public function getByDay(Tour $tour, $dayIndex)
    {
        $itineraries = $tour->itineraries()
            ->with('place.media')
            ->where('day_index', $dayIndex)
            ->orderBy('time')
            ->get();

        return response()->json($itineraries);
    }

    /**
     * Bulk update itineraries for a tour
     */
    public function bulkUpdate(Request $request, Tour $tour)
    {
        $request->validate([
            'itineraries' => 'required|array',
            'itineraries.*.id' => 'sometimes|exists:itineraries,id',
            'itineraries.*.day_index' => 'required|integer|min:1',
            'itineraries.*.time' => 'nullable|date_format:H:i',
            'itineraries.*.place_id' => 'required|exists:places,id',
            'itineraries.*.details' => 'nullable|string',
        ]);

        foreach ($request->itineraries as $itineraryData) {
            if (isset($itineraryData['id'])) {
                // Update existing itinerary
                $itinerary = Itinerary::where('tour_id', $tour->id)
                    ->findOrFail($itineraryData['id']);
                $itinerary->update($itineraryData);
            } else {
                // Create new itinerary
                $tour->itineraries()->create($itineraryData);
            }
        }

        return response()->json(['message' => 'Itineraries updated successfully']);
    }
}