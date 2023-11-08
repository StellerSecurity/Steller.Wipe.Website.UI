@extends('layouts.app')

@section('content')
<div class="container wipe-page">
    {{-- <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <p>Stellar Protect allows you to remote wipe your device if it has been stolen,
                        lost or if you just want to delete all contents on your phone.<br><br>

                        If you don't know your login-details write to us on Signal on <strong>+591 73436721</strong> or <strong>info@stellarsecurity.com</strong>. Then we can help.
                    </p>
                    <hr>

                    @isset($error_message)
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ $error_message }}</strong>
                    </div>
                    @endisset

                    <form method="POST" action="/">
                        @csrf

                        <div class="row mb-3">
                            <label for="username" class="col-md-4 col-form-label text-md-end">Username</label>

                            <div class="col-md-6">
                                <input id="username" type="text" class="form-control" name="username" required autofocus>

                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="content-parent d-flex flex-column align-items-center justify-content-center">
        <div class="headline mt-5">
            <h1 class="mb-0 text-center fs-xl font-silka">Wipe your <br> <span class="fw-bold">Phone</span></h1>
        </div>
        <div class="card border border-grey-light mt-5 bg-white rounded-4 p-2" style="max-width: 570px;">
            <div class="card-body">
                <div class="upper-card border-b border-grey-light pb-3">
                    <p class="mb-0 fw-bold font-silka fs-18px">Stellar Protect allows you to remote wipe your device</p>
                    <p class="mb-0 fs-18px">if it has been stolen, lost or if you just want to delete all contents on your phone.</p>
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
                            <label for="username" class="font-silka text-uppercase fw-semibold">Username <sup>*</sup></label>
                            <input type="text" name="username" id="username" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required autofocus placeholder="Add your Username">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="font-silka text-uppercase fw-semibold">Password <sup>*</sup></label>
                            <div class="input-parent position-relative">
                                <input type="password" name="password" id="password" class="form-control bg-white border border-grey-light font-silka rounded-3" style="height: 50px;" required placeholder="Add your Password">
                                <img src="{{ asset('build/assets/images/eye-closed.svg') }}" class="position-absolute top-50 end-0 translate-middle" style="cursor: pointer">
                            </div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="action-btn">
                            <button type="submit" class="btn btn-blue rounded-3 text-white p-2 font-silka fw-semibold">Login <img src="{{ asset('build/assets/images/lock.svg') }}" class="ms-2"></button>
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
