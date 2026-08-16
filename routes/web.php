<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'show'])
    ->name('login');

Route::get('/login', [LoginController::class, 'show'])
    ->name('login.form');

Route::post('/login', [LoginController::class, 'authenticate'])
    ->middleware('throttle:wipe-login')
    ->name('login.attempt');

Route::middleware('wipe.session')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])
        ->name('dashboard');

    Route::get('/dashboard/wipe', [DashboardController::class, 'confirmWipe'])
        ->name('dashboard.wipe.confirm');

    Route::post('/dashboard/wipe', [DashboardController::class, 'wipe'])
        ->middleware('throttle:wipe-action')
        ->name('dashboard.wipe');

    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');
});
