@extends('layouts.app')

@push('scripts')
<script type="module" src="{{ asset('js/wipe/login.js') }}"></script>
@endpush

@section('content')
<div class="wipe-shell">
    <section class="wipe-intro" aria-labelledby="wipe-title">
        <p class="wipe-eyebrow">Stellar Protect</p>
        <h1 id="wipe-title">Securely wipe <br>your device</h1>
        <p class="wipe-intro__copy">Choose how you want to verify your identity. Your wipe request is protected from start to finish.</p>
        <div class="wipe-trust" aria-label="Security information">
            <div class="wipe-trust__item">
                <span class="wipe-trust__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                <span><strong>Encrypted</strong><small>Protected connection</small></span>
            </div>
            <div class="wipe-trust__item">
                <span class="wipe-trust__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg></span>
                <span><strong>Short session</strong><small>Usually under a minute</small></span>
            </div>
        </div>
    </section>

    <section class="wipe-panel" aria-labelledby="wipe-method-title">
        <div class="wipe-panel__heading">
            <div>
                <p class="wipe-panel__kicker">Verification</p>
                <h2 id="wipe-method-title">Choose wipe method</h2>
            </div>
            <span class="wipe-step">Step 1 of 2</span>
        </div>

        @if(session('error_message'))
            <div class="alert alert-danger wipe-alert" role="alert"><strong>{{ session('error_message') }}</strong></div>
        @endif

        <div class="accordion wipe-methods" id="accordionExample">
            <div class="accordion-item wipe-method">
                <h3 class="accordion-header" id="headingOne">
                    <button class="accordion-button wipe-method__trigger" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        <span class="wipe-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M6.5 19c.6-3.2 2.4-5 5.5-5s4.9 1.8 5.5 5"/></svg></span>
                        <span><strong>Username and password</strong><small>Use your Stellar Protect account</small></span>
                    </button>
                </h3>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body wipe-method__body">
                        <form method="POST" action="{{ route('login.attempt', absolute: false) }}">
                            <input type="hidden" name="method" value="0">
                            @csrf
                            <div class="wipe-field">
                                <label for="username" class="form-label">Username <span aria-hidden="true">*</span></label>
                                <input type="text" name="username" id="username" autocomplete="username" autocapitalize="none" spellcheck="false" class="form-control" required autofocus placeholder="Enter your username">
                            </div>
                            <div class="wipe-field">
                                <label for="password" class="form-label">Password <span aria-hidden="true">*</span></label>
                                <input type="password" name="password" id="password" autocomplete="current-password" class="form-control" required placeholder="Enter your password">
                                @error('password')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-blue wipe-primary-action"><span>Continue securely</span><span aria-hidden="true">→</span></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="accordion-item wipe-method">
                <h3 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed wipe-method__trigger" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        <span class="wipe-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 12a4 4 0 1 1 4 4H8v3H5v-3H3v-4h5Z"/><circle cx="8" cy="8" r="1"/></svg></span>
                        <span><strong>Wipe token</strong><small>Use a saved token from Protect</small></span>
                    </button>
                </h3>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body wipe-method__body">
                        <form id="wipe-token-entry" autocomplete="off">
                            <p class="wipe-method__explanation">Use the wipe token saved from Protect. Both new and older tokens are supported.</p>
                            <div class="wipe-field">
                                <label for="token" class="form-label">Wipe token <span aria-hidden="true">*</span></label>
                                {{-- No name or submit button: a failed script must never send the private token. --}}
                                <input type="password" id="token" maxlength="5505" autocomplete="off" autocapitalize="none" spellcheck="false" class="form-control" required placeholder="Paste your wipe token" aria-describedby="wipe-token-help">
                                <p id="wipe-token-help" class="wipe-field__help">New tokens are checked and signed in your browser. The token itself is not sent to our servers. Checking a token does not wipe your phone.</p>
                                @error('token')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <button id="token-continue" type="button" disabled class="btn btn-blue wipe-primary-action"><span>Continue securely</span><span aria-hidden="true">→</span></button>
                            <noscript><p class="wipe-alert" role="alert">Enable JavaScript to use a wipe token safely. Username and password login is still available above.</p></noscript>
                        </form>
                        <form id="legacy-token-login" method="POST" action="{{ route('login.attempt', absolute: false) }}" hidden>
                            @csrf
                            <input type="hidden" name="method" value="1">
                            <input type="hidden" name="token" value="">
                        </form>
                        <section id="signed-wipe-panel" class="signed-wipe-panel" hidden aria-label="Wipe confirmation">
                            <p id="signed-wipe-status" role="status" aria-live="polite" tabindex="-1"></p>
                            <p id="signed-wipe-target" class="wipe-field__help" hidden>Token device reference:<br><span id="signed-wipe-device" class="text-break"></span></p>
                            <form id="signed-wipe-confirmation" autocomplete="off" hidden>
                                <h4>Confirm remote wipe</h4>
                                <p class="text-danger"><strong>This permanently erases the phone associated with this token. It cannot be undone.</strong></p>
                                <p class="wipe-field__help">If the phone is offline, the request stays queued until it comes online. A request that reaches the phone cannot be cancelled. Requires the latest version of Protect.</p>
                                <div class="wipe-field">
                                    <label for="signed-wipe-phrase" class="form-label">Type WIPE to confirm</label>
                                    <input id="signed-wipe-phrase" class="form-control" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" required pattern="WIPE" maxlength="4">
                                </div>
                                <button id="signed-wipe-submit" type="submit" disabled class="btn btn-danger">Wipe my phone</button>
                            </form>
                            <button id="signed-wipe-retry" type="button" class="btn btn-danger" hidden>Retry the same request</button>
                            <button id="signed-wipe-close" type="button" class="btn btn-outline-secondary">Cancel</button>
                        </section>
                    </div>
                </div>
            </div>

            <div class="accordion-item wipe-method">
                <h3 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed wipe-method__trigger" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        <span class="wipe-method__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="9" r="3"/><circle cx="17" cy="10" r="2"/><path d="M3.5 19c.6-3.3 2.4-5 5.5-5s4.9 1.7 5.5 5M15 15c2.8-.3 4.6 1 5.2 3.5"/></svg></span>
                        <span><strong>Stellar Circle</strong><small>Ask a trusted Circle contact</small></span>
                    </button>
                </h3>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                    <div class="accordion-body wipe-method__body">
                        <p class="wipe-method__explanation">To wipe a phone with Stellar Circle, your trusted contact must have Stellar Protect on their phone. They can wipe your phone with one click.</p>
                        <a class="wipe-text-link" href="https://stellarsecurity.com/stellar-circle">Learn more about Stellar Circle <span aria-hidden="true">→</span></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="wipe-panel__footer">
            <p>Need help? <a href="https://stellarsecurity.com/contact-us">Contact support</a></p>
            <p class="wipe-panel__secured"><span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="6" y="10" width="12" height="9" rx="2"/><path d="M9 10V7a3 3 0 0 1 6 0v3"/></svg></span>Encrypted by Stellar Security</p>
        </div>
    </section>
</div>
@endsection
