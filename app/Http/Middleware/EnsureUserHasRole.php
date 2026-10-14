<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-based access control middleware (Ch.4.4.2 - Security requirement).
 * Usage in routes: ->middleware('role:admin') or ->middleware('role:partner,admin')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
