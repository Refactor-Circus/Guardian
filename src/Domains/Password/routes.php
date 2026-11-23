<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Password\Http\Controllers\PasswordController;

// The password rules and a check against them, for anyone (a sign-up form).
Route::get('password-policy', [PasswordController::class, 'policy'])->name('password-policy.show');
Route::post('passwords/check', [PasswordController::class, 'check'])->name('passwords.check');
