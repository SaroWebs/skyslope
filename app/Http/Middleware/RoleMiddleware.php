<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     * @param  string|null  $permission
     */
    public function handle(Request $request, Closure $next, string $role, ?string $permission = null): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        // Check if user has the required role
        if (!$user->hasRole($role)) {
            return response()->json(['error' => 'Forbidden: Insufficient role permissions'], 403);
        }

        // If a specific permission is required, check for it
        if ($permission && !$user->hasPermission($permission)) {
            return response()->json(['error' => 'Forbidden: Insufficient permissions'], 403);
        }

        return $next($request);
    }
}
