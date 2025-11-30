<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    /**
     * Display a listing of destinations.
     */
    public function index(Request $request)
    {
        $query = Destination::active();

        // Filter by state if provided
        if ($request->has('state') && !empty($request->state)) {
            $query->where('state', $request->state);
        }

        // Filter by type if provided
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        $destinations = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json($destinations);
    }

    /**
     * Display the specified destination.
     */
    public function show(Destination $destination)
    {
        return response()->json($destination);
    }
}