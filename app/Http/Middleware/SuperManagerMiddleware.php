<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperManagerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->galleryManager) {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized - Not a Super Manager'], 403);
    }
}