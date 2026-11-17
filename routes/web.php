<?php

use App\Http\Controllers\Auth\HiveAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [HiveAuthController::class, 'redirectToHive'])
    ->middleware('guest')
    ->name('login');

Route::get('/auth/callback', [HiveAuthController::class, 'handleCallback'])
    ->name('auth.callback');

Route::post('/logout', [HiveAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/', fn () => redirect()->route('dashboard'))
    ->middleware('auth');

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');
