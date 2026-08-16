@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-body">
                    <h2>Confirm remote wipe</h2>
                    <p>This action is destructive and cannot be undone. Type <strong>WIPE</strong> exactly to confirm.</p>

                    <form method="POST" action="{{ route('dashboard.wipe') }}" autocomplete="off">
                        @csrf
                        <input type="hidden" name="action_token" value="{{ $action_token }}">

                        <div class="mb-3">
                            <label for="confirmation" class="form-label">Type WIPE</label>
                            <input
                                type="text"
                                name="confirmation"
                                id="confirmation"
                                class="form-control"
                                required
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                            >
                        </div>

                        @error('confirmation')
                            <div class="alert alert-danger" role="alert">Type WIPE exactly to continue.</div>
                        @enderror

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger">Wipe my phone</button>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
