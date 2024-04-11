<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/login', [\App\Http\Controllers\LoginController::class, 'auth']);


Route::match(['post', 'get'], '/dashboard', [\App\Http\Controllers\DashboardController::class, 'dashboard'])
    ->name('Login');


Route::match(['get'], '/', [\App\Http\Controllers\LoginController::class, 'auth'])
    ->name('Login');

Route::match(['post'], '/', [\App\Http\Controllers\LoginController::class, 'auth'])->middleware("throttle:20,2");
