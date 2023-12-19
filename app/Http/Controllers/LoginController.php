<?php

namespace App\Http\Controllers;

use App\Services\WipeService;
use Illuminate\Http\Request;


class LoginController extends Controller
{

    private WipeService $wipeService;

    public function __construct(WipeService $wipeService) {
        $this->wipeService = $wipeService;
    }

    public function auth(Request $request) {

        $data = array();

        if($request->isMethod('post')) {

            $login = null;
            if($request->input('method') === 0) {
                $username = $request->input('username');
                $password = $request->input('password');
                $login = $this->wipeService->auth($username, $password)->object();
            } else if($request->input('token') !== null) {
                $login = $this->wipeService->findbytoken($request->input('token'))->object();
            }

            if($login == null) {
                $data['error_message'] = "The login details you provided does not exist. Try again.";
            } else {
                session(['auth_token' => $login->auth_token]);
                return redirect('/dashboard');
            }

        }

        return view('auth.login', $data);

    }
}
