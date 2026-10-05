<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;


class CanAccessMenu
{
    public function handle(Request $request, Closure $next, string $route)
    {
        // Get the authenticated admin using JWTAuth
        $admin = Auth::guard('api')->user();


        // If no admin is authenticated, return a 401 Unauthorized response
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak terautentikasi',
                'status' => 401,
            ], 401);
        }

        // Check if the admin has access to the requested menu
        if (!$admin->hasAccessToMenu($route)) {
            return response()->json([
                'success' => false,
                'message' => "Tidak memiliki akses ke menu: $route",
                'status' => 403,
            ], 403);
        }


        // Proceed to the next middleware if access is granted
        return $next($request);
    }
}
