<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeliveryManagerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->deliveryManager) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized - DeliveryManager only'], 403);
    }
}
