<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Scim\Http\Controllers\ScimTokenController;

Route::get('organizations/{organization}/scim-tokens', [ScimTokenController::class, 'index'])->name('organizations.scim-tokens.index');
Route::post('organizations/{organization}/scim-tokens', [ScimTokenController::class, 'store'])->name('organizations.scim-tokens.store');
Route::delete('scim-tokens/{token}', [ScimTokenController::class, 'destroy'])->name('scim-tokens.destroy');
