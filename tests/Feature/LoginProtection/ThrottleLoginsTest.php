<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Middleware\EnforceIpRules;
use RefactorCircus\Guardian\Domains\LoginProtection\Http\Middleware\ThrottleLogins;
use RefactorCircus\Guardian\Domains\LoginProtection\Services\LoginFailures;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;

// Criterion 49: throttling and lockout.

beforeEach(function (): void {
    Notification::fake();
    withLoginRoutes();
});

it('registers the guardian.login-throttle and guardian.ip aliases', function (): void {
    expect(Route::getMiddleware()['guardian.login-throttle'] ?? null)->toBe(ThrottleLogins::class)
        ->and(Route::getMiddleware()['guardian.ip'] ?? null)->toBe(EnforceIpRules::class);
});

it('locks an identifier out after five failures: 429 with Retry-After, Lockout fired and recorded', function (): void {
    user(['email' => 'ada@example.com', 'password' => 'the right passphrase']);
    Event::fake([Lockout::class]);

    foreach (range(1, 5) as $failure) {
        attempt('ada@example.com')->assertStatus(422);
    }

    attempt('ada@example.com', 'the right passphrase')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonStructure(['message', 'retry_after'])
        ->assertJsonPath('retry_after', fn (int $seconds): bool => $seconds > 0 && $seconds <= 900);

    Event::assertDispatched(Lockout::class);

    // Another identifier from the same address is not locked out.
    attempt('bob@example.com')->assertStatus(422);
});

it('records the lockout without the identifier typed', function (): void {
    foreach (range(1, 5) as $failure) {
        attempt('Ada@Example.com');
    }

    attempt('Ada@Example.com')->assertTooManyRequests();

    $event = SecurityEventModel::query()->where('type', 'login.locked_out')->sole();
    expect($event->identifier_hash)->not->toBeNull()
        ->and((string) json_encode($event->toArray()))->not->toContain('Ada@Example.com')->not->toContain('ada@example.com');
});

it('counts failures by the configured identifier field, case-insensitively', function (): void {
    config()->set('guardian.login.max_attempts', 2);

    attempt('ADA@example.com');
    attempt('ada@example.com');

    attempt('ada@EXAMPLE.com')->assertTooManyRequests();
});

it('forgets failures after decay_minutes', function (): void {
    foreach (range(1, 5) as $failure) {
        attempt('ada@example.com');
    }

    attempt('ada@example.com')->assertTooManyRequests();

    $this->travel(16)->minutes();

    attempt('ada@example.com')->assertStatus(422);
});

it('locks an address out after fifty failures, whatever the identifier', function (): void {
    // Forty-nine counted as the listener counts them (each real attempt
    // pays for a password hash), and the fiftieth through the login route.
    foreach (range(1, 49) as $failure) {
        app(LoginFailures::class)->failed("user{$failure}@example.com", '198.51.100.9');
    }

    attempt('user50@example.com', ip: '198.51.100.9')->assertStatus(422);

    attempt('fresh@example.com', ip: '198.51.100.9')->assertTooManyRequests();
    attempt('fresh@example.com', ip: '198.51.100.10')->assertStatus(422);
});

it('clears the identifier\'s counter on a sign-in', function (): void {
    user(['email' => 'ada@example.com', 'password' => 'the right passphrase']);

    foreach (range(1, 4) as $failure) {
        attempt('ada@example.com');
    }

    attempt('ada@example.com', 'the right passphrase')->assertOk();
    expect(app(LoginFailures::class)->attempts('ada@example.com'))->toBe(0);

    foreach (range(1, 4) as $failure) {
        attempt('ada@example.com')->assertStatus(422);
    }
});
