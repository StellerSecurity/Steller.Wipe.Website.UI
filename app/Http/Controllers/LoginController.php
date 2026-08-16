<?php

namespace App\Http\Controllers;

use App\Services\WipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly WipeService $wipeService)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        if ($this->hasValidSession($request)) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'method' => ['required', 'in:0,1'],
            'username' => ['nullable', 'string', 'max:191', 'required_if:method,0'],
            'password' => ['nullable', 'string', 'max:512', 'required_if:method,0'],
            'token' => ['nullable', 'string', 'max:512', 'required_if:method,1'],
        ]);

        if ($validated['method'] === '0') {
            $response = $this->wipeService->auth(
                trim((string) $validated['username']),
                (string) $validated['password']
            );
        } else {
            $response = $this->wipeService->findByToken(trim((string) $validated['token']));
        }

        $login = $response->successful() ? $response->object() : null;
        $authToken = is_object($login) && isset($login->auth_token) ? (string) $login->auth_token : '';

        if ($authToken === '') {
            return back()->with('error_message', 'The login details you provided are invalid. Try again.');
        }

        $request->session()->regenerate(true);
        $request->session()->regenerateToken();
        $request->session()->put([
            'auth_token' => $authToken,
            'wipe_authenticated_at' => time(),
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function hasValidSession(Request $request): bool
    {
        $authToken = $request->session()->get('auth_token');
        $authenticatedAt = (int) $request->session()->get('wipe_authenticated_at', 0);
        $maxAge = max(60, (int) config('wipe_security.session_max_age_seconds', 900));

        $sessionAge = time() - $authenticatedAt;

        return is_string($authToken)
            && $authToken !== ''
            && $authenticatedAt > 0
            && $sessionAge >= 0
            && $sessionAge <= $maxAge;
    }
}
