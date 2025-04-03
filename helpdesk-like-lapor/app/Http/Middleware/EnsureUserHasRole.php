<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException; // Import correct exception
use Illuminate\Http\Exceptions\HttpResponseException; // For custom response

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles Allowed roles.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::user(); // Or $request->user()

        // If user is not authenticated by Sanctum/guard, this will likely be null
        if (! $user) {
            // Throw standard authentication exception or custom response
             throw new AuthenticationException();
             // Or: abort(401, 'Unauthenticated.');
        }

        // Check if user has one of the allowed roles
        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        // If no allowed role matched, throw forbidden response
        // abort(403, 'This action is unauthorized.');
        throw new HttpResponseException(response()->json([
            'message' => 'This action is unauthorized. Required role(s): ' . implode(', ', $roles)
        ], 403));
    }
}