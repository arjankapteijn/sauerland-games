<?php

use App\Http\Controllers\DashboardPinController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard/pin', [DashboardPinController::class, 'show'])->name('dashboard.pin');
Route::post('/dashboard/pin', [DashboardPinController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('dashboard.pin.store');
Route::livewire('/dashboard', 'dashboard')->middleware('dashboard.pin')->name('dashboard');
