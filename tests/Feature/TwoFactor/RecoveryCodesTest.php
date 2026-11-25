<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\DB;
use RefactorCircus\Guardian\Domains\SecurityEvent\Models\SecurityEventModel;
use RefactorCircus\Guardian\Domains\TwoFactor\Actions\RegenerateRecoveryCodesAction;
use RefactorCircus\Guardian\Domains\TwoFactor\Exceptions\TwoFactorException;
use RefactorCircus\Guardian\Domains\TwoFactor\Services\RecoveryCodes;

// Criterion 28: recovery codes.

beforeEach(fn () => $this->travelTo(now()->startOfMinute()->addSeconds(10)));

it('makes ten xxxxx-xxxxx codes, stored only as HMACs', function (): void {
    $ada = user();
    [, , $codes] = enrolTotp($ada);

    $hashes = DB::table('guardian_recovery_codes')->where('user_id', $ada->getKey())->pluck('code_hash')->all();

    expect($codes)->toHaveCount(10)
        ->and(array_unique((array) $codes))->toHaveCount(10)
        ->and($codes)->each->toMatch('/^[a-z0-9]{5}-[a-z0-9]{5}$/')
        ->and($hashes)->toHaveCount(10)
        ->and($hashes)->each->toMatch('/^[0-9a-f]{64}$/')
        ->and(array_intersect($hashes, (array) $codes))->toBe([]);
});

it('accepts each code once, in any case and spacing', function (): void {
    $ada = user();
    [, , $codes] = enrolTotp($ada);
    $recovery = app(RecoveryCodes::class);

    expect($recovery->consume($ada->getKey(), ' '.strtoupper((string) $codes[0]).' '))->toBeTrue()
        ->and($recovery->consume($ada->getKey(), (string) $codes[0]))->toBeFalse()
        ->and($recovery->consume($ada->getKey(), 'aaaaa-aaaaa'))->toBeFalse()
        ->and($recovery->remaining($ada->getKey()))->toBe(9);
});

it('accepts a code only for its own user', function (): void {
    $ada = user();
    [, , $codes] = enrolTotp($ada);

    expect(app(RecoveryCodes::class)->consume(user()->getKey(), (string) $codes[0]))->toBeFalse();
});

it('replaces every code on regeneration, answering the new ones once, and records it', function (): void {
    $ada = user();
    [, , $old] = enrolTotp($ada);

    $new = app(RegenerateRecoveryCodesAction::class)->execute($ada);

    expect($new)->toHaveCount(10)
        ->and(array_intersect($new, (array) $old))->toBe([])
        ->and(app(RecoveryCodes::class)->consume($ada->getKey(), (string) $old[0]))->toBeFalse()
        ->and(app(RecoveryCodes::class)->consume($ada->getKey(), $new[0]))->toBeTrue()
        ->and(SecurityEventModel::query()->where('type', 'two_factor.recovery_codes_regenerated')->count())->toBe(1);
});

it('regenerates only for a user with two-factor on', function (): void {
    expect(fn () => app(RegenerateRecoveryCodesAction::class)->execute(user()))->toThrow(TwoFactorException::class);
});

describe('over HTTP', function (): void {
    it('regenerates with 201 and answers only the count afterwards', function (): void {
        $ada = user();
        enrolTotp($ada);
        $this->actingAs($ada);

        $codes = $this->postJson(route('guardian.two-factor.recovery-codes.store'))->assertCreated()->assertJsonCount(10, 'data.recovery_codes')->json('data.recovery_codes');

        $status = $this->getJson(route('guardian.two-factor.show'))->assertOk()->assertJsonPath('data.recovery_codes_remaining', 10);

        foreach ($codes as $code) {
            expect($status->getContent())->not->toContain($code);
        }
    });

    it('answers 422 two_factor_not_enabled without a factor', function (): void {
        $this->actingAs(user())->postJson(route('guardian.two-factor.recovery-codes.store'))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'two_factor_not_enabled');
    });
});
