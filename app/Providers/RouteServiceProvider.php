<?php

namespace App\Providers;

use App\Support\WipeSecurity;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: WipeSecurity::clientKey($request));
        });

        RateLimiter::for('wipe-login', function (Request $request) {
            $clientKey = WipeSecurity::clientKey($request);
            $credentialKey = WipeSecurity::credentialKey($request);

            return [
                Limit::perMinutes(15, 15)->by('wipe-login:client:15m:'.$clientKey),
                Limit::perDay(100)->by('wipe-login:client:day:'.$clientKey),
                Limit::perMinutes(15, 5)->by('wipe-login:credential:15m:'.$credentialKey),
                Limit::perDay(20)->by('wipe-login:credential:day:'.$credentialKey),
            ];
        });

        RateLimiter::for('wipe-action', function (Request $request) {
            return [
                Limit::perMinute(2)->by('wipe-action:session:'.$request->session()->getId()),
                Limit::perHour(5)->by('wipe-action:client:'.WipeSecurity::clientKey($request)),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
