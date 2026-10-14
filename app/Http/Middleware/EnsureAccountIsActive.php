<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspension and bans used to be checked only at login, so someone already
 * signed in (or holding a "remember me" cookie) kept full access after an
 * admin suspended or banned them. This ends such a session on the very
 * next request.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->is_suspended || $user->isBanned())) {
            $loginRoute = $user->isAdmin() ? 'admin.login' : 'login';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($loginRoute)->withErrors([
                $user->isAdmin() ? 'email' : 'login' => 'Your account is no longer active. Contact FlexiRent support.',
            ]);
        }

        return $next($request);
    }
}
