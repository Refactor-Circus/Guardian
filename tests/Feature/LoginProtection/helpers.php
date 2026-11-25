<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/Session/helpers.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

/**
 * A host's login route behind `guardian.login-throttle`, signing in with
 * the session guard, and a route behind `guardian.ip`.
 */
function withLoginRoutes(): void
{
    withTrackedSessions();

    Route::middleware(['web', 'guardian.login-throttle'])->post('test-attempt', function (): mixed {
        return Auth::guard('web')->attempt(['email' => request()->string('email')->toString(), 'password' => request()->string('password')->toString()])
            ? response('signed in')
            : response('wrong', 422);
    });

    Route::middleware('guardian.ip')->get('test-guarded', fn (): string => 'guarded');
}

/**
 * One sign-in attempt from an address.
 */
function attempt(string $email, string $password = 'wrong password', string $ip = '203.0.113.7', string $userAgent = 'Mozilla/5.0 (Macintosh) Firefox/130.0'): TestResponse
{
    return test()->withHeader('User-Agent', $userAgent)->withServerVariables(['REMOTE_ADDR' => $ip])->post('test-attempt', ['email' => $email, 'password' => $password]);
}

/**
 * A request to the IP-guarded route from an address.
 */
function fromIp(string $ip): TestResponse
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])->getJson('test-guarded');
}
