<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Session\Http\Controllers\DeviceController;
use RefactorCircus\Guardian\Domains\Session\Http\Controllers\SessionController;

// The signed-in user's own sessions and devices.
Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
Route::delete('sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.others.destroy');
Route::delete('sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

// Another user's sessions, for an admin.
Route::get('users/{user}/sessions', [SessionController::class, 'indexForUser'])->name('users.sessions.index');
Route::delete('users/{user}/sessions', [SessionController::class, 'destroyForUser'])->name('users.sessions.destroy');
