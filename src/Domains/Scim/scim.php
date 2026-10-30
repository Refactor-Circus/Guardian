<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Scim\Http\Controllers\ScimController;
use RefactorCircus\Guardian\Domains\Scim\Http\Middleware\AuthenticateScimToken;

if (config('guardian.scim.enabled') !== true) {
    return;
}

/** @var array<int, string> $middleware */
$middleware = config('guardian.scim.middleware', ['api', 'throttle:guardian']);

Route::middleware([...$middleware, AuthenticateScimToken::class])
    ->prefix(trim((string) config('guardian.scim.prefix', 'scim/v2'), '/').'/{organization}')
    ->name('guardian.scim.')
    ->group(function (): void {
        Route::get('Users', [ScimController::class, 'listUsers'])->name('users.index');
        Route::post('Users', [ScimController::class, 'createUser'])->name('users.store');
        Route::get('Users/{id}', [ScimController::class, 'showUser'])->name('users.show');
        Route::put('Users/{id}', [ScimController::class, 'replaceUser'])->name('users.replace');
        Route::patch('Users/{id}', [ScimController::class, 'patchUser'])->name('users.patch');
        Route::delete('Users/{id}', [ScimController::class, 'deleteUser'])->name('users.destroy');

        Route::get('Groups', [ScimController::class, 'listGroups'])->name('groups.index');
        Route::post('Groups', [ScimController::class, 'createGroup'])->name('groups.store');
        Route::get('Groups/{id}', [ScimController::class, 'showGroup'])->name('groups.show');
        Route::put('Groups/{id}', [ScimController::class, 'replaceGroup'])->name('groups.replace');
        Route::patch('Groups/{id}', [ScimController::class, 'patchGroup'])->name('groups.patch');
        Route::delete('Groups/{id}', [ScimController::class, 'deleteGroup'])->name('groups.destroy');

        Route::post('Bulk', [ScimController::class, 'bulk'])->name('bulk');
        Route::get('ServiceProviderConfig', [ScimController::class, 'serviceProviderConfig'])->name('config');
        Route::get('ResourceTypes', [ScimController::class, 'resourceTypes'])->name('resource-types');
        Route::get('Schemas', [ScimController::class, 'schemas'])->name('schemas');
    });
