<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\TwoFactor\Http\Controllers\TwoFactorController;

// The signed-in user's own two-factor.
Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
Route::post('two-factor/totp', [TwoFactorController::class, 'storeTotp'])->name('two-factor.totp.store');
Route::post('two-factor/totp/confirm', [TwoFactorController::class, 'confirmTotp'])->name('two-factor.totp.confirm');
Route::post('two-factor/one-time-codes', [TwoFactorController::class, 'storeOneTimeCode'])->name('two-factor.one-time-codes.store');
Route::post('two-factor/one-time-codes/confirm', [TwoFactorController::class, 'confirmOneTimeCode'])->name('two-factor.one-time-codes.confirm');
Route::post('two-factor/recovery-codes', [TwoFactorController::class, 'storeRecoveryCodes'])->name('two-factor.recovery-codes.store');
Route::delete('two-factor/methods/{method}', [TwoFactorController::class, 'destroyMethod'])->name('two-factor.methods.destroy');

// An admin's reset of another user's two-factor.
Route::delete('users/{user}/two-factor', [TwoFactorController::class, 'reset'])->name('users.two-factor.destroy');
