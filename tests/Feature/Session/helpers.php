<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/TwoFactor/helpers.php';

use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\Session\Models\SessionModel;
use Workbench\App\Models\User;

/**
 * Give Guardian's routes a session, add the test sign-in route, a route
 * behind `guardian.session` and one that regenerates the session id.
 */
function withTrackedSessions(): void
{
    withSessions();

    Route::middleware(['web', 'auth', 'guardian.session'])->get('test-tracked', fn (): string => 'tracked');
    Route::middleware(['web', 'auth', 'guardian.session'])->post('test-regenerate', function (): string {
        session()->regenerate();

        return 'regenerated';
    });
}

/**
 * Sign in from a browser with a user agent and address.
 */
function signInFrom(User $user, string $userAgent = 'Mozilla/5.0 (Macintosh) Firefox/130.0', string $ip = '203.0.113.7'): void
{
    test()->withHeader('User-Agent', $userAgent)->withServerVariables(['REMOTE_ADDR' => $ip]);
    signIn($user);
}

/**
 * A session record written straight to the table, as another browser's
 * sign-in would have.
 *
 * @param  array<string, mixed>  $attributes
 */
function sessionRecord(User $user, array $attributes = []): SessionModel
{
    return SessionModel::query()->create($attributes + [
        'user_id' => $user->getKey(),
        'key_hash' => hash('sha256', random_bytes(16)),
        'ip' => '198.51.100.9',
        'user_agent' => 'Other browser',
        'last_active_at' => now(),
    ]);
}
