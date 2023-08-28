@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <img src="/Logo_Steller_Protect_RGB.svg" style="height: 96px"><br><br><br><br>
        <div class="col-md-8">
            <div class="card">


                <div class="card-body">
                    <h3>There is currently <strong>1</strong> device linked to your account</h3>


                    @if($is_wiped)
                        <p>Your linked device has been set to be wiped. Data cannot be recovered.</p>
                    @endif

                    @if(!$is_wiped)

                    If you have lost your device and want to wipe it, click on the WIPE button.<br><br>

                    <strong style="color: red">* WARNING YOUR DEVICE DATA CANNOT BE RECOVERED UNLESS YOU HAVE BACKUP!</strong>

                    <hr>

                    <button type="button" class="btn btn-danger" onclick="javascript:wipe()">Wipe my phone</button>

                    <script>
                        function wipe() {

                            if(confirm("* ARE YOU SURE YOU WANT TO WIPE YOUR PHONE? THIS CANNOT BE UNDONE!")) {
                                window.location.href="/dashboard?do_wipe=1&csrf_token={{ $csrf }}"
                            }

                        }
                    </script>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
