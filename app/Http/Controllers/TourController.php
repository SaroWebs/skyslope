<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tours = Tour::with(['itineraries'])
            ->where('available_to', '>=', now())
            ->orderBy('available_from')
            ->get();

        return response()->json($tours);
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after:available_from',
            'image' => 'nullable|image|max:2048'
        ]);

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('tours', 'public');
            $validated['image_path'] = $imagePath;
        }

        // Remove 'image' from validated data before creating the Tour
        unset($validated['image']);

        $tour = Tour::create($validated);

        return response()->json($tour, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tour = Tour::with(['itineraries.place.media', 'guides', 'drivers'])
            ->findOrFail($id);

        return response()->json($tour);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tour = Tour::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after:available_from',
            'image' => 'nullable|image|max:2048'
        ]);

        // Handle image upload if present
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if (!empty($tour->image_path) && Storage::disk('public')->exists($tour->image_path)) {
                Storage::disk('public')->delete($tour->image_path);
            }
            $imagePath = $request->file('image')->store('tours', 'public');
            $validated['image_path'] = $imagePath;
        }

        // Remove 'image' from validated data before updating the Tour
        unset($validated['image']);

        $tour->update($validated);

        return response()->json($tour);
    }

    /**
     * Book a tour.
     */
    public function bookTour(Request $request)
    {
        $validated = $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'start_date' => 'required|date',
            'number_of_people' => 'required|integer|min:1|max:20',
        ]);

        // Here you would typically create a booking record
        // For now, we'll just return success
        return response()->json([
            'message' => 'Tour booked successfully',
            'booking' => $validated
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
