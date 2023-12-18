@extends('layouts.app')

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

                @isset($error_message)
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ $error_message }}</strong>
                    </div>
                @endisset


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
                                    <form method="POST" action="/">
                                        <input type="hidden" name="method" value="0">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="username" class="font-silka text-uppercase form-label">Username <sup>*</sup></label>
                                            <input type="text" name="username" id="username" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required autofocus placeholder="Add your Username">
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="font-silka text-uppercase form-label">Password <sup>*</sup></label>
                                            <input type="password" name="password" id="password" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required placeholder="Add your Password">
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
                                I want to Wipe the phone with my Wipe Token
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <div class="forms pt-3">
                                    <form method="POST" action="/">
                                        <input type="hidden" name="method" value="1">
                                        @csrf
                                        <p>The Wipe Auth Token can be found in the Protect-app.</p>


                                        <div class="mb-3">
                                            <label for="password" class="font-silka text-uppercase form-label">Wipe Auth Token <sup>*</sup></label>
                                            <input type="text" name="token" id="token" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required placeholder="Wipe Auth Token">
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
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                I want to Wipe my phone with my Signal / Telegram or Whats-app number
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                This method does only work for Stellar Phone. Please contact our support to get help.
                                <a href="/contact-us">Contact us</a>, you can also use our live-chat.
                            </div>
                        </div>
                    </div>
                </div>



                <div class="information py-3 border-b border-grey-light">
                    <p class="mb-0 text-secondary">If you need any help, you can use our <a href="/contact-us">contact-us page.</a></p>
                </div>

                <div class="card-foot pt-3 text-center">
                    <p class="mb-0 font-silka"><img src="{{ asset('build/assets/images/lock-circle.svg') }}"> Encrypted, secured and protected by Stellar Security</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
