<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAzureFrontDoor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('wipe_security.azure_front_door.required', false)) {
            return $next($request);
        }

        $expected = trim((string) config('wipe_security.azure_front_door.id', ''));
        $actual = trim((string) $request->header('X-Azure-FDID', ''));

        if ($expected === '' || $actual === '' || ! hash_equals($expected, $actual)) {
            abort(403);
        }

        return $next($request);
    }
}
