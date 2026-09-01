<?php

use App\Http\Controllers\GearController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'stage-title')->name('home');
Route::view('/debug', 'debug')->name('stage.debug');
Route::view('/shifter', 'shifter')->name('shifter');

Route::post('/gear', [GearController::class, 'shift'])->name('gear.shift');
Route::get('/gear/status', [GearController::class, 'status'])->name('gear.status');
Route::post('/gear/reset', [GearController::class, 'reset'])->name('gear.reset');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
