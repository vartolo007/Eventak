<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class IsVenueOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role === 'venue_owner') {
            return $next($request);
        }

        return response()->json(['message' => __('messages.access.forbidden_venue_owner')], 403);
    }
}
