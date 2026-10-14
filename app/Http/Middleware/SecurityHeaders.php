<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds basic security headers to every page (Security requirement).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // the site cannot be shown inside an iframe on another site (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // the browser must not guess the file type
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // don't send the full URL to other websites
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // force HTTPS once the site is online
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
