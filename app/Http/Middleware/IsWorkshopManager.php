<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsWorkshopManager
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); 

        if (!$user || !$user->workshopManager) {
            return response()->json([
                'message' => 'Unauthorized. You are not a Workshop Manager.'
            ], 403);
        }

        return $next($request);
    }
}