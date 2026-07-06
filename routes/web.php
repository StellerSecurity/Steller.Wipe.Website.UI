<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;

Route::middleware('throttle:15,2')->group(function () {
    Route::match(['get', 'post'], '/', [LoginController::class, 'auth'])
        ->name('login');

    Route::get('/login', [LoginController::class, 'auth']);
});

Route::middleware('throttle:10,1')->group(function () {
    Route::match(['get', 'post'], '/dashboard', [DashboardController::class, 'dashboard'])
        ->name('dashboard');
});
