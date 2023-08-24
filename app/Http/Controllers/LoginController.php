<?php

namespace App\Http\Controllers;

class LoginController extends Controller
{
    public function auth() {
        return view('login', ['name' => 'Alex']);
    }
}
