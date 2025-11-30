<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class LocationController extends Controller
{
    /**
     * Search locations using Google Places API or local destinations.
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:100',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lng' => 'sometimes|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = $request->input('query');
        $results = [];

        // First, search in local destinations
        $localDestinations = Destination::active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('state', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($destination) {
                return [
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'address' => $destination->name . ', ' . $destination->state,
                    'type' => 'destination',
                    'lat' => $destination->lat ?? null,
                    'lng' => $destination->lng ?? null,
                ];
            });

        $results = $localDestinations->toArray();

        // If Google Maps API key is available, search Google Places
        $googleApiKey = config('services.google_maps.api_key') ?: env('GOOGLE_MAPS_API_KEY');

        if ($googleApiKey && count($results) < 5) {
            try {
                $googleResponse = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                    'query' => $query,
                    'key' => $googleApiKey,
                    'region' => 'in', // India region
                ]);

                if ($googleResponse->successful()) {
                    $googleData = $googleResponse->json();

                    if (isset($googleData['results'])) {
                        $googleResults = collect($googleData['results'])
                            ->take(5 - count($results))
                            ->map(function ($place) {
                                return [
                                    'id' => $place['place_id'],
                                    'name' => $place['name'],
                                    'address' => $place['formatted_address'] ?? '',
                                    'type' => 'google_place',
                                    'lat' => $place['geometry']['location']['lat'] ?? null,
                                    'lng' => $place['geometry']['location']['lng'] ?? null,
                                ];
                            });

                        $results = array_merge($results, $googleResults->toArray());
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue with local results
                \Log::warning('Google Places API error: ' . $e->getMessage());
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Get popular destinations.
     */
    public function popular(Request $request)
    {
        $destinations = Destination::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($destination) {
                return [
                    'id' => $destination->id,
                    'name' => $destination->name,
                    'address' => $destination->name . ', ' . $destination->state,
                    'type' => 'destination',
                    'lat' => $destination->lat ?? null,
                    'lng' => $destination->lng ?? null,
                    'image' => $destination->image ?? null,
                ];
            });

        return response()->json(['destinations' => $destinations]);
    }

    /**
     * Validate if a location is within service area.
     */
    public function validateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Define service area boundaries (example for Northeast India)
        $serviceArea = [
            'north' => 28.0,  // Northern boundary
            'south' => 20.0,  // Southern boundary
            'east' => 97.0,   // Eastern boundary
            'west' => 88.0,   // Western boundary
        ];

        $isWithinServiceArea = $request->lat >= $serviceArea['south'] &&
                              $request->lat <= $serviceArea['north'] &&
                              $request->lng >= $serviceArea['west'] &&
                              $request->lng <= $serviceArea['east'];

        // Check if location is near any active destination (within 50km)
        $nearDestination = Destination::active()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->filter(function ($destination) use ($request) {
                $distance = $this->calculateDistance(
                    $request->lat,
                    $request->lng,
                    $destination->lat,
                    $destination->lng
                );
                return $distance <= 50; // Within 50km of any destination
            })
            ->isNotEmpty();

        $isValid = $isWithinServiceArea || $nearDestination;

        return response()->json([
            'valid' => $isValid,
            'within_service_area' => $isWithinServiceArea,
            'near_destination' => $nearDestination,
            'message' => $isValid ? 'Location is within service area' : 'Location is outside service area',
        ]);
    }

    /**
     * Get place details from Google Places API.
     */
    public function placeDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $googleApiKey = config('services.google_maps.api_key') ?: env('GOOGLE_MAPS_API_KEY');

        if (!$googleApiKey) {
            return response()->json(['error' => 'Google Maps API not configured'], 500);
        }

        try {
            $response = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $request->place_id,
                'fields' => 'place_id,name,formatted_address,geometry',
                'key' => $googleApiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['result'])) {
                    $place = $data['result'];
                    return response()->json([
                        'id' => $place['place_id'],
                        'name' => $place['name'],
                        'address' => $place['formatted_address'],
                        'lat' => $place['geometry']['location']['lat'],
                        'lng' => $place['geometry']['location']['lng'],
                    ]);
                }
            }

            return response()->json(['error' => 'Place not found'], 404);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch place details'], 500);
        }
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
