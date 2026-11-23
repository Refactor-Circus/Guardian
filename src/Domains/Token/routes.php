<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Token\Http\Controllers\TokenController;

// The signed-in user's own API tokens.
Route::get('tokens', [TokenController::class, 'index'])->name('tokens.index');
Route::post('tokens', [TokenController::class, 'store'])->name('tokens.store');
Route::delete('tokens/{token}', [TokenController::class, 'destroy'])->name('tokens.destroy');

// Another user's API tokens, for an admin.
Route::get('users/{user}/tokens', [TokenController::class, 'indexForUser'])->name('users.tokens.index');
Route::delete('users/{user}/tokens', [TokenController::class, 'destroyForUser'])->name('users.tokens.destroy');
