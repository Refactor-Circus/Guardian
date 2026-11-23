<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RefactorCircus\Guardian\Domains\SecurityEvent\Enums\SecurityEventType;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\SecurityEvent\Support\IdentifierHash;

// Criterion 24: Laravel's Login, Failed, Lockout, Logout and PasswordReset
// events are recorded.

function onlySecurityEvent(): SecurityEventModel
{
    return SecurityEventModel::query()->sole();
}

it('records a sign-in and a sign-out through the guard', function (): void {
    $ada = user(['email' => 'ada@example.com', 'password' => bcrypt('correct-horse')]);

    expect(Auth::attempt(['email' => 'ada@example.com', 'password' => 'correct-horse']))->toBeTrue();

    $login = onlySecurityEvent();

    expect($login->type)->toBe(SecurityEventType::LoginSucceeded)
        ->and($login->user_id)->toEqual($ada->getKey())
        ->and($login->context)->toBe(['guard' => 'web']);

    Auth::logout();

    expect(SecurityEventModel::query()->where('type', SecurityEventType::Logout->value)->sole()->user_id)->toEqual($ada->getKey());
});

it('records a failed sign-in with the identifier\'s HMAC, and the user when there is one', function (): void {
    $ada = user(['email' => 'ada@example.com', 'password' => bcrypt('correct-horse')]);

    expect(Auth::attempt(['email' => 'ada@example.com', 'password' => 'wrong-horse']))->toBeFalse()
        ->and(Auth::attempt(['email' => 'nobody@example.com', 'password' => 'wrong-horse']))->toBeFalse();

    $known = SecurityEventModel::query()->where('identifier_hash', IdentifierHash::of('ada@example.com'))->sole();
    $unknown = SecurityEventModel::query()->where('identifier_hash', IdentifierHash::of('nobody@example.com'))->sole();

    expect($known->type)->toBe(SecurityEventType::LoginFailed)
        ->and($known->user_id)->toEqual($ada->getKey())
        ->and($unknown->user_id)->toBeNull()
        ->and((string) json_encode(SecurityEventModel::query()->get()->toArray()))->not->toContain('nobody@example.com')->not->toContain('wrong-horse');
});

it('records a lockout with the identifier\'s HMAC and the request\'s address', function (): void {
    $request = Request::create('/login', 'POST', ['email' => 'mal@example.com', 'password' => 'guess'], server: ['REMOTE_ADDR' => '198.51.100.4', 'HTTP_USER_AGENT' => 'curl/8']);

    event(new Lockout($request));

    $lockout = onlySecurityEvent();

    expect($lockout->type)->toBe(SecurityEventType::LoginLockedOut)
        ->and($lockout->identifier_hash)->toBe(IdentifierHash::of('mal@example.com'))
        ->and($lockout->ip)->toBe('198.51.100.4')
        ->and($lockout->user_agent)->toBe('curl/8');
});

it('records a password reset', function (): void {
    $ada = user();

    event(new PasswordReset($ada));

    expect(onlySecurityEvent()->type)->toBe(SecurityEventType::PasswordReset)
        ->and(onlySecurityEvent()->user_id)->toEqual($ada->getKey());
});

it('reads the identifier from guardian.security_log.identifier_keys', function (): void {
    config(['guardian.security_log.identifier_keys' => ['username']]);

    event(new Failed('web', null, ['username' => 'Ada', 'password' => 'x']));

    expect(onlySecurityEvent()->identifier_hash)->toBe(IdentifierHash::of('ada'));
});

it('records none of them with guardian.security_log.enabled off', function (): void {
    config(['guardian.security_log.enabled' => false]);
    $ada = user();

    event(new Login('web', $ada, false));
    event(new Logout('web', $ada));
    event(new Failed('web', null, ['email' => 'a@example.com']));
    event(new PasswordReset($ada));

    expect(SecurityEventModel::query()->count())->toBe(0);
});
