<?php

namespace App\Http\Controllers;

use App\Services\WipeService;
use App\WipeStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    private WipeService $wipeService;

    public function __construct(WipeService $wipeService) {
        $this->wipeService = $wipeService;
    }

    public function dashboard(Request $request) {

        $auth_token = session('auth_token');

        if($auth_token === null) {
            return redirect('/');
        }

        $findbytoken = $this->wipeService->findbytoken($auth_token)->object();

        if($findbytoken ===  null) {
            return redirect('/');
        }

        if($request->input('do_wipe') == 1 && $request->input('csrf_token') === session('csrf_token')) {
            $this->wipeService->updateStatus($findbytoken->id, WipeStatus::WIPING->value);
            return redirect('/dashboard?s=1');
        }

        $token = $request->session()->token();
        session(['csrf_token' => $token]);

        $is_wiped = $findbytoken->status == WipeStatus::WIPING->value OR $findbytoken->status == WipeStatus::WIPED->value;

        $data = [
            'is_wiped' => $is_wiped,
            'csrf' => $token
        ];

        return view('dashboard', $data);

    }

}
