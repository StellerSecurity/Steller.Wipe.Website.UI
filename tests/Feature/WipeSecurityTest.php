<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WipeSecurityTest extends TestCase
{
    public function test_default_token_lookup_uses_header_and_never_the_url(): void
    {
        config()->set('services.wipe_api.token_lookup_method', 'get');

        Http::fake([
            '*findbytoken*' => Http::response([
                'id' => 'device-1',
                'status' => 1,
            ], 200),
        ]);

        $response = app(\App\Services\WipeService::class)->findByToken('device-secret');

        $this->assertSame(200, $response->status());
        $this->assertSame('{"id":"device-1","status":1}', $response->body());

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->method() === 'GET'
                && $request->hasHeader('X-Wipe-Token', 'device-secret')
                && ! str_contains($request->url(), 'device-secret')
                && ! str_contains($request->url(), 'auth_token');
        });
    }

    public function test_dashboard_requires_an_authenticated_wipe_session(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_expired_wipe_session_is_rejected(): void
    {
        $this->withSession([
            'auth_token' => 'server-auth-token',
            'wipe_authenticated_at' => time() - 901,
        ])->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_login_success_regenerates_into_a_wipe_session(): void
    {
        Http::fake([
            '*loginauth' => Http::response(['auth_token' => 'server-auth-token'], 200),
        ]);

        $response = $this->post('/login', [
            'method' => '0',
            'username' => 'test-user',
            'password' => 'test-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('auth_token', 'server-auth-token');
        $response->assertSessionHas('wipe_authenticated_at');
    }

    public function test_login_route_uses_the_named_wipe_login_limiter(): void
    {
        $route = Route::getRoutes()->getByName('login.attempt');

        $this->assertNotNull($route);
        $this->assertContains('throttle:wipe-login', $route->gatherMiddleware());
    }

    public function test_legacy_get_query_cannot_trigger_a_wipe(): void
    {
        Http::fake([
            '*findbytoken*' => Http::response([
                'id' => 'device-1',
                'status' => 1,
                'auth_token' => 'server-auth-token',
            ], 200),
            '*patch' => Http::response([], 200),
        ]);

        $this->withSession([
            'auth_token' => 'server-auth-token',
            'wipe_authenticated_at' => time(),
        ])->get('/dashboard?do_wipe=1&csrf_token=anything')->assertOk();

        Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');
    }

    public function test_wipe_requires_a_one_time_server_session_token_and_confirmation_phrase(): void
    {
        Http::fake([
            '*findbytoken*' => Http::response([
                'id' => 'device-1',
                'status' => 1,
                'auth_token' => 'server-auth-token',
            ], 200),
            '*patch' => Http::response([], 200),
        ]);

        $session = [
            'auth_token' => 'server-auth-token',
            'wipe_authenticated_at' => time(),
        ];

        $confirmationPage = $this->withSession($session)->get('/dashboard/wipe');
        $confirmationPage->assertOk();

        preg_match('/name="action_token" value="([A-Za-z0-9]{64})"/', $confirmationPage->getContent(), $matches);
        $this->assertArrayHasKey(1, $matches);

        $actionToken = $matches[1];

        $this->withSession([
            ...$session,
            'wipe_action_token_hash' => hash('sha256', $actionToken),
            'wipe_action_issued_at' => time(),
        ])->post('/dashboard/wipe', [
            'action_token' => $actionToken,
            'confirmation' => 'WIPE',
        ])->assertRedirect(route('dashboard'));

        Http::assertSent(fn ($request) => $request->method() === 'PATCH');
    }
    public function test_expired_wipe_confirmation_token_is_rejected(): void
    {
        $actionToken = str_repeat('A', 64);

        $this->withSession([
            'auth_token' => 'server-auth-token',
            'wipe_authenticated_at' => time(),
            'wipe_action_token_hash' => hash('sha256', $actionToken),
            'wipe_action_issued_at' => time() - 301,
        ])->post('/dashboard/wipe', [
            'action_token' => $actionToken,
            'confirmation' => 'WIPE',
        ])->assertStatus(419);
    }

}
