<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Controllers\LoginProtectionController;

// Lifting a sign-in lockout.
Route::delete('lockouts', [LoginProtectionController::class, 'destroyLockout'])->name('lockouts.destroy');

// IP rules.
Route::get('ip-rules', [LoginProtectionController::class, 'indexIpRules'])->name('ip-rules.index');
Route::post('ip-rules', [LoginProtectionController::class, 'storeIpRule'])->name('ip-rules.store');
Route::delete('ip-rules/{rule}', [LoginProtectionController::class, 'destroyIpRule'])->name('ip-rules.destroy');
