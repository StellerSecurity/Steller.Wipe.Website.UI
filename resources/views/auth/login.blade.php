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
                    <p class="mb-0 fs-20px">if it has been stolen, lost or if you just want to delete all contents on your phone.</p>
                    <strong style="font-size: 18px">Please choose which method you want to Wipe your phone with:</strong>
                </div>
                <div class="forms pt-3">
                    @isset($error_message)
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ $error_message }}</strong>
                    </div>
                    @endisset

                    <form method="POST" action="/">
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

                <div class="information py-3 border-b border-grey-light">
                    <p class="mb-0 text-secondary">If you don't know your login-details write to us on Signal on <a href="#" class="fw-bold text-blue">+591 73436721</a> or <a href="#" class="fw-bold text-blue">info@stellarsecurity.com</a>. Then we can help.</p>
                </div>

                <div class="card-foot pt-3 text-center">
                    <p class="mb-0 font-silka"><img src="{{ asset('build/assets/images/lock-circle.svg') }}"> Encrypted, secured and protected by Stellar Security</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
