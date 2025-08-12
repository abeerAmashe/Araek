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

        // إذا عندك علاقة superManager في الـ User Model
        if ($user && $user->superManager) {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized - Not a Super Manager'], 403);
    }
}