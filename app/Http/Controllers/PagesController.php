<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Tour;
use App\Models\Place;

class PagesController extends Controller
{
    public function index()
    {
        return Inertia::render('home');
    }

    public function about()
    {
        return Inertia::render('about');
    }

    public function contact()
    {
        return Inertia::render('contact');
    }

    public function tours()
    {
        $tours = Tour::with(['itineraries'])
            ->where('available_to', '>=', now())
            ->orderBy('available_from')
            ->get();

        return Inertia::render('tours', [
            'tours' => $tours
        ]);
    }

    public function destinations()
    {
        $places = Place::with(['media', 'itineraries'])
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        return Inertia::render('destinations', [
            'places' => $places
        ]);
    }

    public function bookNow()
    {
        return Inertia::render('book-now');
    }

    public function carRental()
    {
        return Inertia::render('car-rental');
    }

    public function rideBooking()
    {
        return Inertia::render('ride-booking/Index');
    }

    public function tourView($id)
    {
        $tour = Tour::with(['itineraries.place.media', 'guides', 'drivers'])
            ->findOrFail($id);

        return Inertia::render('TourView', [
            'tour' => $tour
        ]);
    }

    public function destinationView($id)
    {
        $place = Place::with(['media', 'itineraries.tour'])
            ->findOrFail($id);

        return Inertia::render('DestinationView', [
            'place' => $place
        ]);
    }
}
