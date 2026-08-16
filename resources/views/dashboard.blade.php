@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <img src="/Logo_Steller_Protect_RGB.svg" alt="Stellar Protect" style="height: 96px"><br><br><br><br>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">There is currently <strong>1</strong> device linked to your account</h3>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">Log out</button>
                        </form>
                    </div>

                    @if(session('error_message'))
                        <div class="alert alert-danger" role="alert">
                            <strong>{{ session('error_message') }}</strong>
                        </div>
                    @endif

                    @if(session('success_message'))
                        <div class="alert alert-success" role="alert">
                            <strong>{{ session('success_message') }}</strong>
                        </div>
                    @endif

                    @if($is_wiped)
                        <p>Your linked device has been set to be wiped. Data cannot be recovered.</p>
                    @else
                        <p>If you have lost your device and want to wipe it, continue to the secure confirmation step.</p>

                        <strong style="color: red">WARNING: YOUR DEVICE DATA CANNOT BE RECOVERED UNLESS YOU HAVE A BACKUP.</strong>

                        <hr>

                        <a href="{{ route('dashboard.wipe.confirm') }}" class="btn btn-danger">Continue to wipe confirmation</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
