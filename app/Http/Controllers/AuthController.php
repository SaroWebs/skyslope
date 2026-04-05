<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return inertia('auth/Login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active (if you have an active field)
        if (isset($user->active) && !$user->active) {
            throw ValidationException::withMessages([
                'email' => ['Your account is deactivated. Please contact administrator.'],
            ]);
        }

        // Log the user in
        Auth::login($user);

        // Redirect to general dashboard, which handles role-based rendering
        return redirect()->route('dashboard');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Show dashboard based on user role
     */
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } elseif ($user->isGuide()) {
            return $this->guideDashboard();
        }

        abort(403);
    }

    /**
     * Admin dashboard
     */
    private function adminDashboard()
    {
        return inertia('admin/Dashboard', [
            'title' => 'Admin Dashboard',
            'user' => Auth::user(),
            'stats' => [
                'total_users' => User::count(),
                'total_customers' => Customer::count(),
                'total_drivers' => Driver::count(),
                'total_tours' => \App\Models\Tour::count(),
                'total_bookings' => \App\Models\Booking::count(),
                'total_ride_bookings' => \App\Models\RideBooking::count(),
                'total_places' => \App\Models\Place::count(),
            ],
            'recent_users' => User::with('roles')->latest()->take(5)->get(),
            'upcoming_tours' => \App\Models\Tour::with(['guides', 'drivers'])->take(5)->get(),
        ]);
    }

    /**
     * Guide dashboard
     */
    private function guideDashboard()
    {
        return inertia('guide/Dashboard', [
            'title' => 'Guide Dashboard',
            'user' => Auth::user(),
            'my_tours' => Auth::user()->tourGuides()->with('tour')->get(),
            'upcoming_tours' => \App\Models\Tour::whereHas('guides', function($q) {
                $q->where('user_id', Auth::id());
            })->where('available_from', '>=', now())->get(),
        ]);
    }

}
