<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarCategory;
use Illuminate\Http\Request;

class CarCategoryController extends Controller
{
    /**
     * Display a listing of car categories.
     */
    public function index(Request $request)
    {
        $query = CarCategory::active();

        // Filter by vehicle type if provided
        if ($request->has('type') && !empty($request->type)) {
            $query->where('vehicle_type', $request->type);
        }

        $carCategories = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json($carCategories);
    }

    /**
     * Display the specified car category.
     */
    public function show(CarCategory $carCategory)
    {
        return response()->json($carCategory);
    }
}