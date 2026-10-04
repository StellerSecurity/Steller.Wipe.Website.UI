<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure() || strtolower((string) $request->header('X-Forwarded-Proto', '')) === 'https') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = trim((string) config('wipe_security.headers.content_security_policy', ''));
        // The page holding the signing key must never load third-party scripts,
        // even when a broader site policy is configured. Only the signed relay
        // is added to the existing same-origin network allowance.
        if ($request->is('/') || $request->is('login')) {
            $csp = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net; img-src 'self' data:; connect-src 'self' https://stellerprotectuiappapiprod.azurewebsites.net/api/v1/signed-wipe/commands; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'";
        }
        if ($csp !== '') {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
