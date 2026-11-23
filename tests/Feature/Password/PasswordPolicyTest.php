<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Validation\Rules\Password;
use RefactorCircus\Guardian\Domains\Password\PasswordPolicy;
use RefactorCircus\Guardian\Domains\Password\Rules\NotRecentlyUsed;

// Criterion 39: the rules built from guardian.passwords.

beforeEach(fn () => fakeBreaches());

it('builds the rules from the defaults: 12 to 128 characters, breach check, no composition', function (): void {
    $rules = PasswordPolicy::rules();

    expect($rules)->toHaveCount(1)
        ->and($rules[0])->toBeInstanceOf(Password::class)
        ->and($rules[0]->appliedRules())->toMatchArray([
            'min' => 12, 'max' => 128, 'mixedCase' => false, 'letters' => false, 'numbers' => false, 'symbols' => false, 'uncompromised' => true, 'compromisedThreshold' => 0,
        ])
        ->and(passwordErrors('short'))->not->toBe([])
        ->and(passwordErrors(str_repeat('a', 129)))->not->toBe([])
        ->and(passwordErrors('correct horse battery'))->toBe([]);
});

it('adds each enabled complexity rule', function (string $key, string $applied, string $failing, string $passing): void {
    config()->set("guardian.passwords.{$key}", true);

    expect(PasswordPolicy::rules()[0]->appliedRules()[$applied])->toBeTrue()
        ->and(passwordErrors($failing))->not->toBe([])
        ->and(passwordErrors($passing))->toBe([]);
})->with([
    'mixed case' => ['mixed_case', 'mixedCase', 'all lower case here', 'Mixed Case Here ok'],
    'numbers' => ['numbers', 'numbers', 'no digits in here', 'digits 1234 in here'],
    'symbols' => ['symbols', 'symbols', 'nosymbolsinhere', 'symbols! in here'],
    'letters' => ['letters', 'letters', '123456789012', '12345678901a'],
]);

it('uses the configured lengths and threshold, and can drop the breach check', function (): void {
    config()->set('guardian.passwords.min_length', 8);
    config()->set('guardian.passwords.max_length', 20);
    config()->set('guardian.passwords.uncompromised_threshold', 3);

    expect(PasswordPolicy::rules()[0]->appliedRules())->toMatchArray(['min' => 8, 'max' => 20, 'compromisedThreshold' => 3]);

    config()->set('guardian.passwords.uncompromised', false);

    expect(PasswordPolicy::rules()[0]->appliedRules()['uncompromised'])->toBeFalse();
});

it('adds NotRecentlyUsed for a user while history is kept', function (): void {
    $ada = user();

    expect(PasswordPolicy::rules($ada))->toHaveCount(2)
        ->and(PasswordPolicy::rules($ada)[1])->toBeInstanceOf(NotRecentlyUsed::class);

    config()->set('guardian.passwords.history', 0);

    expect(PasswordPolicy::rules($ada))->toHaveCount(1);
});
