<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Carbon\CarbonImmutable;
use Laravel\Sanctum\PersonalAccessToken;
use RefactorCircus\Guardian\Domains\Token\Drivers\SanctumTokenDriver;
use RefactorCircus\Guardian\Domains\Token\Exceptions\TokenDriverException;

// Criterion 46: the Sanctum adapter, against laravel/sanctum.

beforeEach(function (): void {
    if (! class_exists(PersonalAccessToken::class)) {
        $this->markTestSkipped('laravel/sanctum is not installed.');
    }

    useSanctum();
});

it('issues a token with abilities and an expiry, through createToken()', function (): void {
    $ada = sanctumUser();
    $expires = CarbonImmutable::now()->addDays(30)->startOfSecond();

    $issued = app(SanctumTokenDriver::class)->issue($ada, 'CLI', ['read', 'write'], $expires);

    $row = PersonalAccessToken::query()->sole();
    expect($issued->plainTextToken)->toStartWith($row->id.'|')
        ->and($issued->token->id)->toBe((string) $row->id)
        ->and($issued->token->name)->toBe('CLI')
        ->and($issued->token->abilities)->toBe(['read', 'write'])
        ->and($issued->token->expiresAt?->equalTo($expires))->toBeTrue()
        ->and($row->tokenable_id)->toBe($ada->getKey())
        ->and(PersonalAccessToken::findToken($issued->plainTextToken)?->is($row))->toBeTrue();
});

it('lists the user\'s tokens that still work, and revokes by deleting', function (): void {
    $ada = sanctumUser();
    $driver = app(SanctumTokenDriver::class);
    $one = $driver->issue($ada, 'One', ['*'], null);
    $driver->issue($ada, 'Expired', ['*'], CarbonImmutable::now()->subMinute());
    $driver->issue(sanctumUser(), 'Theirs', ['*'], null);

    expect(array_map(fn ($token): string => $token->name, $driver->tokens($ada)))->toBe(['One'])
        ->and($driver->revoke(sanctumUser(), $one->token->id))->toBeFalse()
        ->and($driver->revoke($ada, $one->token->id))->toBeTrue()
        ->and($driver->revoke($ada, $one->token->id))->toBeFalse()
        ->and(PersonalAccessToken::query()->whereKey($one->token->id)->exists())->toBeFalse();
});

it('revokes all of a user\'s tokens', function (): void {
    $ada = sanctumUser();
    $driver = app(SanctumTokenDriver::class);
    $driver->issue($ada, 'One', ['*'], null);
    $driver->issue($ada, 'Two', ['*'], null);
    $driver->issue(sanctumUser(), 'Theirs', ['*'], null);

    expect($driver->revokeAll($ada))->toBe(2)
        ->and($driver->tokens($ada))->toBe([])
        ->and(PersonalAccessToken::query()->count())->toBe(1);
});

it('refuses a user model without Sanctum\'s HasApiTokens', function (): void {
    expect(fn () => app(SanctumTokenDriver::class)->issue(user(), 'CLI', ['*'], null))->toThrow(TokenDriverException::class, 'HasApiTokens');
});
