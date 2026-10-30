<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Sso\Http\Controllers\SsoController;

Route::get('organizations/{organization}/sso-connections', [SsoController::class, 'index'])->name('organizations.sso-connections.index');
Route::post('organizations/{organization}/sso-connections', [SsoController::class, 'store'])->name('organizations.sso-connections.store');
Route::get('sso-connections/{connection}', [SsoController::class, 'show'])->name('sso-connections.show');
Route::patch('sso-connections/{connection}', [SsoController::class, 'update'])->name('sso-connections.update');
Route::delete('sso-connections/{connection}', [SsoController::class, 'destroy'])->name('sso-connections.destroy');
Route::get('users/{user}/sso-identities', [SsoController::class, 'identities'])->name('users.sso-identities.index');
Route::delete('sso-identities/{identity}', [SsoController::class, 'unlink'])->name('sso-identities.destroy');
