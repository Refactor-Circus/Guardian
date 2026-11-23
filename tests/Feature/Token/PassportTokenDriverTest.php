<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Carbon\CarbonImmutable;
use Laravel\Passport\Token;
use RefactorCircus\Guardian\Domains\Token\Drivers\PassportTokenDriver;
use RefactorCircus\Guardian\Domains\Token\Exceptions\TokenDriverException;

// Criterion 47: the Passport adapter, against laravel/passport.

beforeEach(function (): void {
    if (! class_exists(Token::class)) {
        $this->markTestSkipped('laravel/passport is not installed.');
    }

    usePassport();
});

it('issues a personal access token with scopes as abilities, and the expiry given', function (): void {
    $ada = passportUser();
    $expires = CarbonImmutable::now()->addDays(7)->startOfSecond();

    $issued = app(PassportTokenDriver::class)->issue($ada, 'CLI', ['read'], $expires);

    $row = Token::query()->sole();
    expect($issued->plainTextToken)->not->toBe('')
        ->and($issued->token->id)->toBe($row->id)
        ->and($issued->token->name)->toBe('CLI')
        ->and($issued->token->abilities)->toBe(['read'])
        ->and($issued->token->expiresAt?->diffInSeconds($expires, true))->toBeLessThan(5)
        ->and(CarbonImmutable::parse($row->expires_at)->diffInSeconds($expires, true))->toBeLessThan(5)
        ->and((string) $row->user_id)->toBe((string) $ada->getKey());
});

it('keeps Passport\'s own expiry when none is given', function (): void {
    $issued = app(PassportTokenDriver::class)->issue(passportUser(), 'CLI', [], null);

    expect($issued->token->expiresAt?->greaterThan(CarbonImmutable::now()->addDays(300)))->toBeTrue();
});

it('lists tokens that are not revoked, and revokes by marking them revoked', function (): void {
    $ada = passportUser();
    $driver = app(PassportTokenDriver::class);
    $one = $driver->issue($ada, 'One', [], null);
    $two = $driver->issue($ada, 'Two', [], null);
    $driver->issue(passportUser(), 'Theirs', [], null);

    expect($driver->revoke(passportUser(), $one->token->id))->toBeFalse()
        ->and($driver->revoke($ada, $one->token->id))->toBeTrue()
        ->and($driver->revoke($ada, $one->token->id))->toBeFalse()
        ->and(Token::query()->findOrFail($one->token->id)->revoked)->toBeTrue()
        ->and(array_map(fn ($token): string => $token->id, $driver->tokens($ada)))->toBe([$two->token->id]);
});

it('revokes all of a user\'s tokens', function (): void {
    $ada = passportUser();
    $driver = app(PassportTokenDriver::class);
    $driver->issue($ada, 'One', [], null);
    $driver->issue($ada, 'Two', [], null);

    expect($driver->revokeAll($ada))->toBe(2)
        ->and($driver->tokens($ada))->toBe([])
        ->and(Token::query()->where('revoked', false)->count())->toBe(0);
});

it('refuses a user model that is not OAuthenticatable', function (): void {
    expect(fn () => app(PassportTokenDriver::class)->issue(user(), 'CLI', [], null))->toThrow(TokenDriverException::class, 'OAuthenticatable');
});
