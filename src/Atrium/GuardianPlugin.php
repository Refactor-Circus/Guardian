<?php

declare(strict_types=1);

namespace RefactorCircus\Guardian\Atrium;

use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Atrium;
use RefactorCircus\Atrium\Domains\Plugins\Support\Plugin;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Guardian\Atrium\Http\Controllers\ScimUiController;
use RefactorCircus\Guardian\Atrium\Http\Controllers\SsoUiController;
use WeakMap;

/**
 * Guardian in the Atrium dashboard: no navigation of its own (D-025). Its
 * tabs and section sit on other packages' screens where
 * `guardian.atrium.screens` places them, and its routes serve their forms
 * and the SSO connection page (`guardian.sso-connection`).
 *
 * Registered by GuardianServiceProvider only while both Roster and Atrium are
 * installed.
 */
final class GuardianPlugin extends Plugin
{
    /**
     * The registries Guardian has placed its screens on: one per application.
     *
     * @var WeakMap<ScreenExtensions, true>|null
     */
    private static ?WeakMap $placed = null;

    public function key(): string
    {
        return 'guardian';
    }

    public function label(): string
    {
        return __('guardian::guardian.label');
    }

    /**
     * Under Atrium's `atrium.` name prefix and its middleware.
     */
    public function routes(): void
    {
        Route::name('guardian.')->prefix('guardian')->group(function (): void {
            Route::post('organizations/{organization}/sso', [SsoUiController::class, 'store'])->name('sso.store');
            Route::get('sso/{connection}', [SsoUiController::class, 'show'])->name('sso.show');
            Route::patch('sso/{connection}', [SsoUiController::class, 'update'])->name('sso.update');
            Route::delete('sso/{connection}', [SsoUiController::class, 'destroy'])->name('sso.destroy');
            Route::delete('sso-identities/{identity}', [SsoUiController::class, 'unlink'])->name('sso-identities.destroy');
            Route::post('organizations/{organization}/scim-tokens', [ScimUiController::class, 'store'])->name('scim-tokens.store');
            Route::delete('scim-tokens/{token}', [ScimUiController::class, 'revoke'])->name('scim-tokens.revoke');
        });
    }

    /**
     * Place Guardian's tabs and sections as `guardian.atrium.screens` says,
     * once per application: booting Guardian's provider again (as a test or
     * a long-running worker may) leaves them as they are, where Atrium would
     * otherwise refuse each one as a duplicate.
     */
    public static function registerScreens(): void
    {
        $registry = app(Atrium::class)->screens();
        self::$placed ??= new WeakMap;

        if (isset(self::$placed[$registry])) {
            return;
        }

        self::$placed[$registry] = true;

        Screens::register($registry, config('guardian.atrium.screens', []));
    }
}
