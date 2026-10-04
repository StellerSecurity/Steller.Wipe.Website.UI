@extends('layouts.app')

@push('scripts')
<script type="module" src="{{ asset('js/wipe/login.js') }}"></script>
@endpush

@section('content')
<div class="container wipe-page">

    <div class="content-parent d-flex flex-column align-items-center justify-content-center">
        <div class="headline mt-5">
            <h1 class="mb-0 text-center fs-xl font-silka">
                <img src="/Logo_Steller_Protect_RGB.svg" style="height: 100px; max-width: 100%">
            </h1>
        </div>
        <div class="card border border-grey-light mt-5 bg-white rounded-4 p-2" style="max-width: 570px;">
            <div class="card-body">
                <div class="upper-card border-b border-grey-light pb-3">
                    <p class="mb-0 font-silka-bold fs-18px title-headline">Stellar Protect allows you to remote wipe your device</p>
                    <strong style="font-size: 18px">Please choose which method you want to Wipe your phone with:</strong>
                </div>

                @if(session('error_message'))
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ session('error_message') }}</strong>
                    </div>
                @endif


                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                I want to Wipe with my Username and Password I created in the Protect App
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <div class="forms pt-3">
                                    <form method="POST" action="{{ route('login.attempt') }}">
                                        <input type="hidden" name="method" value="0">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="username" class="font-silka text-uppercase form-label">Username <sup>*</sup></label>
                                            <input type="text" name="username" id="username" autocomplete="username" autocapitalize="none" spellcheck="false" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required autofocus placeholder="Add your Username">
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="font-silka text-uppercase form-label">Password <sup>*</sup></label>
                                            <input type="password" name="password" id="password" autocomplete="current-password" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required placeholder="Add your Password">
                                            @error('password')
                                            <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                            @enderror
                                        </div>

                                        <div class="action-btn">
                                            <button type="submit" class="btn btn-blue rounded-3 text-white p-2 font-silka font-silka-medium">Login <img src="{{ asset('build/assets/images/lock.svg') }}" class="ms-2"></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                I want to Wipe my phone with my Wipe Token
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <div class="forms pt-3">
                                    <form id="wipe-token-entry" autocomplete="off">
                                        <p>Use the wipe token saved from Protect. Both new and older tokens are supported.</p>
                                        <div class="mb-3">
                                            <label for="token" class="font-silka text-uppercase form-label">Wipe token <sup>*</sup></label>
                                            {{-- No name or submit button: a failed script must never send the private token. --}}
                                            <input type="password" id="token" maxlength="5505" autocomplete="off" autocapitalize="none" spellcheck="false" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required placeholder="Paste your wipe token" aria-describedby="wipe-token-help">
                                            <p id="wipe-token-help" class="small text-secondary mt-2">New tokens are checked and signed in your browser. The token itself is not sent to our servers. Checking a token does not wipe your phone.</p>
                                            @error('token')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>

                                        <div class="action-btn">
                                            <button id="token-continue" type="button" disabled class="btn btn-blue rounded-3 text-white p-2 font-silka font-silka-medium">Continue <img src="{{ asset('build/assets/images/lock.svg') }}" class="ms-2" alt=""></button>
                                        </div>
                                        <noscript><p role="alert">Enable JavaScript to use a wipe token safely. Username and password login is still available above.</p></noscript>
                                    </form>
                                    <form id="legacy-token-login" method="POST" action="{{ route('login.attempt') }}" hidden>
                                        @csrf
                                        <input type="hidden" name="method" value="1">
                                        <input type="hidden" name="token" value="">
                                    </form>
                                    <section id="signed-wipe-panel" hidden aria-label="Wipe confirmation">
                                        <p id="signed-wipe-status" role="status" aria-live="polite" tabindex="-1"></p>
                                        <p id="signed-wipe-target" class="small text-secondary" hidden>Token device reference:<br><span id="signed-wipe-device" class="text-break"></span></p>
                                        <form id="signed-wipe-confirmation" autocomplete="off" hidden>
                                            <h3 class="fs-5">Confirm remote wipe</h3>
                                            <p class="text-danger"><strong>This permanently erases the phone associated with this token. It cannot be undone.</strong></p>
                                            <p class="small text-secondary">If the phone is offline, the request stays queued until it comes online. A request that reaches the phone cannot be cancelled. Requires the latest version of Protect.</p>
                                            <div class="mb-3">
                                                <label for="signed-wipe-phrase" class="form-label">Type WIPE to confirm</label>
                                                <input id="signed-wipe-phrase" class="form-control" type="text" autocomplete="off" autocapitalize="characters" spellcheck="false" required pattern="WIPE" maxlength="4">
                                            </div>
                                            <button id="signed-wipe-submit" type="submit" disabled class="btn btn-danger mb-2">Wipe my phone</button>
                                        </form>
                                        <button id="signed-wipe-retry" type="button" class="btn btn-danger mb-2" hidden>Retry the same request</button>
                                        <button id="signed-wipe-close" type="button" class="btn btn-outline-secondary">Cancel</button>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                   <!-- <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                I want to Wipe my phone with my Signal / Telegram or Whats-app number
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                This method does only work for <a href="https://stellarsecurity.com/stellar-phone">Stellar Phone</a>. Please contact our support to get help.
                                <a href="https://stellarsecurity.com/contact-us">Contact us</a>, you can also use our live-chat.
                            </div>
                        </div>
                    </div>-->

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                I want to Wipe my phone with Stellar Circle
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                To wipe a phone with Stellar Circle, your friend must have Stellar Protect on their phone. In any situation they can with one-click wipe your phone. See more here: <a href="https://stellarsecurity.com/stellar-circle">Stellar Circle</a>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="information py-3 border-b border-grey-light">
                    <p class="mb-0 text-secondary">If you need any help, you can use our <a href="https://stellarsecurity.com/contact-us">contact-us page.</a></p>
                </div>

                <div class="card-foot pt-3 text-center">
                    <p class="mb-0 font-silka"><img src="{{ asset('build/assets/images/lock-circle.svg') }}"> Encrypted, secured and protected by Stellar Security</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
