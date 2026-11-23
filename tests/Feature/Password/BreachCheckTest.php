<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;

// Criterion 40: the breach check, never on the network in tests.

it('refuses a breached password and accepts another, through the bound verifier', function (): void {
    $verifier = fakeBreaches(['password1234']);

    expect(passwordErrors('password1234'))->not->toBe([])
        ->and(passwordErrors('a fine long passphrase'))->toBe([])
        ->and($verifier->thresholds)->toBe([0, 0]);
});

it('never reaches the network: a real HIBP call would fail the test', function (): void {
    expect(fn () => Http::get('https://api.pwnedpasswords.com/range/ABCDE'))->toThrow(StrayRequestException::class);
});

it('asks the HIBP range API with a five-character prefix and padding', function (): void {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr(strtoupper(sha1('password1234')), 5).":42\r\n0000000000000000000000000000000000A:0")]);

    expect(passwordErrors('password1234'))->not->toBe([]);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.pwnedpasswords.com/range/'.substr(strtoupper(sha1('password1234')), 0, 5)
        && $request->hasHeader('Add-Padding', 'true'));
});

it('passes during an outage and records password.breach_check_unavailable, without the password', function (): void {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 503)]);

    expect(passwordErrors('password1234'))->toBe([]);

    $event = SecurityEventModel::query()->where('type', 'password.breach_check_unavailable')->sole();
    $prefix = substr(strtoupper(sha1('password1234')), 0, 5);

    expect((string) json_encode($event->toArray()))->not->toContain('password1234')->not->toContain($prefix);
});

it('records the outage when the call throws', function (): void {
    Http::fake(fn () => throw new RuntimeException('down'));

    expect(passwordErrors('password1234'))->toBe([])
        ->and(SecurityEventModel::query()->where('type', 'password.breach_check_unavailable')->count())->toBe(1);
});
