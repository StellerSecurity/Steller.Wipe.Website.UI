<?php

namespace App\Http\Controllers;

use App\Services\WipeService;
use App\WipedBy;
use App\WipeStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly WipeService $wipeService)
    {
    }

    public function show(Request $request): View|RedirectResponse
    {
        $device = $this->resolveDevice($request);

        if ($device === null) {
            return $this->invalidateAndRedirect($request);
        }

        $isWiped = in_array(
            (int) $device->status,
            [WipeStatus::WIPING->value, WipeStatus::WIPED->value],
            true
        );

        return view('dashboard', [
            'is_wiped' => $isWiped,
        ]);
    }

    public function confirmWipe(Request $request): View|RedirectResponse
    {
        $device = $this->resolveDevice($request);

        if ($device === null) {
            return $this->invalidateAndRedirect($request);
        }

        if (in_array((int) $device->status, [WipeStatus::WIPING->value, WipeStatus::WIPED->value], true)) {
            return redirect()->route('dashboard');
        }

        $actionToken = Str::random(64);
        $request->session()->put([
            'wipe_action_token_hash' => hash('sha256', $actionToken),
            'wipe_action_issued_at' => time(),
        ]);

        return view('confirm-wipe', [
            'action_token' => $actionToken,
        ]);
    }

    public function wipe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action_token' => ['required', 'string', 'size:64'],
            'confirmation' => ['required', 'string', 'in:WIPE'],
        ]);

        $expectedHash = (string) $request->session()->pull('wipe_action_token_hash', '');
        $issuedAt = (int) $request->session()->pull('wipe_action_issued_at', 0);
        $actualHash = hash('sha256', (string) $validated['action_token']);
        $confirmationMaxAge = max(60, (int) config('wipe_security.confirmation_max_age_seconds', 300));
        $confirmationAge = time() - $issuedAt;

        if (
            $expectedHash === ''
            || $issuedAt <= 0
            || $confirmationAge < 0
            || $confirmationAge > $confirmationMaxAge
            || ! hash_equals($expectedHash, $actualHash)
        ) {
            abort(419);
        }

        $device = $this->resolveDevice($request);

        if ($device === null) {
            return $this->invalidateAndRedirect($request);
        }

        if (in_array((int) $device->status, [WipeStatus::WIPING->value, WipeStatus::WIPED->value], true)) {
            return redirect()->route('dashboard');
        }

        $response = $this->wipeService->updateStatus(
            (string) $device->id,
            WipeStatus::WIPING->value,
            WipedBy::WEBSITE->value
        );

        if (! $response->successful()) {
            return redirect()
                ->route('dashboard')
                ->with('error_message', 'The wipe request could not be completed. Please try again.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success_message', 'Your device has been queued for secure wipe.');
    }

    private function resolveDevice(Request $request): ?object
    {
        $authToken = (string) $request->session()->get('auth_token', '');

        if ($authToken === '') {
            return null;
        }

        $response = $this->wipeService->findByToken($authToken);

        if (! $response->successful()) {
            return null;
        }

        $device = $response->object();

        return is_object($device) && isset($device->id, $device->status) ? $device : null;
    }

    private function invalidateAndRedirect(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
