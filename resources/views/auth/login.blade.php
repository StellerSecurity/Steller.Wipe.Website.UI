@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <img src="/Logo_Steller_Protect_RGB.svg" style="height: 96px"><br><br><br>
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
    </div>
</div>
@endsection
