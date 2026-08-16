<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWipeSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $authToken = $request->session()->get('auth_token');
        $authenticatedAt = (int) $request->session()->get('wipe_authenticated_at', 0);
        $maxAge = max(60, (int) config('wipe_security.session_max_age_seconds', 900));

        if (! is_string($authToken) || $authToken === '' || $authenticatedAt <= 0 || (time() - $authenticatedAt) > $maxAge) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
